<?php

namespace App\Jobs\Reservation;

use App\Enums\ReservationState;
use App\Events\Reservation\CheckinWindowOpenedEvent;
use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Services\Notification\CredentialCommunicationPolicy;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Reservation\AccessCredentialService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CHECKIN_CHECKOUT Wave 3
 *
 * Orchestration job that validates readiness and dispatches credential delivery.
 *
 * Pipeline:
 *   CheckinWindowOpenedEvent
 *     → ProcessCheckinWindowOpenedJob (this job)
 *       → Atomic readiness validation (reservation + credential + channel)
 *       → SendAccessCredentialJob::dispatch() for each eligible channel
 *
 * SECURITY INVARIANT (W3-INV-1):
 *   This job carries NO credential plaintext.
 *   Only credential_id (integer) crosses the queue boundary.
 *   Decryption happens inside SendAccessCredentialJob::handle() — the last possible moment.
 *
 * Retry: $tries = 3, backoff = [30s, 60s, 120s]
 *
 * @see SendAccessCredentialJob
 * @see CredentialCommunicationPolicy
 */
class ProcessCheckinWindowOpenedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $maxExceptions = 1;

    public function __construct(
        public readonly CheckinWindowOpenedEvent $event,
    ) {}

    public function handle(
        AccessCredentialService $credentialService,
        CredentialCommunicationPolicy $policy,
        NotificationDispatcher $dispatcher,
    ): void {
        Log::info('ProcessCheckinWindowOpenedJob: processing', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'ilan_id'       => $this->event->ilanId,
        ]);

        // ── 1. Atomic reservation readiness validation ──────────────────────────
        // MUST: reservation is CONFIRMED, not cancelled, tenant matches
        $reservation = PropertyReservation::query()
            ->where('id', $this->event->reservationId)
            ->where('tenant_id', $this->event->tenantId)
            ->where('reservation_state', ReservationState::CONFIRMED)
            ->whereNull('deleted_at')
            ->first();

        if (!$reservation) {
            Log::info('ProcessCheckinWindowOpenedJob: reservation not eligible — skipping silently', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id'      => $this->event->tenantId,
                'reason'         => 'not_confirmed_or_deleted_or_tenant_mismatch',
            ]);
            return;
        }

        // MUST: check-in date is today or in the future
        $startDate = \Carbon\Carbon::parse($this->event->startDate)->startOfDay();
        $today = \Carbon\Carbon::today()->startOfDay();

        if ($startDate->lt($today)) {
            Log::info('ProcessCheckinWindowOpenedJob: check-in date in past — skipping silently', [
                'reservation_id' => $this->event->reservationId,
                'start_date'     => $this->event->startDate,
                'reason'         => 'checkin_date_in_past',
            ]);
            return;
        }

        // ── 2. Load ilan ───────────────────────────────────────────────────────
        $ilan = Ilan::query()
            ->where('id', $this->event->ilanId)
            ->where('tenant_id', $this->event->tenantId)
            ->first();

        if (!$ilan) {
            Log::warning('ProcessCheckinWindowOpenedJob: ilan not found — skipping', [
                'reservation_id' => $this->event->reservationId,
                'ilan_id'       => $this->event->ilanId,
                'tenant_id'      => $this->event->tenantId,
            ]);
            return;
        }

        // ── 3. Credential existence and validity ────────────────────────────────
        // AccessCredentialService::getActiveCredential() handles:
        //   - Tenant isolation (enforceTenantMatch)
        //   - Expiration check (isExpired)
        //   - is_active check
        $credential = $credentialService->getActiveCredential($ilan);

        if (!$credential) {
            Log::warning('ProcessCheckinWindowOpenedJob: no active credential found — skipping', [
                'reservation_id' => $this->event->reservationId,
                'ilan_id'       => $this->event->ilanId,
                'tenant_id'      => $this->event->tenantId,
                'reason'         => 'no_active_credential',
            ]);
            return;
        }

        // Additional validity check
        if (!$credential->isValid()) {
            Log::warning('ProcessCheckinWindowOpenedJob: credential not valid — skipping', [
                'reservation_id' => $this->event->reservationId,
                'credential_id'  => $credential->id,
                'is_active'      => $credential->is_active,
                'is_expired'     => $credential->isExpired(),
                'requires_reset' => $credential->requires_reset,
            ]);
            return;
        }

        // ── 4. Determine eligible channels ──────────────────────────────────────
        $channels = $policy->getEligibleChannelsForCredential($this->event);

        if (empty($channels)) {
            Log::info('ProcessCheckinWindowOpenedJob: no eligible channels — skipping silently', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id'      => $this->event->tenantId,
                'reason'         => 'no_valid_phone_or_email_or_consent',
            ]);
            return;
        }

        // ── 5. Dispatch SendAccessCredentialJob per channel ─────────────────────
        // SECURITY (W3-INV-1): Only credential_id (integer) crosses the queue.
        // The plaintext credential is NEVER in the job payload.
        // Decryption happens inside SendAccessCredentialJob::handle().
        foreach ($channels as $channel => $recipient) {
            // Idempotency check per channel
            if ($policy->isCheckinCredentialAlreadySent($this->event->reservationId, $channel)) {
                Log::info('ProcessCheckinWindowOpenedJob: already sent on channel — skipping', [
                    'reservation_id' => $this->event->reservationId,
                    'channel'        => $channel,
                ]);
                continue;
            }

            SendAccessCredentialJob::dispatch(
                $this->event->reservationId,
                $credential->id,
                $this->event->tenantId,
                $this->event->ilanId,
                $channel,
                $recipient,
            );

            Log::info('ProcessCheckinWindowOpenedJob: SendAccessCredentialJob dispatched', [
                'reservation_id' => $this->event->reservationId,
                'credential_id'  => $credential->id,
                'tenant_id'      => $this->event->tenantId,
                'channel'        => $channel,
                'recipient'      => $recipient,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessCheckinWindowOpenedJob: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'ilan_id'       => $this->event->ilanId,
            'error'          => $exception->getMessage(),
        ]);
    }
}

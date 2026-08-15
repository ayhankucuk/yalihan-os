<?php

namespace App\Jobs\Reservation;

use App\DTOs\Notification\AccessCredentialNotification;
use App\Enums\ReservationState;
use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\Notification\OutboundNotification;
use App\Queue\Contracts\TenantAwareJobInterface;
use App\Services\Notification\CredentialCommunicationPolicy;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Notification\NotificationRetryService;
use App\Services\Reservation\AccessCredentialService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CHECKIN_CHECKOUT Wave 3
 *
 * Credential delivery job. Decrypts and sends access credentials to the guest.
 *
 * SECURITY CRITICAL (W3-INV-1, W3-INV-2, W3-INV-3):
 *
 *   This job is the ONLY place in Wave 3 where credential plaintext exists.
 *
 *   PLAINTEXT LIFECYCLE:
 *     1. $plainValue = $credential->getCredentialValue()  ← Decrypt HERE (Crypt::decryptString)
 *     2. AccessCredentialNotification::make() uses plainValue for message rendering
 *     3. NotificationDispatcher::dispatch() queues it for delivery
 *     4. $plainValue and $notification go out of scope → garbage collected
 *
 *   PLAINTEXT MUST NEVER:
 *     ✗ Enter a log statement
 *     ✗ Enter an exception message
 *     ✗ Be serialized to a failed-job table
 *     ✗ Appear in OutboundNotification.payload_data
 *     ✗ Appear in queue introspection tools
 *     ✗ Be visible to AI/agent context
 *
 *   Queue payload contains ONLY: credential_id (integer) — no sensitive data.
 *
 * Retry: $tries = 3, backoff = [30s, 60s, 120s]
 *
 * @see ProcessCheckinWindowOpenedJob
 * @see CredentialCommunicationPolicy
 * @see AccessCredentialNotification
 */
class SendAccessCredentialJob implements ShouldQueue, TenantAwareJobInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $maxExceptions = 1;

    // W3-INV-1: Only identifiers cross the queue — NO plaintext credential
    public readonly int    $reservationId;
    public readonly int    $credentialId;
    public readonly int    $ilanId;
    public readonly string $channel;
    public readonly string $recipient;

    public ?int $tenantId;
    public ?int $userId;

    public function __construct(
        int    $reservationId,
        int    $credentialId,
        int    $tenantId,
        int    $ilanId,
        string $channel,
        string $recipient,
    ) {
        $this->reservationId = $reservationId;
        $this->credentialId = $credentialId;
        $this->tenantId = $tenantId;
        $this->ilanId = $ilanId;
        $this->channel = $channel;
        $this->recipient = $recipient;
        $this->userId = null;
        $this->onQueue('notifications');
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function middleware(): array
    {
        return [new \App\Queue\Middleware\RestoreTenantContext(
            app(\App\Services\SaaS\TenantContextService::class)
        )];
    }

    public function handle(
        AccessCredentialService $credentialService,
        CredentialCommunicationPolicy $policy,
        NotificationDispatcher $dispatcher,
        NotificationRetryService $retryService,
    ): void {
        // ── 1. Idempotency check ────────────────────────────────────────────────
        // Even though ProcessCheckinWindowOpenedJob already checked, we double-check
        // here because the job may be retried or replayed.
        if ($policy->isCheckinCredentialAlreadySent($this->reservationId, $this->channel)) {
            Log::info('SendAccessCredentialJob: already sent — skipping', [
                'reservation_id' => $this->reservationId,
                'channel'       => $this->channel,
            ]);
            return;
        }

        // ── 2. Re-validate reservation state ────────────────────────────────────
        // Guard against race condition: reservation might have been cancelled
        // between ProcessCheckinWindowOpenedJob dispatch and now.
        $reservation = PropertyReservation::query()
            ->where('id', $this->reservationId)
            ->where('tenant_id', $this->tenantId)
            ->where('reservation_state', ReservationState::CONFIRMED)
            ->whereNull('deleted_at')
            ->first();

        if (!$reservation) {
            Log::info('SendAccessCredentialJob: reservation no longer eligible — skipping', [
                'reservation_id' => $this->reservationId,
                'channel'       => $this->channel,
                'reason'        => 'not_confirmed_or_deleted',
            ]);
            return;
        }

        // ── 3. Load ilan (tenant-scoped) ───────────────────────────────────────
        $ilan = Ilan::query()
            ->where('id', $this->ilanId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if (!$ilan) {
            Log::error('SendAccessCredentialJob: ilan not found — blocking', [
                'reservation_id' => $this->reservationId,
                'ilan_id'       => $this->ilanId,
                'tenant_id'      => $this->tenantId,
            ]);
            return;
        }

        // ── 4. Load and decrypt credential ──────────────────────────────────────
        // W3-INV-1: credential is encrypted at rest; we decrypt here, use immediately,
        // and ensure it never enters logs, exception messages, or payload_data.
        $credential = AccessCredential::query()
            ->where('id', $this->credentialId)
            ->where('ilan_id', $this->ilanId)
            ->where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            Log::warning('SendAccessCredentialJob: credential not found or inactive — skipping', [
                'credential_id'  => $this->credentialId,
                'ilan_id'       => $this->ilanId,
                'tenant_id'      => $this->tenantId,
                'reservation_id' => $this->reservationId,
            ]);
            return;
        }

        // W3-INV-2: Decrypt credential — plaintext exists ONLY in this function's memory
        $plainValue = $credential->getCredentialValue();

        if ($plainValue === null) {
            Log::error('SendAccessCredentialJob: credential decrypt failed — blocking', [
                'credential_id'  => $this->credentialId,
                'ilan_id'       => $this->ilanId,
                // NOTE: NO plainValue logged — W3-INV-1
            ]);
            return;
        }

        // Additional validity check post-decrypt
        if (!$credential->isValid()) {
            Log::warning('SendAccessCredentialJob: credential not valid after decrypt — skipping', [
                'credential_id'  => $this->credentialId,
                'is_active'     => $credential->is_active,
                'is_expired'    => $credential->isExpired(),
                'requires_reset' => $credential->requires_reset,
            ]);
            return;
        }

        // ── 5. Build notification DTO ────────────────────────────────────────────
        // W3-INV-1: AccessCredentialNotification DTO does NOT include plaintext
        // credential in getData(). Only masked_value enters payload_data.
        // The plaintext is used only for immediate message body rendering.
        $notification = AccessCredentialNotification::make(
            $plainValue,
            $credential->getCredentialLocation(), // may be null
            $credential->credential_type,
            $this->channel,
            $this->recipient,
            [
                'reservation_id' => $this->reservationId,
                'tenant_id'    => $this->tenantId,
                'ilan_id'       => $this->ilanId,
                'guest_name'    => $reservation->guest_name,
                'start_date'   => $reservation->start_date instanceof \Carbon\Carbon
                    ? $reservation->start_date->format('Y-m-d')
                    : (string) $reservation->start_date,
                'end_date'     => $reservation->end_date instanceof \Carbon\Carbon
                    ? $reservation->end_date->format('Y-m-d')
                    : (string) $reservation->end_date,
                'checkin_time' => $ilan->check_in_time ?? '14:00',
                // W3-INV-1: Only masked value goes to payload
                'masked_value' => $credential->getMaskedValue(),
            ],
        );

        // ── 6. Dispatch via NotificationDispatcher ────────────────────────────────
        // NotificationDispatcher::dispatch() handles:
        //   - canDispatch() pilot gate
        //   - OutboundNotification audit record creation (with masked payload)
        //   - Async routing via SendNotificationJob
        $dispatched = $dispatcher->dispatch($notification, $this->tenantId, $this->ilanId);

        Log::info('SendAccessCredentialJob: notification dispatched', [
            'reservation_id' => $this->reservationId,
            'credential_id' => $this->credentialId,
            'tenant_id'     => $this->tenantId,
            'channel'       => $this->channel,
            'recipient'     => $this->recipient,
            'dispatched'    => $dispatched,
            // NOTE: NO plainValue in log — W3-INV-1
        ]);

        // W3-INV-2: Explicitly null the variable so it can be garbage collected
        // before the job fully completes.
        unset($plainValue, $credential);
    }

    public function failed(\Throwable $exception): void
    {
        // W3-INV-1: The exception message must NEVER contain the credential.
        // Laravel's failed_jobs table stores job payloads — we ensure the job
        // constructor has no plaintext, so failed-job introspection is safe.
        Log::error('SendAccessCredentialJob: all retries exhausted', [
            'reservation_id' => $this->reservationId,
            'credential_id' => $this->credentialId,
            'tenant_id'     => $this->tenantId,
            'channel'       => $this->channel,
            // NOTE: NO plainValue in exception context — W3-INV-1
            'error'         => $exception->getMessage(),
        ]);
    }
}

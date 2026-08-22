<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationCancelledEvent;
use App\Services\Notification\GuestCommunicationPolicy;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GuestCancellationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @sab-ignore Context7 — $eventData keys match ReservationCancelledEvent readonly properties, not DB columns
 *
 * SendCancellationNotificationJob — A2: Cancellation Communication Wave.
 *
 * Canonical entry point for guest cancellation notifications triggered by
 * ReservationCancelledEvent. Idempotent, tenant-scoped, retryable.
 *
 * Pipeline:
 *   ReservationCancelledEvent
 *     → ListenCancellationCommunication::handle()
 *       → SendCancellationNotificationJob (this job)
 *         → GuestCommunicationPolicy (consent + contact check)
 *           → GuestCancellationNotification DTO
 *             → NotificationDispatcher::dispatch()
 *               → SendNotificationJob (async)
 *                 → WhatsAppAdapter / EmailAdapter
 *                   → OutboundNotification (evidence)
 *
 * Retry: $tries = 3, backoff = [30s, 60s, 120s]
 *
 * IMPORTANT: Queue name is 'notifications' to align with existing listener dispatch.
 * IMPORTANT: Idempotency is handled by GuestCommunicationPolicy::isAlreadySent().
 *
 * A2 — Cancellation Communication Wave
 * SAAB Decision: Cancellation Communication Pipeline
 */
class SendCancellationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $maxExceptions = 1;

    public function __construct(
        public readonly ReservationCancelledEvent $event,
    ) {}

    public function handle(
        GuestCommunicationPolicy $policy,
        NotificationDispatcher $dispatcher,
    ): void {
        Log::info('SendCancellationNotificationJob: processing', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'cancelled_by'   => $this->event->cancelledBy,
        ]);

        // ── 1. Determine eligible channels ────────────────────────────────
        $channels = $this->getEligibleChannels($policy);

        if (empty($channels)) {
            Log::info('SendCancellationNotificationJob: no eligible channels — skipping silently', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id'      => $this->event->tenantId,
                'reason'         => 'no_valid_phone_or_email',
            ]);
            return;
        }

        // ── 2. Build event data for notification DTO ───────────────────────
        $eventData = [
            'reservationId' => $this->event->reservationId,
            'tenantId'      => $this->event->tenantId,
            'ilanId'        => $this->event->ilanId,
            'guestName'     => $this->event->guestName,
            'guestPhone'    => $this->event->guestPhone,
            'guestEmail'    => $this->event->guestEmail,
            'startDate'     => $this->event->startDate,
            'endDate'       => $this->event->endDate,
            'nights'        => $this->event->nights,
            'cancelledAt'    => $this->event->cancelledAt,
            'cancelledBy'    => $this->event->cancelledBy,
            'reason'         => $this->event->reason,
            'externalChannel' => $this->event->externalChannel,
            'externalReservationId' => $this->event->externalReservationId,
        ];

        foreach ($channels as $channel => $recipient) {
            // ── 3. Idempotency: deduplicate per reservation + channel ─────
            // Uses template_key = 'reservation_cancellation' — distinct from confirmation
            if ($policy->isAlreadySent($this->event->reservationId, $channel, 'reservation_cancellation')) {
                Log::info('SendCancellationNotificationJob: already sent on channel — skipping', [
                    'reservation_id' => $this->event->reservationId,
                    'channel'        => $channel,
                    'template'       => 'reservation_cancellation',
                ]);
                continue;
            }

            // ── 4. Build notification DTO ─────────────────────────────────
            $notification = GuestCancellationNotification::fromCancelledEvent(
                $eventData,
                $channel,
                $recipient,
            );

            // ── 5. Dispatch through canonical dispatcher ───────────────────
            // NotificationDispatcher::dispatch() handles:
            //   - canDispatch() feature flag gate
            //   - creates OutboundNotification audit record
            //   - routes to WhatsAppAdapter or EmailAdapter via SendNotificationJob
            $dispatched = $dispatcher->dispatch(
                $notification,
                $this->event->tenantId,
                $this->event->ilanId,
            );

            Log::info('SendCancellationNotificationJob: notification dispatched', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id'      => $this->event->tenantId,
                'channel'        => $channel,
                'recipient'      => $recipient,
                'dispatched'     => $dispatched,
            ]);
        }
    }

    /**
     * Determine which channels are eligible for cancellation notification.
     * Mirrors GuestCommunicationPolicy for confirmation but uses ReservationCancelledEvent.
     */
    private function getEligibleChannels(GuestCommunicationPolicy $policy): array
    {
        $eligible = [];

        // ── WhatsApp ────────────────────────────────────────────────────
        $phone = $this->resolveGuestPhone($policy);
        if ($phone !== null) {
            $eligible['whatsapp'] = $phone;
        }

        // ── Email ──────────────────────────────────────────────────────
        $email = $this->resolveGuestEmail();
        if ($email !== null && $policy->isValidEmail($email)) {
            $eligible['email'] = $email;
        }

        return $eligible;
    }

    private function resolveGuestPhone(GuestCommunicationPolicy $policy): ?string
    {
        if (empty($this->event->guestPhone)) {
            return null;
        }
        return $policy->normalizePhone($this->event->guestPhone);
    }

    private function resolveGuestEmail(): ?string
    {
        return $this->event->guestEmail ?? null;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendCancellationNotificationJob: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'ilan_id'        => $this->event->ilanId,
            'error'          => $exception->getMessage(),
        ]);
    }
}

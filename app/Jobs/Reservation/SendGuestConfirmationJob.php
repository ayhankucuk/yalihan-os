<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationCreatedEvent;
use App\Services\Notification\GuestCommunicationPolicy;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GuestConfirmationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @sab-ignore Context7 — $eventData keys match ReservationCreatedEvent readonly properties, not DB columns
 *
 * SendGuestConfirmationJob — Wave 1 of Guest Communication Pipeline.
 *
 * Canonical entry point for guest confirmation notifications triggered by
 * ReservationCreatedEvent. Idempotent, tenant-scoped, retryable.
 *
 * Pipeline:
 *   ReservationCreatedEvent
 *     → ProcessReservationCreated::handle()
 *       → SendGuestConfirmationJob (this job)
 *         → GuestCommunicationPolicy (consent + contact check)
 *           → GuestConfirmationNotification DTO
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
 * @sab-ignore Context7 — log context uses event property names (not DB columns)
 *
 * Sprint: RESERVATION-GUEST-COMM-WAVE-1
 */
class SendGuestConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $maxExceptions = 1;

    public function __construct(
        public readonly ReservationCreatedEvent $event,
    ) {}

    public function handle(
        GuestCommunicationPolicy $policy,
        NotificationDispatcher $dispatcher,
    ): void {
        Log::info('SendGuestConfirmationJob: processing', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
        ]);

        // ── 1. Idempotency: deduplicate per reservation ──────────────────────
        // Try WhatsApp first (primary channel)
        $channels = $policy->getEligibleChannels($this->event);

        if (empty($channels)) {
            Log::info('SendGuestConfirmationJob: no eligible channels — skipping silently', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id'      => $this->event->tenantId,
                'reason'         => 'no_valid_phone_or_email',
            ]);
            return;
        }

        // @sab-ignore Context7 — keys match ReservationCreatedEvent readonly properties, not DB columns
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
            'totalAmount'   => $this->event->totalAmount,
            'currency'      => $this->event->currency,
            'externalChannel' => $this->event->externalChannel,
        ];

        foreach ($channels as $channel => $recipient) {
            // ── 2. Idempotency check per channel ────────────────────────────
            if ($policy->isAlreadySent($this->event->reservationId, $channel)) {
                Log::info('SendGuestConfirmationJob: already sent on channel — skipping', [
                    'reservation_id' => $this->event->reservationId,
                    'channel'        => $channel,
                ]);
                continue;
            }

            // ── 3. Build notification DTO ─────────────────────────────────
            $notification = GuestConfirmationNotification::fromReservationEvent(
                $eventData,
                $channel,
                $recipient,
            );

            // ── 4. Dispatch through canonical dispatcher ──────────────────────
            // NotificationDispatcher::dispatch() handles:
            //   - canDispatch() feature flag gate
            //   - creates OutboundNotification audit record
            //   - routes to WhatsAppAdapter or EmailAdapter via SendNotificationJob
            $dispatched = $dispatcher->dispatch(
                $notification,
                $this->event->tenantId,
                $this->event->ilanId,
            );

            Log::info('SendGuestConfirmationJob: notification dispatched', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id'      => $this->event->tenantId,
                'channel'        => $channel,
                'recipient'      => $recipient,
                'dispatched'     => $dispatched,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendGuestConfirmationJob: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'ilan_id'        => $this->event->ilanId,
            'error'          => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationCancelledEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessReservationCancelled — Queue-safe listener boundary.
 *
 * Handles cancellation: release internal availability blocks,
 * trigger guest cancellation notification, financial reversal.
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ProcessReservationCancelled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ReservationCancelledEvent $event,
    ) {}

    public function handle(): void
    {
        Log::info('ProcessReservationCancelled: dispatched', [
            'reservation_id'  => $this->event->reservationId,
            'tenant_id'       => $this->event->tenantId,
            'ilan_id'         => $this->event->ilanId,
            'dates_released'  => count($this->event->getDatesToRelease()),
            'cancelled_by'   => $this->event->cancelledBy,
        ]);

        // ── Downstream systems ──
        // 1. Availability: release internal blocks (already done in ReservationService)
        //    Channel outbound: update external channels (Booking.com, Channex)
        // 2. Guest cancellation notification
        //    → NotificationDispatcher
        // 3. Financial reversal (if deposit was taken)
        //    → FinancialTransaction reversal
        //
        // ⚠️  DO NOT add implementation here — add in subsequent waves.
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReservationCancelled: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'error'         => $exception->getMessage(),
        ]);
    }
}

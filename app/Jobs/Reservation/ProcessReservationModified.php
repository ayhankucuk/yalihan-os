<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationModifiedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessReservationModified — Queue-safe listener boundary.
 *
 * Handles date changes (availability release + re-block)
 * and guest data changes (notification).
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ProcessReservationModified implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ReservationModifiedEvent $event,
    ) {}

    public function handle(): void
    {
        Log::info('ProcessReservationModified: dispatched', [
            'reservation_id'  => $this->event->reservationId,
            'tenant_id'       => $this->event->tenantId,
            'ilan_id'         => $this->event->ilanId,
            'prev_dates'      => $this->event->previousStartDate . '→' . $this->event->previousEndDate,
            'new_dates'      => $this->event->newStartDate . '→' . $this->event->newEndDate,
        ]);

        // ── Downstream systems ──
        // 1. Availability: release old dates, block new dates
        //    → AvailabilitySynchronizationService (release + block)
        // 2. Guest notification: inform of date change
        //    → NotificationDispatcher
        //
        // ⚠️  DO NOT add implementation here — add in subsequent waves.
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReservationModified: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'error'         => $exception->getMessage(),
        ]);
    }
}

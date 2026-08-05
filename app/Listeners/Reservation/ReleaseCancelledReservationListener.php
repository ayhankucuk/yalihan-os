<?php

namespace App\Listeners\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Events\Reservation\ReservationCancelledEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ReleaseCancelledReservationListener
 *
 * RESERVATION_CORE Phase 2: E01 — Availability Projection Foundation
 *
 * Domain event listener for ReservationCancelledEvent.
 * Delegates to AvailabilityProjectionService for idempotent release.
 *
 * Mimari Kural:
 * Reservation → Event → Projection Service → PropertyAvailability
 */
class ReleaseCancelledReservationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AvailabilityProjectionContract $projectionService
    ) {}

    /**
     * Handle the ReservationCancelledEvent.
     */
    public function handle(ReservationCancelledEvent $event): void
    {
        $this->projectionService->projectCancel(
            $event->reservationId,
            $event->tenantId,
            $event->startDate,
            $event->endDate
        );
    }
}

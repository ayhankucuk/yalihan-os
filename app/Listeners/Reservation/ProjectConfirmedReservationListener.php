<?php

namespace App\Listeners\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Events\Reservation\ReservationConfirmedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ProjectConfirmedReservationListener
 *
 * RESERVATION_CORE Phase 2: E01 — Availability Projection Foundation
 *
 * Domain event listener for ReservationConfirmedEvent.
 * Delegates to AvailabilityProjectionService for deterministic, idempotent projection.
 *
 * Mimari Kural:
 * Reservation → Event → Projection Service → PropertyAvailability
 */
class ProjectConfirmedReservationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AvailabilityProjectionContract $projectionService
    ) {}

    /**
     * Handle the ReservationConfirmedEvent.
     */
    public function handle(ReservationConfirmedEvent $event): void
    {
        $this->projectionService->projectConfirm(
            $event->reservationId,
            $event->tenantId,
            $event->propertyId,
            $event->startDate,
            $event->endDate
        );
    }
}

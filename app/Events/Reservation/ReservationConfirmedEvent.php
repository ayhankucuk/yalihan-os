<?php

namespace App\Events\Reservation;

use App\Enums\ReservationState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationConfirmedEvent
 *
 * RESERVATION_CORE Phase 2: E01 — Availability Projection Foundation
 *
 * Domain event tetiklenir when a reservation is confirmed.
 * Bu event, PropertyAvailability projeksiyonunu tetikler.
 *
 * Mimari Kural:
 * Reservation → Event → Projection Service → PropertyAvailability
 */
class ReservationConfirmedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $reservationId,
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly int $nights,
        public readonly string $guestName,
        public readonly ReservationState $previousState = ReservationState::PENDING,
        public readonly ?int $confirmedBy = null,
    ) {}

    /**
     * Get the dates affected by this reservation.
     */
    public function getDates(): array
    {
        $dates = [];
        $current = \Carbon\Carbon::parse($this->startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($this->endDate)->startOfDay();

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Get the projection idempotency key for a specific date.
     */
    public function getProjectionKey(string $date): string
    {
        return "reservation:{$this->reservationId}:{$date}";
    }

    /**
     * Get all projection keys for this reservation.
     */
    public function getAllProjectionKeys(): array
    {
        return array_map(
            fn($date) => $this->getProjectionKey($date),
            $this->getDates()
        );
    }
}

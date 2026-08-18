<?php

namespace App\Events\ChannelManager;

/**
 * BookingReservationModifiedEvent — Fired after a Booking.com modification is applied.
 *
 * Sprint 4.12 — Booking.com Provider Wave 3
 */
final readonly class BookingReservationModifiedEvent
{
    public function __construct(
        public int    $reservationId,
        public int    $tenantId,
        public int    $ilanId,
        public string $externalReservationId,
        public string $hotelCode,
        public string $oldArrival,
        public string $oldDeparture,
        public string $newArrival,
        public string $newDeparture,
    ) {}
}

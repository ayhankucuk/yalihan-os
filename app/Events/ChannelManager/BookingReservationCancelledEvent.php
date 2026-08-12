<?php

namespace App\Events\ChannelManager;

/**
 * BookingReservationCancelledEvent — Fired after a Booking.com cancellation is applied.
 *
 * Sprint 4.12 — Booking.com Provider Wave 3
 */
final readonly class BookingReservationCancelledEvent
{
    public function __construct(
        public int    $reservationId,
        public int    $tenantId,
        public int    $ilanId,
        public string $externalReservationId,
        public string $hotelCode,
    ) {}
}

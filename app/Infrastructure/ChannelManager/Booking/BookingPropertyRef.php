<?php

namespace App\Infrastructure\ChannelManager\Booking;

/**
 * BookingPropertyRef — Canonical property reference from Booking HotelCode.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 */
readonly final class BookingPropertyRef
{
    public function __construct(
        public int    $ilanId,
        public int    $tenantId,
        public string $hotelCode,
    ) {}
}

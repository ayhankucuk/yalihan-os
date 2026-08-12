<?php

namespace App\Events\ChannelManager;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BookingReservationRejectedEvent
 *
 * Dispatched when a Booking.com reservation is rejected (unknown HotelCode, etc.)
 * Sprint 4.11 — Wave 2
 */
class BookingReservationRejectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $externalReservationId,
        public readonly string $hotelCode,
        public readonly int    $tenantId,
        public readonly string $reason,     // 'UNKNOWN_HOTEL_CODE' | 'PERSISTENCE_FAILED' | etc.
        public readonly bool   $retryable,
    ) {}
}

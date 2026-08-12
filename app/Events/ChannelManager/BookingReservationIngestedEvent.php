<?php

namespace App\Events\ChannelManager;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BookingReservationIngestedEvent
 *
 * Dispatched when a Booking.com reservation is successfully ingested and ACK sent.
 * Sprint 4.11 — Wave 2
 */
class BookingReservationIngestedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $reservationId,
        public readonly int    $tenantId,
        public readonly int    $ilanId,
        public readonly string $externalReservationId,
        public readonly string $hotelCode,
        public readonly string $channel = 'booking_com',
    ) {}
}

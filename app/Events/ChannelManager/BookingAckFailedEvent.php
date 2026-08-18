<?php

namespace App\Events\ChannelManager;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BookingAckFailedEvent
 *
 * Dispatched when ACK to Booking.com fails but reservation is already committed.
 * ADR-009 §2: ACK failure → NO rollback
 * Sprint 4.11 — Wave 2
 */
class BookingAckFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $reservationId,
        public readonly int    $tenantId,
        public readonly string $externalReservationId,
        public readonly string $errorCode,
        public readonly string $errorMessage,
        public readonly bool   $retryable,
    ) {}
}

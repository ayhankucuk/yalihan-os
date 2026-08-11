<?php

namespace App\Events\ChannelManager;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ChannexReservationCancelledViaChanEvent
 * CHANNEL_MANAGER_PROVIDER Wave 3 — ADR-008
 * Dispatched when a Channex cancellation webhook is successfully processed.
 */
class ChannexReservationCancelledViaChanEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $reservationId,
        public readonly int    $tenantId,
        public readonly string $externalReservationId,
        public readonly string $externalChannel,
    ) {}
}

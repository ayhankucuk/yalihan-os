<?php

namespace App\Events\ChannelManager;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ChannexReservationModifiedEvent
 * CHANNEL_MANAGER_PROVIDER Wave 3 — ADR-008
 */
class ChannexReservationModifiedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $reservationId,
        public readonly int    $tenantId,
        public readonly string $externalReservationId,
        public readonly string $externalChannel,
        public readonly string $newStartDate,
        public readonly string $newEndDate,
    ) {}
}

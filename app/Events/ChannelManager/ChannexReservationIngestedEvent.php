<?php

namespace App\Events\ChannelManager;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannexReservationIngestedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $reservationId,
        public readonly int    $tenantId,
        public readonly int    $propertyId,
        public readonly string $externalReservationId,
        public readonly string $externalChannel,
        public readonly bool   $conflictDetected = false,
    ) {}
}

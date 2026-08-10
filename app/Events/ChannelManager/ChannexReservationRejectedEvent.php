<?php

namespace App\Events\ChannelManager;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannexReservationRejectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string  $externalReservationId,
        public readonly ?string $externalListingId,
        public readonly string  $errorCode,
        public readonly string  $errorMessage,
        public readonly bool    $retryable,
    ) {}
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\Uuid;

class LeadRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $eventId;
    public ?int $listingId;
    public int $ownerId;
    public string $type; // 'lead', 'message', 'action'
    public string $occurredAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ?int $listingId,
        int $ownerId,
        string $type = 'lead'
    ) {
        $this->eventId = Uuid::uuid4()->toString();
        $this->listingId = $listingId;
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->occurredAt = now()->toDateTimeString();
    }
}

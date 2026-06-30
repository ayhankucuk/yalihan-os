<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeadAgentAtandi Event
 * Phase 12: Lead'e bir satış temsilcisi (agent) atandığında tetiklenir.
 */
class LeadAgentAtandi
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Lead $lead,
        public ?int $eskiAgentId,
        public int $yeniAgentId
    ) {}
}

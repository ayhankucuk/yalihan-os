<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeadDurumDegisti Event
 * Phase 12: Lead'in CRM durumu (crm_durumu) değiştiğinde tetiklenir.
 */
class LeadDurumDegisti
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Lead $lead,
        public int $eskiDurum,
        public int $yeniDurum
    ) {}
}

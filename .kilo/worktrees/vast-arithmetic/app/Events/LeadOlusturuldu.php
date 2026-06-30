<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeadOlusturuldu Event
 * Yeni bir lead (potansiyel müşteri) oluşturulduğunda tetiklenir.
 * Context7: CRM Entegrasyonu için temel event.
 */
class LeadOlusturuldu
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Lead $lead)
    {
    }
}

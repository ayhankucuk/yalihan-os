<?php

namespace App\Events;

use App\Models\Ilan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ilan Updated Event
 *
 * Context7: İlan güncellendiğinde tetiklenir
 * Cache invalidation için kullanılır
 */
class IlanUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Güncellenen ilan
     */
    public Ilan $ilan;

    /**
     * Create a new event instance.
     */
    public function __construct(Ilan $ilan)
    {
        $this->ilan = $ilan;
    }
}

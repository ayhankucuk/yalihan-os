<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ilan Deleted Event
 *
 * Context7: İlan silindiğinde tetiklenir
 * Cache invalidation için kullanılır
 * Not: Model silindiği için ID olarak tutulur
 */
class IlanDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Silinen ilanın ID'si
     */
    public int $ilanId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $ilanId)
    {
        $this->ilanId = $ilanId;
    }
}

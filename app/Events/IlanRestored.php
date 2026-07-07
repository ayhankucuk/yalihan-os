<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ilan Restored Event
 *
 * Context7: İlan arşivden geri alındığında tetiklenir
 * Cache invalidation ve notification için kullanılır
 */
class IlanRestored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $ilanId;

    public function __construct(int $ilanId)
    {
        $this->ilanId = $ilanId;
    }
}

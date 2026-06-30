<?php

namespace App\Events;

use App\Models\Ilan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ilan Created Event
 *
 * Context7: Yeni ilan oluşturulduğunda tetiklenir
 * Tersine eşleştirme (Reverse Matching) için kullanılır
 */
class IlanCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Oluşturulan ilan
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

<?php

namespace App\Events;

use App\Models\Ilan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * IlanYayinlandiEvent
 * Bir ilanın yayin_durumu 'yayinda' olduğunda tetiklenir.
 * Context7: status/active kelimeleri yasaktır.
 */
class IlanYayinlandiEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ilan $ilan)
    {
        //
    }
}

<?php

namespace App\Events;

use App\Models\Talep;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TalepReceived Event
 *
 * Context7: Yeni bir talep oluşturulduğunda fırlatılan event
 * Otonom fırsat sentezi ve bildirim sistemi için tetikleyici
 */
class TalepReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Yeni oluşturulan talep
     */
    public Talep $talep;

    /**
     * Create a new event instance.
     */
    public function __construct(Talep $talep)
    {
        $this->talep = $talep;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}

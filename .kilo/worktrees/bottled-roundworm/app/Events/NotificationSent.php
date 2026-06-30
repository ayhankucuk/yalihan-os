<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notification Sent Event
 *
 * Context7: C7-NOTIFICATION-EVENT-2025-12-19
 *
 * Real-time WebSocket broadcast event
 */
class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $type;
    public array $data;
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, string $type, array $data)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->data = $data;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel
    {
        return new Channel("user.{$this->userId}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return "notification.{$this->type}";
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
        ];
    }
}

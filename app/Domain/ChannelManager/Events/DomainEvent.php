<?php

namespace App\Domain\ChannelManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DomainEvent — Base class for Channel Manager domain events
 *
 * Sprint 13 E01: Domain Foundation
 *
 * These events are dispatched via Laravel's event system and can be
 * queued for async processing by channel adapters.
 */
abstract class DomainEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $channelId,
        public readonly string $occurredAt,
    ) {}

    /**
     * Get the event name (matches ChannelManagerEventVocabulary)
     */
    abstract public function getEventName(): string;

    /**
     * Get the event payload
     */
    abstract public function getPayload(): array;

    /**
     * Get channel-specific routing key
     */
    public function getRoutingKey(): string
    {
        return "channel.{$this->channelId}";
    }
}

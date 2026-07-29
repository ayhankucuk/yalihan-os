<?php

namespace App\Domain\ChannelManager\Events;

use App\Domain\ChannelManager\Enums\ChannelManagerEventVocabulary;

/**
 * AvailabilitySyncedEvent — Fired when availability is synchronized
 *
 * Sprint 13 E01: Domain Foundation
 */
class AvailabilitySyncedEvent extends DomainEvent
{
    public function __construct(
        int $tenantId,
        int $propertyId,
        string $channelId,
        string $date,
        bool $available,
        string $source, // 'local' | 'remote'
        string $occurredAt,
    ) {
        parent::__construct($tenantId, $propertyId, $channelId, $occurredAt);
        $this->date = $date;
        $this->available = $available;
        $this->source = $source;
    }

    public function getEventName(): string
    {
        return ChannelManagerEventVocabulary::AVAILABILITY_SYNCED->value;
    }

    public function getPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'property_id' => $this->propertyId,
            'channel_id' => $this->channelId,
            'date' => $this->date,
            'available' => $this->available,
            'source' => $this->source,
            'occurred_at' => $this->occurredAt,
        ];
    }
}

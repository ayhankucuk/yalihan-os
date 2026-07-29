<?php

namespace App\Domain\ChannelManager\Events;

use App\Domain\ChannelManager\Enums\ChannelManagerEventVocabulary;

/**
 * AvailabilityConflictDetectedEvent — Fired when a double-booking conflict is detected
 *
 * Sprint 13 E01: Domain Foundation
 */
class AvailabilityConflictDetectedEvent extends DomainEvent
{
    public function __construct(
        int $tenantId,
        int $propertyId,
        string $channelId,
        string $date,
        bool $localState,
        bool $remoteState,
        string $occurredAt,
    ) {
        parent::__construct($tenantId, $propertyId, $channelId, $occurredAt);
        $this->date = $date;
        $this->localState = $localState;
        $this->remoteState = $remoteState;
    }

    public function getEventName(): string
    {
        return ChannelManagerEventVocabulary::AVAILABILITY_CONFLICTED->value;
    }

    public function getPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'property_id' => $this->propertyId,
            'channel_id' => $this->channelId,
            'date' => $this->date,
            'local_state' => $this->localState,
            'remote_state' => $this->remoteState,
            'occurred_at' => $this->occurredAt,
        ];
    }
}

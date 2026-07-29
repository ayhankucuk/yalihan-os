<?php

namespace App\Application\ChannelManager\Exceptions;

use App\Domain\ChannelManager\Events\AvailabilityConflictDetectedEvent;

/**
 * AvailabilityConflictException — Thrown when availability conflict is detected
 *
 * Sprint 13 E02: Availability Synchronization
 *
 * Represents a conflict between local and remote availability states.
 * The system MUST NOT silently overwrite conflicting availability.
 */
class AvailabilityConflictException extends \Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $channelId,
        public readonly string $date,
        public readonly bool $localState,
        public readonly bool $remoteState,
        public readonly ?string $resolution = null,
    ) {
        $message = "Availability conflict on {$date}: local=" . ($localState ? 'available' : 'blocked')
            . ", remote=" . ($remoteState ? 'available' : 'blocked')
            . " for property {$propertyId} on channel {$channelId}";

        parent::__construct($message, 409);
    }

    /**
     * Get conflict details as array
     */
    public function getConflictDetails(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'property_id' => $this->propertyId,
            'channel_id' => $this->channelId,
            'date' => $this->date,
            'local_state' => $this->localState,
            'remote_state' => $this->remoteState,
            'resolution' => $this->resolution,
            'requires_resolution' => $this->resolution === null,
        ];
    }

    /**
     * Check if this conflict requires manual resolution
     */
    public function requiresManualResolution(): bool
    {
        return $this->resolution === null;
    }
}

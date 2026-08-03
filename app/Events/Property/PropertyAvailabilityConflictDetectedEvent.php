<?php

namespace App\Events\Property;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyAvailabilityConflictDetectedEvent
 * Fired when an incoming block request collides with an existing higher-or-equal priority block.
 */
class PropertyAvailabilityConflictDetectedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $propertyId,
        public string $requestedStartDate,
        public string $requestedEndDate,
        public string $incomingBlockReason,
        public int $incomingPriorityTier,
        public array $conflictingDates,
        public ?string $idempotencyKey = null
    ) {}
}

<?php

namespace App\Events\Property;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyAvailabilityConflictDetectedEvent
 *
 * Fired when an incoming block request collides with an existing higher-or-equal priority block.
 *
 * Sprint 22 E01: Added $origin and $conflictReasonCode (Enhancement 2 + Enhancement 4).
 */
class PropertyAvailabilityConflictDetectedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $requestedStartDate,
        public readonly string $requestedEndDate,
        public readonly string $incomingBlockReason,
        public readonly int $incomingPriorityTier,
        public readonly array $conflictingDates,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $origin = null,           // E2: source of the incoming block attempt
        public readonly ?string $conflictReasonCode = null // E4: CONFLICT_HIGHER_PRIORITY|CONFLICT_MAINTENANCE|etc.
    ) {}
}

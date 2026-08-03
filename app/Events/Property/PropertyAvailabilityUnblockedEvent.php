<?php

namespace App\Events\Property;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyAvailabilityUnblockedEvent
 *
 * Fired when a date range block is cleared on a property calendar.
 *
 * Sprint 22 E01: Added $origin field (Enhancement 2).
 */
class PropertyAvailabilityUnblockedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $reason = null,
        public readonly ?string $origin = null  // E2: who triggered the unblock
    ) {}
}

<?php

namespace App\Events\Property;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyAvailabilityBlockedEvent
 *
 * Fired when a date range is successfully blocked on a property calendar.
 *
 * Sprint 22 E01: Added $origin field (Enhancement 2).
 */
class PropertyAvailabilityBlockedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $blockReason,
        public readonly int $priorityTier,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $sourceSystem = 'internal',
        public readonly ?string $externalRef = null,
        public readonly ?string $origin = null  // E2: reservation|owner|maintenance|ical|booking|airbnb|manual|system
    ) {}
}

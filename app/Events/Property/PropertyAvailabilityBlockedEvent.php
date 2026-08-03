<?php

namespace App\Events\Property;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyAvailabilityBlockedEvent
 * Fired when a date range is blocked on a property calendar.
 */
class PropertyAvailabilityBlockedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $propertyId,
        public string $startDate,
        public string $endDate,
        public string $blockReason,
        public int $priorityTier,
        public ?string $idempotencyKey = null,
        public ?string $sourceSystem = 'internal',
        public ?string $externalRef = null
    ) {}
}

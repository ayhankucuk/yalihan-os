<?php

namespace App\Events\Property;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyAvailabilityUnblockedEvent
 * Fired when a date range block is unblocked/cleared on a property calendar.
 */
class PropertyAvailabilityUnblockedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $propertyId,
        public string $startDate,
        public string $endDate,
        public ?string $idempotencyKey = null,
        public ?string $reason = null
    ) {}
}

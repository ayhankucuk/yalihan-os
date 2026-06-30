<?php

namespace App\Domain\PropertyHub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * FeatureAssignedEvent
 *
 * [SAB ENFORCEMENT]: Domain Event
 * Bir pivot'a ozellik atandiktan sonra dispatch edilir.
 */
class FeatureAssignedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $pivotId,
        public readonly array $assignments,
        public readonly ?int $userId = null
    ) {}
}

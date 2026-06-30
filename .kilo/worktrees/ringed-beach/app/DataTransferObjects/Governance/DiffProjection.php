<?php

namespace App\DataTransferObjects\Governance;

use App\Enums\Governance\GovernanceState;

final class DiffProjection
{
    public function __construct(
        public readonly string $entityType,
        public readonly int|string $entityId,
        public readonly GovernanceState $currentState,
        public readonly array $changes,
        public readonly bool $canPublish,
    ) {
    }
}

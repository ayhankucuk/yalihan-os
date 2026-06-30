<?php

namespace App\Events\Governance;

use Illuminate\Foundation\Events\Dispatchable;

class OverrideApplied
{
    use Dispatchable;

    public function __construct(
        public readonly int $decisionId,
        public readonly string $previousDecision,
        public readonly string $newDecision,
        public readonly string $reason,
        public readonly int $overrideBy,
    ) {}
}

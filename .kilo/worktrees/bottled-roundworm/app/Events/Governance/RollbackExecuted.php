<?php

namespace App\Events\Governance;

use App\Models\GovernanceDecision;
use App\Models\GovernanceRollback;
use Illuminate\Foundation\Events\Dispatchable;

class RollbackExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly int $decisionId,
        public readonly string $reason,
        public readonly ?int $rolledBackBy = null,
    ) {}
}

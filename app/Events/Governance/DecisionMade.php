<?php

namespace App\Events\Governance;

use App\DTOs\CortexFinding;
use Illuminate\Foundation\Events\Dispatchable;

class DecisionMade
{
    use Dispatchable;

    public function __construct(
        public readonly CortexFinding $finding,
        public readonly string $classification, // auto_run, needs_review, blocked
        public readonly ?float $confidence = null,
        public readonly ?int $agentRunId = null,
    ) {}
}

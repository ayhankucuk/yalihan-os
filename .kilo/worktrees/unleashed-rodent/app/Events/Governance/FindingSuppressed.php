<?php

namespace App\Events\Governance;

use App\DTOs\CortexFinding;
use Illuminate\Foundation\Events\Dispatchable;

class FindingSuppressed
{
    use Dispatchable;

    public function __construct(
        public readonly CortexFinding $finding,
        public readonly ?string $ruleKey = null,
        public readonly ?int $agentRunId = null,
    ) {}
}

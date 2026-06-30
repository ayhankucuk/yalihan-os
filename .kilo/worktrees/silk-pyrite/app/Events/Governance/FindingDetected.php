<?php

namespace App\Events\Governance;

use App\DTOs\CortexFinding;
use Illuminate\Foundation\Events\Dispatchable;

class FindingDetected
{
    use Dispatchable;

    public function __construct(
        public readonly CortexFinding $finding,
        public readonly ?int $agentRunId = null,
    ) {}
}

<?php

namespace App\Events\Governance;

use Illuminate\Foundation\Events\Dispatchable;

class ActionApplied
{
    use Dispatchable;

    public function __construct(
        public readonly string $findingId,
        public readonly string $proposalFilename,
        public readonly ?int $agentRunId = null,
    ) {}
}

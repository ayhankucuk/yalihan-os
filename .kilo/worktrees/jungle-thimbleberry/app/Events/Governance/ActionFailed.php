<?php

namespace App\Events\Governance;

use Illuminate\Foundation\Events\Dispatchable;

class ActionFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $findingId,
        public readonly string $errorMessage,
        public readonly ?int $agentRunId = null,
    ) {}
}

<?php

namespace App\Events\AI;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiTaskCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CortexRequestData $request,
        public CortexResponseData $response,
        public array $metadata = []
    ) {}
}

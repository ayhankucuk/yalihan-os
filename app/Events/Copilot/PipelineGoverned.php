<?php

namespace App\Events\Copilot;

use App\Models\PipelineRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PipelineGoverned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PipelineRun $run,
        public readonly array $decision,
    ) {}
}

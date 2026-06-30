<?php

namespace App\Events\Copilot;

use App\Models\PipelineRun;
use App\Models\PipelineStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PipelineStepFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PipelineRun $run,
        public readonly PipelineStep $step,
    ) {}
}

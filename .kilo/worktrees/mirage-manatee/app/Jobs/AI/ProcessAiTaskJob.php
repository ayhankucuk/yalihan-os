<?php

namespace App\Jobs\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AI\YalihanCortex;
use App\Services\AI\DTO\AIRequest;

/**
 * 🛡️ SAB SEALED
 * Generic AI Task Processor.
 */
class ProcessAiTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public AIRequest $request
    ) {}

    public function handle(YalihanCortex $cortex): void
    {
        $cortex->execute($this->request);
    }
}

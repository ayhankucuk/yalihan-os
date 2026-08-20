<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SystemQueueCanaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(
        public readonly string $canaryId,
        public readonly string $dispatchedAt
    ) {}

    public function handle(): void
    {
        Log::info('[CANARY_JOB_EXECUTED]', [
            'canary_id' => $this->canaryId,
            'dispatched_at' => $this->dispatchedAt,
            'executed_at' => now()->toISOString(),
            'worker_pid' => getmypid(),
        ]);
    }
}

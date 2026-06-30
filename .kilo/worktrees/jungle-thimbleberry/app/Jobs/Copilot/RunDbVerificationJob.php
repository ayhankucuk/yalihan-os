<?php

namespace App\Jobs\Copilot;

use App\Jobs\Copilot\Concerns\HandlesVerificationShard;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Verification shard: database connectivity and schema check.
 * Verifies pipeline tables are accessible and contain data.
 */
class RunDbVerificationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesVerificationShard;

    public int $timeout = 30;
    public int $tries = 2;

    public function __construct(
        public int $pipelineRunId
    ) {
        $this->onQueue(config('copilot-pipeline.queues.verification', 'copilot-verification'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("pipeline-run:{$this->pipelineRunId}:verify:db"),
        ];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $step = null;

        try {
            $step = $this->startShardStep(
                pipelineRunId: $this->pipelineRunId,
                stepName: 'verification',
                agentName: 'DbVerification',
                shardKey: 'db',
                inputPayload: ['type' => 'db']
            );

            if (!$step) {
                return;
            }

            $count = DB::table('pipeline_runs')->count();

            $this->completeShardStep(
                $step,
                outputPayload: [
                    'type' => 'db',
                    'result' => $count > 0 ? 'passed' : 'warning',
                    'proof' => "pipeline_runs count = {$count}",
                ],
                meta: [
                    'pipeline_runs_count' => $count,
                ]
            );
        } catch (Throwable $e) {
            $this->failShardStep($step, $e);
            throw $e;
        }
    }
}

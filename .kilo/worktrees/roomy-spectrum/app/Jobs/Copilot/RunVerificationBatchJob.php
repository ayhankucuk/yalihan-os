<?php

namespace App\Jobs\Copilot;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Events\Copilot\PipelineStepStarted;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verification batch orchestrator.
 * Dispatches 4 parallel shard jobs via Bus::batch(),
 * then chains to AggregateVerificationResultsJob.
 *
 * Flow:
 * RunVerificationBatchJob
 *  ├─ RunFeatureTestVerificationJob
 *  ├─ RunEndpointVerificationJob
 *  ├─ RunDbVerificationJob
 *  └─ RunRegressionScanJob
 *       ↓
 * AggregateVerificationResultsJob
 *       ↓
 * RunGovernanceStepJob
 */
class RunVerificationBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            new WithoutOverlapping("pipeline-run:{$this->pipelineRunId}:verification-batch"),
        ];
    }

    public function handle(): void
    {
        $run = PipelineRun::query()->findOrFail($this->pipelineRunId);

        // Terminal guard
        if ($run->pipeline_durumu->isTerminal()) {
            return;
        }

        // Create/acquire the batch orchestrator step
        DB::transaction(function () use ($run) {
            $step = PipelineStep::query()->firstOrCreate(
                [
                    'pipeline_run_id' => $run->id,
                    'adim_adi' => 'verification_batch',
                    'shard_key' => 'verification_batch',
                ],
                [
                    'agent_adi' => 'VerificationBatch',
                    'adim_durumu' => PipelineAdimDurumu::PENDING,
                    'queue_name' => config('copilot-pipeline.queues.verification', 'copilot-verification'),
                    'deneme_sayisi' => 0,
                ]
            );

            if ($step->adim_durumu === PipelineAdimDurumu::COMPLETED) {
                return;
            }

            $step->update([
                'adim_durumu' => PipelineAdimDurumu::RUNNING,
                'started_at' => now(),
                'deneme_sayisi' => $step->deneme_sayisi + 1,
                'input_payload' => [
                    'pipeline_run_id' => $run->id,
                    'stage' => 'verify',
                ],
            ]);

            $run->update([
                'mevcut_asama' => 'verify',
                'pipeline_durumu' => PipelineDurumu::VERIFICATION_RUNNING,
            ]);

            event(new PipelineStepStarted($run, $step));
        });

        // Read shard list from config
        $shards = config('copilot-pipeline.verification_shards', [
            'feature_tests',
            'endpoint',
            'db',
            'regression',
        ]);

        // Build shard job instances
        $shardJobs = collect($shards)->map(fn (string $shard) => $this->buildShardJob($shard, $run->id))->filter()->all();

        // Dispatch parallel batch
        $batch = Bus::batch($shardJobs)
            ->name("pipeline-run-{$run->id}-verification")
            ->allowFailures()
            ->then(function (Batch $batch) use ($run) {
                AggregateVerificationResultsJob::dispatch($run->id, $batch->id)
                    ->onQueue(config('copilot-pipeline.queues.verification', 'copilot-verification'));
            })
            ->catch(function (Batch $batch, Throwable $e) use ($run) {
                // Still aggregate even on partial failure
                AggregateVerificationResultsJob::dispatch($run->id, $batch->id)
                    ->onQueue(config('copilot-pipeline.queues.verification', 'copilot-verification'));
            })
            ->dispatch();

        // Store batch metadata on the orchestrator step
        PipelineStep::query()
            ->where('pipeline_run_id', $run->id)
            ->where('adim_adi', 'verification_batch')
            ->where('shard_key', 'verification_batch')
            ->update([
                'meta' => [
                    'batch_id' => $batch->id,
                    'jobs_total' => count($shardJobs),
                    'shards' => $shards,
                ],
            ]);

        Log::info('RunVerificationBatchJob: batch dispatched', [
            'run_uuid' => $run->run_uuid,
            'batch_id' => $batch->id,
            'shard_count' => count($shardJobs),
        ]);
    }

    /**
     * Map shard name → job class.
     */
    protected function buildShardJob(string $shard, int $runId): ?ShouldQueue
    {
        return match ($shard) {
            'feature_tests' => new RunFeatureTestVerificationJob($runId),
            'endpoint' => new RunEndpointVerificationJob($runId),
            'db' => new RunDbVerificationJob($runId),
            'regression' => new RunRegressionScanJob($runId),
            default => null,
        };
    }
}

<?php

namespace App\Jobs\Copilot;

use App\Enums\PipelineAdimDurumu;
use App\Events\Copilot\PipelineStepCompleted;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fan-in aggregator: collects all verification shard step results from DB,
 * builds a summary, then chains to RunGovernanceStepJob.
 * Replaces Cache-based inter-job communication with DB-backed steps.
 */
class AggregateVerificationResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries = 2;

    public function __construct(
        public int $pipelineRunId,
        public ?string $batchId = null
    ) {
        $this->onQueue(config('copilot-pipeline.queues.verification', 'copilot-verification'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("pipeline-run:{$this->pipelineRunId}:verify:aggregate"),
        ];
    }

    public function handle(): void
    {
        $run = PipelineRun::query()->findOrFail($this->pipelineRunId);

        // Terminal guard
        if ($run->pipeline_durumu->isTerminal()) {
            return;
        }

        // Read shard list from config
        $shardKeys = config('copilot-pipeline.verification_shards', [
            'feature_tests', 'endpoint', 'db', 'regression',
        ]);

        // Collect shard step results from DB
        $shardSteps = PipelineStep::query()
            ->where('pipeline_run_id', $run->id)
            ->where('adim_adi', 'verification')
            ->whereIn('shard_key', $shardKeys)
            ->get();

        $results = [];
        $hasFailure = false;
        $hasWarning = false;

        foreach ($shardSteps as $step) {
            $output = $step->output_payload ?? [];

            $results[] = [
                'type' => data_get($output, 'type', $step->shard_key),
                'result' => data_get($output, 'result', $step->adim_durumu->value),
                'proof' => data_get($output, 'proof', $step->hata_mesaji),
                'shard_key' => $step->shard_key,
                'adim_durumu' => $step->adim_durumu->value,
            ];

            if (
                $step->adim_durumu === PipelineAdimDurumu::FAILED ||
                data_get($output, 'result') === 'failed'
            ) {
                $hasFailure = true;
            }

            if (data_get($output, 'result') === 'warning') {
                $hasWarning = true;
            }
        }

        $summary = [
            'type' => 'verification_summary',
            'result' => $hasFailure ? 'failed' : ($hasWarning ? 'warning' : 'passed'),
            'proof' => sprintf(
                'Verification shards: %d total, failure=%s, warning=%s',
                count($results),
                $hasFailure ? 'yes' : 'no',
                $hasWarning ? 'yes' : 'no'
            ),
            'items' => $results,
        ];

        // Create/update aggregate step
        $aggregateStep = PipelineStep::query()->firstOrCreate(
            [
                'pipeline_run_id' => $run->id,
                'adim_adi' => 'verification_aggregate',
                'shard_key' => 'verification_aggregate',
            ],
            [
                'agent_adi' => 'VerificationAggregator',
                'adim_durumu' => PipelineAdimDurumu::PENDING,
                'queue_name' => config('copilot-pipeline.queues.verification', 'copilot-verification'),
                'deneme_sayisi' => 0,
            ]
        );

        $aggregateStep->update([
            'adim_durumu' => PipelineAdimDurumu::COMPLETED,
            'started_at' => $aggregateStep->started_at ?? now(),
            'finished_at' => now(),
            'output_payload' => $summary,
            'meta' => [
                'batch_id' => $this->batchId,
                'shard_count' => count($results),
            ],
        ]);

        // Also mark the batch orchestrator step as completed
        PipelineStep::query()
            ->where('pipeline_run_id', $run->id)
            ->where('adim_adi', 'verification_batch')
            ->where('shard_key', 'verification_batch')
            ->update([
                'adim_durumu' => PipelineAdimDurumu::COMPLETED,
                'finished_at' => now(),
                'output_payload' => $summary,
            ]);

        // Also complete the pre-created 'verification' step from the dispatcher
        PipelineStep::query()
            ->where('pipeline_run_id', $run->id)
            ->where('adim_adi', 'verification')
            ->whereNull('shard_key')
            ->update([
                'adim_durumu' => PipelineAdimDurumu::COMPLETED,
                'finished_at' => now(),
                'output_payload' => $summary,
            ]);

        $run->update(['completed_steps' => $run->completed_steps + 1]);

        event(new PipelineStepCompleted($run, $aggregateStep));

        // Chain to governance
        RunGovernanceStepJob::dispatch($run->id)
            ->onQueue(config('copilot-pipeline.queues.governance', 'copilot-governance'));

        Log::info('AggregateVerificationResultsJob: merged', [
            'run_uuid' => $run->run_uuid,
            'shard_count' => count($results),
            'result' => $summary['result'],
            'batch_id' => $this->batchId,
        ]);
    }
}

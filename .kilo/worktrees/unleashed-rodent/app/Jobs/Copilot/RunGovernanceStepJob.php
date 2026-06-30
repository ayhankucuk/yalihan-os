<?php

namespace App\Jobs\Copilot;

use App\Enums\PipelineDurumu;
use App\Events\Copilot\PipelineGoverned;
use App\Events\Copilot\PipelineStepCompleted;
use App\Events\Copilot\PipelineStepFailed;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use App\Services\AI\Copilot\Pipeline\GovernanceResolver;
use App\Services\AI\Copilot\Pipeline\PipelineStateManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Final step: governance decision.
 * Reads verification summary + all previous step outputs.
 * Uses GovernanceResolver for confidence-aware decisions.
 * Never parallelized — single source of truth.
 */
class RunGovernanceStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Governance does not retry — fail is final
    public int $timeout = 30;

    public function __construct(
        public int $pipelineRunId
    ) {
        $this->onQueue(config('copilot-pipeline.queues.governance', 'copilot-governance'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("pipeline-run:{$this->pipelineRunId}:govern"),
        ];
    }

    public function handle(
        PipelineStateManager $stateManager,
        GovernanceResolver $governanceResolver,
    ): void {
        $run = PipelineRun::query()->findOrFail($this->pipelineRunId);

        // Terminal guard
        if ($run->pipeline_durumu->isTerminal()) {
            return;
        }

        if ($stateManager->isStepCompleted($run, 'govern')) {
            return;
        }

        $step = $stateManager->getOrCreateStep($run, 'govern', 'GovernanceResolver');
        $step->markRunning();
        $stateManager->transitionRun($run, PipelineDurumu::GOVERNING);

        try {
            // Resolve final decision (confidence-aware)
            $decision = $governanceResolver->resolve($run);

            // Read verification summary from aggregate step
            $verificationAggregate = PipelineStep::query()
                ->where('pipeline_run_id', $run->id)
                ->where('adim_adi', 'verification_aggregate')
                ->where('shard_key', 'verification_aggregate')
                ->first();

            $verificationItems = data_get($verificationAggregate?->output_payload, 'items', []);

            // Build final output
            $finalOutput = [
                'stage' => 'govern',
                'summary' => 'Governance decision completed.',
                'findings' => [],
                'fixes' => [],
                'execution' => [],
                'verification' => $verificationItems,
                'decision' => $decision,
                'warnings' => ($decision['action'] === 'proceed_with_caution')
                    ? ['verification_warning']
                    : [],
                'meta' => [
                    'governed_at' => now()->toIso8601String(),
                    'verification_result' => data_get($verificationAggregate?->output_payload, 'result', 'unknown'),
                ],
            ];

            // Apply decision
            $governanceResolver->applyDecision($run, $decision);

            $step->markCompleted([
                'decision' => $decision,
                'governed_at' => now()->toIso8601String(),
            ]);

            $run->update([
                'completed_steps' => $run->completed_steps + 1,
                'final_output' => $finalOutput,
            ]);

            // Transition to terminal state
            $terminalState = match ($decision['action']) {
                'block' => PipelineDurumu::HALTED,
                default => PipelineDurumu::COMPLETED,
            };

            $stateManager->transitionRun($run, $terminalState);

            PipelineStepCompleted::dispatch($run, $step);
            PipelineGoverned::dispatch($run, $decision);

            Log::info('RunGovernanceStepJob completed', [
                'run_uuid' => $run->run_uuid,
                'action' => $decision['action'],
                'confidence' => $decision['confidence'] ?? 0,
                'final_state' => $terminalState->value,
            ]);

        } catch (\Throwable $e) {
            $step->markFailed($e->getMessage());
            $stateManager->failRun($run, 'Governance step failed: ' . $e->getMessage());
            PipelineStepFailed::dispatch($run, $step);

            Log::error('RunGovernanceStepJob failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

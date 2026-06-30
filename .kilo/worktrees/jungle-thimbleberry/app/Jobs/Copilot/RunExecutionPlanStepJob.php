<?php

namespace App\Jobs\Copilot;

use App\Enums\PipelineDurumu;
use App\Events\Copilot\PipelineStepCompleted;
use App\Events\Copilot\PipelineStepFailed;
use App\Models\PipelineRun;
use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use App\Services\AI\Copilot\Pipeline\PipelineStateManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Converts fix plans into IDE-ready execution instructions.
 * Produces file-level patches, commands, and verify steps.
 */
class RunExecutionPlanStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly int $pipelineRunId,
    ) {}

    public function handle(
        PipelineStateManager $stateManager,
        PipelineDispatcher $dispatcher,
    ): void {
        $run = PipelineRun::findOrFail($this->pipelineRunId);

        if ($stateManager->isStepCompleted($run, 'execution')) {
            return;
        }

        $step = $stateManager->getOrCreateStep($run, 'execution', 'DebugExecutorAgent');
        $step->markRunning();
        $stateManager->transitionRun($run, PipelineDurumu::EXECUTION_RUNNING);

        try {
            // Read fix plans
            $fixStep = $run->steps()->forStep('fix')->first();
            $fixes = $fixStep?->output_payload['fixes'] ?? [];

            // Generate execution plan
            $executionPlan = [];
            foreach ($fixes as $fix) {
                $executionPlan[] = [
                    'finding_index' => $fix['finding_index'],
                    'action' => $fix['fix_type'] === 'auto' ? 'apply' : 'review',
                    'target_files' => $fix['target_files'],
                    'instructions' => $fix['recommended_action'],
                    'risk_level' => $fix['risk_level'],
                    'verify_command' => $this->generateVerifyCommand($fix),
                ];
            }

            $output = [
                'execution' => $executionPlan,
                'total_actions' => count($executionPlan),
                'auto_apply_count' => count(array_filter($executionPlan, fn ($e) => $e['action'] === 'apply')),
            ];

            $step->markCompleted($output);
            $run->update(['completed_steps' => $run->completed_steps + 1]);

            PipelineStepCompleted::dispatch($run, $step);
            $dispatcher->dispatchNextStep($run, 'execution');

        } catch (\Throwable $e) {
            $step->markFailed($e->getMessage());
            $stateManager->failRun($run, 'Execution plan step failed: ' . $e->getMessage());
            PipelineStepFailed::dispatch($run, $step);

            Log::error('RunExecutionPlanStepJob failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function generateVerifyCommand(array $fix): ?string
    {
        return match ($fix['fix_type']) {
            'auto' => 'php artisan test --filter=' . ($fix['finding_title'] ?? 'unknown'),
            'manual' => 'php artisan sab:integrity-scan',
            default => null,
        };
    }
}

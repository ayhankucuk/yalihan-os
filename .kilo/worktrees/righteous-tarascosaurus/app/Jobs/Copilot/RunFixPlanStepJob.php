<?php

namespace App\Jobs\Copilot;

use App\Enums\PipelineAdimDurumu;
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
 * Generates fix plans from audit findings.
 * Each finding gets a fix recommendation with scope, risk, and steps.
 */
class RunFixPlanStepJob implements ShouldQueue
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

        if ($stateManager->isStepCompleted($run, 'fix')) {
            return;
        }

        $step = $stateManager->getOrCreateStep($run, 'fix', 'FixGeneratorAgent');
        $step->markRunning();
        $stateManager->transitionRun($run, PipelineDurumu::FIX_RUNNING);

        try {
            // Read audit output
            $auditStep = $run->steps()->forStep('audit')->first();
            $findings = $auditStep?->output_payload['findings'] ?? [];

            // Generate fix plan for each finding
            $fixes = [];
            foreach ($findings as $index => $finding) {
                $fixes[] = [
                    'finding_index' => $index,
                    'finding_title' => $finding['title'] ?? $finding['message'] ?? 'Unknown',
                    'severity' => $finding['severity'] ?? 'medium',
                    'fix_type' => $this->classifyFixType($finding),
                    'target_files' => $finding['target_files'] ?? [],
                    'recommended_action' => $finding['recommendation'] ?? 'Manual review required.',
                    'risk_level' => $this->assessRisk($finding),
                    'estimated_scope' => $this->estimateScope($finding),
                ];
            }

            $output = [
                'fixes' => $fixes,
                'total_fixes' => count($fixes),
                'auto_fixable' => count(array_filter($fixes, fn ($f) => $f['fix_type'] === 'auto')),
            ];

            $step->markCompleted($output);
            $run->update(['completed_steps' => $run->completed_steps + 1]);

            PipelineStepCompleted::dispatch($run, $step);
            $dispatcher->dispatchNextStep($run, 'fix');

        } catch (\Throwable $e) {
            $step->markFailed($e->getMessage());
            $stateManager->failRun($run, 'Fix plan step failed: ' . $e->getMessage());
            PipelineStepFailed::dispatch($run, $step);

            Log::error('RunFixPlanStepJob failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function classifyFixType(array $finding): string
    {
        $category = $finding['category'] ?? '';

        return match (true) {
            str_contains($category, 'data_quality') => 'auto',
            str_contains($category, 'configuration') => 'auto',
            str_contains($category, 'data_integrity') => 'manual',
            ($finding['severity'] ?? '') === 'critical' => 'manual',
            default => 'semi_auto',
        };
    }

    protected function assessRisk(array $finding): string
    {
        return match ($finding['severity'] ?? 'medium') {
            'critical' => 'high',
            'high' => 'medium',
            default => 'low',
        };
    }

    protected function estimateScope(array $finding): string
    {
        $targets = $finding['target_files'] ?? [];

        return match (true) {
            count($targets) > 5 => 'broad',
            count($targets) > 1 => 'moderate',
            default => 'narrow',
        };
    }
}

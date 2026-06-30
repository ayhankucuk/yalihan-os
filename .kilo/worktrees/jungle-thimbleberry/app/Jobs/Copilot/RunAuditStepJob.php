<?php

namespace App\Jobs\Copilot;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Events\Copilot\PipelineStepCompleted;
use App\Events\Copilot\PipelineStepFailed;
use App\Models\PipelineRun;
use App\Services\AI\Copilot\CopilotAuditEngine;
use App\Services\AI\Copilot\ContextCollector;
use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use App\Services\AI\Copilot\Pipeline\PipelineStateManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs the audit engine against the normalized payload.
 * Produces findings with severity, category, and recommendations.
 */
class RunAuditStepJob implements ShouldQueue
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
        ContextCollector $contextCollector,
        CopilotAuditEngine $auditEngine,
    ): void {
        $run = PipelineRun::findOrFail($this->pipelineRunId);

        // Idempotency
        if ($stateManager->isStepCompleted($run, 'audit')) {
            return;
        }

        $step = $stateManager->getOrCreateStep($run, 'audit', 'CopilotAuditEngine');
        $step->markRunning();
        $stateManager->transitionRun($run, PipelineDurumu::AUDIT_RUNNING);

        try {
            $payload = $run->normalized_payload ?? $run->input_payload ?? [];

            // Build context from payload
            $routeName = $payload['route'] ?? $payload['context']['route'] ?? 'dashboard';
            $entityId = $payload['entity_id'] ?? $payload['context']['entity_id'] ?? null;
            $context = $contextCollector->collect($routeName, $entityId);

            // Run audit engine
            $findings = $auditEngine->audit($context);

            $output = [
                'findings' => $findings,
                'total_count' => count($findings),
                'critical_count' => count(array_filter($findings, fn ($f) => ($f['severity'] ?? '') === 'critical')),
                'high_count' => count(array_filter($findings, fn ($f) => ($f['severity'] ?? '') === 'high')),
            ];

            $step->markCompleted($output);
            $run->update(['completed_steps' => $run->completed_steps + 1]);

            PipelineStepCompleted::dispatch($run, $step);

            // Chain: dispatch fix
            $dispatcher->dispatchNextStep($run, 'audit');

        } catch (\Throwable $e) {
            $step->markFailed($e->getMessage());
            $stateManager->failRun($run, 'Audit step failed: ' . $e->getMessage());
            PipelineStepFailed::dispatch($run, $step);

            Log::error('RunAuditStepJob failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

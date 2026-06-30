<?php

namespace App\Agents;

use App\Models\AgentMemory;
use Illuminate\Support\Facades\Log;

/**
 * WatcherAgent — Pipeline Coordinator
 *
 * Orchestrates the full multi-agent pipeline:
 *   Cortex → Governance → Execution → Optimizer
 *
 * Handles: scheduling, agent sequencing, failure isolation,
 * pipeline health monitoring, and cross-agent communication.
 *
 * If any agent fails, the pipeline continues with degraded output.
 */
class WatcherAgent extends BaseAgent
{
    public function __construct(
        private readonly CortexAgent $cortexAgent,
        private readonly GovernanceAgent $governanceAgent,
        private readonly ExecutionAgent $executionAgent,
        private readonly OptimizerAgent $optimizerAgent,
    ) {}

    public function name(): string
    {
        return 'watcher';
    }

    protected function execute(array $context): array
    {
        $pipeline = [];
        $pipelineDegraded = false;

        // ─── Step 1: Detection (Cortex) ─────────────────────
        $cortexResult = $this->cortexAgent->run([
            'source' => $context['source'] ?? null,
        ]);

        $pipeline['cortex'] = [
            'agent_durumu' => $cortexResult['success'] ? 'completed' : 'failed',
            'findings_count' => $cortexResult['findings_count'] ?? 0,
            'run_id' => $cortexResult['run_id'] ?? null,
        ];

        if (!($cortexResult['success'] ?? false)) {
            $pipelineDegraded = true;
            Log::warning('WatcherAgent: Cortex failed, pipeline degraded', [
                'error' => $cortexResult['error'] ?? 'unknown',
            ]);

            return $this->buildDegradedResult($pipeline, 'cortex');
        }

        $findings = $cortexResult['findings'] ?? [];
        if (empty($findings)) {
            return $this->buildResult($pipeline, 'No findings detected — system healthy.');
        }

        // ─── Step 2: Classification (Governance) ────────────
        $governanceResult = $this->governanceAgent->run([
            'findings' => $findings,
        ]);

        $pipeline['governance'] = [
            'agent_durumu' => $governanceResult['success'] ? 'completed' : 'failed',
            'decisions_count' => $governanceResult['decisions_count'] ?? 0,
            'summary' => $governanceResult['summary'] ?? [],
            'run_id' => $governanceResult['run_id'] ?? null,
        ];

        if (!($governanceResult['success'] ?? false)) {
            $pipelineDegraded = true;
            Log::warning('WatcherAgent: Governance failed, pipeline degraded');

            return $this->buildDegradedResult($pipeline, 'governance');
        }

        // ─── Step 3: Execution ──────────────────────────────
        $executionResult = $this->executionAgent->run([
            'classified' => $governanceResult['classified'] ?? [],
        ]);

        $pipeline['execution'] = [
            'agent_durumu' => $executionResult['success'] ? 'completed' : 'failed',
            'summary' => $executionResult['summary'] ?? [],
            'run_id' => $executionResult['run_id'] ?? null,
        ];

        if (!($executionResult['success'] ?? false)) {
            $pipelineDegraded = true;
            Log::warning('WatcherAgent: Execution failed, pipeline degraded');
        }

        // ─── Step 4: Optimization (non-blocking) ────────────
        $skipOptimizer = $context['skip_optimizer'] ?? false;
        if (!$skipOptimizer) {
            $optimizerResult = $this->optimizerAgent->run([
                'lookback_days' => $context['optimizer_lookback'] ?? 30,
            ]);

            $pipeline['optimizer'] = [
                'agent_durumu' => $optimizerResult['success'] ? 'completed' : 'failed',
                'suggestions_count' => $optimizerResult['summary']['total_suggestions'] ?? 0,
                'run_id' => $optimizerResult['run_id'] ?? null,
            ];

            if (!($optimizerResult['success'] ?? false)) {
                // Optimizer failure is non-critical
                Log::info('WatcherAgent: Optimizer failed (non-critical)');
            }
        }

        // ─── Store pipeline run in memory ───────────────────
        AgentMemory::remember($this->name(), 'last_pipeline', 'metric', [
            'completed_at' => now()->toIso8601String(),
            'findings' => $cortexResult['findings_count'] ?? 0,
            'decisions' => $governanceResult['decisions_count'] ?? 0,
            'auto_run' => $executionResult['summary']['auto_run'] ?? 0,
            'queued' => $executionResult['summary']['queued'] ?? 0,
            'degraded' => $pipelineDegraded,
        ]);

        return $this->buildResult($pipeline, $pipelineDegraded ? 'Pipeline completed with degradation' : 'Pipeline completed successfully');
    }

    /**
     * Collect health from all agents.
     */
    public function pipelineHealth(): array
    {
        return [
            'cortex' => $this->cortexAgent->health(),
            'governance' => $this->governanceAgent->health(),
            'execution' => $this->executionAgent->health(),
            'optimizer' => $this->optimizerAgent->health(),
            'watcher' => $this->health(),
        ];
    }

    private function buildResult(array $pipeline, string $message): array
    {
        $totalFindings = $pipeline['cortex']['findings_count'] ?? 0;
        $totalDecisions = $pipeline['governance']['decisions_count'] ?? 0;

        return [
            'success' => true,
            'message' => $message,
            'pipeline' => $pipeline,
            'findings_count' => $totalFindings,
            'decisions_count' => $totalDecisions,
            'summary' => [
                'pipeline_agents' => count($pipeline),
                'total_findings' => $totalFindings,
                'total_decisions' => $totalDecisions,
                'execution' => $pipeline['execution']['summary'] ?? [],
                'optimizer' => $pipeline['optimizer'] ?? ['agent_durumu' => 'skipped'],
            ],
        ];
    }

    private function buildDegradedResult(array $pipeline, string $failedAt): array
    {
        return [
            'success' => false,
            'message' => "Pipeline degraded: {$failedAt} agent failed",
            'pipeline' => $pipeline,
            'findings_count' => $pipeline['cortex']['findings_count'] ?? 0,
            'decisions_count' => 0,
            'summary' => [
                'degraded_at' => $failedAt,
                'pipeline_agents' => count($pipeline),
            ],
        ];
    }
}

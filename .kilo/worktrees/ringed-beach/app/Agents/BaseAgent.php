<?php

namespace App\Agents;

use App\Agents\Contracts\AgentContract;
use App\Models\AgentRun;
use Illuminate\Support\Facades\Log;

/**
 * BaseAgent — SAB4 Agent Foundation
 *
 * Handles: run lifecycle tracking, health reporting, failure isolation.
 * Every agent execution is recorded in the agent_runs table.
 */
abstract class BaseAgent implements AgentContract
{
    protected ?AgentRun $currentRun = null;

    abstract public function name(): string;

    /**
     * Agent-specific execution logic. Override in concrete agents.
     */
    abstract protected function execute(array $context): array;

    /**
     * Run the agent with full lifecycle tracking.
     * Failure is isolated — agent failure does not crash the pipeline.
     */
    public function run(array $context = []): array
    {
        $this->currentRun = AgentRun::create([
            'agent_name' => $this->name(),
            'agent_durumu' => 'running',
            'started_at' => now(),
            'input_summary' => $this->summarizeInput($context),
        ]);

        try {
            $result = $this->execute($context);

            $this->currentRun->markCompleted(
                $result['summary'] ?? [],
                $result['findings_count'] ?? 0,
                $result['decisions_count'] ?? 0
            );

            return array_merge($result, [
                'agent' => $this->name(),
                'run_id' => $this->currentRun->id,
            ]);
        } catch (\Throwable $e) {
            $this->currentRun->markFailed($e->getMessage());

            Log::error("Agent [{$this->name()}] failed", [
                'hata_mesaji' => $e->getMessage(),
                'run_id' => $this->currentRun->id,
            ]);

            return [
                'success' => false,
                'agent' => $this->name(),
                'run_id' => $this->currentRun->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Agent health check based on recent run history.
     */
    public function health(): array
    {
        $lastRun = AgentRun::forAgent($this->name())
            ->latest('started_at')
            ->first();

        $recentFails = AgentRun::forAgent($this->name())
            ->failed()
            ->recent(24)
            ->count();

        $totalRuns = AgentRun::forAgent($this->name())
            ->recent(24)
            ->count();

        return [
            'agent' => $this->name(),
            'agent_durumu' => $this->determineHealthStatus($recentFails, $totalRuns),
            'last_run' => $lastRun?->started_at?->toIso8601String(),
            'last_durumu' => $lastRun?->agent_durumu,
            'last_duration_ms' => $lastRun?->duration_ms,
            'recent_failures' => $recentFails,
            'total_runs_24h' => $totalRuns,
        ];
    }

    /**
     * Determine health status: healthy, degraded, or critical.
     */
    private function determineHealthStatus(int $failures, int $total): string
    {
        if ($total === 0) {
            return 'idle';
        }
        if ($failures === 0) {
            return 'healthy';
        }
        if ($failures >= 5 || ($total > 0 && $failures / $total > 0.5)) {
            return 'critical';
        }

        return 'degraded';
    }

    /**
     * Truncate input for storage (prevent large JSON in DB).
     */
    private function summarizeInput(array $context): array
    {
        $summary = [];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $summary[$key] = count($value) . ' items';
            } elseif (is_string($value) && strlen($value) > 200) {
                $summary[$key] = substr($value, 0, 200) . '...';
            } else {
                $summary[$key] = $value;
            }
        }

        return $summary;
    }
}

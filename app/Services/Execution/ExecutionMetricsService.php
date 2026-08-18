<?php

namespace App\Services\Execution;

use App\Models\WorkforceExecution;
use App\Repositories\EloquentExecutionRuntimeRepository;
use App\Repositories\ExecutionRuntimeRepositoryInterface;
use App\Traits\GuardsAgentWrites;

/**
 * ExecutionMetricsService — Sprint 15 Operations Console
 *
 * Provides runtime execution metrics for the Operations Dashboard.
 *
 * Metrics calculated from WorkforceExecution records:
 *   - Success/failure/replay rates
 *   - Average retry count
 *   - Capability breakdown
 *
 * READ-ONLY: All methods are query-only, no state mutations.
 *
 * @see OperationsConsoleController
 */
class ExecutionMetricsService
{
    use GuardsAgentWrites;

    public function __construct(
        protected ExecutionRuntimeRepositoryInterface $repository,
    ) {}

    /**
     * Generate a full metrics report for a tenant.
     *
     * @return array{total_executions: int, success_rate: float, failure_rate: float,
     *                replay_rate: float, avg_retry_count: float}
     */
    public function generateReport(?int $tenantId = null): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        $executions = $this->repository->getActiveExecutions($tenantId);

        $all = $executions;
        $failed = $all->filter(fn ($e) => $e->execution_status === WorkforceExecution::STATUS_FAILED);
        $completed = $all->filter(fn ($e) => $e->execution_status === WorkforceExecution::STATUS_COMPLETED);
        $replays = $all->filter(fn ($e) => $e->trigger_type === WorkforceExecution::TRIGGER_REPLAY);

        $total = $all->count();
        if ($total === 0) {
            return [
                'total_executions' => 0,
                'success_rate' => 0.0,
                'failure_rate' => 0.0,
                'replay_rate' => 0.0,
                'avg_retry_count' => 0.0,
            ];
        }

        $retryCounts = $all->pluck('retry_count')->filter()->map(fn ($v) => (int) $v);
        $avgRetry = $retryCounts->isNotEmpty()
            ? $retryCounts->avg()
            : 0.0;

        return [
            'total_executions' => $total,
            'success_rate' => $total > 0 ? round($completed->count() / $total, 4) : 0.0,
            'failure_rate' => $total > 0 ? round($failed->count() / $total, 4) : 0.0,
            'replay_rate' => $total > 0 ? round($replays->count() / $total, 4) : 0.0,
            'avg_retry_count' => round($avgRetry, 2),
        ];
    }

    /**
     * Calculate capability-level metrics for a tenant.
     *
     * @return array<string, array{total: int, success: int, failed: int, success_rate: float}>
     */
    public function calculateCapabilityMetrics(?int $tenantId = null, ?string $capability = null): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        $query = WorkforceExecution::query()
            ->byTenant($tenantId ?? 1);

        if ($capability !== null) {
            $query->where('capability', $capability);
        }

        $executions = $query->get();

        $grouped = $executions->groupBy('capability');

        $result = [];
        foreach ($grouped as $cap => $items) {
            $total = $items->count();
            $success = $items->filter(
                fn ($e) => $e->execution_status === WorkforceExecution::STATUS_COMPLETED
            )->count();
            $failed = $items->filter(
                fn ($e) => $e->execution_status === WorkforceExecution::STATUS_FAILED
            )->count();

            $result[$cap] = [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'success_rate' => $total > 0 ? round($success / $total, 4) : 0.0,
            ];
        }

        return $result;
    }
}

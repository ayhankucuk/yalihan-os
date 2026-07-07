<?php

namespace App\Services\Workspace;

use App\Models\WorkspaceExecution;
use Illuminate\Support\Facades\Log;

/**
 * RetryService
 *
 * Sprint 4.7: Workspace Execution Engine
 *
 * Manages retry logic for failed WorkspaceExecutions.
 *
 * Retry Rules:
 * - After each failure, execution is marked as 'retrying' briefly
 * - Then a NEW execution record is created with same payload (same as ReplayService)
 * - After max_attempts, the record becomes permanently 'failed'
 * - failure_reason is stored PERMANENTLY on the original failed record
 * - Backoff is exponential: [10s, 60s, 300s] by default
 *
 * Architecture:
 *   ProcessWorkspaceExecutionJob catches failure
 *   → RetryService::shouldRetry() determines if retry is possible
 *   → If yes: RetryService::scheduleRetry() creates new execution record
 *   → If no: execution stays 'failed' permanently
 */
class RetryService
{
    public function __construct(
        private readonly WorkspaceExecutionService $executionService,
    ) {}

    /**
     * Check if a failed execution should be retried.
     */
    public function shouldRetry(WorkspaceExecution $execution): bool
    {
        return $execution->canRetry();
    }

    /**
     * Schedule a retry for a failed execution.
     * Creates a NEW execution record (never mutates original).
     *
     * @throws \InvalidArgumentException if retry not possible
     */
    public function scheduleRetry(WorkspaceExecution $failedExecution): WorkspaceExecution
    {
        if (!$this->shouldRetry($failedExecution)) {
            throw new \InvalidArgumentException(
                "Execution {$failedExecution->id} cannot be retried. " .
                "State: {$failedExecution->state}, " .
                "RetryCount: {$failedExecution->retry_count}, " .
                "MaxAttempts: {$failedExecution->max_attempts}"
            );
        }

        Log::info('[RetryService] Scheduling retry', [
            'original_execution_id' => $failedExecution->id,
            'workspace_id'         => $failedExecution->workspace_id,
            'retry_count'          => $failedExecution->retry_count + 1,
            'max_attempts'        => $failedExecution->max_attempts,
        ]);

        // Mark the new execution as a retry of the original
        return $this->executionService->dispatch(
            $failedExecution->workspace,
            $failedExecution->execution_type,
            $failedExecution->execution_label,
            $failedExecution->input_payload ?? [],
            [
                'max_attempts' => $failedExecution->max_attempts,
                'backoff'       => $failedExecution->backoff_intervals,
                'queue'        => $failedExecution->queue_name,
                'timeout'      => $failedExecution->timeout_seconds,
                'chain_id'      => $failedExecution->chain_id,
                'triggered_by'  => WorkspaceExecution::TRIGGERED_BY_MANUAL,
            ]
        );
    }

    /**
     * Permanently mark an execution as failed (no more retries).
     * Used when manual cancellation of retry is needed.
     */
    public function giveUp(WorkspaceExecution $execution): void
    {
        $execution->update([
            'state' => WorkspaceExecution::STATE_FAILED,
        ]);

        Log::info('[RetryService] Given up on retry', [
            'execution_id' => $execution->id,
        ]);
    }

    /**
     * Get the backoff delay for the next retry attempt.
     */
    public function getBackoffDelay(WorkspaceExecution $execution): int
    {
        return $execution->getBackoffSeconds();
    }

    /**
     * Update retry configuration on an execution.
     */
    public function configureRetry(
        WorkspaceExecution $execution,
        int $maxAttempts,
        ?array $backoffIntervals = null,
    ): void {
        $execution->update([
            'max_attempts'      => $maxAttempts,
            'backoff_intervals' => $backoffIntervals ?? WorkspaceExecution::DEFAULT_BACKOFF,
        ]);
    }

    /**
     * Get retry statistics for a workspace.
     */
    public function getStats(int $workspaceId): array
    {
        $executions = WorkspaceExecution::query()
            ->forWorkspace($workspaceId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $total    = $executions->count();
        $failed   = $executions->filter(fn($e) => $e->state === WorkspaceExecution::STATE_FAILED)->count();
        $succeeded = $executions->filter(fn($e) => $e->state === WorkspaceExecution::STATE_SUCCEEDED)->count();
        $retried  = $executions->filter(fn($e) => $e->retry_count > 0)->count();
        $canRetry = $executions->filter(fn($e) => $e->canRetry())->count();

        return [
            'total_executions' => $total,
            'failed_count'    => $failed,
            'succeeded_count'  => $succeeded,
            'executions_retried' => $retried,
            'can_retry_count' => $canRetry,
            'success_rate'    => $total > 0 ? round(($succeeded / $total) * 100, 1) : 0,
            'retry_rate'      => $succeeded > 0 ? round(($retried / $succeeded) * 100, 1) : 0,
        ];
    }
}

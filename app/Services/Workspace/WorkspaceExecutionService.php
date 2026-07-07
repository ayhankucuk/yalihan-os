<?php

namespace App\Services\Workspace;

use App\Jobs\Workspace\ProcessWorkspaceExecutionJob;
use App\Models\PortfolioDriveWorkspace;
use App\Models\WorkspaceExecution;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WorkspaceExecutionService
 *
 * Sprint 4.7: Workspace Execution Engine
 *
 * Central service for dispatching, tracking, and managing workspace executions.
 * Every long-running operation is wrapped as a WorkspaceExecution record and
 * processed asynchronously through the queue.
 *
 * Architecture:
 *   dispatch() → creates WorkspaceExecution record → queues ProcessWorkspaceExecutionJob
 *   Worker picks up → executes agent → updates WorkspaceExecution state
 *   Hermes listens → updates Workspace state (lifecycle)
 */
class WorkspaceExecutionService
{
    /**
     * Dispatch a new execution for a workspace.
     *
     * @param PortfolioDriveWorkspace $workspace
     * @param string $type Execution type (e.g. 'photo_agent', 'drive_sync')
     * @param string $label Human-readable label (Turkish)
     * @param array $payload Input payload for the agent
     * @param array $options {
     *     max_attempts?: int,
     *     backoff?: int[],
     *     queue?: string,
     *     timeout?: int,
     *     chain_id?: string,
     *     triggered_by?: string,
     *     triggered_by_user_id?: int,
     * }
     */
    public function dispatch(
        PortfolioDriveWorkspace $workspace,
        string $type,
        string $label,
        array $payload = [],
        array $options = [],
    ): WorkspaceExecution {
        $ilanId = $workspace->ilan_id;

        // Create the execution record
        $execution = WorkspaceExecution::create([
            'workspace_id'   => $workspace->id,
            'ilan_id'       => $ilanId,
            'tenant_id'     => $workspace->tenant_id,
            'execution_type' => $type,
            'execution_label' => $label,
            'chain_id'       => $options['chain_id'] ?? $this->newChainId(),
            'state'          => WorkspaceExecution::STATE_QUEUED,
            'queued_at'      => now(),
            'attempt_number' => 1,
            'max_attempts'  => $options['max_attempts'] ?? 3,
            'retry_count'    => 0,
            'backoff_intervals' => $options['backoff'] ?? WorkspaceExecution::DEFAULT_BACKOFF,
            'input_payload'  => $payload,
            'queue_name'     => $options['queue'] ?? 'workspace',
            'timeout_seconds' => $options['timeout'] ?? 300,
            'triggered_by'  => $options['triggered_by'] ?? WorkspaceExecution::TRIGGERED_BY_HERMES,
            'triggered_by_user_id' => $options['triggered_by_user_id'] ?? null,
        ]);

        Log::info('[WorkspaceExecutionService] Dispatched', [
            'execution_id'  => $execution->id,
            'workspace_id'  => $workspace->id,
            'ilan_id'       => $ilanId,
            'type'          => $type,
            'label'         => $label,
            'chain_id'      => $execution->chain_id,
        ]);

        // Queue the job
        $job = new ProcessWorkspaceExecutionJob($execution);
        $jobId = dispatch($job->onQueue($execution->queue_name));

        // Record job ID
        $execution->update(['job_id' => $jobId ?? null]);

        return $execution;
    }

    /**
     * Dispatch a retry for a failed execution.
     * Creates a NEW execution record with same payload (never mutates failed record).
     */
    public function retry(WorkspaceExecution $failedExecution): WorkspaceExecution
    {
        if (!$failedExecution->canRetry()) {
            throw new \InvalidArgumentException(
                "Execution {$failedExecution->id} cannot be retried (state={$failedExecution->state}, retry_count={$failedExecution->retry_count})"
            );
        }

        $workspace = $failedExecution->workspace;
        if (!$workspace) {
            throw new \RuntimeException("Execution {$failedExecution->id} has no associated workspace");
        }

        Log::info('[WorkspaceExecutionService] Retrying', [
            'original_id'   => $failedExecution->id,
            'workspace_id'  => $workspace->id,
            'retry_count'   => $failedExecution->retry_count + 1,
        ]);

        return $this->dispatch($workspace, $failedExecution->execution_type, $failedExecution->execution_label, $failedExecution->input_payload ?? [], [
            'max_attempts'  => $failedExecution->max_attempts,
            'backoff'       => $failedExecution->backoff_intervals,
            'queue'        => $failedExecution->queue_name,
            'timeout'      => $failedExecution->timeout_seconds,
            'chain_id'      => $failedExecution->chain_id,
            'triggered_by'  => WorkspaceExecution::TRIGGERED_BY_MANUAL,
        ]);
    }

    /**
     * Replay a terminal execution.
     * Creates a NEW execution record with same payload (idempotent).
     */
    public function replay(WorkspaceExecution $execution, ?int $userId = null): WorkspaceExecution
    {
        if (!$execution->canReplay()) {
            throw new \InvalidArgumentException(
                "Execution {$execution->id} cannot be replayed (state={$execution->state})"
            );
        }

        $workspace = $execution->workspace;
        if (!$workspace) {
            throw new \RuntimeException("Execution {$execution->id} has no associated workspace");
        }

        Log::info('[WorkspaceExecutionService] Replaying', [
            'original_id'  => $execution->id,
            'workspace_id' => $workspace->id,
            'user_id'     => $userId,
        ]);

        return $this->dispatch($workspace, $execution->execution_type, $execution->execution_label, $execution->input_payload ?? [], [
            'max_attempts'  => $execution->max_attempts,
            'backoff'       => $execution->backoff_intervals,
            'queue'        => $execution->queue_name,
            'timeout'      => $execution->timeout_seconds,
            'chain_id'      => $this->newChainId(), // New chain for replay
            'triggered_by'  => WorkspaceExecution::TRIGGERED_BY_REPLAY,
            'triggered_by_user_id' => $userId,
        ]);
    }

    /**
     * Cancel a queued/running execution.
     */
    public function cancel(WorkspaceExecution $execution): void
    {
        if ($execution->isTerminal()) {
            throw new \InvalidArgumentException(
                "Cannot cancel terminal execution {$execution->id} (state={$execution->state})"
            );
        }

        $execution->markCancelled();

        // If job is in queue, forget it
        if ($execution->job_id) {
            try {
                \Illuminate\Support\Facades\Queue::getJobPool()->forget($execution->job_id);
            } catch (\Throwable) {
                // Best effort
            }
        }

        Log::info('[WorkspaceExecutionService] Cancelled', [
            'execution_id' => $execution->id,
        ]);
    }

    /**
     * Get execution summary for a workspace (for cockpit).
     */
    public function getSummary(int $workspaceId): array
    {
        $executions = WorkspaceExecution::query()
            ->forWorkspace($workspaceId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $total      = $executions->count();
        $active    = $executions->filter(fn($e) => $e->isActive())->count();
        $queued    = $executions->filter(fn($e) => $e->state === WorkspaceExecution::STATE_QUEUED)->count();
        $running   = $executions->filter(fn($e) => $e->state === WorkspaceExecution::STATE_RUNNING)->count();
        $failed    = $executions->filter(fn($e) => $e->state === WorkspaceExecution::STATE_FAILED)->count();
        $succeeded = $executions->filter(fn($e) => $e->state === WorkspaceExecution::STATE_SUCCEEDED)->count();

        // Last execution
        $lastExecution = $executions->first();

        // Success rate (last 10)
        $recent = $executions->take(10);
        $recentCount = $recent->count();
        $recentSuccess = $recent->filter(fn($e) => $e->state === WorkspaceExecution::STATE_SUCCEEDED)->count();
        $successRate = $recentCount > 0 ? (int) round(($recentSuccess / $recentCount) * 100) : 0;

        // Failed executions available for replay
        $failedForReplay = $executions
            ->filter(fn($e) => $e->state === WorkspaceExecution::STATE_FAILED && $e->canRetry())
            ->take(5)
            ->map(fn($e) => [
                'id'           => $e->id,
                'type'         => $e->execution_type,
                'label'        => $e->execution_label,
                'retry_count'  => $e->retry_count,
                'max_attempts' => $e->max_attempts,
                'reason'       => $e->failure_reason,
                'created_at'   => $e->created_at?->toIso8601String(),
            ])
            ->toArray();

        return [
            'total_count'     => $total,
            'active_count'   => $active,
            'queued_count'   => $queued,
            'running_count'  => $running,
            'failed_count'   => $failed,
            'succeeded_count'=> $succeeded,
            'success_rate'   => $successRate,
            'last_execution' => $lastExecution ? [
                'id'          => $lastExecution->id,
                'type'        => $lastExecution->execution_type,
                'label'       => $lastExecution->execution_label,
                'state'       => $lastExecution->state,
                'state_label' => $lastExecution->getStateLabel(),
                'duration_ms' => $lastExecution->duration_ms,
                'duration'     => $lastExecution->getDurationHuman(),
                'created_at'  => $lastExecution->created_at?->toIso8601String(),
            ] : null,
            'failed_for_replay' => $failedForReplay,
            'has_active'     => $active > 0,
        ];
    }

    /**
     * Get executions list for a workspace (paginated).
     */
    public function getForWorkspace(int $workspaceId, int $limit = 20): array
    {
        return WorkspaceExecution::query()
            ->forWorkspace($workspaceId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($e) => $this->executionToArray($e))
            ->toArray();
    }

    private function executionToArray(WorkspaceExecution $e): array
    {
        return [
            'id'            => $e->id,
            'type'          => $e->execution_type,
            'label'         => $e->execution_label,
            'state'         => $e->state,
            'state_label'   => $e->getStateLabel(),
            'state_color'   => $e->getStateColor(),
            'chain_id'       => $e->chain_id,
            'duration_ms'   => $e->duration_ms,
            'duration'       => $e->getDurationHuman(),
            'attempt_number' => $e->attempt_number,
            'retry_count'    => $e->retry_count,
            'can_retry'     => $e->canRetry(),
            'can_replay'    => $e->canReplay(),
            'can_cancel'    => !$e->isTerminal(),
            'failure_reason' => $e->failure_reason,
            'progress_pct'  => $e->progress_pct,
            'triggered_by'  => $e->triggered_by,
            'queued_at'     => $e->queued_at?->toIso8601String(),
            'started_at'    => $e->started_at?->toIso8601String(),
            'completed_at'  => $e->completed_at?->toIso8601String(),
            'created_at'    => $e->created_at?->toIso8601String(),
        ];
    }

    private function newChainId(): string
    {
        return 'chain_' . Str::uuid()->toString();
    }
}

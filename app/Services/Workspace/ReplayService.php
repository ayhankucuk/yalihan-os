<?php

namespace App\Services\Workspace;

use App\Models\WorkspaceExecution;
use Illuminate\Support\Facades\Log;

/**
 * ReplayService
 *
 * Sprint 4.7: Workspace Execution Engine
 *
 * Replays a terminal WorkspaceExecution by creating a NEW execution record.
 * Replay restarts from the failed execution, not from the beginning.
 * Replay is idempotent — safe to replay multiple times.
 *
 * Rules:
 * - NEVER mutates the original failed execution record
 * - ALWAYS creates a new execution record (preserves audit trail)
 * - Uses a new chain_id (independent from original)
 * - triggered_by is always 'replay'
 */
class ReplayService
{
    public function __construct(
        private readonly WorkspaceExecutionService $executionService,
    ) {}

    /**
     * Replay a terminal execution.
     *
     * @throws \InvalidArgumentException if execution is not terminal
     * @throws \RuntimeException if workspace not found
     */
    public function replay(WorkspaceExecution $execution, ?int $userId = null): WorkspaceExecution
    {
        if (!$execution->canReplay()) {
            throw new \InvalidArgumentException(
                "Execution {$execution->id} cannot be replayed. State: {$execution->state}"
            );
        }

        Log::info('[ReplayService] Replaying execution', [
            'execution_id'  => $execution->id,
            'type'          => $execution->execution_type,
            'workspace_id'  => $execution->workspace_id,
            'user_id'       => $userId,
            'original_state' => $execution->state,
        ]);

        return $this->executionService->replay($execution, $userId);
    }

    /**
     * Bulk replay all failed executions for a workspace.
     *
     * @return array<WorkspaceExecution> newly created executions
     */
    public function replayAllFailed(int $workspaceId, ?int $userId = null): array
    {
        $failed = WorkspaceExecution::query()
            ->forWorkspace($workspaceId)
            ->failed()
            ->whereNull('original_execution_id') // Only root failures, not retries
            ->orderBy('created_at', 'desc')
            ->get();

        $created = [];
        foreach ($failed as $execution) {
            try {
                $created[] = $this->replay($execution, $userId);
            } catch (\Throwable $e) {
                Log::warning('[ReplayService] Could not replay', [
                    'execution_id' => $execution->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }

    /**
     * Check if replay is available (has a terminal state).
     */
    public function isReplayAvailable(WorkspaceExecution $execution): bool
    {
        return $execution->canReplay();
    }

    /**
     * Get replay history for an execution (all replay executions derived from it).
     */
    public function getReplayChain(WorkspaceExecution $original): array
    {
        return WorkspaceExecution::query()
            ->where('original_execution_id', $original->id)
            ->orWhere('id', $original->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($e) => [
                'id'           => $e->id,
                'state'        => $e->state,
                'state_label'  => $e->getStateLabel(),
                'is_original'  => $e->id === $original->id,
                'retry_count'  => $e->retry_count,
                'duration_ms'  => $e->duration_ms,
                'failure_reason'=> $e->failure_reason,
                'created_at'   => $e->created_at?->toIso8601String(),
            ])
            ->toArray();
    }
}

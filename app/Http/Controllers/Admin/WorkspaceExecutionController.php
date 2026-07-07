<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortfolioDriveWorkspace;
use App\Models\WorkspaceExecution;
use App\Services\Workspace\ReplayService;
use App\Services\Workspace\RetryService;
use App\Services\Workspace\WorkspaceExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WorkspaceExecutionController
 *
 * Sprint 4.7: Workspace Execution Engine
 *
 * REST API for WorkspaceExecution CRUD + replay + retry.
 *
 * Routes:
 *   GET    /admin/workspace/{id}/executions         → list
 *   GET    /admin/workspace/{id}/executions/{execId} → show
 *   POST   /admin/workspace/{id}/executions         → dispatch
 *   POST   /admin/workspace/{id}/executions/{execId}/replay → replay
 *   POST   /admin/workspace/{id}/executions/{execId}/retry  → retry
 *   POST   /admin/workspace/{id}/executions/{execId}/cancel → cancel
 */
/**
 * @sab-ignore-thin
 */
class WorkspaceExecutionController extends Controller
{
    public function __construct(
        private readonly WorkspaceExecutionService $executionService,
        private readonly ReplayService $replayService,
        private readonly RetryService $retryService,
    ) {}

    // ─── Read ────────────────────────────────────────────────────────────────

    /**
     * GET /admin/workspace/{id}/executions
     */
    public function index(int $id): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        $executions = $this->executionService->getForWorkspace($workspace->id);

        return response()->json([
            'workspace_id' => $workspace->id,
            'executions'   => $executions,
            'count'        => count($executions),
        ]);
    }

    /**
     * GET /admin/workspace/{id}/executions/{execId}
     */
    public function show(int $id, int $execId): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        $exec = WorkspaceExecution::query()
            ->forWorkspace($workspace->id)
            ->find($execId);

        if (!$exec) {
            return response()->json(['error' => 'Execution bulunamadı'], 404);
        }

        return response()->json([
            'execution'   => $this->formatExecution($exec),
            'replay_chain' => $this->replayService->getReplayChain($exec),
        ]);
    }

    /**
     * GET /admin/workspace/{id}/executions-summary
     */
    public function summary(int $id): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        return response()->json($this->executionService->getSummary($workspace->id));
    }

    // ─── Write ──────────────────────────────────────────────────────────────

    /**
     * POST /admin/workspace/{id}/executions
     * Dispatch a new execution manually.
     */
    public function dispatchExecution(Request $request, int $id): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        $validated = $request->validate([
            'execution_type'   => 'required|string|max:60',
            'execution_label' => 'required|string|max:120',
            'payload'         => 'array',
            'queue'           => 'string|max:40',
            'timeout'         => 'integer|min:30|max:3600',
            'max_attempts'   => 'integer|min:1|max:10',
        ]);

        try {
            $execution = $this->executionService->dispatch(
                $workspace,
                $validated['execution_type'],
                $validated['execution_label'],
                $validated['payload'] ?? [],
                [
                    'queue'           => $validated['queue'] ?? 'workspace',
                    'timeout'         => $validated['timeout'] ?? 300,
                    'max_attempts'   => $validated['max_attempts'] ?? 3,
                    'triggered_by'    => WorkspaceExecution::TRIGGERED_BY_MANUAL,
                    'triggered_by_user_id' => $request->user()?->id,
                ]
            );

            return response()->json([
                'execution' => $this->formatExecution($execution),
                'message'   => 'Execution queued successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('[WorkspaceExecutionController] dispatch failed', [
                'workspace_id' => $id,
                'error'        => $e->getMessage(),
            ]);
            return $this->apiError($e);
        }
    }

    /**
     * POST /admin/workspace/{id}/executions/{execId}/replay
     */
    public function replay(Request $request, int $id, int $execId): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        $exec = WorkspaceExecution::query()
            ->forWorkspace($workspace->id)
            ->find($execId);

        if (!$exec) {
            return response()->json(['error' => 'Execution bulunamadı'], 404);
        }

        try {
            $newExec = $this->replayService->replay($exec, $request->user()?->id);

            return response()->json([
                'original_execution_id' => $exec->id,
                'execution'             => $this->formatExecution($newExec),
                'message'               => 'Replay queued successfully',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->apiError($e);
        }
    }

    /**
     * POST /admin/workspace/{id}/executions/{execId}/retry
     */
    public function retry(Request $request, int $id, int $execId): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        $exec = WorkspaceExecution::query()
            ->forWorkspace($workspace->id)
            ->find($execId);

        if (!$exec) {
            return response()->json(['error' => 'Execution bulunamadı'], 404);
        }

        try {
            $newExec = $this->retryService->scheduleRetry($exec);

            return response()->json([
                'original_execution_id' => $exec->id,
                'execution'             => $this->formatExecution($newExec),
                'message'               => 'Retry queued successfully',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->apiError($e);
        }
    }

    /**
     * POST /admin/workspace/{id}/executions/{execId}/cancel
     */
    public function cancel(int $id, int $execId): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        $exec = WorkspaceExecution::query()
            ->forWorkspace($workspace->id)
            ->find($execId);

        if (!$exec) {
            return response()->json(['error' => 'Execution bulunamadı'], 404);
        }

        try {
            $this->executionService->cancel($exec);
            $exec->refresh();

            return response()->json([
                'execution' => $this->formatExecution($exec),
                'message'   => 'Execution cancelled',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->apiError($e);
        }
    }

    // ─── Private ─────────────────────────────────────────────────────────

    private function findWorkspace(int $id): ?PortfolioDriveWorkspace
    {
        return PortfolioDriveWorkspace::query()
            ->withoutGlobalScopes()
            ->find($id);
    }

    private function authorizeWorkspace(PortfolioDriveWorkspace $workspace): void
    {
        if ($workspace->tenant_id !== null) {
            $this->authorize('view', $workspace);
        }
    }

    private function formatExecution(WorkspaceExecution $e): array
    {
        return [
            'id'              => $e->id,
            'type'            => $e->execution_type,
            'label'           => $e->execution_label,
            'state'           => $e->state,
            'state_label'     => $e->getStateLabel(),
            'state_color'     => $e->getStateColor(),
            'chain_id'        => $e->chain_id,
            'duration_ms'     => $e->duration_ms,
            'duration'         => $e->getDurationHuman(),
            'attempt_number'  => $e->attempt_number,
            'retry_count'     => $e->retry_count,
            'max_attempts'   => $e->max_attempts,
            'can_retry'      => $e->canRetry(),
            'can_replay'     => $e->canReplay(),
            'can_cancel'     => !$e->isTerminal(),
            'failure_reason' => $e->failure_reason,
            'progress_pct'    => $e->progress_pct,
            'triggered_by'    => $e->triggered_by,
            'queued_at'      => $e->queued_at?->toIso8601String(),
            'started_at'     => $e->started_at?->toIso8601String(),
            'completed_at'   => $e->completed_at?->toIso8601String(),
            'created_at'     => $e->created_at?->toIso8601String(),
        ];
    }

    private function apiError(\Throwable $e, int $status = 500): JsonResponse
    {
        Log::error('WorkspaceExecutionController error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'error'  => 'İşlem sırasında bir hata oluştu.',
            'detail' => config('app.debug') ? $e->getMessage() : null,
        ], $status);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Services\Hermes\HermesReplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HermesReplayController
 *
 * Sprint 4.7: Async Queue + Event Replay + Workspace Execution Engine
 *
 * Provides administrative controls for:
 *  - Replaying failed Hermes events
 *  - Retrying individual handler executions
 *  - Pausing / resuming / aborting workforce chains
 *  - Chain state visibility
 *
 * Routes:
 *   GET    /admin/hermes/replay                    → failed events list
 *   POST   /admin/hermes/replay/{logId}            → replay event sync
 *   POST   /admin/hermes/replay/{logId}/async      → replay event via queue
 *   POST   /admin/hermes/retry/{execLogId}         → retry handler execution
 *   POST   /admin/hermes/retry/{execLogId}/async   → retry handler via queue
 *   GET    /admin/hermes/chain/{ilanId}             → chain status
 *   POST   /admin/hermes/chain/{chainId}/pause      → pause chain
 *   POST   /admin/hermes/chain/{chainId}/resume    → resume chain
 *   POST   /admin/hermes/chain/{chainId}/abort     → abort chain
 */
class HermesReplayController extends Controller
{
    public function __construct(
        private readonly HermesReplayService $replayService,
    ) {}

    // ─── Failed Events ─────────────────────────────────────────────────────

    /**
     * List failed Hermes events.
     * GET /admin/hermes/replay
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 20), 100);

        $failed = HermesEventLog::query()
            ->where('status', HermesEventLog::STATUS_FAILED)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'event'       => $log->event_name,
                'tenant_id'   => $log->tenant_id,
                'error'       => $log->error_message,
                'occurred_at' => $log->occurred_at?->toIso8601String(),
                'payload'     => $log->payload,
                'can_replay'  => class_exists($log->event_class),
                'actions'     => [
                    'replay_sync'  => "/admin/hermes/replay/{$log->id}",
                    'replay_async' => "/admin/hermes/replay/{$log->id}/async",
                ],
            ]);

        return response()->json([
            'count'  => $failed->count(),
            'events' => $failed,
        ]);
    }

    // ─── Event Replay ────────────────────────────────────────────────────

    /**
     * Replay a failed event synchronously.
     * POST /admin/hermes/replay/{logId}
     */
    public function replay(int $logId): JsonResponse
    {
        $log = HermesEventLog::findOrFail($logId);

        try {
            $newLog = $this->replayService->replayEvent($logId);

            Log::info('[HermesReplayController] Event replayed', [
                'original_log_id' => $logId,
                'new_log_id'     => $newLog->id,
            ]);

            return response()->json([
                'success'         => true,
                'original_log_id'  => $logId,
                'new_log_id'      => $newLog->id,
                'new_log_status'  => $newLog->status,
                'message'         => "Event '{$log->event_name}' replayed successfully.",
            ]);
        } catch (\Throwable $e) {
            Log::error('[HermesReplayController] replay failed', [
                'log_id' => $logId,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'log_id'  => $logId,
            ], 500);
        }
    }

    /**
     * Replay a failed event via async queue.
     * POST /admin/hermes/replay/{logId}/async
     */
    public function replayAsync(int $logId): JsonResponse
    {
        HermesEventLog::findOrFail($logId);

        try {
            $this->replayService->replayEventAsync($logId);

            return response()->json([
                'success' => true,
                'log_id'  => $logId,
                'message' => 'Replay jobs dispatched to queue.',
            ]);
        } catch (\Throwable $e) {
            Log::error('[HermesReplayController] replayAsync failed', [
                'log_id' => $logId,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'log_id'  => $logId,
            ], 500);
        }
    }

    // ─── Handler Retry ───────────────────────────────────────────────────

    /**
     * Retry a specific failed handler execution.
     * POST /admin/hermes/retry/{execLogId}
     */
    public function retryHandler(int $execLogId): JsonResponse
    {
        $execLog = WorkforceExecutionLog::findOrFail($execLogId);

        try {
            $result = $this->replayService->retryHandler(
                $execLog->hermes_event_log_id,
                $execLog->agent_class,
            );

            return response()->json([
                'success'    => true,
                'exec_log_id' => $execLogId,
                'result'    => $result['result'],
                'message'   => "Handler '{$execLog->agent_name}' retried successfully.",
            ]);
        } catch (\Throwable $e) {
            Log::error('[HermesReplayController] retryHandler failed', [
                'exec_id' => $execLogId,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'success'  => false,
                'error'    => $e->getMessage(),
                'exec_id'  => $execLogId,
            ], 500);
        }
    }

    /**
     * Retry a specific handler execution via queue.
     * POST /admin/hermes/retry/{execLogId}/async
     */
    public function retryHandlerAsync(int $execLogId): JsonResponse
    {
        $execLog = WorkforceExecutionLog::findOrFail($execLogId);

        try {
            $this->replayService->retryHandlerAsync(
                $execLog->hermes_event_log_id,
                $execLog->agent_class,
            );

            return response()->json([
                'success'  => true,
                'exec_id'  => $execLogId,
                'message' => 'Handler retry dispatched to queue.',
            ]);
        } catch (\Throwable $e) {
            Log::error('[HermesReplayController] retryHandlerAsync failed', [
                'exec_id' => $execLogId,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── Chain Operations ───────────────────────────────────────────────

    /**
     * Chain status for an Ilan.
     * GET /admin/hermes/chain/{ilanId}
     */
    public function chainStatus(int $ilanId): JsonResponse
    {
        $status = $this->replayService->chainStatus($ilanId);

        return response()->json($status);
    }

    /**
     * Pause a workforce chain.
     * POST /admin/hermes/chain/{chainId}/pause
     */
    public function pauseChain(string $chainId): JsonResponse
    {
        $count = $this->replayService->pauseChain($chainId);

        Log::info('[HermesReplayController] Chain paused', [
            'chain_id'      => $chainId,
            'steps_skipped' => $count,
        ]);

        return response()->json([
            'success'  => true,
            'chain_id' => $chainId,
            'skipped'  => $count,
            'message'  => "Chain '{$chainId}' paused. {$count} step(s) skipped.",
        ]);
    }

    /**
     * Resume a paused workforce chain.
     * POST /admin/hermes/chain/{chainId}/resume
     */
    public function resumeChain(string $chainId): JsonResponse
    {
        $count = $this->replayService->resumeChain($chainId);

        Log::info('[HermesReplayController] Chain resumed', [
            'chain_id'         => $chainId,
            'steps_dispatched' => $count,
        ]);

        return response()->json([
            'success'    => true,
            'chain_id'  => $chainId,
            'dispatched' => $count,
            'message'   => $count > 0
                ? "Chain '{$chainId}' resumed. {$count} step(s) dispatched."
                : "Chain '{$chainId}' has no paused steps to resume.",
        ]);
    }

    /**
     * Abort a workforce chain.
     * POST /admin/hermes/chain/{chainId}/abort
     */
    public function abortChain(Request $request, string $chainId): JsonResponse
    {
        $reason = $request->input('reason', 'Aborted by operator');
        $count = $this->replayService->abortChain($chainId, $reason);

        Log::warning('[HermesReplayController] Chain aborted', [
            'chain_id'     => $chainId,
            'reason'        => $reason,
            'steps_failed' => $count,
        ]);

        return response()->json([
            'success'  => true,
            'chain_id' => $chainId,
            'failed'   => $count,
            'message' => "Chain '{$chainId}' aborted. {$count} step(s) marked failed.",
        ]);
    }
}

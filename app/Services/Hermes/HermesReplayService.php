<?php

namespace App\Services\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Jobs\Hermes\AsyncHandlerDispatchJob;
use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use Illuminate\Support\Facades\Log;

/**
 * HermesReplayService
 *
 * Sprint 4.7: Async Queue + Event Replay + Workspace Execution Engine
 *
 * Provides:
 *  1. Replay a failed HermesEventLog through the full handler chain
 *  2. Retry a specific failed handler for a given event
 *  3. Dispatch a replay to the async queue (non-blocking)
 *  4. Pause / resume / abort a workforce chain
 *  5. Chain state summary
 *
 * Architecture: Hermes remains the Single Source of Truth. ReplayService is a
 * read/re-dispatch layer — it never modifies the event log, it creates new ones.
 */
class HermesReplayService
{
    /**
     * Factory map for reconstructing events by event name.
     * Maps event_name → callable(HermesEventLog): HermesEventContract
     * Each factory receives the log record and must return a properly constructed event.
     *
     * H-06 fix: workforce events require multi-parameter constructors, not just payload.
     *
     * @var array<string, callable(HermesEventLog): HermesEventContract>
     */
    private const EVENT_FACTORIES = [
        'portfolio.created' => [self::class, 'reconstructPortfolioCreated'],
        'workforce.workspace.created' => [self::class, 'reconstructWorkspaceCreated'],
        'workforce.photo_analysis.completed' => [self::class, 'reconstructPhotoAnalysisCompleted'],
        'workforce.description.completed' => [self::class, 'reconstructDescriptionCompleted'],
        'workforce.property_score.calculated' => [self::class, 'reconstructPropertyScoreCalculated'],
        'workforce.publishing.decision_ready' => [self::class, 'reconstructPublishingDecisionReady'],
    ];

    public function __construct(
        private readonly HermesService $hermesService,
        private readonly HermesRegistry $registry,
    ) {}

    // ─── Core Replay ─────────────────────────────────────────────────────────

    /**
     * Replay a single failed HermesEventLog.
     * Creates a NEW HermesEventLog with the same payload and dispatches it.
     *
     * @return HermesEventLog The new event log created for this replay
     */
    public function replayEvent(int $hermesEventLogId): HermesEventLog
    {
        $original = HermesEventLog::findOrFail($hermesEventLogId);

        Log::info('[HermesReplay] Replaying event', [
            'original_log_id' => $hermesEventLogId,
            'event' => $original->event_name,
        ]);

        // Reconstruct the event from the original payload
        $event = $this->reconstructEvent($original);

        // Receive it fresh — this creates a new log and re-dispatches
        return $this->hermesService->receive($event);
    }

    /**
     * Replay via async queue (non-blocking).
     */
    public function replayEventAsync(int $hermesEventLogId): void
    {
        $original = HermesEventLog::findOrFail($hermesEventLogId);
        $event    = $this->reconstructEvent($original);

        $handlers = $this->registry->getHandlers($original->event_name);

        foreach ($handlers as $handler) {
            if ($handler->isAsync()) {
                AsyncHandlerDispatchJob::dispatch($handler, $event, $original->id)
                    ->onQueue('hermes');
            }
        }

        Log::info('[HermesReplay] Async replay dispatched', [
            'original_log_id' => $hermesEventLogId,
            'handler_count'  => count($handlers),
        ]);
    }

    // ─── Single Handler Retry ────────────────────────────────────────────────

    /**
     * Retry a specific failed handler for an event.
     * Finds the latest WorkforceExecutionLog entry for this handler and re-runs it.
     *
     * @param int $hermesEventLogId
     * @param string $handlerClass Fully-qualified class name
     * @return array{log: WorkforceExecutionLog, result: array}
     */
    public function retryHandler(int $hermesEventLogId, string $handlerClass): array
    {
        $hermesLog = HermesEventLog::findOrFail($hermesEventLogId);

        // Get the latest execution record for this handler
        $execLog = WorkforceExecutionLog::query()
            ->where('hermes_event_log_id', $hermesEventLogId)
            ->where('agent_class', $handlerClass)
            ->orderBy('id', 'desc')
            ->first();

        if (!$execLog) {
            throw new \InvalidArgumentException(
                "No execution log found for handler {$handlerClass} on event {$hermesEventLogId}"
            );
        }

        Log::info('[HermesReplay] Retrying handler', [
            'hermes_log_id' => $hermesEventLogId,
            'handler'      => $handlerClass,
            'exec_log_id'  => $execLog->id,
            'attempt'      => $execLog->status === WorkforceExecutionLog::STATUS_FAILED ? 'retry_1' : 'force',
        ]);

        // Reset to pending
        $execLog->update(['status' => WorkforceExecutionLog::STATUS_PENDING]);

        // Rebuild event
        $event = $this->reconstructEvent($hermesLog);

        // Get handler instance from registry
        $handlers = $this->registry->getHandlers($hermesLog->event_name);
        $handler  = collect($handlers)->first(fn($h) => get_class($h) === $handlerClass);

        if (!$handler) {
            throw new \InvalidArgumentException("Handler {$handlerClass} not registered for event");
        }

        // Run synchronously
        $result = $handler->handle($event);

        $execLog->markCompleted($result);

        return ['log' => $execLog, 'result' => $result];
    }

    /**
     * Retry handler via async queue.
     */
    public function retryHandlerAsync(int $hermesEventLogId, string $handlerClass): void
    {
        $hermesLog = $this->validateHandlerExists($hermesEventLogId, $handlerClass);

        $event    = $this->reconstructEvent($hermesLog);
        $handlers = $this->registry->getHandlers($hermesLog->event_name);
        $handler  = collect($handlers)->first(fn($h) => get_class($h) === $handlerClass);

        AsyncHandlerDispatchJob::dispatch($handler, $event, $hermesEventLogId)
            ->onQueue('hermes');

        Log::info('[HermesReplay] Async handler retry dispatched', [
            'hermes_log_id' => $hermesEventLogId,
            'handler'      => $handlerClass,
        ]);
    }

    // ─── Chain Operations ────────────────────────────────────────────────────

    /**
     * Pause a workforce chain (mark all pending/running steps as skipped).
     */
    public function pauseChain(string $chainId): int
    {
        $count = WorkforceExecutionLog::query()
            ->where('chain_id', $chainId)
            ->whereIn('status', [
                WorkforceExecutionLog::STATUS_PENDING,
                WorkforceExecutionLog::STATUS_RUNNING,
            ])
            ->update([
                'status'       => WorkforceExecutionLog::STATUS_SKIPPED,
                'error_message' => 'Paused by operator',
                'completed_at' => now(),
            ]);

        Log::info('[HermesReplay] Chain paused', [
            'chain_id'      => $chainId,
            'steps_skipped' => $count,
        ]);

        return $count;
    }

    /**
     * Resume a paused chain by re-dispatching all skipped steps in order.
     * Returns the number of steps re-dispatched.
     */
    public function resumeChain(string $chainId): int
    {
        $skipped = WorkforceExecutionLog::query()
            ->where('chain_id', $chainId)
            ->where('status', WorkforceExecutionLog::STATUS_SKIPPED)
            ->orderBy('event_chain_step')
            ->get();

        if ($skipped->isEmpty()) {
            Log::info('[HermesReplay] No skipped steps to resume', ['chain_id' => $chainId]);
            return 0;
        }

        $count = 0;
        foreach ($skipped as $step) {
            if (!$step->hermes_event_log_id) {
                continue;
            }

            $hermesLog = HermesEventLog::find($step->hermes_event_log_id);
            if (!$hermesLog) {
                continue;
            }

            $event    = $this->reconstructEvent($hermesLog);
            $handlers = $this->registry->getHandlers($hermesLog->event_name);
            $handler  = collect($handlers)
                ->first(fn($h) => get_class($h) === $step->agent_class);

            if (!$handler) {
                continue;
            }

            // Reset execution log
            $step->update([
                'status'       => WorkforceExecutionLog::STATUS_PENDING,
                'error_message' => null,
                'completed_at' => null,
            ]);

            AsyncHandlerDispatchJob::dispatch($handler, $event, $hermesLog->id)
                ->onQueue('hermes');

            $count++;
        }

        Log::info('[HermesReplay] Chain resumed', [
            'chain_id'          => $chainId,
            'steps_dispatched' => $count,
        ]);

        return $count;
    }

    /**
     * Abort a chain (mark all steps as failed/skipped with a reason).
     */
    public function abortChain(string $chainId, string $reason = 'Aborted by operator'): int
    {
        $count = WorkforceExecutionLog::query()
            ->where('chain_id', $chainId)
            ->whereIn('status', [
                WorkforceExecutionLog::STATUS_PENDING,
                WorkforceExecutionLog::STATUS_RUNNING,
            ])
            ->update([
                'status'       => WorkforceExecutionLog::STATUS_FAILED,
                'error_message' => $reason,
                'completed_at'  => now(),
            ]);

        Log::info('[HermesReplay] Chain aborted', [
            'chain_id'      => $chainId,
            'reason'        => $reason,
            'steps_failed' => $count,
        ]);

        return $count;
    }

    // ─── Chain Status ───────────────────────────────────────────────────────

    /**
     * Full chain summary for a workspace.
     */
    public function chainStatus(int $ilanId): array
    {
        $logs = WorkforceExecutionLog::query()
            ->where('ilan_id', $ilanId)
            ->orderBy('started_at', 'desc')
            ->get();

        $byChain = $logs->groupBy('chain_id');

        $chains = [];
        foreach ($byChain as $chainId => $executions) {
            $chains[] = [
                'chain_id'    => $chainId,
                'ilan_id'     => $ilanId,
                'total_steps' => $executions->count(),
                'completed'   => $executions->where('status', WorkforceExecutionLog::STATUS_COMPLETED)->count(),
                'failed'     => $executions->where('status', WorkforceExecutionLog::STATUS_FAILED)->count(),
                'skipped'    => $executions->where('status', WorkforceExecutionLog::STATUS_SKIPPED)->count(),
                'pending'    => $executions->where('status', WorkforceExecutionLog::STATUS_PENDING)->count(),
                'running'    => $executions->where('status', WorkforceExecutionLog::STATUS_RUNNING)->count(),
                'is_complete' => $executions->where('status', WorkforceExecutionLog::STATUS_COMPLETED)->count() >= 4,
                'is_abortable' => $executions->whereIn('status', [
                    WorkforceExecutionLog::STATUS_PENDING,
                    WorkforceExecutionLog::STATUS_RUNNING,
                ])->isNotEmpty(),
                'agents'     => $executions->map(fn($e) => [
                    'agent'  => $e->agent_name,
                    'status' => $e->status,
                    'error'  => $e->error_message,
                    'step'   => $e->event_chain_step,
                ])->values()->all(),
            ];
        }

        return [
            'ilan_id' => $ilanId,
            'chain_count' => $byChain->count(),
            'chains'  => $chains,
        ];
    }

    /**
     * Failed events with execution breakdown.
     */
    public function failedEvents(int $limit = 20): array
    {
        return HermesEventLog::query()
            ->where('status', HermesEventLog::STATUS_FAILED)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'event'       => $log->event_name,
                'tenant_id'    => $log->tenant_id,
                'error'       => $log->error_message,
                'occurred_at'  => $log->occurred_at?->toIso8601String(),
                'retry_url'    => "/admin/hermes/replay/{$log->id}",
            ])
            ->toArray();
    }

    // ─── Private Helpers ────────────────────────────────────────────────────

    /**
     * Reconstruct a HermesEventContract from a HermesEventLog record.
     *
     * H-06 fix: uses factory methods for workforce events that require
     * multi-parameter constructors (workspace, result array, metadata).
     * Falls back to generic payload-based reconstruction for unknown event types.
     */
    private function reconstructEvent(HermesEventLog $log): HermesEventContract
    {
        $eventClass = $log->event_class;

        if (!class_exists($eventClass)) {
            throw new \RuntimeException("Event class {$eventClass} not found");
        }

        // Use factory if registered
        $factory = self::EVENT_FACTORIES[$log->event_name] ?? null;
        if ($factory !== null) {
            return ($factory[0])::$factory[1]($log);
        }

        // Generic fallback for unknown events (passes payload as single array argument)
        // This may fail at runtime if the event constructor expects different parameters.
        // Register the event in EVENT_FACTORIES to fix.
        $event = new $eventClass($log->payload);

        return $event;
    }

    // ─── Event Factory Methods ────────────────────────────────────────────

    /**
     * Factory: PortfolioCreated
     */
    private static function reconstructPortfolioCreated(HermesEventLog $log): \App\Contracts\Hermes\HermesEventContract
    {
        $payload = $log->payload;
        $ilan = \App\Models\Ilan::find($payload['ilan_id'] ?? null);
        if (!$ilan) {
            throw new \RuntimeException("Ilan not found for replay: {$payload['ilan_id']}");
        }
        return new $log->event_class($ilan, $payload['metadata'] ?? []);
    }

    /**
     * Factory: PropertyWorkspaceCreated
     */
    private static function reconstructWorkspaceCreated(HermesEventLog $log): \App\Contracts\Hermes\HermesEventContract
    {
        $payload = $log->payload;
        $workspace = \App\Models\PortfolioDriveWorkspace::find($payload['workspace_id'] ?? null);
        if (!$workspace) {
            throw new \RuntimeException("Workspace not found for replay: {$payload['workspace_id']}");
        }
        return new $log->event_class($workspace, $payload['metadata'] ?? []);
    }

    /**
     * Factory: PhotoAnalysisCompleted
     */
    private static function reconstructPhotoAnalysisCompleted(HermesEventLog $log): \App\Contracts\Hermes\HermesEventContract
    {
        $payload = $log->payload;
        $workspace = \App\Models\PortfolioDriveWorkspace::find($payload['workspace_id'] ?? null);
        if (!$workspace) {
            throw new \RuntimeException("Workspace not found for replay: {$payload['workspace_id']}");
        }
        $analysisResult = [
            'quality_score' => $payload['quality_score'] ?? null,
            'recommendations' => $payload['recommendations'] ?? [],
            'suggested_photo_count' => $payload['suggested_photo_count'] ?? null,
        ];
        return new $log->event_class($workspace, $analysisResult, $payload['metadata'] ?? []);
    }

    /**
     * Factory: DescriptionCompleted
     */
    private static function reconstructDescriptionCompleted(HermesEventLog $log): \App\Contracts\Hermes\HermesEventContract
    {
        $payload = $log->payload;
        $workspace = \App\Models\PortfolioDriveWorkspace::find($payload['workspace_id'] ?? null);
        if (!$workspace) {
            throw new \RuntimeException("Workspace not found for replay: {$payload['workspace_id']}");
        }
        $analysisResult = [
            'title_score' => $payload['title_score'] ?? null,
            'improved_title' => $payload['improved_title'] ?? null,
            'keywords' => $payload['keywords'] ?? [],
            'suggestions' => $payload['suggestions'] ?? [],
        ];
        return new $log->event_class($workspace, $analysisResult, $payload['metadata'] ?? []);
    }

    /**
     * Factory: PropertyScoreCalculated
     */
    private static function reconstructPropertyScoreCalculated(HermesEventLog $log): \App\Contracts\Hermes\HermesEventContract
    {
        $payload = $log->payload;
        $workspace = \App\Models\PortfolioDriveWorkspace::find($payload['workspace_id'] ?? null);
        if (!$workspace) {
            throw new \RuntimeException("Workspace not found for replay: {$payload['workspace_id']}");
        }
        $scoreResult = [
            'overall_score' => $payload['overall_score'] ?? null,
            'component_scores' => $payload['component_scores'] ?? [],
            'market_positioning' => $payload['market_positioning'] ?? null,
            'quality_tier' => $payload['quality_tier'] ?? null,
            'recommendations' => $payload['recommendations'] ?? [],
        ];
        return new $log->event_class($workspace, $scoreResult, $payload['metadata'] ?? []);
    }

    /**
     * Factory: PublishingDecisionReady
     */
    private static function reconstructPublishingDecisionReady(HermesEventLog $log): \App\Contracts\Hermes\HermesEventContract
    {
        $payload = $log->payload;
        $workspace = \App\Models\PortfolioDriveWorkspace::find($payload['workspace_id'] ?? null);
        if (!$workspace) {
            throw new \RuntimeException("Workspace not found for replay: {$payload['workspace_id']}");
        }
        $decision = [
            'decision' => $payload['decision'] ?? null,
            'property_score' => $payload['property_score'] ?? null,
            'confidence' => $payload['confidence'] ?? null,
            'publish_targets' => $payload['publish_targets'] ?? [],
            'blocking_issues' => $payload['blocking_issues'] ?? [],
            'message' => $payload['message'] ?? null,
        ];
        return new $log->event_class($workspace, $decision, $payload['metadata'] ?? []);
    }

    /**
     * Validate that a handler is registered for an event.
     */
    private function validateHandlerExists(int $logId, string $handlerClass): HermesEventLog
    {
        $log      = HermesEventLog::findOrFail($logId);
        $handlers = $this->registry->getHandlers($log->event_name);
        $exists   = collect($handlers)->contains(fn($h) => get_class($h) === $handlerClass);

        if (!$exists) {
            throw new \InvalidArgumentException(
                "Handler {$handlerClass} is not registered for event {$log->event_name}"
            );
        }

        return $log;
    }
}

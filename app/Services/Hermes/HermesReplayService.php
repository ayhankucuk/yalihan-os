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
     */
    private function reconstructEvent(HermesEventLog $log): HermesEventContract
    {
        $eventClass = $log->event_class;

        if (!class_exists($eventClass)) {
            throw new \RuntimeException("Event class {$eventClass} not found");
        }

        $event = new $eventClass($log->payload);

        return $event;
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

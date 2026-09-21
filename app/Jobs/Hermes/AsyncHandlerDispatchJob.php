<?php

namespace App\Jobs\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Models\Hermes\WorkforceExecutionLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AsyncHandlerDispatchJob
 *
 * Sprint 4.7: Async Queue + Event Replay + Workspace Execution Engine
 *
 * Wraps a single Hermes event handler invocation as an async queue job.
 * This decouples heavy agents (Drive, Photo, Description) from the synchronous
 * event chain, preventing one slow agent from blocking the entire pipeline.
 *
 * IMPORTANT: The job stores only the handler FQCN string, NOT the resolved
 * handler/service instance. The handler is resolved fresh from the Laravel
 * container at execution time. This prevents serializing the entire service
 * dependency graph into the queue payload (see HERMES-QUEUE-OBJECT-GRAPH-
 * SERIALIZATION-EXPLOSION).
 *
 * STATE-MACHINE CONTRACT (TASK: HERMES_ASYNC_EXECUTION_STATE_REMEDIATION_02):
 *   Canonical lifecycle for the WorkforceExecutionLog record attached to this
 *   job:
 *
 *     HermesDispatcher::dispatchAsync() → creates PENDING record + dispatches
 *     AsyncHandlerDispatchJob::handle() → atomic PENDING → RUNNING claim
 *                                       → executes handler
 *                                       → RUNNING → COMPLETED (same record)
 *     AsyncHandlerDispatchJob::failed() → PENDING/RUNNING → FAILED
 *
 *   The atomic claim (WHERE id = ? AND status = 'pending' UPDATE status =
 *   'running') guarantees only one worker wins the transition under
 *   concurrent contention. If the claim affects 0 rows, the record has
 *   already been picked up by another worker or reached a terminal state,
 *   and this job exits without invoking the handler again.
 *
 * QUEUE-LEVEL UNIQUENESS:
 *   Implements ShouldBeUnique so the queue driver rejects a duplicate
 *   dispatch of the same logical execution (same hermesEventLogId +
 *   handlerClass + event) while an earlier instance is still in flight.
 *   Complements but does not replace the DB-side atomic claim above.
 *
 * EXTERNAL SIDE-EFFECT NON-ATOMICITY (SEPARATE FINDING):
 *   External side effects (e.g., Telegram HTTP calls inside handlers) are
 *   NOT enrolled in the same transaction as the RUNNING → COMPLETED status
 *   update. A worker crash between a successful external side effect and
 *   the completion write may cause a retry to re-invoke the handler. This
 *   remediation does NOT close HERMES-ASYNC-EXTERNAL-SIDE-EFFECT-NON-
 *   ATOMIC-WINDOW.
 */
class AsyncHandlerDispatchJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 1;
    public int $timeout = 300; // 5 minutes for heavy AI agents
    public array $backoff = [10, 60, 300]; // 10s, 1m, 5m

    /**
     * Uniqueness lock lifetime (seconds).
     *
     * Longer than any single handler run (timeout = 300s) so the DB-driven
     * lock cannot be released mid-run, but bounded so a truly stuck lock
     * eventually expires and does not permanently block re-dispatch.
     */
    public int $uniqueFor = 1800;

    public function __construct(
        public readonly string $handlerClass,
        public readonly HermesEventContract $event,
        public readonly ?int $hermesEventLogId = null,
    ) {}

    /**
     * Queue name for Hermes async handlers.
     */
    public function queue(): string
    {
        return 'hermes';
    }

    /**
     * Execute the job.
     *
     * Lifecycle:
     *   1. Resolve handler fresh from container (no serialized service graph).
     *   2. If tracked (hermesEventLogId set), locate the canonical
     *      WorkforceExecutionLog and branch on its status:
     *        - COMPLETED / SKIPPED → idempotent no-op
     *        - FAILED              → terminal, do not re-run
     *        - RUNNING             → another worker owns the claim, skip
     *        - PENDING             → atomic claim then execute
     *   3. If untracked (hermesEventLogId null), execute without lifecycle
     *      transitions. This path exists for legacy/test dispatch only;
     *      production dispatch always passes a log id.
     */
    public function handle(): void
    {
        $handlerClass = $this->handlerClass;
        $eventName    = $this->event->eventName();

        // Resolve the handler fresh from the container
        $handler = $this->resolveHandler($handlerClass);

        // Untracked dispatch — execute without lifecycle transitions.
        if ($this->hermesEventLogId === null) {
            $this->runHandlerUntracked($handler, $handlerClass, $eventName);
            return;
        }

        $execLog = WorkforceExecutionLog::query()
            ->where('hermes_event_log_id', $this->hermesEventLogId)
            ->where('agent_class', $handlerClass)
            ->orderBy('id')
            ->first();

        // No exec log record found — dispatcher path was skipped (manual
        // dispatch, replay path). Execute the handler without tracking to
        // preserve backward-compatible behavior, but log the anomaly.
        if ($execLog === null) {
            Log::warning('[AsyncHandlerDispatchJob] No WorkforceExecutionLog record found — running handler without tracking', [
                'log_id'  => $this->hermesEventLogId,
                'handler' => $handlerClass,
                'event'   => $eventName,
            ]);
            $this->runHandlerUntracked($handler, $handlerClass, $eventName);
            return;
        }

        switch ($execLog->status) {
            case WorkforceExecutionLog::STATUS_COMPLETED:
            case WorkforceExecutionLog::STATUS_SKIPPED:
                Log::info('[AsyncHandlerDispatchJob] Skipping — handler already succeeded', [
                    'log_id'  => $this->hermesEventLogId,
                    'handler' => $handlerClass,
                    'event'   => $eventName,
                    'status'  => $execLog->status,
                ]);
                return;

            case WorkforceExecutionLog::STATUS_FAILED:
                Log::info('[AsyncHandlerDispatchJob] Skipping — handler already failed terminally', [
                    'log_id'  => $this->hermesEventLogId,
                    'handler' => $handlerClass,
                    'event'   => $eventName,
                ]);
                return;

            case WorkforceExecutionLog::STATUS_RUNNING:
                // Another worker is already running the handler for this
                // execution record. Do not invoke the handler concurrently.
                Log::warning('[AsyncHandlerDispatchJob] Skipping — execution record already RUNNING under another worker', [
                    'log_id'  => $this->hermesEventLogId,
                    'handler' => $handlerClass,
                    'event'   => $eventName,
                ]);
                return;

            case WorkforceExecutionLog::STATUS_PENDING:
                // Fall through to atomic claim + execution.
                break;

            default:
                Log::warning('[AsyncHandlerDispatchJob] Unknown execution status — treating as no-op', [
                    'log_id'  => $this->hermesEventLogId,
                    'handler' => $handlerClass,
                    'event'   => $eventName,
                    'status'  => $execLog->status,
                ]);
                return;
        }

        // Atomic PENDING → RUNNING claim. Exactly one worker wins.
        $claimed = $execLog->claimForRun();
        if ($claimed !== 1) {
            Log::warning('[AsyncHandlerDispatchJob] Lost claim — another worker transitioned the record first', [
                'log_id'  => $this->hermesEventLogId,
                'handler' => $handlerClass,
                'event'   => $eventName,
            ]);
            return;
        }

        Log::info('[AsyncHandlerDispatchJob] Processing', [
            'handler' => $handlerClass,
            'event'   => $eventName,
            'log_id'  => $this->hermesEventLogId,
            'attempt' => $this->attempts(),
        ]);

        $startTime = microtime(true);

        // Handler exceptions propagate to Laravel's retry machinery.
        // failed() finalizes the terminal FAILED state after tries are exhausted.
        $result   = $handler->handle($this->event);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('[AsyncHandlerDispatchJob] Completed', [
            'handler'     => $handlerClass,
            'event'       => $eventName,
            'duration_ms' => $duration,
        ]);

        // Mark the same claimed record COMPLETED. Second lookup avoided —
        // $execLog holds the canonical row we already own.
        $execLog->markCompleted(is_array($result) ? $result : ['result' => $result]);
    }

    /**
     * Handle job failure (after all retries exhausted).
     */
    public function failed(?\Throwable $exception): void
    {
        $handlerClass = $this->handlerClass;
        $eventName    = $this->event->eventName();

        Log::error('[AsyncHandlerDispatchJob] Permanently failed', [
            'handler' => $handlerClass,
            'event'   => $eventName,
            'log_id'  => $this->hermesEventLogId,
            'error'   => $exception?->getMessage(),
        ]);

        if ($this->hermesEventLogId === null) {
            return;
        }

        $execLog = WorkforceExecutionLog::query()
            ->where('hermes_event_log_id', $this->hermesEventLogId)
            ->where('agent_class', $handlerClass)
            ->whereIn('status', [
                WorkforceExecutionLog::STATUS_PENDING,
                WorkforceExecutionLog::STATUS_RUNNING,
            ])
            ->orderBy('id')
            ->first();

        if ($execLog !== null) {
            $execLog->markFailed($exception?->getMessage() ?? 'Unknown error');
        }
    }

    /**
     * Unique job identity used by ShouldBeUnique.
     *
     * Identity resolution:
     *   - Tracked dispatch (hermesEventLogId set): identity derives from
     *     (log id, handler class, event name). This is the production path.
     *   - Untracked dispatch (hermesEventLogId null): fall back to a stable
     *     fingerprint of the event payload + occurred-at timestamp so
     *     unrelated null-id dispatches do NOT collapse into a single global
     *     unique job.
     */
    public function uniqueId(): string
    {
        if ($this->hermesEventLogId !== null) {
            return sprintf(
                '%d-%s-%s',
                $this->hermesEventLogId,
                $this->handlerClass,
                $this->event->eventName()
            );
        }

        $fingerprint = hash('sha256', serialize([
            'payload'     => $this->event->toPayload(),
            'occurred_at' => $this->event->occurredAt()->format('Y-m-d\TH:i:s.uP'),
        ]));

        return sprintf(
            'no-log-%s-%s-%s',
            $this->handlerClass,
            $this->event->eventName(),
            substr($fingerprint, 0, 32)
        );
    }

    /**
     * Execute the handler without lifecycle tracking (fallback path).
     */
    private function runHandlerUntracked(HermesHandlerContract $handler, string $handlerClass, string $eventName): void
    {
        Log::info('[AsyncHandlerDispatchJob] Processing', [
            'handler' => $handlerClass,
            'event'   => $eventName,
            'log_id'  => $this->hermesEventLogId,
            'attempt' => $this->attempts(),
        ]);

        $startTime = microtime(true);

        $handler->handle($this->event);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('[AsyncHandlerDispatchJob] Completed', [
            'handler'     => $handlerClass,
            'event'       => $eventName,
            'duration_ms' => $duration,
        ]);
    }

    /**
     * Resolve a handler from the Laravel container and verify it implements HermesHandlerContract.
     *
     * @throws \RuntimeException if the resolved object does not implement HermesHandlerContract
     */
    private function resolveHandler(string $handlerClass): HermesHandlerContract
    {
        if (!class_exists($handlerClass)) {
            throw new \RuntimeException(
                "[AsyncHandlerDispatchJob] Handler class does not exist: {$handlerClass}"
            );
        }

        $handler = app($handlerClass);

        if (!$handler instanceof HermesHandlerContract) {
            throw new \RuntimeException(
                "[AsyncHandlerDispatchJob] Resolved handler does not implement HermesHandlerContract: {$handlerClass}"
            );
        }

        return $handler;
    }
}

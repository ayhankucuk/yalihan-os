<?php

namespace App\Jobs\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Services\Hermes\HermesRegistry;
use Illuminate\Bus\Queueable;
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
 * Usage:
 *   AsyncHandlerDispatchJob::dispatch($handler, $event)->onQueue('hermes');
 *
 * The job is idempotent via hermes_event_log_id tracking — if the event
 * was already processed, the job is a no-op.
 */
class AsyncHandlerDispatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 1;
    public int $timeout = 300; // 5 minutes for heavy AI agents
    public array $backoff = [10, 60, 300]; // 10s, 1m, 5m

    public function __construct(
        public readonly HermesHandlerContract $handler,
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
     */
    public function handle(HermesRegistry $registry): void
    {
        $handlerClass = get_class($this->handler);
        $eventName   = $this->event->eventName();

        // Skip if this handler was already processed for this event
        if ($this->hermesEventLogId) {
            $alreadyDone = \App\Models\Hermes\WorkforceExecutionLog::query()
                ->where('hermes_event_log_id', $this->hermesEventLogId)
                ->where('agent_class', $handlerClass)
                ->whereIn('status', [
                    \App\Models\Hermes\WorkforceExecutionLog::STATUS_COMPLETED,
                    \App\Models\Hermes\WorkforceExecutionLog::STATUS_SKIPPED,
                ])
                ->exists();

            if ($alreadyDone) {
                Log::info('[AsyncHandlerDispatchJob] Skipping — handler already succeeded', [
                    'log_id'   => $this->hermesEventLogId,
                    'handler'   => $handlerClass,
                    'event'     => $eventName,
                ]);
                return;
            }
        }

        Log::info('[AsyncHandlerDispatchJob] Processing', [
            'handler' => $handlerClass,
            'event'   => $eventName,
            'log_id'  => $this->hermesEventLogId,
            'attempt'  => $this->attempts(),
        ]);

        $startTime = microtime(true);

        $result = $this->handler->handle($this->event);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('[AsyncHandlerDispatchJob] Completed', [
            'handler'     => $handlerClass,
            'event'       => $eventName,
            'duration_ms' => $duration,
        ]);

        // Mark WorkforceExecutionLog as completed if we have a log ID
        if ($this->hermesEventLogId) {
            $execLog = \App\Models\Hermes\WorkforceExecutionLog::query()
                ->where('hermes_event_log_id', $this->hermesEventLogId)
                ->where('agent_class', $handlerClass)
                ->where('status', \App\Models\Hermes\WorkforceExecutionLog::STATUS_RUNNING)
                ->first();

            if ($execLog) {
                $execLog->markCompleted($result);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $handlerClass = get_class($this->handler);
        $eventName   = $this->event->eventName();

        Log::error('[AsyncHandlerDispatchJob] Permanently failed', [
            'handler' => $handlerClass,
            'event'   => $eventName,
            'log_id'  => $this->hermesEventLogId,
            'error'   => $exception?->getMessage(),
        ]);

        if ($this->hermesEventLogId) {
            $execLog = \App\Models\Hermes\WorkforceExecutionLog::query()
                ->where('hermes_event_log_id', $this->hermesEventLogId)
                ->where('agent_class', $handlerClass)
                ->whereIn('status', [
                    \App\Models\Hermes\WorkforceExecutionLog::STATUS_PENDING,
                    \App\Models\Hermes\WorkforceExecutionLog::STATUS_RUNNING,
                ])
                ->first();

            if ($execLog) {
                $execLog->markFailed($exception?->getMessage() ?? 'Unknown error');
            }
        }
    }

    /**
     * Unique job ID to prevent duplicate dispatch.
     */
    public function uniqueId(): string
    {
        return sprintf(
            '%s-%s-%s',
            $this->hermesEventLogId ?? 'no-log',
            get_class($this->handler),
            $this->event->eventName()
        );
    }
}

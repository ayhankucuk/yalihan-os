<?php

namespace App\Services\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Models\Hermes\HermesEventLog;
use Illuminate\Support\Facades\Log;

/**
 * HermesService
 *
 * Sprint 4.7: Async Queue + Event Replay + Workspace Execution Engine
 *
 * Main entry point for Hermes event bus.
 * Records events, dispatches to handlers (sync + async), supports replay.
 */
class HermesService
{
    public function __construct(
        private readonly HermesDispatcher $dispatcher,
    ) {}

    /**
     * Receive and process an event through Hermes.
     *
     * @return HermesEventLog
     */
    public function receive(HermesEventContract $event): HermesEventLog
    {
        $eventName = $event->eventName();

        // 1. Record the event
        $log = $this->recordEvent($event);

        Log::info('[Hermes] Event received', [
            'event'     => $eventName,
            'log_id'    => $log->id,
            'tenant_id' => $event->tenantId(),
        ]);

        // 2. Dispatch to handlers — pass log ID so async jobs track their WorkforceExecutionLog
        $this->dispatchToHandlers($log, $event);

        return $log;
    }

    /**
     * Record event to HermesEventLog.
     */
    private function recordEvent(HermesEventContract $event): HermesEventLog
    {
        return HermesEventLog::create([
            'event_name'  => $event->eventName(),
            'event_class' => get_class($event),
            'payload'     => $event->toPayload(),
            'tenant_id'  => $event->tenantId(),
            'occurred_at' => $event->occurredAt(),
            'status'     => HermesEventLog::STATUS_RECEIVED,
        ]);
    }

    /**
     * Dispatch event to registered handlers.
     */
    private function dispatchToHandlers(HermesEventLog $log, HermesEventContract $event): void
    {
        $log->markProcessing();

        try {
            $results = $this->dispatcher->dispatch($event, $log->id);
            $log->markProcessed($results);
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Sprint 4.7: Expose ReplayService for external replay/pause/resume operations.
     */
    public function replayService(): HermesReplayService
    {
        return app(HermesReplayService::class);
    }

    /**
     * Check if an event has registered handlers.
     */
    public function hasHandlers(string $eventName): bool
    {
        return app(HermesRegistry::class)->hasHandlers($eventName);
    }
}

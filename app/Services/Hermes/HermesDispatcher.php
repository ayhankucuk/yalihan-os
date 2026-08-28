<?php

namespace App\Services\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Jobs\Hermes\AsyncHandlerDispatchJob;
use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use Illuminate\Support\Facades\Log;

/**
 * HermesDispatcher
 *
 * Sprint 4.7: Async Queue + Event Replay + Workspace Execution Engine
 *
 * Dispatches events to their registered handlers.
 * Supports both synchronous and asynchronous (queue) handlers.
 */
class HermesDispatcher
{
    public function __construct(
        private readonly HermesRegistry $registry,
    ) {}

    /**
     * Dispatch an event to all registered handlers.
     *
     * Async handlers are dispatched to the queue.
     * Sync handlers are invoked in-process.
     *
     * @return array<string, array> Handler results keyed by handler class
     */
    public function dispatch(HermesEventContract $event, ?int $hermesEventLogId = null): array
    {
        $eventName = $event->eventName();
        $handlers  = $this->registry->getHandlers($eventName);
        $results  = [];

        if (empty($handlers)) {
            Log::debug('[HermesDispatcher] No handlers registered for event', [
                'event' => $eventName,
            ]);
            return $results;
        }

        Log::info('[HermesDispatcher] Dispatching event', [
            'event'         => $eventName,
            'handler_count' => count($handlers),
            'tenant_id'     => $event->tenantId(),
        ]);

        foreach ($handlers as $handler) {
            if ($handler->isAsync()) {
                $this->dispatchAsync($handler, $event, $hermesEventLogId);
                $results[get_class($handler)] = [
                    'async'  => true,
                    'queued' => true,
                    'result' => null,
                ];
            } else {
                $results[get_class($handler)] = $this->invokeHandler($handler, $event, $hermesEventLogId);
            }
        }

        return $results;
    }

    /**
     * Dispatch a handler asynchronously via queue.
     */
    public function dispatchAsync(
        HermesHandlerContract $handler,
        HermesEventContract $event,
        ?int $hermesEventLogId = null,
    ): void {
        $handlerClass = get_class($handler);

        // Record WorkforceExecutionLog for audit trail
        $execLog = WorkforceExecutionLog::create([
            'hermes_event_log_id' => $hermesEventLogId,
            'ilan_id'             => method_exists($event, 'ilanId') ? $event->ilanId() : null,
            'tenant_id'           => $event->tenantId(),
            'chain_id'           => method_exists($event, 'chainId') ? $event->chainId() : 0,
            'agent_name'          => $this->inferAgentName($handlerClass),
            'agent_class'         => $handlerClass,
            'event_received'      => $event->eventName(),
            'event_chain_step'    => $this->inferChainStep($event, $handler),
            'input_payload'       => $event->toPayload(),
            'output_payload'      => [],
            'status'             => WorkforceExecutionLog::STATUS_PENDING,
            'started_at'          => now(),
        ]);

        // Track exec log ID for the job to update
        AsyncHandlerDispatchJob::dispatch($handler, $event, $hermesEventLogId)
            ->onQueue('hermes');

        Log::info('[HermesDispatcher] Async handler queued', [
            'handler'   => $handlerClass,
            'event'     => $event->eventName(),
            'exec_log_id' => $execLog->id,
        ]);
    }

    // ─── Private ───────────────────────────────────────────────────────────

    /**
     * Invoke a single synchronous handler.
     *
     * Note: WorkforceExecutionLog is created by the agent's handle() method.
     * The dispatcher only handles timing, error catching, and result wrapping.
     */
    private function invokeHandler(
        HermesHandlerContract $handler,
        HermesEventContract $event,
        ?int $hermesEventLogId,
    ): array {
        $handlerClass = get_class($handler);
        $startTime   = microtime(true);

        try {
            $result   = $handler->handle($event);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('[HermesDispatcher] Handler executed', [
                'handler'     => $handlerClass,
                'event'       => $event->eventName(),
                'duration_ms' => $duration,
            ]);

            return [
                'success'     => true,
                'result'      => $result,
                'duration_ms'  => $duration,
            ];
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('[HermesDispatcher] Handler failed', [
                'handler'     => $handlerClass,
                'event'       => $event->eventName(),
                'error'       => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return [
                'success'     => false,
                'error'       => $e->getMessage(),
                'duration_ms'  => $duration,
            ];
        }
    }

    /**
     * Create a WorkforceExecutionLog record for a handler invocation.
     */
    private function recordExecutionLog(
        HermesHandlerContract $handler,
        HermesEventContract $event,
        ?int $hermesEventLogId,
    ): WorkforceExecutionLog {
        return WorkforceExecutionLog::create([
            'hermes_event_log_id' => $hermesEventLogId,
            'ilan_id'            => method_exists($event, 'ilanId') ? $event->ilanId() : ($event->toPayload()['ilan_id'] ?? null),
            'tenant_id'          => $event->tenantId(),
            'chain_id'           => method_exists($event, 'chainId') ? $event->chainId() : ($event->toPayload()['chain_id'] ?? null),
            'agent_name'         => $this->inferAgentName(get_class($handler)),
            'agent_class'        => get_class($handler),
            'event_received'     => $event->eventName(),
            'event_chain_step'    => $this->inferChainStep($event, $handler),
            'input_payload'       => $event->toPayload(),
            'output_payload'      => [],
            'status'             => WorkforceExecutionLog::STATUS_PENDING,
            'started_at'          => now(),
        ]);
    }

    /**
     * Infer agent name from handler class name.
     * e.g. App\Services\Hermes\Handlers\Workforce\DriveAgent → drive_agent
     */
    private function inferAgentName(string $handlerClass): string
    {
        $short = class_basename($handlerClass);

        return match (true) {
            str_ends_with($short, 'Agent') => strtolower(preg_replace('/(?<!^)[A-Z]/', '_', $short)),
            default => strtolower($short),
        };
    }

    /**
     * Infer chain step from event + handler context.
     * Step is inferred from the event's position in the known workforce chain.
     */
    private function inferChainStep(HermesEventContract $event, HermesHandlerContract $handler): int
    {
        $eventName = $event->eventName();

        // Known workforce chain order
        $chainMap = [
            'portfolio.created'                        => 0,
            'workforce.workspace.created'              => 1,
            'workforce.photo_analysis.completed'       => 2,
            'workforce.description.completed'          => 3,
            'workforce.property_score.calculated'      => 4,
            'workforce.publishing.decision_ready'       => 5,
            'workforce.notification.sent'              => 6,
        ];

        return $chainMap[$eventName] ?? 99;
    }
}

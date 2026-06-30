<?php

namespace App\Services\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use Illuminate\Support\Facades\Log;

/**
 * HermesDispatcher
 *
 * Dispatches events to their registered handlers.
 * Handles both sync and async handlers.
 */
class HermesDispatcher
{
    public function __construct(
        private HermesRegistry $registry,
    ) {}

    /**
     * Dispatch an event to all registered handlers
     *
     * @return array<string, array> Handler results keyed by handler class
     */
    public function dispatch(HermesEventContract $event): array
    {
        $eventName = $event->eventName();
        $handlers = $this->registry->getHandlers($eventName);
        $results = [];

        if (empty($handlers)) {
            Log::debug("[HermesDispatcher] No handlers registered for event", [
                'event' => $eventName,
            ]);
            return $results;
        }

        Log::info("[HermesDispatcher] Dispatching event", [
            'event' => $eventName,
            'handler_count' => count($handlers),
            'tenant_id' => $event->tenantId(),
        ]);

        foreach ($handlers as $handler) {
            $results[get_class($handler)] = $this->invokeHandler($handler, $event);
        }

        return $results;
    }

    /**
     * Invoke a single handler
     *
     * @return array Result data
     */
    private function invokeHandler(HermesHandlerContract $handler, HermesEventContract $event): array
    {
        $handlerClass = get_class($handler);
        $startTime = microtime(true);

        try {
            $result = $handler->handle($event);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("[HermesDispatcher] Handler executed", [
                'handler' => $handlerClass,
                'event' => $event->eventName(),
                'duration_ms' => $duration,
            ]);

            return [
                'success' => true,
                'result' => $result,
                'duration_ms' => $duration,
            ];
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error("[HermesDispatcher] Handler failed", [
                'handler' => $handlerClass,
                'event' => $event->eventName(),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ];
        }
    }
}

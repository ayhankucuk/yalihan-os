<?php

namespace App\Services\Hermes;

use App\Services\Hermes\Contracts\HandlerInterface;
use App\Services\Hermes\Contracts\RetryPolicyInterface;
use App\Models\HandlerExecution;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Core dispatcher for Hermes event handlers.
 * Supports both synchronous and asynchronous execution modes.
 *
 * Sync mode (default): Handlers execute immediately in sequence
 * Async mode (configurable): Handlers are dispatched to queue
 */
class HermesDispatcher
{
    /** @var array<string, class-string<HandlerInterface>> */
    private array $handlers = [];

    private bool $asyncMode = false;

    public function __construct(
        private readonly HandlerExecutionService $executionService,
        private readonly RetryPolicyInterface $retryPolicy,
    ) {
        $this->asyncMode = config('hermes.async_enabled', false);
    }

    /**
     * Register a handler
     */
    public function register(string $eventName, string $handlerClass): void
    {
        $this->handlers[$eventName] = $handlerClass;
    }

    /**
     * Dispatch an event to registered handlers
     *
     * @param string $eventName
     * @param array $payload
     * @param string|null $eventId
     * @return array Results keyed by handler name
     */
    public function dispatch(string $eventName, array $payload, ?string $eventId = null): array
    {
        $results = [];
        $tenantId = $this->extractTenantId($payload);

        // Find handlers for this event
        $handlers = $this->resolveHandlers($eventName);

        foreach ($handlers as $handlerClass) {
            $handler = app($handlerClass);

            if (!$handler->isEnabled()) {
                Log::debug("Hermes: Handler {$handlerClass} is disabled, skipping");
                continue;
            }

            try {
                if ($this->asyncMode) {
                    $results[$handlerClass] = $this->dispatchAsync(
                        $handlerClass,
                        $eventName,
                        $payload,
                        $eventId,
                        $tenantId
                    );
                } else {
                    $results[$handlerClass] = $this->dispatchSync(
                        $handlerClass,
                        $eventName,
                        $payload,
                        $eventId,
                        $tenantId
                    );
                }
            } catch (\Throwable $e) {
                // Handler failure does not crash Hermes core
                Log::error("Hermes: Handler {$handlerClass} threw exception", [
                    'event' => $eventName,
                    'error' => $e->getMessage(),
                ]);
                $results[$handlerClass] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Synchronous handler execution
     */
    private function dispatchSync(
        string $handlerClass,
        string $eventName,
        array $payload,
        ?string $eventId,
        ?int $tenantId
    ): array {
        $execution = $this->executionService->createExecution(
            $handlerClass,
            $eventName,
            $payload,
            $eventId,
            $tenantId
        );

        $this->executionService->markRunning($execution);

        try {
            $handler = app($handlerClass);
            $handler->handle($eventName, $payload);

            $this->executionService->markSuccess($execution);

            return [
                'success' => true,
                'execution_id' => $execution->id,
            ];
        } catch (\Throwable $e) {
            $shouldRetry = $this->executionService->handleFailure($execution, $e->getMessage());

            if ($shouldRetry) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'will_retry' => true,
                    'execution_id' => $execution->id,
                ];
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'will_retry' => false,
                'dead_letter' => true,
                'execution_id' => $execution->id,
            ];
        }
    }

    /**
     * Asynchronous handler execution (queues job)
     */
    private function dispatchAsync(
        string $handlerClass,
        string $eventName,
        array $payload,
        ?string $eventId,
        ?int $tenantId
    ): array {
        $execution = $this->executionService->createExecution(
            $handlerClass,
            $eventName,
            $payload,
            $eventId,
            $tenantId
        );

        $execution->markDispatched();

        // Dispatch to queue
        dispatch(new \App\Jobs\Hermes\ProcessHandlerJob(
            $execution->id,
            $handlerClass,
            $eventName,
            $payload,
            $eventId,
            $tenantId
        ))->onQueue(config('hermes.queue_name', 'hermes'));

        return [
            'success' => true,
            'execution_id' => $execution->id,
            'dispatched' => true,
        ];
    }

    /**
     * Resolve handlers for an event name
     *
     * @return class-string<HandlerInterface>[]
     */
    private function resolveHandlers(string $eventName): array
    {
        $handlers = [];

        // Exact match
        if (isset($this->handlers[$eventName])) {
            $handlers[] = $this->handlers[$eventName];
        }

        // Wildcard match (e.g., "ilan.*" matches "ilan.created")
        foreach ($this->handlers as $pattern => $handlerClass) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($eventName, $prefix . '.')) {
                    $handlers[] = $handlerClass;
                }
            }
        }

        return array_unique($handlers);
    }

    /**
     * Extract tenant ID from payload
     */
    private function extractTenantId(array $payload): ?int
    {
        return $payload['tenant_id']
            ?? $payload['user_id']
            ?? $payload['ulke_id']
            ?? null;
    }

    /**
     * Enable async mode
     */
    public function enableAsync(): void
    {
        $this->asyncMode = true;
    }

    /**
     * Disable async mode (sync mode)
     */
    public function disableAsync(): void
    {
        $this->asyncMode = false;
    }

    /**
     * Check if async mode is enabled
     */
    public function isAsyncMode(): bool
    {
        return $this->asyncMode;
    }

    /**
     * Get registered handlers
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}

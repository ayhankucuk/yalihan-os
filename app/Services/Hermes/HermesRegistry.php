<?php

namespace App\Services\Hermes;

use App\Contracts\Hermes\HermesHandlerContract;
use Illuminate\Support\Facades\Log;

/**
 * HermesRegistry
 *
 * In-memory registry for Hermes event handlers.
 * Maintains the mapping between event names and their handlers.
 */
class HermesRegistry
{
    /**
     * @var array<string, array<HermesHandlerContract>>
     */
    private array $handlers = [];

    /**
     * Register a handler for one or more events
     */
    public function register(HermesHandlerContract $handler): void
    {
        foreach ($handler->subscribesTo() as $eventName) {
            $this->handlers[$eventName][] = $handler;
            Log::debug("[HermesRegistry] Registered handler", [
                'event' => $eventName,
                'handler' => get_class($handler),
            ]);
        }
    }

    /**
     * Get handlers for a specific event
     *
     * @return array<HermesHandlerContract>
     */
    public function getHandlers(string $eventName): array
    {
        return $this->handlers[$eventName] ?? [];
    }

    /**
     * Check if any handlers are registered for an event
     */
    public function hasHandlers(string $eventName): bool
    {
        return !empty($this->handlers[$eventName]);
    }

    /**
     * Get all registered event names
     *
     * @return array<string>
     */
    public function getRegisteredEvents(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * Clear all handlers (useful for testing)
     */
    public function clear(): void
    {
        $this->handlers = [];
    }

    /**
     * Get count of handlers for an event
     */
    public function handlerCount(string $eventName): int
    {
        return count($this->handlers[$eventName] ?? []);
    }
}

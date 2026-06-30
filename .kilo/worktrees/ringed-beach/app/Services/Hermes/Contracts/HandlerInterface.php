<?php

namespace App\Services\Hermes\Contracts;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Interface for event handlers.
 * Implementations should be stateless and throw on failure.
 */
interface HandlerInterface
{
    /**
     * Get the event names this handler subscribes to
     *
     * @return string[]
     */
    public static function handles(): array;

    /**
     * Handle the event
     *
     * @param string $eventName
     * @param array $payload
     * @return void
     * @throws \Throwable
     */
    public function handle(string $eventName, array $payload): void;

    /**
     * Check if handler is enabled
     */
    public function isEnabled(): bool;
}

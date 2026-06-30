<?php

namespace App\Contracts\Hermes;

/**
 * HermesHandlerContract
 *
 * Interface for event handlers registered with Hermes.
 * Each handler processes specific event types.
 */
interface HermesHandlerContract
{
    /**
     * Get the list of event names this handler subscribes to
     *
     * @return array<string>
     */
    public function subscribesTo(): array;

    /**
     * Handle the event
     *
     * @param HermesEventContract $event
     * @return array Result data for logging
     */
    public function handle(HermesEventContract $event): array;

    /**
     * Check if this handler should run synchronously or async
     */
    public function isAsync(): bool;
}

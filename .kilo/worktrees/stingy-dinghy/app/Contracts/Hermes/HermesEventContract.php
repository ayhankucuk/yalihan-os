<?php

namespace App\Contracts\Hermes;

/**
 * HermesEventContract
 *
 * Interface for all domain events that flow through Hermes event bus.
 * All events must implement this contract to be eligible for Hermes routing.
 */
interface HermesEventContract
{
    /**
     * Get the canonical event name for routing
     */
    public function eventName(): string;

    /**
     * Get the tenant ID for isolation (null if no tenant context)
     */
    public function tenantId(): ?int;

    /**
     * Get the event payload as array
     */
    public function toPayload(): array;

    /**
     * Get the occurred-at timestamp
     */
    public function occurredAt(): \DateTimeImmutable;
}

<?php

namespace App\Domain\Hermes\Models;

/**
 * CapabilityBinding
 *
 * Represents the binding between an event and capabilities that should handle it.
 *
 * @property string $event_name
 * @property array $capabilities_required
 * @property array $capabilities_optional
 * @property string $routing_strategy (all|any|first)
 */
class CapabilityBinding
{
    public function __construct(
        public readonly string $eventName,
        public readonly array $capabilitiesRequired,
        public readonly array $capabilitiesOptional = [],
        public readonly string $routingStrategy = 'all',
    ) {}

    /**
     * Get all capabilities for this binding
     */
    public function allCapabilities(): array
    {
        return array_unique(array_merge(
            $this->capabilitiesRequired,
            $this->capabilitiesOptional
        ));
    }

    /**
     * Check if binding requires specific capability
     */
    public function requires(string $capability): bool
    {
        return in_array($capability, $this->capabilitiesRequired, true);
    }

    /**
     * Check if binding optionally uses specific capability
     */
    public function optionallyUses(string $capability): bool
    {
        return in_array($capability, $this->capabilitiesOptional, true);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName,
            'capabilities_required' => $this->capabilitiesRequired,
            'capabilities_optional' => $this->capabilitiesOptional,
            'routing_strategy' => $this->routingStrategy,
        ];
    }
}

<?php

namespace App\Domain\Hermes\Models;

/**
 * AgentRegistryEntry
 *
 * Represents a registered agent in the Hermes ontology.
 *
 * @property string $agent_name
 * @property string $agent_class
 * @property array $subscribed_events
 * @property array $capabilities
 * @property string $layer (detection|decision|action|learning|monitoring)
 * @property bool $enabled
 */
class AgentRegistryEntry
{
    public function __construct(
        public readonly string $agentName,
        public readonly string $agentClass,
        public readonly array $subscribedEvents,
        public readonly array $capabilities,
        public readonly string $layer,
        public readonly bool $enabled = true,
    ) {}

    /**
     * Check if this agent handles a specific event
     */
    public function handlesEvent(string $eventName): bool
    {
        return in_array($eventName, $this->subscribedEvents, true);
    }

    /**
     * Check if this agent has a specific capability
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /**
     * Get canonical layer label
     */
    public function layerLabel(): string
    {
        return match ($this->layer) {
            'detection' => '🔍 Detection',
            'decision' => '⚖️ Decision',
            'action' => '⚡ Action',
            'learning' => '🧠 Learning',
            'monitoring' => '👁️ Monitoring',
            'notification' => '📢 Notification',
            default => $this->layer,
        };
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'agent_name' => $this->agentName,
            'agent_class' => $this->agentClass,
            'subscribed_events' => $this->subscribedEvents,
            'capabilities' => $this->capabilities,
            'layer' => $this->layer,
            'layer_label' => $this->layerLabel(),
            'enabled' => $this->enabled,
        ];
    }
}

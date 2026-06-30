<?php

namespace App\Services\Hermes\Registry;

use App\Domain\Hermes\Enums\HermesCapability;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use App\Domain\Hermes\Models\CapabilityBinding;
use Illuminate\Support\Facades\Log;

/**
 * CapabilityRegistry
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Event → Capability eşleştirme tablosu.
 * HermesDispatcher bir event aldığında hangi capability'lere ihtiyaç olduğunu buradan öğrenir.
 */
class CapabilityRegistry
{
    /** @var array<string, CapabilityBinding> */
    private array $bindings = [];

    public function __construct()
    {
        $this->bootstrapDefaults();
    }

    /**
     * Bootstrap default event → capability bindings
     */
    private function bootstrapDefaults(): void
    {
        // Portfolio events → notification capability
        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::PORTFOLIO_CREATED->value,
            capabilitiesRequired: [
                HermesCapability::NOTIFY_PORTFOLIO_CREATED->value,
            ],
            capabilitiesOptional: [
                HermesCapability::ANALYZE_PORTFOLIO_TREND->value,
            ],
            routingStrategy: 'all',
        ));

        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::PORTFOLIO_UPDATED->value,
            capabilitiesRequired: [
                HermesCapability::NOTIFY_PORTFOLIO_CREATED->value, // Reuse notification capability
            ],
            capabilitiesOptional: [],
            routingStrategy: 'all',
        ));

        // Lead events → notification capability
        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::LEAD_CREATED->value,
            capabilitiesRequired: [
                HermesCapability::NOTIFY_LEAD_CREATED->value,
            ],
            capabilitiesOptional: [
                HermesCapability::ANALYZE_LEAD_FLOW->value,
            ],
            routingStrategy: 'all',
        ));

        // Governance events → governance + notification capabilities
        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::GOVERNANCE_DECISION_MADE->value,
            capabilitiesRequired: [
                HermesCapability::GOVERN_DECISION->value,
            ],
            capabilitiesOptional: [
                HermesCapability::NOTIFY_GOVERNANCE_DECISION->value,
            ],
            routingStrategy: 'all',
        ));

        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::GOVERNANCE_ROLLBACK_EXECUTED->value,
            capabilitiesRequired: [
                HermesCapability::GOVERN_ROLLBACK->value,
                HermesCapability::NOTIFY_EXECUTION_RESULT->value,
            ],
            capabilitiesOptional: [],
            routingStrategy: 'all',
        ));

        // Execution events → execution + notification capabilities
        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::EXECUTION_ACTION_APPLIED->value,
            capabilitiesRequired: [
                HermesCapability::EXECUTE_AUTO_FIX->value,
            ],
            capabilitiesOptional: [
                HermesCapability::NOTIFY_EXECUTION_RESULT->value,
            ],
            routingStrategy: 'all',
        ));

        // Cortex detection → detection capabilities
        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::CORTEX_FINDING_DETECTED->value,
            capabilitiesRequired: [
                HermesCapability::DETECT_FINDING->value,
            ],
            capabilitiesOptional: [
                HermesCapability::DETECT_QUALITY_ISSUE->value,
            ],
            routingStrategy: 'all',
        ));

        // Watcher → detection capability
        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::WATCHER_ANOMALY_DETECTED->value,
            capabilitiesRequired: [
                HermesCapability::DETECT_ANOMALY->value,
            ],
            capabilitiesOptional: [
                HermesCapability::NOTIFY_GOVERNANCE_DECISION->value,
            ],
            routingStrategy: 'all',
        ));

        // Optimizer → learning capability
        $this->bind(new CapabilityBinding(
            eventName: HermesEventVocabulary::OPTIMIZER_SUGGESTION_READY->value,
            capabilitiesRequired: [
                HermesCapability::LEARN_OPTIMIZE->value,
            ],
            capabilitiesOptional: [
                HermesCapability::LEARN_PATTERN_RECOGNITION->value,
            ],
            routingStrategy: 'all',
        ));

        Log::debug('[CapabilityRegistry] Default bindings bootstrapped', [
            'binding_count' => count($this->bindings),
        ]);
    }

    /**
     * Register a capability binding
     */
    public function bind(CapabilityBinding $binding): void
    {
        $this->bindings[$binding->eventName] = $binding;
        Log::debug('[CapabilityRegistry] Binding registered', [
            'event' => $binding->eventName,
            'required' => $binding->capabilitiesRequired,
            'optional' => $binding->capabilitiesOptional,
        ]);
    }

    /**
     * Get binding for an event
     */
    public function getBinding(string $eventName): ?CapabilityBinding
    {
        return $this->bindings[$eventName] ?? null;
    }

    /**
     * Get all required capabilities for an event
     *
     * @return array<string>
     */
    public function getRequiredCapabilities(string $eventName): array
    {
        return $this->bindings[$eventName]?->capabilitiesRequired ?? [];
    }

    /**
     * Get all optional capabilities for an event
     *
     * @return array<string>
     */
    public function getOptionalCapabilities(string $eventName): array
    {
        return $this->bindings[$eventName]?->capabilitiesOptional ?? [];
    }

    /**
     * Get all capabilities (required + optional) for an event
     *
     * @return array<string>
     */
    public function getAllCapabilities(string $eventName): array
    {
        return $this->bindings[$eventName]?->allCapabilities() ?? [];
    }

    /**
     * Get routing strategy for an event
     */
    public function getRoutingStrategy(string $eventName): string
    {
        return $this->bindings[$eventName]?->routingStrategy ?? 'all';
    }

    /**
     * Check if event has registered binding
     */
    public function hasBinding(string $eventName): bool
    {
        return isset($this->bindings[$eventName]);
    }

    /**
     * Check if event requires a specific capability
     */
    public function requiresCapability(string $eventName, string $capability): bool
    {
        return $this->bindings[$eventName]?->requires($capability) ?? false;
    }

    /**
     * Get all registered event names
     *
     * @return array<string>
     */
    public function getRegisteredEvents(): array
    {
        return array_keys($this->bindings);
    }

    /**
     * Get all registered capabilities
     *
     * @return array<string>
     */
    public function getRegisteredCapabilities(): array
    {
        $capabilities = [];
        foreach ($this->bindings as $binding) {
            $capabilities = array_merge($capabilities, $binding->allCapabilities());
        }
        return array_unique($capabilities);
    }

    /**
     * Validate vocabulary consistency
     * Ensures all bound events exist in vocabulary
     */
    public function validate(): array
    {
        $issues = [];

        foreach (array_keys($this->bindings) as $eventName) {
            if (!HermesEventVocabulary::isValid($eventName)) {
                $issues[] = "Event '{$eventName}' is bound but not in vocabulary";
            }
        }

        foreach ($this->bindings as $binding) {
            foreach ($binding->allCapabilities() as $capability) {
                if (!HermesCapability::isValid($capability)) {
                    $issues[] = "Capability '{$capability}' is bound but not in vocabulary";
                }
            }
        }

        return $issues;
    }

    /**
     * Get registry statistics
     */
    public function stats(): array
    {
        return [
            'total_bindings' => count($this->bindings),
            'total_events_bound' => count($this->bindings),
            'total_capabilities_used' => count($this->getRegisteredCapabilities()),
            'by_event' => array_map(
                fn (CapabilityBinding $b) => $b->toArray(),
                $this->bindings
            ),
        ];
    }
}

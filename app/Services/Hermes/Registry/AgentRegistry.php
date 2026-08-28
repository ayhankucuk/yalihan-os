<?php

namespace App\Services\Hermes\Registry;

use App\Domain\Hermes\Enums\HermesCapability;
use App\Domain\Hermes\Enums\HermesWorkforceCapability;
use App\Domain\Hermes\Models\AgentRegistryEntry;
use App\Domain\Hermes\Models\CapabilityBinding;
use App\Services\Hermes\Handlers\AnalyticsHandler;
use App\Services\Hermes\Handlers\GovernanceNotificationHandler;
use App\Services\Hermes\Handlers\NotificationAgentHandler;
use App\Services\Hermes\Handlers\TelegramNotificationHandler;
use App\Services\Hermes\Handlers\Workflow\PropertyScoreAgent;
use App\Services\Hermes\Handlers\Workflow\PublishDecisionAgent;
use App\Services\Hermes\Handlers\Workforce\DescriptionAgent;
use App\Services\Hermes\Handlers\Workforce\DriveAgent;
use App\Services\Hermes\Handlers\Workforce\NotificationAgent;
use App\Services\Hermes\Handlers\Workforce\PhotoAgent;
use Illuminate\Support\Facades\Log;

/**
 * AgentRegistry
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Merkezi ajan kayıt servisi.
 * HermesDispatcher'dan önce ajanları ve yeteneklerini yönetir.
 */
class AgentRegistry
{
    /** @var array<string, AgentRegistryEntry> */
    private array $agents = [];

    public function __construct()
    {
        $this->bootstrapDefaults();
    }

    /**
     * Bootstrap default agent registrations
     */
    private function bootstrapDefaults(): void
    {
        // Notification Agent — Hermes'in kendi handler'ı
        $this->register(new AgentRegistryEntry(
            agentName: 'notification_agent',
            agentClass: NotificationAgentHandler::class,
            subscribedEvents: [
                'portfolio.created',
                'ilan.created',
                'lead.created',
                'notification.failed',
            ],
            capabilities: [
                HermesCapability::NOTIFY_PORTFOLIO_CREATED->value,
                HermesCapability::NOTIFY_LEAD_CREATED->value,
                HermesCapability::NOTIFY_GOVERNANCE_DECISION->value,
                HermesCapability::NOTIFY_EXECUTION_RESULT->value,
            ],
            layer: 'notification',
        ));

        // Cortex Detection Agent
        $this->register(new AgentRegistryEntry(
            agentName: 'cortex',
            agentClass: \App\Agents\CortexAgent::class,
            subscribedEvents: [
                'cortex.finding_detected',
            ],
            capabilities: [
                HermesCapability::DETECT_FINDING->value,
                HermesCapability::DETECT_QUALITY_ISSUE->value,
            ],
            layer: 'detection',
        ));

        // Governance Decision Agent
        $this->register(new AgentRegistryEntry(
            agentName: 'governance',
            agentClass: \App\Agents\GovernanceAgent::class,
            subscribedEvents: [
                'governance.decision_made',
                'governance.finding_suppressed',
            ],
            capabilities: [
                HermesCapability::GOVERN_DECISION->value,
                HermesCapability::GOVERN_SUPPRESS->value,
            ],
            layer: 'decision',
        ));

        // Optimizer Learning Agent
        $this->register(new AgentRegistryEntry(
            agentName: 'optimizer',
            agentClass: \App\Agents\OptimizerAgent::class,
            subscribedEvents: [
                'optimizer.suggestion_ready',
            ],
            capabilities: [
                HermesCapability::LEARN_OPTIMIZE->value,
                HermesCapability::LEARN_PATTERN_RECOGNITION->value,
            ],
            layer: 'learning',
        ));

        // Analytics Handler
        $this->register(new AgentRegistryEntry(
            agentName: 'analytics',
            agentClass: AnalyticsHandler::class,
            subscribedEvents: [
                'portfolio.created',
                'portfolio.updated',
                'portfolio.deleted',
                'portfolio.published',
                'cortex.finding_detected',
                'governance.decision_made',
                'execution.action_applied',
                'lead.created',
                'lead.assigned',
            ],
            capabilities: [
                HermesCapability::ANALYZE_PORTFOLIO_TREND->value,
                HermesCapability::ANALYZE_LISTING_PERFORMANCE->value,
                HermesCapability::ANALYZE_LEAD_FLOW->value,
            ],
            layer: 'analytics',
        ));

        // Governance Notification Handler
        $this->register(new AgentRegistryEntry(
            agentName: 'governance_notification',
            agentClass: GovernanceNotificationHandler::class,
            subscribedEvents: [
                'governance.decision_made',
                'governance.finding_suppressed',
                'governance.rollback_executed',
                'governance.override_applied',
                'governance.action_failed',
            ],
            capabilities: [
                HermesCapability::NOTIFY_GOVERNANCE_DECISION->value,
            ],
            layer: 'governance',
        ));

        // Telegram Notification Handler (stub)
        $this->register(new AgentRegistryEntry(
            agentName: 'telegram',
            agentClass: TelegramNotificationHandler::class,
            subscribedEvents: [
                'notification.sent',
                'notification.failed',
            ],
            capabilities: [
                HermesCapability::NOTIFY_EXECUTION_RESULT->value,
            ],
            layer: 'notification',
            enabled: false, // Stub: disabled by default
        ));

        // ─── AI Workforce Agents — Sprint 4.5 Workspace-First Chain ───────
        //
        // New chain: PropertyWorkspaceCreated → PhotoAgent → DescriptionAgent
        //          → PropertyScoreAgent → PublishDecisionAgent → NotificationAgent
        //
        // Old Sprint 4.3 chain (portfolio.created → workforce.*_requested) is deprecated.

        // DriveAgent — creates workspace, emits PropertyWorkspaceCreated
        $this->register(new AgentRegistryEntry(
            agentName: 'drive_agent',
            agentClass: DriveAgent::class,
            subscribedEvents: [
                'portfolio.created',
            ],
            capabilities: [
                HermesWorkforceCapability::CREATE_DRIVE_WORKSPACE->value,
                HermesWorkforceCapability::MANAGE_DRIVE_WORKSPACE->value,
            ],
            layer: 'workforce',
        ));

        // PhotoAgent — subscribes workspace.created, emits photo_analysis.completed
        $this->register(new AgentRegistryEntry(
            agentName: 'photo_agent',
            agentClass: PhotoAgent::class,
            subscribedEvents: [
                'workforce.workspace.created',
            ],
            capabilities: [
                HermesWorkforceCapability::ANALYZE_PHOTOS->value,
                HermesWorkforceCapability::SUGGEST_PHOTO_IMPROVEMENTS->value,
            ],
            layer: 'workforce',
        ));

        // DescriptionAgent — subscribes photo_analysis.completed, emits description.completed
        $this->register(new AgentRegistryEntry(
            agentName: 'description_agent',
            agentClass: DescriptionAgent::class,
            subscribedEvents: [
                'workforce.photo_analysis.completed',
            ],
            capabilities: [
                HermesWorkforceCapability::GENERATE_DESCRIPTION->value,
                HermesWorkforceCapability::IMPROVE_DESCRIPTION->value,
            ],
            layer: 'workforce',
        ));

        // PropertyScoreAgent — subscribes both completions, emits property_score.calculated
        $this->register(new AgentRegistryEntry(
            agentName: 'property_score_agent',
            agentClass: PropertyScoreAgent::class,
            subscribedEvents: [
                'workforce.photo_analysis.completed',
                'workforce.description.completed',
            ],
            capabilities: [
                HermesWorkforceCapability::CALCULATE_PROPERTY_SCORE->value,
                HermesWorkforceCapability::ANALYZE_QUALITY->value,
            ],
            layer: 'workforce',
        ));

        // PublishDecisionAgent — subscribes property_score.calculated, emits publishing.decision_ready
        $this->register(new AgentRegistryEntry(
            agentName: 'publish_decision_agent',
            agentClass: PublishDecisionAgent::class,
            subscribedEvents: [
                'workforce.property_score.calculated',
            ],
            capabilities: [
                HermesWorkforceCapability::DECIDE_PUBLISHING->value,
                HermesWorkforceCapability::EVALUATE_LISTING_QUALITY->value,
            ],
            layer: 'workforce',
        ));

        // NotificationAgent — subscribes publishing.decision_ready, notifies advisor
        $this->register(new AgentRegistryEntry(
            agentName: 'workforce_notification_agent',
            agentClass: NotificationAgent::class,
            subscribedEvents: [
                'workforce.publishing.decision_ready',
            ],
            capabilities: [
                HermesWorkforceCapability::SEND_PORTFOLIO_NOTIFICATION->value,
                HermesWorkforceCapability::SEND_CHAIN_COMPLETE_NOTIFICATION->value,
            ],
            layer: 'workforce',
        ));

        Log::debug('[AgentRegistry] Default agents bootstrapped', [
            'agent_count' => count($this->agents),
        ]);
    }

    /**
     * Register an agent
     */
    public function register(AgentRegistryEntry $entry): void
    {
        $this->agents[$entry->agentName] = $entry;
        Log::debug('[AgentRegistry] Agent registered', [
            'agent' => $entry->agentName,
            'events' => $entry->subscribedEvents,
            'capabilities' => $entry->capabilities,
        ]);
    }

    /**
     * Get agent by name
     */
    public function get(string $agentName): ?AgentRegistryEntry
    {
        return $this->agents[$agentName] ?? null;
    }

    /**
     * Get all registered agents
     *
     * @return array<AgentRegistryEntry>
     */
    public function all(): array
    {
        return array_values($this->agents);
    }

    /**
     * Get agents that handle a specific event
     *
     * @return array<AgentRegistryEntry>
     */
    public function getByEvent(string $eventName): array
    {
        return array_values(array_filter(
            $this->agents,
            fn (AgentRegistryEntry $agent) => $agent->handlesEvent($eventName)
        ));
    }

    /**
     * Get agents that have a specific capability
     *
     * @return array<AgentRegistryEntry>
     */
    public function getByCapability(string $capability): array
    {
        return array_values(array_filter(
            $this->agents,
            fn (AgentRegistryEntry $agent) => $agent->hasCapability($capability)
        ));
    }

    /**
     * Get agents by layer
     *
     * @return array<AgentRegistryEntry>
     */
    public function getByLayer(string $layer): array
    {
        return array_values(array_filter(
            $this->agents,
            fn (AgentRegistryEntry $agent) => $agent->layer === $layer
        ));
    }

    /**
     * Get all registered event names
     *
     * @return array<string>
     */
    public function getRegisteredEvents(): array
    {
        $events = [];
        foreach ($this->agents as $agent) {
            $events = array_merge($events, $agent->subscribedEvents);
        }
        return array_unique($events);
    }

    /**
     * Get all registered capabilities
     *
     * @return array<string>
     */
    public function getRegisteredCapabilities(): array
    {
        $capabilities = [];
        foreach ($this->agents as $agent) {
            $capabilities = array_merge($capabilities, $agent->capabilities);
        }
        return array_unique($capabilities);
    }

    /**
     * Check if agent exists
     */
    public function has(string $agentName): bool
    {
        return isset($this->agents[$agentName]);
    }

    /**
     * Enable/disable an agent
     */
    public function setEnabled(string $agentName, bool $enabled): void
    {
        if (isset($this->agents[$agentName])) {
            $entry = $this->agents[$agentName];
            $this->agents[$agentName] = new AgentRegistryEntry(
                agentName: $entry->agentName,
                agentClass: $entry->agentClass,
                subscribedEvents: $entry->subscribedEvents,
                capabilities: $entry->capabilities,
                layer: $entry->layer,
                enabled: $enabled,
            );
        }
    }

    /**
     * Get registry statistics
     */
    public function stats(): array
    {
        return [
            'total_agents' => count($this->agents),
            'total_events' => count($this->getRegisteredEvents()),
            'total_capabilities' => count($this->getRegisteredCapabilities()),
            'by_layer' => array_count_values(array_column(
                array_map(fn ($a) => $a->toArray(), $this->agents),
                'layer'
            )),
            'agents' => array_map(fn ($a) => $a->toArray(), $this->agents),
        ];
    }
}

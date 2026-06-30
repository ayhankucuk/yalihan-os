<?php

namespace Tests\Unit\Hermes;

use App\Domain\Hermes\Enums\HermesCapability;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use App\Domain\Hermes\Models\AgentRegistryEntry;
use App\Services\Hermes\Registry\AgentRegistry;
use Tests\TestCase;

/**
 * Agent Registry Unit Tests
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Tests:
 * - Registry bootstraps with default agents
 * - Agent retrieval works correctly
 * - Event-based lookup works
 * - Capability-based lookup works
 * - Layer-based lookup works
 */
class AgentRegistryTest extends TestCase
{
    private AgentRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new AgentRegistry();
    }

    /**
     * Test registry bootstraps with default agents
     */
    public function test_registry_bootstraps_with_defaults(): void
    {
        $agents = $this->registry->all();

        $this->assertNotEmpty($agents);
        $this->assertGreaterThanOrEqual(1, count($agents));
    }

    /**
     * Test notification_agent is registered
     */
    public function test_notification_agent_registered(): void
    {
        $agent = $this->registry->get('notification_agent');

        $this->assertNotNull($agent);
        $this->assertInstanceOf(AgentRegistryEntry::class, $agent);
        $this->assertEquals('notification_agent', $agent->agentName);
    }

    /**
     * Test getByEvent returns agents that handle event
     */
    public function test_get_by_event(): void
    {
        $agents = $this->registry->getByEvent('portfolio.created');

        $this->assertNotEmpty($agents);
        $this->assertContains('notification_agent', array_map(
            fn ($a) => $a->agentName,
            $agents
        ));
    }

    /**
     * Test getByCapability returns agents with capability
     */
    public function test_get_by_capability(): void
    {
        $agents = $this->registry->getByCapability(
            HermesCapability::NOTIFY_PORTFOLIO_CREATED->value
        );

        $this->assertNotEmpty($agents);
        $this->assertEquals('notification_agent', $agents[0]->agentName);
    }

    /**
     * Test getByLayer returns agents in layer
     */
    public function test_get_by_layer(): void
    {
        $agents = $this->registry->getByLayer('notification');

        $this->assertNotEmpty($agents);
        $this->assertEquals('notification', $agents[0]->layer);
    }

    /**
     * Test notification_agent handles portfolio.created
     */
    public function test_notification_agent_subscribes_to_portfolio_events(): void
    {
        $agent = $this->registry->get('notification_agent');

        $this->assertTrue($agent->handlesEvent('portfolio.created'));
        $this->assertTrue($agent->handlesEvent('lead.created'));
    }

    /**
     * Test notification_agent has notification capabilities
     */
    public function test_notification_agent_has_notification_capabilities(): void
    {
        $agent = $this->registry->get('notification_agent');

        $this->assertTrue($agent->hasCapability(
            HermesCapability::NOTIFY_PORTFOLIO_CREATED->value
        ));
        $this->assertTrue($agent->hasCapability(
            HermesCapability::NOTIFY_LEAD_CREATED->value
        ));
    }

    /**
     * Test layerLabel returns human-readable label
     */
    public function test_layer_label(): void
    {
        $agent = $this->registry->get('notification_agent');

        $this->assertNotEmpty($agent->layerLabel());
        $this->assertStringContainsString('Notification', $agent->layerLabel());
    }

    /**
     * Test getRegisteredEvents returns all unique events
     */
    public function test_get_registered_events(): void
    {
        $events = $this->registry->getRegisteredEvents();

        $this->assertNotEmpty($events);
        $this->assertContains('portfolio.created', $events);
    }

    /**
     * Test getRegisteredCapabilities returns all unique capabilities
     */
    public function test_get_registered_capabilities(): void
    {
        $capabilities = $this->registry->getRegisteredCapabilities();

        $this->assertNotEmpty($capabilities);
        $this->assertContains(
            HermesCapability::NOTIFY_PORTFOLIO_CREATED->value,
            $capabilities
        );
    }

    /**
     * Test setEnabled toggles agent status
     */
    public function test_set_enabled(): void
    {
        $this->registry->setEnabled('notification_agent', false);

        $agent = $this->registry->get('notification_agent');
        $this->assertFalse($agent->enabled);

        $this->registry->setEnabled('notification_agent', true);

        $agent = $this->registry->get('notification_agent');
        $this->assertTrue($agent->enabled);
    }

    /**
     * Test stats returns correct structure
     */
    public function test_stats_structure(): void
    {
        $stats = $this->registry->stats();

        $this->assertArrayHasKey('total_agents', $stats);
        $this->assertArrayHasKey('total_events', $stats);
        $this->assertArrayHasKey('total_capabilities', $stats);
        $this->assertArrayHasKey('by_layer', $stats);
        $this->assertArrayHasKey('agents', $stats);
    }

    /**
     * Test has returns correct results
     */
    public function test_has(): void
    {
        $this->assertTrue($this->registry->has('notification_agent'));
        $this->assertFalse($this->registry->has('nonexistent_agent'));
    }

    /**
     * Test register adds new agent
     */
    public function test_register_adds_new_agent(): void
    {
        $entry = new AgentRegistryEntry(
            agentName: 'test_agent',
            agentClass: \stdClass::class,
            subscribedEvents: ['test.event'],
            capabilities: ['test.capability'],
            layer: 'test',
        );

        $this->registry->register($entry);

        $this->assertTrue($this->registry->has('test_agent'));
        $this->assertEquals('test_agent', $this->registry->get('test_agent')->agentName);
    }
}

<?php

namespace Tests\Unit\Hermes;

use App\Domain\Hermes\Enums\HermesCapability;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use App\Domain\Hermes\Models\CapabilityBinding;
use App\Services\Hermes\Registry\CapabilityRegistry;
use Tests\TestCase;

/**
 * Capability Registry Unit Tests
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Tests:
 * - Registry bootstraps with default bindings
 * - Binding retrieval works correctly
 * - Required/optional capabilities are returned
 * - Vocabulary validation works
 */
class CapabilityRegistryTest extends TestCase
{
    private CapabilityRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CapabilityRegistry();
    }

    /**
     * Test registry bootstraps with default bindings
     */
    public function test_registry_bootstraps_with_defaults(): void
    {
        $events = $this->registry->getRegisteredEvents();

        $this->assertNotEmpty($events);
        $this->assertContains('portfolio.created', $events);
        $this->assertContains('lead.created', $events);
        $this->assertContains('governance.decision_made', $events);
    }

    /**
     * Test portfolio.created has required notification capability
     */
    public function test_portfolio_created_binding(): void
    {
        $required = $this->registry->getRequiredCapabilities('portfolio.created');
        $optional = $this->registry->getOptionalCapabilities('portfolio.created');

        $this->assertContains('notification.notify_portfolio_created', $required);
        $this->assertContains('analytics.analyze_portfolio_trend', $optional);
    }

    /**
     * Test governance.decision_made has required governance capability
     */
    public function test_governance_decision_binding(): void
    {
        $required = $this->registry->getRequiredCapabilities('governance.decision_made');

        $this->assertContains('governance.decide', $required);
    }

    /**
     * Test hasBinding returns correct results
     */
    public function test_has_binding(): void
    {
        $this->assertTrue($this->registry->hasBinding('portfolio.created'));
        $this->assertTrue($this->registry->hasBinding('lead.created'));
        $this->assertFalse($this->registry->hasBinding('nonexistent.event'));
    }

    /**
     * Test getBinding returns correct binding
     */
    public function test_get_binding(): void
    {
        $binding = $this->registry->getBinding('portfolio.created');

        $this->assertInstanceOf(CapabilityBinding::class, $binding);
        $this->assertEquals('portfolio.created', $binding->eventName);
    }

    /**
     * Test getAllCapabilities returns both required and optional
     */
    public function test_get_all_capabilities(): void
    {
        $capabilities = $this->registry->getAllCapabilities('portfolio.created');

        $this->assertNotEmpty($capabilities);
        $this->assertContains('notification.notify_portfolio_created', $capabilities);
        $this->assertContains('analytics.analyze_portfolio_trend', $capabilities);
    }

    /**
     * Test routing strategy is returned
     */
    public function test_routing_strategy(): void
    {
        $strategy = $this->registry->getRoutingStrategy('portfolio.created');

        $this->assertEquals('all', $strategy);
    }

    /**
     * Test validate returns no issues for default bindings
     */
    public function test_validate_returns_no_issues(): void
    {
        $issues = $this->registry->validate();

        $this->assertEmpty($issues, 'Default bindings should be valid');
    }

    /**
     * Test stats returns correct structure
     */
    public function test_stats_structure(): void
    {
        $stats = $this->registry->stats();

        $this->assertArrayHasKey('total_bindings', $stats);
        $this->assertArrayHasKey('total_events_bound', $stats);
        $this->assertArrayHasKey('total_capabilities_used', $stats);
        $this->assertGreaterThan(0, $stats['total_bindings']);
    }

    /**
     * Test bind adds new binding
     */
    public function test_bind_adds_new_binding(): void
    {
        $binding = new CapabilityBinding(
            eventName: 'custom.event',
            capabilitiesRequired: ['notification.notify_portfolio_created'],
            capabilitiesOptional: [],
            routingStrategy: 'all',
        );

        $this->registry->bind($binding);

        $this->assertTrue($this->registry->hasBinding('custom.event'));
        $this->assertEquals(
            ['notification.notify_portfolio_created'],
            $this->registry->getRequiredCapabilities('custom.event')
        );
    }
}

<?php

namespace Tests\Unit\Hermes;

use App\Domain\Hermes\Enums\HermesCapability;
use Tests\TestCase;

/**
 * Hermes Capability Vocabulary Unit Tests
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Tests:
 * - Capability enum contains all canonical capabilities
 * - Values are correctly formatted
 * - Validation works correctly
 * - Domain extraction works
 * - Labels are human-readable
 */
class HermesCapabilityVocabularyTest extends TestCase
{
    /**
     * Test capability vocabulary contains expected capabilities
     */
    public function test_vocabulary_contains_expected_capabilities(): void
    {
        $expectedCapabilities = [
            'notification.notify_portfolio_created',
            'notification.notify_lead_created',
            'analytics.analyze_portfolio_trend',
            'governance.decide',
            'execution.auto_fix',
            'detection.anomaly',
            'learning.optimize',
        ];

        $actualCapabilities = HermesCapability::values();

        foreach ($expectedCapabilities as $capability) {
            $this->assertContains(
                $capability,
                $actualCapabilities,
                "Capability '{$capability}' should be in vocabulary"
            );
        }
    }

    /**
     * Test capability naming convention: {domain}.{action}
     */
    public function test_capability_naming_convention(): void
    {
        $capabilities = HermesCapability::values();

        foreach ($capabilities as $capability) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+\.[a-z_]+$/',
                $capability,
                "Capability '{$capability}' should follow {domain}.{action} convention"
            );
        }
    }

    /**
     * Test isValid correctly validates capabilities
     */
    public function test_is_valid_validates_capabilities(): void
    {
        $this->assertTrue(HermesCapability::isValid('notification.notify_portfolio_created'));
        $this->assertTrue(HermesCapability::isValid('governance.decide'));
        $this->assertFalse(HermesCapability::isValid('invalid.capability'));
        $this->assertFalse(HermesCapability::isValid('NOTIFICATION.CAPABILITY'));
    }

    /**
     * Test domain extraction from capability
     */
    public function test_domain_extraction(): void
    {
        $this->assertEquals('notification', HermesCapability::domain('notification.notify_portfolio_created'));
        $this->assertEquals('analytics', HermesCapability::domain('analytics.analyze_portfolio_trend'));
        $this->assertEquals('governance', HermesCapability::domain('governance.decide'));
    }

    /**
     * Test all capabilities have human-readable labels
     */
    public function test_all_capabilities_have_labels(): void
    {
        $capabilities = HermesCapability::cases();

        foreach ($capabilities as $capability) {
            $label = $capability->label();
            $this->assertNotEmpty($label);
            $this->assertIsString($label);
        }
    }

    /**
     * Test notification capabilities
     */
    public function test_notification_capabilities(): void
    {
        $this->assertEquals(
            'notification.notify_portfolio_created',
            HermesCapability::NOTIFY_PORTFOLIO_CREATED->value
        );
        $this->assertEquals(
            'Portföy Oluşturma Bildirimi',
            HermesCapability::NOTIFY_PORTFOLIO_CREATED->label()
        );
    }

    /**
     * Test analytics capabilities
     */
    public function test_analytics_capabilities(): void
    {
        $this->assertEquals(
            'analytics.analyze_portfolio_trend',
            HermesCapability::ANALYZE_PORTFOLIO_TREND->value
        );
        $this->assertEquals(
            'Portföy Trendi Analizi',
            HermesCapability::ANALYZE_PORTFOLIO_TREND->label()
        );
    }
}

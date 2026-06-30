<?php

namespace Tests\Unit\Hermes;

use App\Domain\Hermes\Enums\HermesCapability;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use Tests\TestCase;

/**
 * Hermes Event Vocabulary Unit Tests
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Tests:
 * - Vocabulary enum contains all canonical events
 * - Values are correctly formatted
 * - Validation works correctly
 * - Domain extraction works
 * - Labels are human-readable
 */
class HermesEventVocabularyTest extends TestCase
{
    /**
     * Test vocabulary contains expected events
     */
    public function test_vocabulary_contains_expected_events(): void
    {
        $expectedEvents = [
            'portfolio.created',
            'portfolio.updated',
            'portfolio.deleted',
            'portfolio.published',
            'cortex.finding_detected',
            'governance.decision_made',
            'execution.action_applied',
            'optimizer.suggestion_ready',
            'watcher.anomaly_detected',
            'lead.created',
            'notification.sent',
        ];

        $actualEvents = HermesEventVocabulary::values();

        foreach ($expectedEvents as $event) {
            $this->assertContains($event, $actualEvents, "Event '{$event}' should be in vocabulary");
        }
    }

    /**
     * Test event naming convention: {domain}.{action}
     */
    public function test_event_naming_convention(): void
    {
        $events = HermesEventVocabulary::values();

        foreach ($events as $event) {
            $this->assertMatchesRegularExpression(
                '/^[a-z]+\.[a-z_]+$/',
                $event,
                "Event '{$event}' should follow {domain}.{action} convention"
            );
        }
    }

    /**
     * Test isValid correctly validates events
     */
    public function test_is_valid_validates_events(): void
    {
        $this->assertTrue(HermesEventVocabulary::isValid('portfolio.created'));
        $this->assertTrue(HermesEventVocabulary::isValid('governance.decision_made'));
        $this->assertFalse(HermesEventVocabulary::isValid('invalid.event'));
        $this->assertFalse(HermesEventVocabulary::isValid('PORTFOLIO.CREATED'));
        $this->assertFalse(HermesEventVocabulary::isValid(''));
    }

    /**
     * Test domain extraction from event name
     */
    public function test_domain_extraction(): void
    {
        $this->assertEquals('portfolio', HermesEventVocabulary::domain('portfolio.created'));
        $this->assertEquals('cortex', HermesEventVocabulary::domain('cortex.finding_detected'));
        $this->assertEquals('governance', HermesEventVocabulary::domain('governance.decision_made'));
    }

    /**
     * Test all events have human-readable labels
     */
    public function test_all_events_have_labels(): void
    {
        $events = HermesEventVocabulary::cases();

        foreach ($events as $event) {
            $label = $event->label();
            $this->assertNotEmpty($label);
            $this->assertIsString($label);
        }
    }

    /**
     * Test PORTFOLIO_CREATED event specifically
     */
    public function test_portfolio_created_event(): void
    {
        $event = HermesEventVocabulary::PORTFOLIO_CREATED;

        $this->assertEquals('portfolio.created', $event->value);
        $this->assertEquals('Yeni Portföy Oluşturuldu', $event->label());
    }
}

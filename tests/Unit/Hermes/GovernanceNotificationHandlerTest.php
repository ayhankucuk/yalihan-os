<?php

namespace Tests\Unit\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use App\Services\Hermes\Handlers\GovernanceNotificationHandler;
use Tests\TestCase;

/**
 * GovernanceNotificationHandler Unit Tests
 */
class GovernanceNotificationHandlerTest extends TestCase
{
    private GovernanceNotificationHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new GovernanceNotificationHandler();
    }

    /**
     * Test handler subscribes to governance events
     */
    public function test_subscribes_to_governance_events(): void
    {
        $events = $this->handler->subscribesTo();

        $this->assertContains('governance.decision_made', $events);
        $this->assertContains('governance.finding_suppressed', $events);
        $this->assertContains('governance.rollback_executed', $events);
        $this->assertContains('governance.override_applied', $events);
        $this->assertContains('governance.action_failed', $events);
    }

    /**
     * Test handler returns correct result structure
     */
    public function test_returns_correct_result_structure(): void
    {
        $event = $this->createMockGovernanceEvent(
            HermesEventVocabulary::GOVERNANCE_DECISION_MADE->value
        );

        $result = $this->handler->handle($event);

        $this->assertEquals(GovernanceNotificationHandler::class, $result['handler']);
        $this->assertEquals('governance.decision_made', $result['event']);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertTrue($result['logged']);
    }

    /**
     * Test critical events have critical severity
     */
    public function test_critical_events_have_critical_severity(): void
    {
        $event = $this->createMockGovernanceEvent(
            HermesEventVocabulary::GOVERNANCE_ACTION_FAILED->value
        );

        $result = $this->handler->handle($event);

        $this->assertEquals('critical', $result['severity']);
        $this->assertEquals('action_failed', $result['type']);
    }

    /**
     * Test rollback events have high severity
     */
    public function test_rollback_events_have_high_severity(): void
    {
        $event = $this->createMockGovernanceEvent(
            HermesEventVocabulary::GOVERNANCE_ROLLBACK_EXECUTED->value
        );

        $result = $this->handler->handle($event);

        $this->assertEquals('high', $result['severity']);
        $this->assertEquals('rollback', $result['type']);
    }

    /**
     * Test suppressed events have medium severity
     */
    public function test_suppressed_events_have_medium_severity(): void
    {
        $event = $this->createMockGovernanceEvent(
            HermesEventVocabulary::GOVERNANCE_FINDING_SUPPRESSED->value
        );

        $result = $this->handler->handle($event);

        $this->assertEquals('medium', $result['severity']);
        $this->assertEquals('suppression', $result['type']);
    }

    /**
     * Test isAsync returns false (sync governance visibility)
     */
    public function test_is_async_returns_false(): void
    {
        $this->assertFalse($this->handler->isAsync());
    }

    /**
     * Create mock governance event
     */
    private function createMockGovernanceEvent(string $eventName): HermesEventContract
    {
        return new class($eventName) implements HermesEventContract
        {
            public function __construct(private string $name) {}

            public function eventName(): string
            {
                return $this->name;
            }

            public function tenantId(): ?int
            {
                return 1;
            }

            public function toPayload(): array
            {
                return [
                    'event' => $this->name,
                    'tenant_id' => 1,
                    'metadata' => [],
                ];
            }

            public function occurredAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };
    }
}

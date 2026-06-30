<?php

namespace Tests\Unit\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Services\Hermes\Handlers\TelegramNotificationHandler;
use Tests\TestCase;

/**
 * TelegramNotificationHandler Stub Tests
 */
class TelegramNotificationHandlerTest extends TestCase
{
    private TelegramNotificationHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new TelegramNotificationHandler();
    }

    /**
     * Test handler subscribes to notification events
     */
    public function test_subscribes_to_notification_events(): void
    {
        $events = $this->handler->subscribesTo();

        $this->assertContains('notification.sent', $events);
        $this->assertContains('notification.failed', $events);
    }

    /**
     * Test handler returns correct result structure
     */
    public function test_returns_correct_result_structure(): void
    {
        $event = $this->createMockNotificationEvent('notification.sent');

        $result = $this->handler->handle($event);

        $this->assertEquals(TelegramNotificationHandler::class, $result['handler']);
        $this->assertEquals('notification.sent', $result['event']);
        $this->assertArrayHasKey('sent', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertTrue($result['stub']);
    }

    /**
     * Test stub does not send when disabled
     */
    public function test_stub_does_not_send_when_disabled(): void
    {
        $event = $this->createMockNotificationEvent('notification.sent');

        $result = $this->handler->handle($event);

        // Stub should not actually send
        $this->assertFalse($result['sent']);
        $this->assertNotEquals('api_called', $result['reason']);
    }

    /**
     * Test format message method
     */
    public function test_format_message(): void
    {
        $portfolioPayload = [
            'notification_type' => 'portfolio_created',
            'ilan_baslik' => 'Test İlan',
        ];

        $message = $this->handler->formatMessage($portfolioPayload);

        $this->assertStringContainsString('Test İlan', $message);
    }

    /**
     * Test isAsync returns true
     */
    public function test_is_async_returns_true(): void
    {
        $this->assertTrue($this->handler->isAsync());
    }

    /**
     * Test validate config returns array
     */
    public function test_validate_config_returns_array(): void
    {
        $issues = $this->handler->validateConfig();

        $this->assertIsArray($issues);
    }

    /**
     * Test check rate limit returns bool
     */
    public function test_check_rate_limit_returns_bool(): void
    {
        $result = $this->handler->checkRateLimit();

        $this->assertIsBool($result);
    }

    /**
     * Create mock notification event
     */
    private function createMockNotificationEvent(string $eventName): HermesEventContract
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
                    'notification_type' => 'test',
                ];
            }

            public function occurredAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };
    }
}

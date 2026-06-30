<?php

namespace Tests\Unit\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Events\PortfolioCreated;
use App\Models\Hermes\HermesEventLog;
use App\Models\Ilan;
use App\Services\Hermes\Handlers\NotificationAgentHandler;
use App\Services\Hermes\HermesDispatcher;
use App\Services\Hermes\HermesRegistry;
use App\Services\Hermes\HermesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hermes Event Bus Unit Tests
 *
 * Tests for Team Hermes event-driven foundation.
 * Coverage:
 * - PortfolioCreated event can be created
 * - Hermes receives event
 * - Hermes records event
 * - NotificationAgentHandler is called
 * - No tenant isolation violation
 * - No direct financial mutation
 */
class HermesEventBusTest extends TestCase
{
    use RefreshDatabase;

    private HermesRegistry $registry;
    private HermesDispatcher $dispatcher;
    private HermesService $hermes;

    protected function setUp(): void
    {
        parent::setUp();

        // Build Hermes components manually for isolated testing
        $this->registry = new HermesRegistry();
        $this->dispatcher = new HermesDispatcher($this->registry);
        $this->hermes = new HermesService($this->dispatcher);

        // Register notification handler
        $handler = new NotificationAgentHandler();
        $this->registry->register($handler);
    }

    /**
     * Test 1: PortfolioCreated event can be created
     */
    public function test_portfolio_created_event_can_be_created(): void
    {
        // Create a mock Ilan
        $ilan = $this->createMockIlan();

        $event = new PortfolioCreated($ilan, ['source' => 'test']);

        $this->assertInstanceOf(PortfolioCreated::class, $event);
        $this->assertInstanceOf(HermesEventContract::class, $event);
        $this->assertEquals('portfolio.created', $event->eventName());
        $this->assertEquals($ilan->id, $event->ilan->id);
        $this->assertEquals('test', $event->metadata['source']);
    }

    /**
     * Test 2: PortfolioCreated event implements HermesEventContract
     */
    public function test_portfolio_created_implements_hermes_event_contract(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan);

        $this->assertEquals('portfolio.created', $event->eventName());
        $this->assertEquals($ilan->tenant_id, $event->tenantId());

        $payload = $event->toPayload();
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('ilan_id', $payload);
        $this->assertArrayHasKey('ilan_baslik', $payload);

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt());
    }

    /**
     * Test 3: Hermes receives event
     */
    public function test_hermes_receives_event(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan, ['source' => 'test']);

        // Verify Hermes has handlers for this event
        $this->assertTrue($this->registry->hasHandlers('portfolio.created'));

        // Get handlers
        $handlers = $this->registry->getHandlers('portfolio.created');
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(HermesHandlerContract::class, $handlers[0]);
    }

    /**
     * Test 4: Hermes records event to HermesEventLog
     */
    public function test_hermes_records_event(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan, ['source' => 'test']);

        // Record event
        $log = $this->hermes->receive($event);

        $this->assertInstanceOf(HermesEventLog::class, $log);
        $this->assertEquals('portfolio.created', $log->event_name);
        $this->assertEquals(PortfolioCreated::class, $log->event_class);
        $this->assertEquals(HermesEventLog::STATUS_PROCESSED, $log->status);
        $this->assertNotNull($log->processed_at);
    }

    /**
     * Test 5: NotificationAgentHandler is called when event is dispatched
     */
    public function test_notification_agent_handler_is_called(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan, ['source' => 'test']);

        // Dispatch to handlers
        $results = $this->dispatcher->dispatch($event);

        $this->assertArrayHasKey(NotificationAgentHandler::class, $results);
        $result = $results[NotificationAgentHandler::class];

        $this->assertTrue($result['success']);
        $this->assertEquals('notification_type', $result['result']['notification_type'], 'portfolio_created');
        $this->assertTrue($result['result']['would_send']);
    }

    /**
     * Test 6: Full flow - Hermes receives, records, and dispatches event
     */
    public function test_full_flow_hermes_receives_records_and_dispatches(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan, ['source' => 'integration_test']);

        // Full Hermes flow
        $log = $this->hermes->receive($event);

        // Verify event was recorded
        $this->assertNotNull($log->id);
        $this->assertEquals('portfolio.created', $log->event_name);

        // Verify event was processed
        $this->assertEquals(HermesEventLog::STATUS_PROCESSED, $log->status);

        // Verify handler results were recorded
        $handlerResults = $log->handler_results;
        $this->assertNotNull($handlerResults);
        $this->assertArrayHasKey(NotificationAgentHandler::class, $handlerResults);
    }

    /**
     * Test 7: No tenant isolation violation - tenant_id is preserved
     */
    public function test_no_tenant_isolation_violation(): void
    {
        // Create ilan with specific tenant
        $ilan = $this->createMockIlan(tenantId: 42);
        $event = new PortfolioCreated($ilan);

        $this->assertEquals(42, $event->tenantId());

        // Record event
        $log = $this->hermes->receive($event);

        // Verify tenant_id is preserved in log
        $this->assertEquals(42, $log->tenant_id);

        // Verify tenant scoping works
        $tenantLogs = HermesEventLog::where('tenant_id', 42)->get();
        $this->assertCount(1, $tenantLogs);

        // Verify other tenant has no access
        $otherTenantLogs = HermesEventLog::where('tenant_id', 999)->get();
        $this->assertCount(0, $otherTenantLogs);
    }

    /**
     * Test 8: No direct financial mutation - event only reads data
     */
    public function test_no_direct_financial_mutation(): void
    {
        $ilan = $this->createMockIlan(fiyat: 1500000);
        $event = new PortfolioCreated($ilan);

        // Dispatch event
        $this->hermes->receive($event);

        // Verify Ilan record was not modified
        $ilan->refresh();

        // Price should be unchanged
        $this->assertEquals(1500000, $ilan->fiyat);

        // Verify no financial records were created
        $financialTables = ['ledger_entries', 'payments', 'invoices'];
        foreach ($financialTables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $count = \DB::table($table)->count();
                $this->assertEquals(0, $count, "Financial table {$table} should have no records");
            }
        }
    }

    /**
     * Test 9: Hermes handles missing handlers gracefully
     */
    public function test_hermes_handles_missing_handlers_gracefully(): void
    {
        // Create event without registered handlers
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan);

        // Dispatch without handler registration
        $dispatcher = new HermesDispatcher(new HermesRegistry());
        $results = $dispatcher->dispatch($event);

        $this->assertEmpty($results);
    }

    /**
     * Test 10: Hermes handles handler failure gracefully
     */
    public function test_hermes_handles_handler_failure_gracefully(): void
    {
        // Create a failing handler
        $failingHandler = new class implements HermesHandlerContract {
            public function subscribesTo(): array
            {
                return ['test.failure'];
            }

            public function handle(HermesEventContract $event): array
            {
                throw new \RuntimeException('Handler failed intentionally');
            }

            public function isAsync(): bool
            {
                return false;
            }
        };

        $registry = new HermesRegistry();
        $registry->register($failingHandler);
        $dispatcher = new HermesDispatcher($registry);

        $event = new class implements HermesEventContract {
            public function eventName(): string
            {
                return 'test.failure';
            }
            public function tenantId(): ?int
            {
                return null;
            }
            public function toPayload(): array
            {
                return [];
            }
            public function occurredAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };

        $results = $dispatcher->dispatch($event);

        $this->assertFalse($results[get_class($failingHandler)]['success']);
        $this->assertEquals('Handler failed intentionally', $results[get_class($failingHandler)]['error']);
    }

    /**
     * Test 11: Event payload contains correct data
     */
    public function test_event_payload_contains_correct_data(): void
    {
        $ilan = $this->createMockIlan(
            baslik: 'Test İlan',
            fiyat: 2500000
        );
        $event = new PortfolioCreated($ilan, ['custom_field' => 'test_value']);

        $payload = $event->toPayload();

        $this->assertEquals($ilan->id, $payload['ilan_id']);
        $this->assertEquals('Test İlan', $payload['ilan_baslik']);
        $this->assertEquals(2500000, $payload['ilan_fiyat']);
        $this->assertEquals('test_value', $payload['metadata']['custom_field']);
    }

    /**
     * Test 12: Registry correctly registers multiple handlers
     */
    public function test_registry_registers_multiple_handlers(): void
    {
        $registry = new HermesRegistry();

        $handler1 = new class implements HermesHandlerContract {
            public function subscribesTo(): array
            {
                return ['event.a', 'event.b'];
            }
            public function handle(HermesEventContract $event): array
            {
                return [];
            }
            public function isAsync(): bool
            {
                return false;
            }
        };

        $handler2 = new class implements HermesHandlerContract {
            public function subscribesTo(): array
            {
                return ['event.b', 'event.c'];
            }
            public function handle(HermesEventContract $event): array
            {
                return [];
            }
            public function isAsync(): bool
            {
                return false;
            }
        };

        $registry->register($handler1);
        $registry->register($handler2);

        $this->assertCount(1, $registry->getHandlers('event.a'));
        $this->assertCount(2, $registry->getHandlers('event.b'));
        $this->assertCount(1, $registry->getHandlers('event.c'));

        $registeredEvents = $registry->getRegisteredEvents();
        $this->assertContains('event.a', $registeredEvents);
        $this->assertContains('event.b', $registeredEvents);
        $this->assertContains('event.c', $registeredEvents);
    }

    /**
     * Helper: Create mock Ilan for testing
     */
    private function createMockIlan(
        ?int $id = null,
        string $baslik = 'Test İlan',
        ?float $fiyat = 1000000,
        ?int $tenantId = null
    ): Ilan {
        $ilan = new Ilan();
        $ilan->id = $id ?? 1;
        $ilan->baslik = $baslik;
        $ilan->fiyat = $fiyat;
        $ilan->tenant_id = $tenantId;
        $ilan->exists = true;

        return $ilan;
    }
}

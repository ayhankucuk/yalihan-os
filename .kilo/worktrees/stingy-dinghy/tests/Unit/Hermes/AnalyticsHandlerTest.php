<?php

namespace Tests\Unit\Hermes;

use App\Events\PortfolioCreated;
use App\Models\Hermes\HermesAnalytics;
use App\Models\Ilan;
use App\Services\Hermes\Handlers\AnalyticsHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AnalyticsHandler Unit Tests
 */
class AnalyticsHandlerTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new AnalyticsHandler();
    }

    /**
     * Test handler subscribes to expected events
     */
    public function test_subscribes_to_expected_events(): void
    {
        $events = $this->handler->subscribesTo();

        $this->assertContains('portfolio.created', $events);
        $this->assertContains('portfolio.updated', $events);
        $this->assertContains('governance.decision_made', $events);
        $this->assertContains('lead.created', $events);
    }

    /**
     * Test handler handles portfolio.created event
     */
    public function test_handles_portfolio_created_event(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan);

        $result = $this->handler->handle($event);

        $this->assertTrue($result['recorded']);
        $this->assertEquals('portfolio.created', $result['event']);
        $this->assertEquals(AnalyticsHandler::class, $result['handler']);
    }

    /**
     * Test analytics record is created in database
     */
    public function test_analytics_record_created(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan);

        $this->handler->handle($event);

        $record = HermesAnalytics::first();

        $this->assertNotNull($record);
        $this->assertEquals('portfolio.created', $record->event_name);
        $this->assertEquals(1, $record->total_count);
    }

    /**
     * Test multiple events increment counter
     */
    public function test_multiple_events_increment_counter(): void
    {
        $ilan = $this->createMockIlan();
        $event = new PortfolioCreated($ilan);

        // Handle same event 3 times
        $this->handler->handle($event);
        $this->handler->handle($event);
        $this->handler->handle($event);

        $record = HermesAnalytics::first();

        $this->assertEquals(3, $record->total_count);
        $this->assertEquals(3, $record->success_count);
    }

    /**
     * Test tenant isolation works
     */
    public function test_tenant_isolation(): void
    {
        $ilan1 = $this->createMockIlan(tenantId: 1);
        $ilan2 = $this->createMockIlan(tenantId: 2);

        $this->handler->handle(new PortfolioCreated($ilan1));
        $this->handler->handle(new PortfolioCreated($ilan1));
        $this->handler->handle(new PortfolioCreated($ilan2));

        $tenant1Records = HermesAnalytics::where('tenant_id', 1)->get();
        $tenant2Records = HermesAnalytics::where('tenant_id', 2)->get();

        $this->assertEquals(1, $tenant1Records->count());
        $this->assertEquals(2, $tenant1Records->first()->total_count);
        $this->assertEquals(1, $tenant2Records->count());
        $this->assertEquals(1, $tenant2Records->first()->total_count);
    }

    /**
     * Test isAsync returns false
     */
    public function test_is_async_returns_false(): void
    {
        $this->assertFalse($this->handler->isAsync());
    }

    /**
     * Create mock Ilan for testing
     */
    private function createMockIlan(
        ?int $id = 1,
        string $baslik = 'Test İlan',
        ?float $fiyat = 1000000,
        ?int $tenantId = null
    ): Ilan {
        $ilan = new Ilan();
        $ilan->id = $id;
        $ilan->baslik = $baslik;
        $ilan->fiyat = $fiyat;
        $ilan->tenant_id = $tenantId;
        $ilan->exists = true;

        return $ilan;
    }
}

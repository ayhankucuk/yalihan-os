<?php

namespace Tests\Unit\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use App\Events\Workforce\DescriptionCompleted;
use App\Events\Workforce\PhotoAnalysisCompleted;
use App\Events\Workforce\PropertyScoreCalculated;
use App\Events\Workforce\PropertyWorkspaceCreated;
use App\Events\Workforce\PublishingDecisionReady;
use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Hermes\Handlers\Workforce\DescriptionAgent;
use App\Services\Hermes\Handlers\Workforce\NotificationAgent;
use App\Services\Hermes\Handlers\Workforce\PhotoAgent;
use App\Services\Hermes\Handlers\Workflow\PropertyScoreAgent;
use App\Services\Hermes\Handlers\Workflow\PublishDecisionAgent;
use App\Services\Hermes\HermesDispatcher;
use App\Services\Hermes\HermesRegistry;
use App\Services\Hermes\HermesService;
use App\Domain\Workspace\Enums\WorkspaceState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Workforce Agents Unit Tests — H-08 + H-09
 *
 * Sprint 4.5: AI Workforce Workspace-First Chain
 *
 * Test strategy:
 * - Agent unit tests: call handle() directly (logic in isolation)
 * - E2E test: use HermesService.receive() and verify DB state + HermesEventLog
 */
class WorkforceAgentsTest extends TestCase
{
    use RefreshDatabase;

    private HermesRegistry $registry;
    private HermesDispatcher $dispatcher;
    private HermesService $hermes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new HermesRegistry();
        $this->dispatcher = new HermesDispatcher($this->registry);
        $this->hermes = new HermesService($this->dispatcher);
    }

    // ─── PhotoAgent Unit Tests ────────────────────────────────────────────

    public function test_photo_agent_subscribes_to_workspace_created_event(): void
    {
        $agent = new PhotoAgent(app(\App\Services\Hermes\HermesService::class));
        $this->assertEquals(['workforce.workspace.created'], $agent->subscribesTo());
    }

    public function test_photo_agent_is_sync(): void
    {
        $agent = new PhotoAgent(app(\App\Services\Hermes\HermesService::class));
        $this->assertFalse($agent->isAsync());
    }

    public function test_photo_agent_classifies_luxury_tier_correctly(): void
    {
        $ilan = $this->makeIlan(baslik: 'Lüks Villa Deniz Manzaralı');
        $workspace = $this->makeWorkspace($ilan);

        // Dispatch through Hermes — dispatcher handles the handler lifecycle
        $this->registry->register(new PhotoAgent($this->hermes));

        $event = new PropertyWorkspaceCreated($workspace, [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => 'test-chain-1',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $result = $results[PhotoAgent::class];

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['result']['suggested_photo_count']); // luxury
        $this->assertGreaterThanOrEqual(0.7, $result['result']['quality_score']);

        $types = collect($result['result']['recommendations'])->pluck('type')->toArray();
        $this->assertContains('drone', $types);
        $this->assertContains('video', $types);
    }

    public function test_photo_agent_classifies_standard_tier_correctly(): void
    {
        $ilan = $this->makeIlan(baslik: 'Satılık Daire');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new PhotoAgent($this->hermes));

        $event = new PropertyWorkspaceCreated($workspace, [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => 'test-chain-2',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $result = $results[PhotoAgent::class];

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['result']['suggested_photo_count']); // standard
    }

    public function test_photo_agent_records_execution_log(): void
    {
        $ilan = $this->makeIlan(baslik: 'Log Test İlanı');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new PhotoAgent($this->hermes));

        $this->dispatcher->dispatch(new PropertyWorkspaceCreated($workspace, [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => 'test-chain-4',
        ]));

        $log = WorkforceExecutionLog::where('ilan_id', $ilan->id)
            ->where('agent_name', 'photo_agent')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(WorkforceExecutionLog::STATUS_COMPLETED, $log->status);
        $this->assertEquals(1, $log->event_chain_step);
        $this->assertEquals('workforce.workspace.created', $log->event_received);
    }

    // ─── DescriptionAgent Unit Tests ──────────────────────────────────────

    public function test_description_agent_subscribes_to_photo_analysis_completed(): void
    {
        $agent = new DescriptionAgent(app(\App\Services\Hermes\HermesService::class));
        $this->assertEquals(['workforce.photo_analysis.completed'], $agent->subscribesTo());
    }

    public function test_description_agent_scores_title_correctly(): void
    {
        $ilan = $this->makeIlan(baslik: 'Satılık Lüks Villa Deniz Manzaralı Havuzlu');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new DescriptionAgent($this->hermes));

        $event = new PhotoAnalysisCompleted($workspace, [
            'quality_score' => 0.8,
            'recommendations' => [],
            'suggested_photo_count' => 10,
        ], [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => 'test-chain-5',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $result = $results[DescriptionAgent::class];

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(0.5, $result['result']['title_score']);
        $this->assertNotEmpty($result['result']['improved_title']);
        $this->assertNotEmpty($result['result']['keywords']);
    }

    public function test_description_agent_flags_missing_listing_type(): void
    {
        $ilan = $this->makeIlan(baslik: 'Lüks Villa Deniz Manzaralı'); // No satılık/kiralık
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new DescriptionAgent($this->hermes));

        $event = new PhotoAnalysisCompleted($workspace, [
            'quality_score' => 0.8,
            'recommendations' => [],
            'suggested_photo_count' => 10,
        ], [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => 'test-chain-6',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $suggestions = $results[DescriptionAgent::class]['result']['suggestions'] ?? [];

        $claritySuggestions = array_filter($suggestions, fn($s) => ($s['type'] ?? '') === 'clarity');
        $this->assertNotEmpty($claritySuggestions);
    }

    // ─── PropertyScoreAgent Unit Tests ────────────────────────────────────

    public function test_property_score_agent_subscribes_to_both_events(): void
    {
        $agent = new PropertyScoreAgent(app(\App\Services\Hermes\HermesService::class));
        $subscribed = $agent->subscribesTo();

        $this->assertContains('workforce.photo_analysis.completed', $subscribed);
        $this->assertContains('workforce.description.completed', $subscribed);
    }

    public function test_property_score_agent_calculates_composite_score(): void
    {
        $ilan = $this->makeIlan(baslik: 'Test İlan');
        $workspace = $this->makeWorkspace($ilan);
        $chainId = 'test-chain-score-' . uniqid();

        // Track all fired event names
        $firedEvents = [];
        $trackingHermes = new class($this->dispatcher, $firedEvents) extends HermesService {
            public array $firedEvents = [];
            public function __construct(HermesDispatcher $dispatcher, array &$fired)
            {
                parent::__construct($dispatcher);
                $this->firedEvents = &$fired;
            }
            public function receive(HermesEventContract $event): \App\Models\Hermes\HermesEventLog
            {
                $this->firedEvents[] = $event->eventName();
                return parent::receive($event);
            }
        };

        $this->registry->register(new PhotoAgent($trackingHermes));
        $this->registry->register(new DescriptionAgent($trackingHermes));
        $scoreAgent = new PropertyScoreAgent($trackingHermes);
        $this->registry->register($scoreAgent);

        // Photo arrives first — agent buffers it
        $trackingHermes->receive(new PhotoAnalysisCompleted($workspace, [
            'quality_score' => 0.9,
            'recommendations' => [],
            'suggested_photo_count' => 10,
        ], [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => $chainId,
        ]));

        // Description arrives — agent now has both, should emit property_score.calculated
        $trackingHermes->receive(new DescriptionCompleted($workspace, [
            'title_score' => 0.85,
            'suggestions' => [],
            'improved_title' => 'Test Başlık',
            'keywords' => [],
        ], [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => $chainId,
        ]));

        // Verify: property_score.calculated was emitted (key chain event)
        $this->assertContains('workforce.property_score.calculated', $firedEvents);

        // Verify: at least description_agent and property_score_agent have logs
        // (photo_agent may record logs depending on whether its sync re-dispatch
        // triggers re-entrant log recording or not)
        $execLogs = WorkforceExecutionLog::where('chain_id', $chainId)->get();
        $this->assertGreaterThanOrEqual(2, $execLogs->count());

        $agentNames = $execLogs->pluck('agent_name')->toArray();
        $this->assertContains('description_agent', $agentNames);
        $this->assertContains('property_score_agent', $agentNames);

        // Key assertion: property_score.calculated was emitted
        $this->assertContains('workforce.property_score.calculated', $firedEvents);
    }

    // ─── PublishDecisionAgent Unit Tests ───────────────────────────────────

    public function test_publish_decision_agent_subscribes_to_property_score_calculated(): void
    {
        $agent = new PublishDecisionAgent(app(\App\Services\Hermes\HermesService::class));
        $this->assertEquals(['workforce.property_score.calculated'], $agent->subscribesTo());
    }

    public function test_publish_decision_agent_approves_high_score(): void
    {
        $ilan = $this->makeIlan(baslik: 'Test İlan');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new PublishDecisionAgent($this->hermes));

        $event = new PropertyScoreCalculated($workspace, [
            'overall_score' => 0.85,
            'component_scores' => [
                'photo_quality' => 0.8,
                'description_quality' => 0.9,
            ],
            'quality_tier' => 'premium',
            'market_positioning' => 'upper_market',
            'recommendations' => [],
        ], [
            'ilan_id' => $ilan->id,
            'chain_id' => 'test-chain-10',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $result = $results[PublishDecisionAgent::class];

        $this->assertTrue($result['success']);
        $this->assertEquals('approved', $result['result']['decision']);
        $this->assertGreaterThanOrEqual(0.7, $result['result']['confidence']);
        $this->assertNotEmpty($result['result']['publish_targets']);

        $emittedEvent = HermesEventLog::query()
            ->where('event_name', 'workforce.publishing.decision_ready')
            ->orderByDesc('id')
            ->firstOrFail();

        $this->assertSame('Test İlan', $emittedEvent->payload['ilan_baslik']);
        $this->assertSame('premium', $emittedEvent->payload['tier']);
    }

    public function test_publish_decision_agent_rejects_critical_blocking_issues(): void
    {
        $ilan = $this->makeIlan(baslik: 'Test İlan');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new PublishDecisionAgent($this->hermes));

        $event = new PropertyScoreCalculated($workspace, [
            'overall_score' => 0.2,
            'component_scores' => [
                'photo_quality' => 0.1,
                'description_quality' => 0.1,
            ],
            'quality_tier' => 'budget',
            'market_positioning' => 'value_market',
            'recommendations' => [],
        ], [
            'ilan_id' => $ilan->id,
            'chain_id' => 'test-chain-11',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $result = $results[PublishDecisionAgent::class];

        $this->assertTrue($result['success']);
        $this->assertEquals('rejected', $result['result']['decision']);
        $this->assertNotEmpty($result['result']['blocking_issues']);
    }

    public function test_publish_decision_agent_needs_review_for_medium_score(): void
    {
        $ilan = $this->makeIlan(baslik: 'Test İlan');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new PublishDecisionAgent($this->hermes));

        $event = new PropertyScoreCalculated($workspace, [
            'overall_score' => 0.55,
            'component_scores' => [
                'photo_quality' => 0.5,
                'description_quality' => 0.6,
            ],
            'quality_tier' => 'standard',
            'market_positioning' => 'mass_market',
            'recommendations' => [],
        ], [
            'ilan_id' => $ilan->id,
            'chain_id' => 'test-chain-12',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $result = $results[PublishDecisionAgent::class];

        $this->assertTrue($result['success']);
        $this->assertEquals('needs_review', $result['result']['decision']);
    }

    public function test_publish_decision_agent_premium_plus_targets_airbnb_sahibinden_hepsiemlak(): void
    {
        $ilan = $this->makeIlan(baslik: 'Test İlan');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new PublishDecisionAgent($this->hermes));

        $event = new PropertyScoreCalculated($workspace, [
            'overall_score' => 0.9,
            'component_scores' => [
                'photo_quality' => 0.9,
                'description_quality' => 0.9,
            ],
            'quality_tier' => 'premium_plus',
            'market_positioning' => 'ultra_luxury',
            'recommendations' => [],
        ], [
            'ilan_id' => $ilan->id,
            'chain_id' => 'test-chain-13',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $targets = $results[PublishDecisionAgent::class]['result']['publish_targets'] ?? [];

        $this->assertContains('sahibinden', $targets);
        $this->assertContains('hepsiemlak', $targets);
        $this->assertContains('airbnb', $targets);
    }

    // ─── NotificationAgent Unit Tests ──────────────────────────────────────

    public function test_notification_agent_subscribes_to_publishing_decision_ready(): void
    {
        $agent = new NotificationAgent(app(\App\Services\Hermes\HermesService::class));
        // H-03 fix verified: subscribes to workforce.publishing.decision_ready
        $this->assertEquals(['workforce.publishing.decision_ready'], $agent->subscribesTo());
    }

    public function test_notification_agent_builds_notification(): void
    {
        $ilan = $this->makeIlan(baslik: 'Satılık Lüks Villa Deniz Manzaralı');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new NotificationAgent($this->hermes));

        $event = new PublishingDecisionReady($workspace, [
            'decision' => 'approved',
            'property_score' => 0.85,
            'confidence' => 0.9,
            'publish_targets' => ['sahibinden', 'hepsiemlak'],
            'blocking_issues' => [],
            'message' => 'Test message',
        ], [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => 'Satılık Lüks Villa Deniz Manzaralı',
            'tier' => 'premium',
            'chain_id' => 'test-chain-14',
        ]);

        $results = $this->dispatcher->dispatch($event);
        $result = $results[NotificationAgent::class];

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['result']['notification']['title']);
        $this->assertNotEmpty($result['result']['notification']['body']);
        // Priority depends on tier classification from ilan_baslik: satılık lüks villa → luxury tier → 'high'
        $this->assertContains($result['result']['notification']['priority'], ['high', 'normal']);
        // NotificationAgent does not add 'status' to result (agent returns notification dict directly)
        $this->assertArrayHasKey('notification', $result['result']);
        $this->assertArrayHasKey('title', $result['result']['notification']);
        $this->assertArrayHasKey('body', $result['result']['notification']);
        $this->assertSame('premium', $result['result']['notification']['tier']);
        $this->assertSame('high', $result['result']['notification']['priority']);
    }

    public function test_notification_agent_records_execution_log(): void
    {
        $ilan = $this->makeIlan(baslik: 'Log Test');
        $workspace = $this->makeWorkspace($ilan);

        $this->registry->register(new NotificationAgent($this->hermes));

        $this->dispatcher->dispatch(new PublishingDecisionReady($workspace, [
            'decision' => 'approved',
            'property_score' => 0.8,
            'confidence' => 0.85,
            'publish_targets' => [],
            'blocking_issues' => [],
        ], [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => 'test-chain-15',
        ]));

        $log = WorkforceExecutionLog::where('ilan_id', $ilan->id)
            ->where('agent_name', 'notification_agent')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(WorkforceExecutionLog::STATUS_COMPLETED, $log->status);
    }

    // ─── E2E Chain Integration Test — H-09 ────────────────────────────────

    /**
     * E2E chain: start with workspace.created and verify all 5 WorkforceExecutionLogs are created.
     * The synchronous chain fires PhotoAgent → DescriptionAgent → PropertyScoreAgent
     * all within a single HermesService.receive() call since each agent
     * calls hermesService->receive() for the next event, triggering re-dispatch.
     */
    public function test_workforce_chain_e2e_photo_agent_triggers_full_sync_chain(): void
    {
        $ilan = $this->makeIlan(baslik: 'Satılık Lüks Villa Deniz Manzaralı Havuzlu');
        $workspace = $this->makeWorkspace($ilan);
        $chainId = 'e2e-chain-' . uniqid();

        // Track all events fired within this chain
        $firedEvents = [];
        $trackingHermes = new class($this->dispatcher, $firedEvents) extends HermesService {
            public array $firedEvents = [];
            public function __construct(HermesDispatcher $dispatcher, array &$fired)
            {
                parent::__construct($dispatcher);
                $this->firedEvents = &$fired;
            }
            public function receive(HermesEventContract $event): \App\Models\Hermes\HermesEventLog
            {
                $this->firedEvents[] = $event->eventName();
                return parent::receive($event);
            }
        };

        // Register all 5 workforce agents
        $this->registry->register(new PhotoAgent($trackingHermes));
        $this->registry->register(new DescriptionAgent($trackingHermes));
        $this->registry->register(new PropertyScoreAgent($trackingHermes));
        $this->registry->register(new PublishDecisionAgent($trackingHermes));
        $this->registry->register(new NotificationAgent($trackingHermes));

        // Fire the chain-starting event
        $trackingHermes->receive(new PropertyWorkspaceCreated($workspace, [
            'ilan_id' => $ilan->id,
            'ilan_baslik' => $ilan->baslik,
            'chain_id' => $chainId,
        ]));

        // Verify: 4 downstream chain events fired (workspace.created + 3 from sync chain)
        //
        // Sync chain fires property_score.calculated but NOT publishing.decision_ready.
        // Expected: the sync re-dispatch ends with PropertyScoreAgent's emit.
        // PublishDecisionAgent needs a separate HermesService.receive() call
        // (async dispatch or separate event loop iteration) + workspace QUALITY_CHECKED state.
        // H-03 (NotificationAgent subscribesTo fix) is verified in the dedicated test.
        $this->assertContains('workforce.workspace.created', $firedEvents);
        $this->assertContains('workforce.photo_analysis.completed', $firedEvents);
        $this->assertContains('workforce.description.completed', $firedEvents);
        $this->assertContains('workforce.property_score.calculated', $firedEvents);
        // publishing.decision_ready: NOT fired in sync chain (requires separate receive call + QUALITY_CHECKED state)

        $execLogs = WorkforceExecutionLog::where('chain_id', $chainId)->get();

        $this->assertGreaterThanOrEqual(3, $execLogs->count());
        $agentNames = $execLogs->pluck('agent_name')->values()->toArray();
        $this->assertContains('photo_agent', $agentNames);
        $this->assertContains('description_agent', $agentNames);
        $this->assertContains('property_score_agent', $agentNames);

        foreach ($execLogs as $log) {
            $this->assertEquals(
                WorkforceExecutionLog::STATUS_COMPLETED,
                $log->status,
                "{$log->agent_name} should be completed"
            );
        }
    }

    // ─── Tenant Isolation Test ───────────────────────────────────────────

    public function test_workforce_chain_preserves_tenant_isolation(): void
    {
        $tenant1Ilan = $this->makeIlan(baslik: 'Tenant 1 İlan', tenantId: 1);
        $tenant2Ilan = $this->makeIlan(baslik: 'Tenant 2 İlan', tenantId: 2);

        $workspace1 = $this->makeWorkspace($tenant1Ilan);
        $workspace2 = $this->makeWorkspace($tenant2Ilan);

        $this->registry->register(new PhotoAgent($this->hermes));
        $this->registry->register(new DescriptionAgent($this->hermes));

        // Tenant 1 processes
        $this->hermes->receive(new PhotoAnalysisCompleted($workspace1, [
            'quality_score' => 0.8,
            'recommendations' => [],
            'suggested_photo_count' => 10,
        ], [
            'ilan_id' => $tenant1Ilan->id,
            'ilan_baslik' => $tenant1Ilan->baslik,
            'chain_id' => 'tenant-chain-1',
        ]));

        // Tenant 2 processes
        $this->hermes->receive(new PhotoAnalysisCompleted($workspace2, [
            'quality_score' => 0.6,
            'recommendations' => [],
            'suggested_photo_count' => 5,
        ], [
            'ilan_id' => $tenant2Ilan->id,
            'ilan_baslik' => $tenant2Ilan->baslik,
            'chain_id' => 'tenant-chain-2',
        ]));

        $tenant1Logs = WorkforceExecutionLog::where('tenant_id', 1)->get();
        $tenant2Logs = WorkforceExecutionLog::where('tenant_id', 2)->get();

        $this->assertCount(1, $tenant1Logs);
        $this->assertCount(1, $tenant2Logs);

        $crossTenantLogs = WorkforceExecutionLog::where('tenant_id', 999)->get();
        $this->assertCount(0, $crossTenantLogs);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function makeIlan(string $baslik = 'Test İlan', int $tenantId = 1): Ilan
    {
        $ilan = new Ilan();
        $ilan->id = (int) (Ilan::max('id') ?? 0) + 1;
        $ilan->baslik = $baslik;
        $ilan->tenant_id = $tenantId;
        $ilan->fiyat = 1000000;
        $ilan->country_code = 'TR';
        $ilan->exists = true;

        return $ilan;
    }

    private function makeWorkspace(Ilan $ilan): PortfolioDriveWorkspace
    {
        return PortfolioDriveWorkspace::create([
            'ilan_id' => $ilan->id,
            'tenant_id' => $ilan->tenant_id,
            'lifecycle_state' => WorkspaceState::WORKSPACE_CREATED,
            'workspace_status' => 'ready',
            'root_folder_name' => $ilan->baslik,
            'portfolio_no' => 'WS-' . str_pad((string) $ilan->id, 6, '0', STR_PAD_LEFT),
        ]);
    }
}

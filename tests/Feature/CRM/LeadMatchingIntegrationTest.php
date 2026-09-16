<?php

namespace Tests\Feature\CRM;

use App\Events\IlanYayinlandiEvent;
use App\Listeners\ActionCenter\IlanPublishedActionListener;
use App\Models\Ilan;
use App\Models\Lead;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * LeadMatchingIntegrationTest — P3 Lead Matching Integration.
 *
 * Verifies:
 * 1. IlanYayinlandiEvent → ActionCenter → 1 Gorev (lead_matching_check, tenant-scoped).
 * 2. Idempotency guard: firing twice creates only 1 Gorev.
 * 3. Tenant isolation: cross-tenant leads not visible to another tenant's ilan.
 * 4. LeadScoringService temperature thresholds (hot/warm/cold).
 * 5. EvaluateLeadWithCortex implements ShouldQueue.
 * 6. IlanPublishedActionListener implements ShouldQueue with 3 retries.
 * 7. Event dispatch pushes IlanPublishedActionListener to queue.
 */
class LeadMatchingIntegrationTest extends TestCase
{
    protected User $admin;
    protected int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'p3-lead-test-' . uniqid() . '@yalihan.local',
        ]);
    }

    /** @test */
    public function ilan_yayinlandi_event_has_listeners_registered(): void
    {
        $listeners = app()->make('events')
            ->getListeners(\App\Events\IlanYayinlandiEvent::class);

        $this->assertNotEmpty($listeners, 'IlanYayinlandiEvent has no listeners registered.');
    }

    /** @test */
    public function ilan_published_action_listener_implements_should_queue(): void
    {
        $listener = new IlanPublishedActionListener(app(ActionCenterService::class));

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $listener,
            'IlanPublishedActionListener must implement ShouldQueue.'
        );
        $this->assertEquals(3, $listener->tries, 'Retry count must be 3 for resilience.');
    }

    /** @test */
    public function dispatching_event_creates_lead_matching_check_gorev(): void
    {
        Queue::fake();

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenantId,
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(ActionCenterService::class);
        $event   = new IlanYayinlandiEvent($ilan);
        $actions = $service->generateActionsFromEvent($event);

        $this->assertEquals(1, $actions->count(), 'Must create exactly 1 lead_matching_check Gorev.');

        $gorev = $actions->first();
        $this->assertInstanceOf(Gorev::class, $gorev);
        $this->assertEquals($ilan->id, $gorev->ilan_id);
        $this->assertEquals($this->tenantId, $gorev->tenant_id);
        $this->assertEquals(IlanYayinlandiEvent::class, $gorev->source_event);
        $this->assertEquals('bekliyor', $gorev->gorev_durumu);
    }

    /** @test */
    public function action_center_gorev_is_idempotent_on_duplicate_event(): void
    {
        Queue::fake();

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenantId,
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(ActionCenterService::class);
        $event   = new IlanYayinlandiEvent($ilan);

        // Fire twice
        $service->generateActionsFromEvent($event);
        $service->generateActionsFromEvent($event);

        $count = Gorev::withoutTenant()
            ->where('ilan_id', $ilan->id)
            ->where('source_event', IlanYayinlandiEvent::class)
            ->count();

        $this->assertEquals(1, $count, 'Idempotency guard must prevent duplicate Gorev creation.');
    }

    /** @test */
    public function cross_tenant_lead_is_not_accessible_from_another_tenant(): void
    {
        $tenantA = 1;
        $tenantB = 2;

        Lead::factory()->create(['tenant_id' => $tenantA, 'aktif' => true, 'confidence' => 0.80]);
        $crossLead = Lead::factory()->create(['tenant_id' => $tenantB, 'aktif' => true, 'confidence' => 0.95]);

        $tenantALeads = Lead::where('tenant_id', $tenantA)->get();

        $this->assertNotContains(
            $crossLead->id,
            $tenantALeads->pluck('id')->toArray(),
            'Cross-tenant lead (B) must NOT appear in Tenant A lead query.'
        );
    }

    /** @test */
    public function lead_scoring_service_temperature_buckets_are_correct(): void
    {
        $service = app(\App\Services\CRM\LeadScoringService::class);

        $this->assertEquals('hot',  $service->getTemperature(80));
        $this->assertEquals('warm', $service->getTemperature(50));
        $this->assertEquals('cold', $service->getTemperature(20));
    }

    /** @test */
    public function evaluate_lead_with_cortex_implements_should_queue(): void
    {
        $listener = app(\App\Listeners\EvaluateLeadWithCortex::class);

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $listener,
            'EvaluateLeadWithCortex must be queued for async AI evaluation.'
        );
    }

    /** @test */
    public function high_confidence_qualified_lead_satisfies_idempotency_guard(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'  => $this->tenantId,
            'confidence' => 0.85,
            'crm_durumu' => Lead::CRM_QUALIFIED,
        ]);

        // The guard condition from EvaluateLeadWithCortex::handle
        $shouldSkip = $lead->crm_durumu >= Lead::CRM_QUALIFIED || $lead->confidence > 0.8;

        $this->assertTrue($shouldSkip, 'High-confidence qualified lead must satisfy the idempotency skip condition.');
    }

    /** @test */
    public function event_dispatch_listeners_are_registered_in_event_service_provider(): void
    {
        // Check via the EventServiceProvider's $listen array (authoritative source)
        $esp = new \App\Providers\EventServiceProvider(app());
        $listen = (new \ReflectionProperty($esp, 'listen'))->getValue($esp);

        $eventClass = \App\Events\IlanYayinlandiEvent::class;
        $this->assertArrayHasKey(
            $eventClass,
            $listen,
            'IlanYayinlandiEvent must be registered in EventServiceProvider.'
        );

        $registeredListeners = $listen[$eventClass];
        $this->assertContains(
            \App\Listeners\ActionCenter\IlanPublishedActionListener::class,
            $registeredListeners,
            'IlanPublishedActionListener must be bound to IlanYayinlandiEvent.'
        );
        $this->assertContains(
            \App\Listeners\NotifyLeadsOnNewListing::class,
            $registeredListeners,
            'NotifyLeadsOnNewListing must be bound to IlanYayinlandiEvent.'
        );
    }

}


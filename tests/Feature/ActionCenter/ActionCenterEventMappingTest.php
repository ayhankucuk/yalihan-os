<?php

namespace Tests\Feature\ActionCenter;

use App\Events\IlanCreated;
use App\Events\IlanPriceChanged;
use App\Events\LeadOlusturuldu;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Models\Ilan;
use App\Models\Lead;
use App\Models\SaaS\Tenant;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\ActionCenter\ActionCenterService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sprint 15 — Action Center Event Mapping Integration Tests.
 *
 * Tests the event-to-action mapping contract from architecture §3.1:
 * 1. IlanCreated → 3 Gorev (foto, açıklama, fiyatlandırma)
 * 2. LeadOlusturuldu → 1 Gorev, deadline +2h
 * 3. ReservationCreatedEvent → no duplicate (existing job handles it)
 * 4. Idempotency: same event 2x → 1 Gorev
 * 5. Tenant isolation: tenant A actions not visible to tenant B
 * 6. Priority scoring: acil/yuksek/normal/dusuk correctly set
 *
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md
 */
class ActionCenterEventMappingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected ActionCenterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();

        $this->service = $this->app->make(ActionCenterService::class);

        // Disable tenant scope globally for test setup — we manage tenant_id manually
        config(['tenant.scope_enabled' => false]);
    }

    /**
     * Create a minimal Ilan without factory dependencies.
     */
    private function createIlan(int $tenantId): Ilan
    {
        return Ilan::withoutTenant()->create([
            'baslik' => 'Test İlan ' . uniqid(),
            'slug' => 'test-ilan-' . uniqid(),
            'aciklama' => 'Test açıklama',
            'fiyat' => 1000000,
            'para_birimi' => 'TL',
            'referans_no' => 'REF-' . uniqid(),
            'yayin_durumu' => 'taslak',
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a minimal Lead without factory dependencies.
     */
    private function createLead(int $tenantId): Lead
    {
        return Lead::withoutTenant()->create([
            'name' => 'Test Lead ' . uniqid(),
            'platform' => 'whatsapp',
            'platform_user_id' => 'wa_test_' . uniqid(),
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
            'confidence' => 0.75,
            'tenant_id' => $tenantId,
        ]);
    }

    /** @test */
    public function test_ilan_created_generates_three_actions(): void
    {
        $ilan = $this->createIlan($this->tenantA->id);

        $event = new IlanCreated($ilan);
        $actions = $this->service->generateActionsFromEvent($event);

        $this->assertCount(3, $actions, 'IlanCreated should generate exactly 3 Gorev records');

        // Verify action types by baslik prefix
        $basliklar = $actions->pluck('baslik')->toArray();
        $this->assertTrue(
            collect($basliklar)->contains(fn ($b) => str_starts_with($b, 'Fotoğraf Yükle')),
            'Should contain foto yükle action'
        );
        $this->assertTrue(
            collect($basliklar)->contains(fn ($b) => str_starts_with($b, 'Açıklama Yaz')),
            'Should contain açıklama yaz action'
        );
        $this->assertTrue(
            collect($basliklar)->contains(fn ($b) => str_starts_with($b, 'Fiyatlandırma')),
            'Should contain fiyatlandırma action'
        );

        // Verify all have source_event set
        foreach ($actions as $gorev) {
            $this->assertEquals(IlanCreated::class, $gorev->source_event);
            $this->assertEquals($this->tenantA->id, $gorev->tenant_id);
            $this->assertEquals('ilan_hazirlama', $gorev->gorev_tipi);
            $this->assertEquals('bekliyor', $gorev->gorev_durumu);
        }
    }

    /** @test */
    public function test_lead_created_generates_contact_sla_action(): void
    {
        $lead = $this->createLead($this->tenantA->id);

        $event = new LeadOlusturuldu($lead);
        $actions = $this->service->generateActionsFromEvent($event);

        $this->assertCount(1, $actions, 'LeadOlusturuldu should generate exactly 1 Gorev');

        $gorev = $actions->first();
        $this->assertEquals('acil', $gorev->oncelik, 'Lead SLA action should be acil priority');
        $this->assertEquals('musteri_takibi', $gorev->gorev_tipi);
        $this->assertEquals(LeadOlusturuldu::class, $gorev->source_event);
        $this->assertEquals($lead->id, $gorev->lead_id);

        // Verify deadline is approximately +2h from now
        $this->assertNotNull($gorev->bitis_tarihi);
        $deadlineDiff = now()->diffInHours($gorev->bitis_tarihi, false);
        $this->assertGreaterThanOrEqual(1, $deadlineDiff, 'Deadline should be at least 1 hour from now');
        $this->assertLessThanOrEqual(3, $deadlineDiff, 'Deadline should be at most 3 hours from now');
    }

    /** @test */
    public function test_reservation_created_does_not_duplicate(): void
    {
        // ReservationCreatedEvent is NOT handled by ActionCenterService.
        // It's already processed by CreateOperationalTasksJob.
        // Verify that passing ReservationCreatedEvent to generateActionsFromEvent
        // returns an empty collection (no duplicate Gorev creation).

        // ReservationCreatedEvent is NOT in the ActionCenterService match statement.
        // Verify that passing it returns an empty collection (no duplicate Gorev creation).
        $event = new ReservationCreatedEvent(
            reservationId: 999,
            tenantId: $this->tenantA->id,
            ilanId: 1,
            startDate: '2026-09-10',
            endDate: '2026-09-15',
            nights: 5,
            guestName: 'Test Guest',
            guestPhone: null,
            guestEmail: null,
            guestCount: null,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: null,
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: 0,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        $actions = $this->service->generateActionsFromEvent($event);

        $this->assertCount(0, $actions, 'ReservationCreatedEvent should NOT generate actions via ActionCenterService');
    }

    /** @test */
    public function test_idempotency_same_event_does_not_create_duplicate(): void
    {
        $ilan = $this->createIlan($this->tenantA->id);

        // First dispatch — should create 3 actions
        $event = new IlanCreated($ilan);
        $actions1 = $this->service->generateActionsFromEvent($event);
        $this->assertCount(3, $actions1, 'First dispatch should create 3 actions');

        // Second dispatch — should create 0 actions (idempotent)
        $actions2 = $this->service->generateActionsFromEvent($event);
        $this->assertCount(0, $actions2, 'Second dispatch should create 0 actions (idempotent)');

        // Verify total Gorev count is still 3
        $totalGorevs = Gorev::withoutTenant()
            ->where('source_event', IlanCreated::class)
            ->where('ilan_id', $ilan->id)
            ->count();
        $this->assertEquals(3, $totalGorevs, 'Total Gorev count should remain 3 after duplicate dispatch');
    }

    /** @test */
    public function test_tenant_isolation(): void
    {
        // Create ilans for both tenants
        $ilanA = $this->createIlan($this->tenantA->id);
        $ilanB = $this->createIlan($this->tenantB->id);

        // Generate actions for tenant A
        $eventA = new IlanCreated($ilanA);
        $this->service->generateActionsFromEvent($eventA);

        // Generate actions for tenant B
        $eventB = new IlanCreated($ilanB);
        $this->service->generateActionsFromEvent($eventB);

        // Verify tenant A actions
        $tenantAGorevs = Gorev::withoutTenant()
            ->where('tenant_id', $this->tenantA->id)
            ->where('source_event', IlanCreated::class)
            ->get();
        $this->assertCount(3, $tenantAGorevs, 'Tenant A should have 3 actions');
        foreach ($tenantAGorevs as $gorev) {
            $this->assertEquals($this->tenantA->id, $gorev->tenant_id);
            $this->assertEquals($ilanA->id, $gorev->ilan_id);
        }

        // Verify tenant B actions
        $tenantBGorevs = Gorev::withoutTenant()
            ->where('tenant_id', $this->tenantB->id)
            ->where('source_event', IlanCreated::class)
            ->get();
        $this->assertCount(3, $tenantBGorevs, 'Tenant B should have 3 actions');
        foreach ($tenantBGorevs as $gorev) {
            $this->assertEquals($this->tenantB->id, $gorev->tenant_id);
            $this->assertEquals($ilanB->id, $gorev->ilan_id);
        }

        // Verify cross-tenant isolation: tenant A's ilan_id should not appear in tenant B's actions
        $tenantBWithIlanA = Gorev::withoutTenant()
            ->where('tenant_id', $this->tenantB->id)
            ->where('ilan_id', $ilanA->id)
            ->count();
        $this->assertEquals(0, $tenantBWithIlanA, 'Tenant B should not have actions for tenant A ilan');

        // Verify getActionQueue is tenant-scoped
        $queueA = $this->service->getActionQueue($this->tenantA->id);
        $this->assertEquals(3, $queueA->total(), 'Tenant A queue should have 3 items');

        $queueB = $this->service->getActionQueue($this->tenantB->id);
        $this->assertEquals(3, $queueB->total(), 'Tenant B queue should have 3 items');
    }

    /** @test */
    public function test_priority_scoring(): void
    {
        // Test that different action types get correct priority levels

        // Lead SLA → acil (score 100)
        $lead = $this->createLead($this->tenantA->id);
        $leadEvent = new LeadOlusturuldu($lead);
        $leadActions = $this->service->generateActionsFromEvent($leadEvent);
        $this->assertCount(1, $leadActions);
        $this->assertEquals('acil', $leadActions->first()->oncelik, 'Lead SLA should be acil');

        // IlanCreated → foto yükle: yuksek, açıklama: yuksek, fiyatlandırma: normal
        $ilan = $this->createIlan($this->tenantA->id);
        $ilanEvent = new IlanCreated($ilan);
        $ilanActions = $this->service->generateActionsFromEvent($ilanEvent);
        $this->assertCount(3, $ilanActions);

        $fotoAction = $ilanActions->first(fn ($g) => str_starts_with($g->baslik, 'Fotoğraf Yükle'));
        $aciklamaAction = $ilanActions->first(fn ($g) => str_starts_with($g->baslik, 'Açıklama Yaz'));
        $fiyatAction = $ilanActions->first(fn ($g) => str_starts_with($g->baslik, 'Fiyatlandırma'));

        $this->assertNotNull($fotoAction);
        $this->assertNotNull($aciklamaAction);
        $this->assertNotNull($fiyatAction);

        $this->assertEquals('yuksek', $fotoAction->oncelik, 'Foto yükle should be yuksek');
        $this->assertEquals('yuksek', $aciklamaAction->oncelik, 'Açıklama yaz should be yuksek');
        $this->assertEquals('normal', $fiyatAction->oncelik, 'Fiyatlandırma should be normal');

        // Test prioritizeActions sorting — lead SLA (acil, 100) should come first
        $allActions = $leadActions->merge($ilanActions);
        $prioritized = $this->service->prioritizeActions($allActions);

        $this->assertEquals('acil', $prioritized->first()->oncelik, 'Highest priority action should be first');
        $this->assertContains(
            $prioritized->last()->oncelik,
            ['normal', 'yuksek'],
            'Lowest priority action should be normal or yuksek'
        );
    }

    /** @test */
    public function test_ilan_price_changed_generates_action(): void
    {
        $ilan = $this->createIlan($this->tenantA->id);
        $event = new IlanPriceChanged($ilan, 5000000.0, 4500000.0, 'TRY');

        $actions = $this->service->generateActionsFromEvent($event);

        $this->assertCount(1, $actions);
        $action = $actions->first();
        $this->assertEquals(IlanPriceChanged::class, $action->source_event);
        $this->assertEquals('diger', $action->gorev_tipi);
        $this->assertEquals('normal', $action->oncelik);
        $this->assertEquals($this->tenantA->id, $action->tenant_id);
    }
}

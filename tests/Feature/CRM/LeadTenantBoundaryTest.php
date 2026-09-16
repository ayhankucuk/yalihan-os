<?php

namespace Tests\Feature\CRM;

use App\Models\Lead;
use App\Models\SaaS\Tenant;
use App\Services\CRM\LeadAuthorityService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sprint 12D: Lead Tenant Boundary Tests
 *
 * Verifies:
 * 1. Lead model has BelongsToTenant trait -> global TenantScope enforces tenant isolation
 * 2. LeadAuthorityService::registerLeadFromExternalSource creates tenant-scoped leads
 * 3. Cross-tenant lead access is blocked by TenantScope (findOrFail throws)
 * 4. Composite unique index (tenant_id, platform, platform_user_id) allows same
 *    platform_user_id in different tenants
 *
 * @group skip-until-migration-complete  <- migrations pending deploy
 */
class LeadTenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();

        // Bootstrap tables required by refreshIntelligence (called by registerLeadFromExternalSource).
        // These tables exist in production schema but their migrations are pending deploy.
        // hasTable guards prevent "already exists" errors when RefreshDatabase runs migrations first.
        if (!Schema::hasTable('ai_lead_scores')) {
            Schema::create('ai_lead_scores', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->unsignedTinyInteger('skor_degeri')->default(50);
                $table->string('skor_etiketi')->nullable();
                $table->text('skor_nedeni')->nullable();
                $table->tinyInteger('win_probability')->nullable();
                $table->json('sinyaller')->nullable();
                $table->timestamp('hesaplama_tarihi')->useCurrent();
                $table->string('model_versiyonu', 255)->default('v1.0');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lead_activities')) {
            Schema::create('lead_activities', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->string('type');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lead_messages')) {
            Schema::create('lead_messages', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->string('platform')->default('whatsapp');
                $table->string('direction', 10)->default('inbound'); // inbound|outbound
                $table->text('message')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    /** @test */
    public function no_tenant_context_returns_zero_leads(): void
    {
        Lead::factory()->forTenant($this->tenantA->id)->count(3)->create();
        Lead::factory()->forTenant($this->tenantB->id)->count(2)->create();

        $results = Lead::all();
        $this->assertCount(0, $results);
    }

    /** @test */
    public function tenant_a_sees_only_tenant_a_leads(): void
    {
        Lead::factory()->forTenant($this->tenantA->id)->count(3)->create();
        Lead::factory()->forTenant($this->tenantB->id)->count(5)->create();

        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);

        $results = Lead::all();

        $this->assertCount(3, $results);
        foreach ($results as $lead) {
            $this->assertEquals($this->tenantA->id, $lead->tenant_id);
        }
    }

    /** @test */
    public function tenant_b_sees_only_tenant_b_leads(): void
    {
        Lead::factory()->forTenant($this->tenantA->id)->count(3)->create();
        Lead::factory()->forTenant($this->tenantB->id)->count(5)->create();

        $this->app->make(TenantContextService::class)->setTenant($this->tenantB);

        $results = Lead::all();

        $this->assertCount(5, $results);
        foreach ($results as $lead) {
            $this->assertEquals($this->tenantB->id, $lead->tenant_id);
        }
    }

    /** @test */
    public function finding_tenant_b_lead_from_tenant_a_context_throws_ModelNotFoundException(): void
    {
        $leadB = Lead::factory()->forTenant($this->tenantB->id)->create();

        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Lead::findOrFail($leadB->id);
    }

    /** @test */
    public function without_tenant_scope_reveals_all_leads_across_tenants(): void
    {
        Lead::factory()->forTenant($this->tenantA->id)->count(2)->create();
        Lead::factory()->forTenant($this->tenantB->id)->count(3)->create();

        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);

        $results = Lead::withoutTenant()->get();

        $this->assertCount(5, $results);
    }

    /** @test */
    public function creating_lead_without_explicit_tenant_id_auto_assigns_from_context(): void
    {
        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);

        $savedLead = Lead::create([
            'name' => 'Auto Tenant Lead',
            'platform' => 'whatsapp',
            'platform_user_id' => 'wa_auto_tenant_' . uniqid(),
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
            'confidence' => 0.75,
            // tenant_id intentionally omitted — BelongsToTenant trait auto-sets
        ]);

        $this->assertEquals($this->tenantA->id, $savedLead->tenant_id);
    }

    /** @test */
    public function two_tenants_can_have_lead_with_same_platform_user_id(): void
    {
        $sharedPlatformUserId = 'wa_shared_' . uniqid();

        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);
        $leadA = Lead::withoutTenant()->create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Tenant A Customer',
            'platform' => 'whatsapp',
            'platform_user_id' => $sharedPlatformUserId,
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
            'confidence' => 0.7,
        ]);
        $this->assertEquals($this->tenantA->id, $leadA->tenant_id);

        $this->app->make(TenantContextService::class)->setTenant($this->tenantB);
        $leadB = Lead::withoutTenant()->create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Tenant B Customer',
            'platform' => 'whatsapp',
            'platform_user_id' => $sharedPlatformUserId,
            'crm_durumu' => Lead::CRM_NEW,
            'aktif' => true,
            'confidence' => 0.7,
        ]);
        $this->assertEquals($this->tenantB->id, $leadB->tenant_id);

        $this->assertNotEquals($leadA->id, $leadB->id);
        $this->assertNotEquals($leadA->tenant_id, $leadB->tenant_id);
    }

    /** @test */
    public function register_lead_from_external_source_assigns_correct_tenant_id(): void
    {
        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);

        /** @var LeadAuthorityService $service */
        $service = $this->app->make(LeadAuthorityService::class);

        $platformUserId = 'wa_auth_service_test_' . uniqid();
        $lead = $service->registerLeadFromExternalSource(
            platform: 'whatsapp',
            platformUserId: $platformUserId,
            messageText: 'Merhaba, daire fiyatı nedir?',
            nlpResult: [
                'intent' => 'inquiry',
                'confidence' => 0.82,
                'entities' => [
                    'property_type' => 'apartment',
                    'location_id' => 1,
                ],
            ]
        );

        $this->assertEquals($this->tenantA->id, $lead->tenant_id);
        $this->assertEquals(Lead::CRM_NEW, $lead->crm_durumu);
        $this->assertEquals('inquiry', $lead->intent);
    }

    /** @test */
    public function first_or_create_is_tenant_scoped_same_platform_user_returns_same_lead(): void
    {
        $platformUserId = 'wa_foc_' . uniqid();

        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);

        $leadA1 = Lead::firstOrCreate(
            [
                'tenant_id' => $this->tenantA->id,
                'platform' => 'whatsapp',
                'platform_user_id' => $platformUserId,
            ],
            [
                'name' => 'Tenant A Customer',
                'crm_durumu' => Lead::CRM_NEW,
                'aktif' => true,
                'confidence' => 0.7,
            ]
        );

        $leadA2 = Lead::firstOrCreate(
            [
                'tenant_id' => $this->tenantA->id,
                'platform' => 'whatsapp',
                'platform_user_id' => $platformUserId,
            ],
            [
                'name' => 'Tenant A Customer — should not overwrite',
                'crm_durumu' => Lead::CRM_REACHED,
                'aktif' => false,
                'confidence' => 0.9,
            ]
        );

        $this->assertEquals($leadA1->id, $leadA2->id);
        $this->assertEquals('Tenant A Customer', $leadA2->name);
        $this->assertEquals(Lead::CRM_NEW, $leadA2->crm_durumu);
    }

    /** @test */
    public function first_or_create_returns_different_lead_per_tenant_same_platform_user(): void
    {
        $platformUserId = 'wa_cross_foc_' . uniqid();

        $this->app->make(TenantContextService::class)->setTenant($this->tenantA);
        $leadA = Lead::firstOrCreate(
            [
                'tenant_id' => $this->tenantA->id,
                'platform' => 'whatsapp',
                'platform_user_id' => $platformUserId,
            ],
            [
                'name' => 'Customer from Tenant A',
                'crm_durumu' => Lead::CRM_NEW,
                'aktif' => true,
                'confidence' => 0.7,
            ]
        );

        $this->app->make(TenantContextService::class)->setTenant($this->tenantB);
        $leadB = Lead::firstOrCreate(
            [
                'tenant_id' => $this->tenantB->id,
                'platform' => 'whatsapp',
                'platform_user_id' => $platformUserId,
            ],
            [
                'name' => 'Customer from Tenant B',
                'crm_durumu' => Lead::CRM_NEW,
                'aktif' => true,
                'confidence' => 0.7,
            ]
        );

        $this->assertNotEquals($leadA->id, $leadB->id);
        $this->assertEquals($this->tenantA->id, $leadA->tenant_id);
        $this->assertEquals($this->tenantB->id, $leadB->tenant_id);
        $this->assertEquals('Customer from Tenant A', $leadA->name);
        $this->assertEquals('Customer from Tenant B', $leadB->name);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ai_lead_scores');
        Schema::dropIfExists('lead_activities');

        if (isset($this->tenantA)) {
            $this->app->make(TenantContextService::class)->setTenant($this->tenantA);
        }

        parent::tearDown();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Cortex;

use App\Console\Commands\Cortex\HuntOpportunitiesCommand;
use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\Lead;
use App\Models\SaaS\Tenant;
use App\Services\Cortex\OpportunityHunter;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class HuntOpportunitiesTenantTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextService $tenantContextService;
    private Tenant $tenantA;
    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContextService = app(TenantContextService::class);
        $this->tenantContextService->clearTenant();

        // Clear pre-seeded tenants to ensure strict deterministic counts
        Tenant::query()->delete();

        $this->tenantA = Tenant::create([
            'name' => 'Tenant A',
            'domain' => 'tenant-a.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Tenant B',
            'domain' => 'tenant-b.test',
            'aktiflik_durumu' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->tenantContextService->clearTenant();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * A — scheduler-like no-context invocation:
     * Proves both Tenant A and Tenant B are processed under their own context.
     */
    public function test_scheduler_invocation_executes_each_tenant_under_its_own_context(): void
    {
        $this->assertFalse($this->tenantContextService->hasTenant());

        $capturedTenants = [];

        $hunterMock = Mockery::mock(OpportunityHunter::class);
        $hunterMock->shouldReceive('scanForOpportunities')
            ->twice()
            ->andReturnUsing(function () use (&$capturedTenants) {
                $this->assertTrue($this->tenantContextService->hasTenant());
                $capturedTenants[] = $this->tenantContextService->getTenant()->id;
                return [];
            });

        $this->app->instance(OpportunityHunter::class, $hunterMock);

        $exitCode = Artisan::call('cortex:hunt');

        $this->assertEquals(0, $exitCode);
        $this->assertEquals([$this->tenantA->id, $this->tenantB->id], $capturedTenants);
        $this->assertFalse($this->tenantContextService->hasTenant());
    }

    /**
     * B — cross-tenant isolation in domain models (TenantScope verification):
     * Proves that under Tenant A context, Tenant B's Ilan and Lead records are invisible,
     * and vice versa.
     */
    public function test_cross_tenant_domain_query_isolation_under_tenant_context(): void
    {
        $ilanA = Ilan::create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Listing',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]);

        $ilanB = Ilan::create([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Listing',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]);

        $leadA = Lead::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Lead Tenant A',
            'phone' => '05001112233',
            'platform' => 'whatsapp',
            'platform_user_id' => 'lead_a_123',
        ]);

        $leadB = Lead::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Lead Tenant B',
            'phone' => '05004445566',
            'platform' => 'whatsapp',
            'platform_user_id' => 'lead_b_456',
        ]);

        // Without context: fail-closed (0 rows)
        $this->tenantContextService->clearTenant();
        $this->assertCount(0, Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->get());
        $this->assertCount(0, Lead::all());

        // Under Tenant A: only Tenant A rows visible
        $this->tenantContextService->setTenant($this->tenantA);
        $ilanlarA = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->get();
        $leadsA = Lead::all();
        $this->assertCount(1, $ilanlarA);
        $this->assertEquals($ilanA->id, $ilanlarA->first()->id);
        $this->assertCount(1, $leadsA);
        $this->assertEquals($leadA->id, $leadsA->first()->id);

        // Under Tenant B: only Tenant B rows visible
        $this->tenantContextService->setTenant($this->tenantB);
        $ilanlarB = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->get();
        $leadsB = Lead::all();
        $this->assertCount(1, $ilanlarB);
        $this->assertEquals($ilanB->id, $ilanlarB->first()->id);
        $this->assertCount(1, $leadsB);
        $this->assertEquals($leadB->id, $leadsB->first()->id);
    }

    /**
     * C — context cleanup after execution:
     * When starting with null context, context is null after command completion.
     */
    public function test_context_is_cleared_after_successful_command_execution(): void
    {
        $this->tenantContextService->clearTenant();
        $this->assertFalse($this->tenantContextService->hasTenant());

        $hunterMock = Mockery::mock(OpportunityHunter::class);
        $hunterMock->shouldReceive('scanForOpportunities')->andReturn([]);
        $this->app->instance(OpportunityHunter::class, $hunterMock);

        Artisan::call('cortex:hunt');

        $this->assertFalse($this->tenantContextService->hasTenant());
    }

    /**
     * D — exception cleanup:
     * If tenant processing throws an exception, no tenant context leaks into
     * subsequent tenants or caller context.
     */
    public function test_exception_in_tenant_processing_is_isolated_and_does_not_leak_context(): void
    {
        $this->tenantContextService->clearTenant();

        $hunterMock = Mockery::mock(OpportunityHunter::class);
        $callCount = 0;

        $hunterMock->shouldReceive('scanForOpportunities')
            ->twice()
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    $this->assertEquals($this->tenantA->id, $this->tenantContextService->getTenant()->id);
                    throw new \RuntimeException('Simulated failure on Tenant A');
                }
                // Second call must be Tenant B, with clean context
                $this->assertEquals($this->tenantB->id, $this->tenantContextService->getTenant()->id);
                return [];
            });

        $this->app->instance(OpportunityHunter::class, $hunterMock);

        $exitCode = Artisan::call('cortex:hunt');

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(2, $callCount);
        $this->assertFalse($this->tenantContextService->hasTenant());
    }

    /**
     * E — pre-existing context restoration:
     * If the command enters with a pre-existing tenant context (Tenant X),
     * that context is safely restored upon command exit.
     */
    public function test_preexisting_context_is_restored_after_command_execution(): void
    {
        $preexistingTenant = Tenant::create([
            'name' => 'Preexisting Tenant X',
            'domain' => 'tenant-x.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->tenantContextService->setTenant($preexistingTenant);
        $this->assertEquals($preexistingTenant->id, $this->tenantContextService->getTenant()->id);

        $hunterMock = Mockery::mock(OpportunityHunter::class);
        $hunterMock->shouldReceive('scanForOpportunities')->andReturn([]);
        $this->app->instance(OpportunityHunter::class, $hunterMock);

        Artisan::call('cortex:hunt');

        $this->assertTrue($this->tenantContextService->hasTenant());
        $this->assertEquals($preexistingTenant->id, $this->tenantContextService->getTenant()->id);
    }

    /**
     * F — no active tenants:
     * When there are no active tenants, the command handles it gracefully without errors.
     */
    public function test_handles_no_active_tenants_gracefully(): void
    {
        // Deactivate all tenants
        Tenant::query()->update(['aktiflik_durumu' => 0, 'status' => 'inactive']);

        $hunterMock = Mockery::mock(OpportunityHunter::class);
        $hunterMock->shouldNotReceive('scanForOpportunities');
        $this->app->instance(OpportunityHunter::class, $hunterMock);

        $exitCode = Artisan::call('cortex:hunt');

        $this->assertEquals(0, $exitCode);
        $this->assertFalse($this->tenantContextService->hasTenant());
    }
}

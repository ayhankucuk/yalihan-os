<?php

namespace Tests\Feature\Finance;

use App\Modules\Finans\Models\FinansalIslem;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FinansalIslemTenantIsolationTest
 *
 * YALIHAN_ENGINE_REAL_REMEDIATION_PILOT_01
 *
 * Tenant isolation regression: FinansalIslem → ilan → tenant_id
 * Verifies Tenant A cannot see FinansalIslem of Tenant B.
 */
class FinansalIslemTenantIsolationTest extends TestCase
{
    private Tenant $tenantA;
    private Tenant $tenantB;
    private Ilan $ilanA;
    private Ilan $ilanB;
    private Kisi $kisiA;
    private Kisi $kisiB;
    private FinansalIslem $islemA;
    private FinansalIslem $islemB;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear tenant context between tests
        app(TenantContextService::class)->clearTenant();

        // Truncate to avoid foreign key conflicts
        DB::table('finansal_islemler')->truncate();

        // Create two tenants
        $this->tenantA = Tenant::create(['name' => 'Tenant A', 'domain' => 'tenant-a.test']);
        DB::table('tenants')->where('id', $this->tenantA->id)->update(['is_active' => true]);

        $this->tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.test']);
        DB::table('tenants')->where('id', $this->tenantB->id)->update(['is_active' => true]);

        // Create ilanlar for each tenant
        $this->ilanA = Ilan::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->ilanB = Ilan::factory()->create(['tenant_id' => $this->tenantB->id]);

        // Create kisiler for each tenant
        $this->kisiA = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->kisiB = Kisi::factory()->create(['tenant_id' => $this->tenantB->id]);

        // Create finansal islemler for each tenant (no global scope in direct DB insert)
        $this->islemA = FinansalIslem::withoutGlobalScopes()->create([
            'ilan_id' => $this->ilanA->id,
            'kisi_id' => $this->kisiA->id,
            'islem_tipi' => 'gelir',
            'miktar' => 1000.00,
            'para_birimi' => 'TRY',
            'islem_statusu' => 'bekliyor',
            'tarih' => now()->toDateString(),
        ]);

        $this->islemB = FinansalIslem::withoutGlobalScopes()->create([
            'ilan_id' => $this->ilanB->id,
            'kisi_id' => $this->kisiB->id,
            'islem_tipi' => 'masraf',
            'miktar' => 500.00,
            'para_birimi' => 'TRY',
            'islem_statusu' => 'bekliyor',
            'tarih' => now()->toDateString(),
        ]);
    }

    protected function tearDown(): void
    {
        app(TenantContextService::class)->clearTenant();
        parent::tearDown();
    }

    /** @test */
    public function tenant_a_cannot_see_tenant_b_finansal_islem()
    {
        // Set Tenant A context
        app(TenantContextService::class)->setTenant($this->tenantA);

        $query = FinansalIslem::query();

        // Tenant A must see islemA
        $this->assertTrue(
            $query->where('id', $this->islemA->id)->exists(),
            'Tenant A must see its own FinansalIslem'
        );

        // Tenant A must NOT see islemB
        $this->assertFalse(
            $query->where('id', $this->islemB->id)->exists(),
            'Tenant A must NOT see Tenant B FinansalIslem'
        );

        // Tenant A should only see 1 record
        $this->assertEquals(1, FinansalIslem::count(), 'Tenant A must see exactly 1 FinansalIslem');
    }

    /** @test */
    public function tenant_b_cannot_see_tenant_a_finansal_islem()
    {
        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        // Tenant B must see islemB
        $this->assertTrue(
            FinansalIslem::where('id', $this->islemB->id)->exists(),
            'Tenant B must see its own FinansalIslem'
        );

        // Tenant B must NOT see islemA
        $this->assertFalse(
            FinansalIslem::where('id', $this->islemA->id)->exists(),
            'Tenant B must NOT see Tenant A FinansalIslem'
        );

        // Tenant B should only see 1 record
        $this->assertEquals(1, FinansalIslem::count(), 'Tenant B must see exactly 1 FinansalIslem');
    }

    /**
     * WITHOUT tenant context returns all records (fail-open for CLI/artisan).
     *
     * Production: Tenant context her zaman auth middleware'dan set edilir.
     * CLI/artisan: Migrations, seeders, bulk operations için gerekli.
     * Tenant isolation testleri tenant context SETLI senaryoları kapsar.
     *
     * @see YALIHAN_ENGINE_REAL_REMEDIATION_PILOT_01
     */
    public function without_tenant_context_returns_all_records()
    {
        // Clear tenant context (CLI/artisan scenario)
        app(TenantContextService::class)->clearTenant();

        // Without tenant context, FinansalIslem returns all (legacy behavior for CLI compatibility)
        // Production: auth middleware always sets tenant context before reaching here
        $this->assertEquals(2, FinansalIslem::count(), 'Without tenant context, FinansalIslem returns all records (CLI mode)');
    }

    /** @test */
    public function pagination_respects_tenant_isolation()
    {
        // Set Tenant A context
        app(TenantContextService::class)->setTenant($this->tenantA);

        // Paginated query should only include Tenant A records
        $paginated = FinansalIslem::query()->paginate(20);

        $this->assertEquals(1, $paginated->total(), 'Paginated query must respect tenant isolation');
        $this->assertEquals($this->islemA->id, $paginated->first()->id);
    }

    // ═══════════════════════════════════════════════════════════════
    // CREATE ISOLATION TESTS — t_b4bbbe31
    // Tenant A must NOT be able to create FinansalIslem for Tenant B's Ilan
    // ═══════════════════════════════════════════════════════════════

    // ═══════════════════════════════════════════════════════════════
    // CREATE ISOLATION TESTS — t_b4bbbe31
    // Tenant A must NOT be able to create FinansalIslem for Tenant B's Ilan
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function tenant_a_can_create_finansalislem_for_own_ilan()
    {
        // Set Tenant A context
        app(TenantContextService::class)->setTenant($this->tenantA);

        // Tenant A creates FinansalIslem for own Ilan via controller
        $controller = app(\App\Modules\Finans\Controllers\FinansalIslemController::class);
        $request = \Illuminate\Http\Request::create('/admin/finans/islemler', 'POST', [
            'ilan_id' => $this->ilanA->id,
            'kisi_id' => $this->kisiA->id,
            'islem_tipi' => 'gelir',
            'miktar' => 2500.00,
            'para_birimi' => 'TRY',
            'tarih' => now()->toDateString(),
        ]);

        $response = $controller->store($request);

        // Success: redirect to index (302) or JSON (201)
        $this->assertTrue(
            $response instanceof \Illuminate\Http\RedirectResponse
                ? $response->getStatusCode() === 302
                : $response->getStatusCode() === 201,
            'Tenant A should be able to create FinansalIslem for own Ilan'
        );
    }

    /** @test */
    public function tenant_a_cannot_create_finansalislem_for_tenant_b_ilan()
    {
        // Set Tenant A context
        app(TenantContextService::class)->setTenant($this->tenantA);

        // Verify ilanB belongs to Tenant B (sanity check)
        $this->assertEquals($this->tenantB->id, $this->ilanB->tenant_id, 'ilanB should belong to Tenant B');

        // Verify tenant context is set
        $this->assertTrue(app(TenantContextService::class)->hasTenant());
        $this->assertEquals($this->tenantA->id, app(TenantContextService::class)->getTenant()->id);

        // Tenant A tries to create FinansalIslem for Tenant B's Ilan via controller
        $controller = app(\App\Modules\Finans\Controllers\FinansalIslemController::class);
        $request = \Illuminate\Http\Request::create('/admin/finans/islemler', 'POST', [
            'ilan_id' => $this->ilanB->id, // Tenant B's Ilan
            'kisi_id' => $this->kisiA->id,
            'islem_tipi' => 'gelir',
            'miktar' => 2500.00,
            'para_birimi' => 'TRY',
            'tarih' => now()->toDateString(),
        ]);

        $response = $controller->store($request);

        // Tenant isolation enforced: should redirect with error message
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response, 'Tenant isolation should block cross-tenant create');
        // Error flash message must be set
        $this->assertTrue(
            session()->has('error') && str_contains(session()->get('error'), 'yetkiniz yok'),
            'Error flash message should be set: ' . session()->get('error')
        );
    }

    /** @test */
    public function tenant_b_cannot_create_finansalislem_for_tenant_a_ilan()
    {
        // Set Tenant B context
        app(TenantContextService::class)->setTenant($this->tenantB);

        // Tenant B tries to create FinansalIslem for Tenant A's Ilan via controller
        $controller = app(\App\Modules\Finans\Controllers\FinansalIslemController::class);
        $request = \Illuminate\Http\Request::create('/admin/finans/islemler', 'POST', [
            'ilan_id' => $this->ilanA->id, // Tenant A's Ilan
            'kisi_id' => $this->kisiB->id,
            'islem_tipi' => 'masraf',
            'miktar' => 1000.00,
            'para_birimi' => 'TRY',
            'tarih' => now()->toDateString(),
        ]);

        $response = $controller->store($request);

        // Tenant isolation enforced: should redirect with error message
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response, 'Tenant isolation should block cross-tenant create');
        // Error flash message must be set
        $this->assertTrue(
            session()->has('error') && str_contains(session()->get('error'), 'yetkiniz yok'),
            'Error flash message should be set: ' . session()->get('error')
        );
    }

    /** @test */
    public function create_without_ilan_id_succeeds_regardless_of_tenant()
    {
        // Set Tenant A context
        app(TenantContextService::class)->setTenant($this->tenantA);

        // Creating FinansalIslem without ilan_id should work
        $controller = app(\App\Modules\Finans\Controllers\FinansalIslemController::class);
        $request = \Illuminate\Http\Request::create('/admin/finans/islemler', 'POST', [
            'kisi_id' => $this->kisiA->id,
            'islem_tipi' => 'gelir',
            'miktar' => 500.00,
            'para_birimi' => 'TRY',
            'tarih' => now()->toDateString(),
        ]);

        $response = $controller->store($request);

        // Success: redirect (302) or JSON (201)
        $this->assertTrue(
            $response instanceof \Illuminate\Http\RedirectResponse
                ? $response->getStatusCode() === 302
                : $response->getStatusCode() === 201,
            'Creating FinansalIslem without ilan_id should succeed'
        );
    }
}

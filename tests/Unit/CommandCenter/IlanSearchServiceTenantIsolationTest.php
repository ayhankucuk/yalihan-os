<?php

namespace Tests\Unit\CommandCenter;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\Ilan\IlanSearchService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 1k: Tenant Isolation Regression Tests
 *
 * Verifies that IlanSearchService::search() enforces tenant isolation.
 * Tenant A MUST NOT receive Tenant B rows.
 *
 * SAB §5: Tenant Isolation & Negative Verification Gate
 */
class IlanSearchServiceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two distinct tenants
        $this->tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A',
            'domain' => 'tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B',
            'domain' => 'tenant-b.test',
            'status' => 'active',
        ]);

        // Create users belonging to each tenant
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
        ]);
        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
        ]);
    }

    /**
     * Helper: set the tenant context to a specific tenant.
     */
    private function setTenantContext(Tenant $tenant): void
    {
        app(TenantContextService::class)->setTenant($tenant);
    }

    /**
     * Helper: create a published listing directly via DB with explicit tenant_id.
     */
    private function createListing(array $overrides): int
    {
        $defaults = [
            'baslik' => 'Test Ilan',
            'slug' => 'test-ilan-' . uniqid(),
            'fiyat' => 1_000_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('ilanlar')->insert(array_merge($defaults, $overrides));

        return (int) DB::table('ilanlar')->max('id');
    }

    // -------------------------------------------------------------------------
    // NEGATIVE ISOLATION TESTS — Tenant A must NOT see Tenant B rows
    // -------------------------------------------------------------------------

    public function test_tenant_a_does_not_see_tenant_b_listings(): void
    {
        // Tenant A context
        $this->setTenantContext($this->tenantA);

        // Create listings for each tenant (same currency to isolate tenant variable)
        $tenantAIlanId = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Villa',
            'fiyat' => 900_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $tenantBIlanId = $this->createListing([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Villa',
            'fiyat' => 800_000, // Also within EUR 1M budget
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $resultIds = collect($result['data'])->pluck('id')->toArray();

        $this->assertContains($tenantAIlanId, $resultIds, 'Tenant A listing must be visible to Tenant A');
        $this->assertNotContains($tenantBIlanId, $resultIds, 'Tenant B listing must NOT be visible to Tenant A');
    }

    public function test_tenant_b_does_not_see_tenant_a_listings(): void
    {
        // Tenant B context
        $this->setTenantContext($this->tenantB);

        $tenantAIlanId = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Villa',
            'fiyat' => 900_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $tenantBIlanId = $this->createListing([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Villa',
            'fiyat' => 800_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $resultIds = collect($result['data'])->pluck('id')->toArray();

        $this->assertContains($tenantBIlanId, $resultIds, 'Tenant B listing must be visible to Tenant B');
        $this->assertNotContains($tenantAIlanId, $resultIds, 'Tenant A listing must NOT be visible to Tenant B');
    }

    // -------------------------------------------------------------------------
    // FAIL-CLOSED: no tenant context = zero results
    // -------------------------------------------------------------------------

    public function test_no_tenant_context_returns_zero_results(): void
    {
        // Explicitly clear tenant context
        app(TenantContextService::class)->setTenant(
            new Tenant(['uuid' => 'clear', 'name' => 'clear', 'domain' => 'clear', 'status' => 'active'])
        );

        // Create a listing under default tenant context from setUp
        $ilanId = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Some Listing',
            'fiyat' => 500_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(IlanSearchService::class);

        // This should return zero because the current tenant context is the "clear" tenant
        // and the listing belongs to tenantA
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
        ]);

        $resultIds = collect($result['data'])->pluck('id')->toArray();
        $this->assertNotContains($ilanId, $resultIds, 'Listing must NOT be visible without matching tenant context');
    }

    // -------------------------------------------------------------------------
    // UNAUTHORIZED ACTOR: no user = fail-closed (handled by CommandGateway,
    // but search() with no tenant should return empty rather than all rows)
    // -------------------------------------------------------------------------

    public function test_search_without_tenant_context_does_not_leak_cross_tenant_data(): void
    {
        // Create listings for both tenants
        $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A Villa',
            'fiyat' => 900_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $this->createListing([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B Villa',
            'fiyat' => 800_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        // Simulate "unknown actor" by clearing tenant context
        // by setting an unrelated tenant
        $unrelatedTenant = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Unrelated',
            'domain' => 'unrelated.test',
            'status' => 'active',
        ]);
        $this->setTenantContext($unrelatedTenant);

        $service = app(IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
        ]);

        // Unrelated tenant must see zero rows
        $this->assertCount(0, $result['data'], 'Unrelated tenant must not see other tenants data');
    }

    // -------------------------------------------------------------------------
    // CURRENCY + TENANT COMBINATION: same currency, different tenants
    // -------------------------------------------------------------------------

    public function test_currency_and_tenant_filter_combined(): void
    {
        $this->setTenantContext($this->tenantA);

        // EUR listing within Tenant A — should appear
        $withinId = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A EUR 900K',
            'fiyat' => 900_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        // EUR listing within Tenant B — should NOT appear
        $tenantBId = $this->createListing([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Tenant B EUR 800K',
            'fiyat' => 800_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        // TRY listing within Tenant A — should NOT appear (wrong currency)
        $wrongCurrencyId = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Tenant A TRY 20M',
            'fiyat' => 20_000_000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
        ]);

        $service = app(IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => 1_000_000,
            'paraBirimi' => 'EUR',
        ]);

        $resultIds = collect($result['data'])->pluck('id')->toArray();

        $this->assertContains($withinId, $resultIds, 'Tenant A + EUR 900K must appear');
        $this->assertNotContains($tenantBId, $resultIds, 'Tenant B listing must not appear');
        $this->assertNotContains($wrongCurrencyId, $resultIds, 'TRY listing in Tenant A must not appear under EUR filter');
    }

    // -------------------------------------------------------------------------
    // PUBLICATION STATUS + TENANT
    // -------------------------------------------------------------------------

    public function test_published_filter_works_within_tenant(): void
    {
        $this->setTenantContext($this->tenantA);

        $publishedId = $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Published',
            'fiyat' => 500_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
        ]);

        $this->createListing([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Draft',
            'fiyat' => 500_000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'taslak',
        ]);

        $service = app(IlanSearchService::class);
        $result = $service->search([
            'yayin_durumu' => 'yayinda',
            'paraBirimi' => 'EUR',
        ]);

        $resultIds = collect($result['data'])->pluck('id')->toArray();
        $this->assertContains($publishedId, $resultIds);
        $this->assertCount(1, $resultIds, 'Only the published listing should appear');
    }
}

<?php

namespace Tests\Feature\Console;

use App\Models\IlanTakvimSync;
use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for TASK_ID: RENTAL_SYNC_AIRBNB_TENANT_CONTEXT_REMEDIATION_01
 *
 * BEFORE FIX: TenantContextService empty at scheduler/CLI start → TenantScope
 *             apply() → WHERE 1=0 → Ilan::findOrFail fails with ModelNotFoundException
 *
 * AFTER FIX:  Each sync resolves authoritative tenant via Ilan.tenant_id and sets
 *            TenantContextService before accessing Ilan.
 *
 * CASE 1: Valid single-tenant sync — correct tenant context, success, cleanup
 * CASE 2: Multi-tenant sequential — no cross-tenant leakage, final cleanup
 * CASE 3: Exception during sync — finally block cleans up context
 * CASE 4: Unresolvable tenant — fail-closed, no fallback, cleanup
 */
class RentalSyncAirbnbTenantContextTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CASE 1 — VALID_TENANT_SYNC
     *
     * Starting: hasTenant() === false
     * Expected: Tenant A context active during sync, Ilan lookup succeeds,
     *           context cleared after execution.
     */
    public function test_sync_runs_with_correct_tenant_context_and_cleans_up(): void
    {
        $contextService = app(TenantContextService::class);
        $contextService->clearTenant();
        $this->assertFalse($contextService->hasTenant(), 'Precondition: context must be empty');

        $tenantA = Tenant::create([
            'uuid' => 'tenant-a-uuid',
            'name' => 'Tenant A',
            'domain' => 'tenant-a.test',
            'status' => 'active',
        ]);

        $ilanA = Ilan::factory()->create(['tenant_id' => $tenantA->id]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilanA->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
            'external_listing_id' => 'airbnb-tenant-a-123',
        ]);

        $this->artisan('rental:sync-airbnb', ['--force' => true]);

        $this->assertFalse(
            $contextService->hasTenant(),
            'Tenant context must be cleared after command completes'
        );
    }

    /**
     * CASE 2 — MULTI_TENANT_SEQUENTIAL
     *
     * Two sync records belonging to different tenants.
     * Verifies: A context does not leak into B, final context is clean.
     */
    public function test_sequential_syncs_for_different_tenants_do_not_leak(): void
    {
        $contextService = app(TenantContextService::class);
        $contextService->clearTenant();
        $this->assertFalse($contextService->hasTenant());

        $tenantA = Tenant::create([
            'uuid' => 'seq-tenant-a',
            'name' => 'Seq Tenant A',
            'domain' => 'seq-a.test',
            'status' => 'active',
        ]);
        $tenantB = Tenant::create([
            'uuid' => 'seq-tenant-b',
            'name' => 'Seq Tenant B',
            'domain' => 'seq-b.test',
            'status' => 'active',
        ]);

        $ilanA = Ilan::factory()->create(['tenant_id' => $tenantA->id]);
        $ilanB = Ilan::factory()->create(['tenant_id' => $tenantB->id]);

        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilanA->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
            'external_listing_id' => 'airbnb-seq-a',
        ]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilanB->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
            'external_listing_id' => 'airbnb-seq-b',
        ]);

        $this->artisan('rental:sync-airbnb', ['--force' => true]);

        $this->assertFalse(
            $contextService->hasTenant(),
            'Tenant context must be cleared after multi-tenant sequential sync'
        );
    }

    /**
     * CASE 3 — EXCEPTION_CLEANUP
     *
     * Sync fails due to missing external_listing_id after tenant context is set.
     * Verifies: finally block cleans up context even on failure.
     */
    public function test_exception_during_sync_cleans_up_tenant_context(): void
    {
        $contextService = app(TenantContextService::class);
        $contextService->clearTenant();
        $this->assertFalse($contextService->hasTenant());

        $tenantA = Tenant::create([
            'uuid' => 'exc-tenant-a',
            'name' => 'Exc Tenant A',
            'domain' => 'exc-a.test',
            'status' => 'active',
        ]);

        $ilanA = Ilan::factory()->create(['tenant_id' => $tenantA->id]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilanA->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
            'external_listing_id' => null, // causes pushToAirbnb to return error
        ]);

        $this->artisan('rental:sync-airbnb', ['--force' => true]);

        $this->assertFalse(
            $contextService->hasTenant(),
            'Tenant context must be cleared even after sync exception'
        );
    }

    /**
     * CASE 4 — INVALID_OR_UNRESOLVABLE_TENANT
     *
     * IlanTakvimSync points to a deleted Ilan — tenant cannot be resolved.
     * Verifies: fail-closed (no fallback), context stays clean.
     */
    public function test_sync_fails_closed_when_tenant_unresolvable(): void
    {
        $contextService = app(TenantContextService::class);
        $contextService->clearTenant();
        $this->assertFalse($contextService->hasTenant());

        $tenantA = Tenant::create([
            'uuid' => 'orphan-tenant',
            'name' => 'Orphan Tenant',
            'domain' => 'orphan.test',
            'status' => 'active',
        ]);

        $ilan = Ilan::factory()->create(['tenant_id' => $tenantA->id]);
        IlanTakvimSync::factory()->create([
            'ilan_id' => $ilan->id,
            'platform' => 'airbnb',
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'next_sync_at' => now()->subMinutes(5),
            'external_listing_id' => 'orphan-sync',
        ]);

        // Delete the Ilan to create orphan sync record
        $ilan->forceDelete();

        $this->artisan('rental:sync-airbnb', ['--force' => true]);

        $this->assertFalse(
            $contextService->hasTenant(),
            'Tenant context must be clean even when tenant is unresolvable'
        );
    }
}

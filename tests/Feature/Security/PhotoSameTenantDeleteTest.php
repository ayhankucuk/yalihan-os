<?php

namespace Tests\Feature\Security;

use App\Models\Ilan;
use App\Models\Photo;
use App\Models\Role;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Same-tenant legitimate photo deletion + cross-tenant delete block.
 *
 * Canonical tenant isolation test suite (PhotoTenantIsolationTest) covers
 * cross-tenant read/list scenarios. This file covers DELETE scenarios.
 *
 * Setup pattern:
 * - TenantContextService::setTenant() requires SaaS\Tenant (type hint)
 * - Photo::withoutTenant() bypasses TenantScope for DB existence checks
 * - BelongsToTenant trait auto-sets tenant_id on Photo::create()
 * - Photo.php method_exists guard ensures files are deleted (no SoftDeletes)
 */
class PhotoSameTenantDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $adminA;
    protected User $adminB;
    protected Ilan $ilanA;
    protected Photo $photoA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'same-tenant-a.test'],
            ['name' => 'Tenant A', 'durum' => 'active']
        );
        $this->tenantB = Tenant::firstOrCreate(
            ['domain' => 'same-tenant-b.test'],
            ['name' => 'Tenant B', 'durum' => 'active']
        );

        $this->ilanA = Ilan::factory()->create(['tenant_id' => $this->tenantA->id]);

        // Create photo WITH tenant context (BelongsToTenant auto-sets tenant_id)
        $tCtx = app(TenantContextService::class);
        $tCtx->setTenant($this->tenantA);

        $this->photoA = Photo::create([
            'ilan_id' => $this->ilanA->id,
            'dosya_yolu' => 'photos/same-tenant-a.jpg',
            'dosya_adi' => 'same-tenant-a.jpg',
            'kapak_fotografi' => true,
            'display_order' => 1,
        ]);
        $tCtx->clearTenant();

        // Verify photo has correct tenant_id
        $this->assertEquals(
            $this->tenantA->id,
            $this->photoA->tenant_id,
            'Photo must have Tenant A tenant_id'
        );

        // Create admin users
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

        $this->adminA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->adminA->roles()->attach($adminRole);

        $this->adminB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
        $this->adminB->roles()->attach($adminRole);
    }

    /**
     * Tenant A admin can delete their own photo.
     * Expected: HTTP 200/204 + photo gone from DB.
     */
    public function test_tenant_a_admin_can_delete_own_photo(): void
    {
        // Set Tenant A context (SetTenantContext middleware behavior)
        $tCtx = app(TenantContextService::class);
        $tCtx->setTenant($this->tenantA);

        // Verify photo exists before delete
        $photoExistsBefore = Photo::withoutTenant()->find($this->photoA->id) !== null;
        $this->assertTrue($photoExistsBefore, 'Photo must exist before delete');

        // Tenant A admin deletes own photo
        $response = $this->actingAs($this->adminA, 'sanctum')
            ->deleteJson("/api/v1/admin/photos/{$this->photoA->id}");

        $tCtx->clearTenant();

        // HTTP response must be success
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 204]),
            "Tenant A should delete own photo successfully, got: {$response->getStatusCode()}"
        );

        // Photo must be gone from DB
        $photoExistsAfter = Photo::withoutTenant()->find($this->photoA->id) !== null;
        $this->assertFalse(
            $photoExistsAfter,
            'Photo must be deleted from DB after Tenant A action'
        );
    }

    /**
     * Tenant B admin cannot delete Tenant A photo (cross-tenant isolation).
     * Expected: HTTP 4xx + photo still in DB.
     */
    public function test_tenant_b_cannot_delete_tenant_a_photo(): void
    {
        // Set Tenant B context
        $tCtx = app(TenantContextService::class);
        $tCtx->setTenant($this->tenantB);

        // Verify photo exists before (bypass TenantScope)
        $photoExistsBefore = Photo::withoutTenant()->find($this->photoA->id) !== null;
        $this->assertTrue($photoExistsBefore, 'Photo must exist before test');

        // Tenant B admin attempts to delete Tenant A photo
        $response = $this->actingAs($this->adminB, 'sanctum')
            ->deleteJson("/api/v1/admin/photos/{$this->photoA->id}");

        $tCtx->clearTenant();

        $status = $response->getStatusCode();

        // HTTP response must NOT be success
        $this->assertNotEquals(200, $status, 'Cross-tenant delete should not return 200');
        $this->assertNotEquals(204, $status, 'Cross-tenant delete should not return 204');

        // Most importantly: photo must still exist in DB
        $photoExistsAfter = Photo::withoutTenant()->find($this->photoA->id) !== null;
        $this->assertTrue(
            $photoExistsAfter,
            'CRITICAL: Tenant A photo must survive Tenant B delete attempt. ' .
            'If this fails, cross-tenant delete vulnerability exists.'
        );
    }
}

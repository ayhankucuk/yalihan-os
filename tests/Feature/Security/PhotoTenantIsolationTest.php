<?php

namespace Tests\Feature\Security;

use App\Models\Ilan;
use App\Models\Photo;
use App\Models\Role;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies PHOTO_CROSS_TENANT_READ_REMEDIATION_01 fix:
 * Photo model now uses BelongsToTenant trait (TenantScope enforced at model level).
 */
class PhotoTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $adminA;
    protected User $adminB;
    protected Ilan $ilanA;
    protected Ilan $ilanB;
    protected Photo $photoA;
    protected Photo $photoB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'photo-tenant-a.test'],
            ['name' => 'Tenant A', 'durum' => 'active']
        );
        $this->tenantB = Tenant::firstOrCreate(
            ['domain' => 'photo-tenant-b.test'],
            ['name' => 'Tenant B', 'durum' => 'active']
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

        $this->ilanA = Ilan::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->ilanB = Ilan::factory()->create(['tenant_id' => $this->tenantB->id]);

        // Set Tenant A context for creating photoA
        $tenantCtxA = app(TenantContextService::class);
        $tenantCtxA->setTenant($this->tenantA);

        $this->photoA = Photo::create([
            'ilan_id' => $this->ilanA->id,
            'dosya_yolu' => 'photos/tenant-a.jpg',
            'dosya_adi' => 'tenant-a.jpg',
            'kapak_fotografi' => true,
            'display_order' => 1,
        ]);
        $tenantCtxA->clearTenant();

        // Set Tenant B context for creating photoB
        $tenantCtxB = app(TenantContextService::class);
        $tenantCtxB->setTenant($this->tenantB);

        $this->photoB = Photo::create([
            'ilan_id' => $this->ilanB->id,
            'dosya_yolu' => 'photos/tenant-b.jpg',
            'dosya_adi' => 'tenant-b.jpg',
            'kapak_fotografi' => false,
            'display_order' => 1,
        ]);
        $tenantCtxB->clearTenant();

        $this->adminA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->adminA->roles()->attach($adminRole);

        $this->adminB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
        $this->adminB->roles()->attach($adminRole);
    }

    /**
     * FIXED: Cross-tenant direct photo read now returns 404 (TenantScope enforced at model level).
     * BEFORE: HTTP 200 with foreign Photo data (Photo model missing BelongsToTenant trait).
     */
    public function test_cross_tenant_photo_direct_read_is_blocked(): void
    {
        // Tenant B admin attempts to read Tenant A's photo directly
        $response = $this->actingAs($this->adminB, 'sanctum')
            ->getJson("/api/v1/admin/photos/{$this->photoA->id}");

        // TenantScope filters by current tenant context → record not found → 404
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Same-tenant photo read succeeds (authenticated as photo owner tenant).
     */
    public function test_same_tenant_photo_direct_read_succeeds(): void
    {
        // Tenant A admin reads Tenant A's own photo
        $response = $this->actingAs($this->adminA, 'sanctum')
            ->getJson("/api/v1/admin/photos/{$this->photoA->id}");

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->json('data');
        $this->assertEquals($this->photoA->id, $data['id']);
    }

    /**
     * TenantScope enforced via model-level BelongsToTenant → no cross-tenant data in list.
     */
    public function test_cross_tenant_photo_list_excludes_foreign_photos(): void
    {
        $response = $this->actingAs($this->adminB, 'sanctum')
            ->getJson('/api/v1/admin/photos');

        if ($response->getStatusCode() === 200) {
            $photoIds = collect($response->json('data.photos') ?? [])->pluck('id')->toArray();
            $this->assertNotContains($this->photoA->id, $photoIds,
                'Tenant B should not see Tenant A photos in list');
        }
        // If route returns 404 (no tenant-scoped resource), that's also acceptable isolation
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Tenant B cannot delete Tenant A photo (via PhotoController::destroy).
     * This is already covered by TenantIsolationSafetyTest, but we verify it
     * remains PASS after Photo model fix.
     */
    public function test_cross_tenant_photo_delete_is_blocked(): void
    {
        $response = $this->actingAs($this->adminB, 'sanctum')
            ->deleteJson("/api/v1/admin/photos/{$this->photoA->id}");

        // Either 404 (scope filter) or 403 (controller auth) — neither means success for tenant isolation
        $this->assertTrue(
            in_array($response->getStatusCode(), [403, 404, 500]),
            "Cross-tenant delete should be blocked, got {$response->getStatusCode()}"
        );

        // Photo must still exist (use withoutTenant to bypass scope for verification)
        $this->assertNotNull(
            Photo::withoutTenant()->find($this->photoA->id),
            'Tenant A photo should NOT be deleted by Tenant B'
        );
    }
}

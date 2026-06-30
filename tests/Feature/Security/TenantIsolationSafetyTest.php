<?php

namespace Tests\Feature\Security;

use App\Models\Ilan;
use App\Models\Photo;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected User $userA;

    protected Tenant $tenantB;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Tenant A and User A
        $this->tenantA = Tenant::firstOrCreate(['slug' => 'tenant-a'], ['name' => 'Tenant A']);
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'aktiflik_durumu' => 1,
        ]);

        // Create Tenant B and User B
        $this->tenantB = Tenant::firstOrCreate(['slug' => 'tenant-b'], ['name' => 'Tenant B']);
        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'aktiflik_durumu' => 1,
        ]);
    }

    /** @test */
    public function tenant_a_cannot_read_tenant_b_listing_details_via_cortex_api()
    {
        // Setup: Create a listing for Tenant B
        $ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'danisman_id' => $this->userB->id,
        ]);

        // Act: Request full details as Tenant A
        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson("/api/v1/cortex/ilan/{$ilanB->id}/full-details");

        // Assert: Access is denied
        $response->assertStatus(404); // Or 403 Forbidden based on isolation model
    }

    /** @test */
    public function tenant_a_cannot_view_tenant_b_listing_via_v2_show()
    {
        // Setup: Create a published listing for Tenant B
        $ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'danisman_id' => $this->userB->id,
        ]);

        // Act: Attempt to view as Tenant A
        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson("/api/v1/ilanlar/{$ilanB->id}");

        // Assert: Access is denied — IDOR protection
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_can_view_own_listing_via_v2_show()
    {
        // Setup: Create a listing for Tenant A
        $ilanA = Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'danisman_id' => $this->userA->id,
        ]);

        // Act: View own listing as Tenant A
        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson("/api/v1/ilanlar/{$ilanA->id}");

        // Assert: Access granted
        $response->assertStatus(200);
    }

    /** @test */
    public function tenant_a_cannot_update_tenant_b_listing()
    {
        // Setup: Create a listing for Tenant B
        $ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'danisman_id' => $this->userB->id,
        ]);

        // Act: Attempt to update as Tenant A
        $response = $this->actingAs($this->userA, 'sanctum')
            ->putJson("/api/v1/ilanlar/{$ilanB->id}", [
                'baslik' => 'Hacked Baslik',
            ]);

        // Assert: Access denied
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_cannot_delete_tenant_b_listing()
    {
        // Setup: Create a listing for Tenant B
        $ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'danisman_id' => $this->userB->id,
        ]);

        // Act: Attempt to delete as Tenant A
        $response = $this->actingAs($this->userA, 'sanctum')
            ->deleteJson("/api/v1/ilanlar/{$ilanB->id}");

        // Assert: Access denied
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_a_cannot_delete_tenant_b_photo()
    {
        // Setup: Create a listing for Tenant B and a photo
        $ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'danisman_id' => $this->userB->id,
        ]);

        $photoB = Photo::create([
            'ilan_id' => $ilanB->id,
            'dosya_yolu' => 'photos/test.png',
            'dosya_adi' => 'test.png',
            'sira' => 1,
            'aktiflik_durumu' => 1,
        ]);

        // Mock admin role and user checks for web-auth route
        $userA = \Mockery::mock($this->userA)->makePartial();
        $userA->shouldReceive('isAdmin')->andReturn(true)->byDefault();
        $userA->shouldReceive('hasRole')->andReturn(true)->byDefault();

        // Act: Attempt to delete Tenant B's photo as Tenant A
        $response = $this->actingAs($userA)
            ->deleteJson("/api/v1/admin/photos/{$photoB->id}");

        // Assert: Access denied
        $response->assertStatus(403);
    }
}

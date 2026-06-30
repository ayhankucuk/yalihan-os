<?php

namespace Tests\Feature\Admin;

use App\Models\Kisi;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Week 2 Day 3: Controller Authorization Hardening Validation
 *
 * Success Metric: "Can a danışman access or modify another danışman's data via Controller?"
 */
class KisiControllerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $danisman1;
    protected User $danisman2;
    protected ?Role $adminRole = null;
    protected ?Role $danismanRole = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable role middleware to test defense-in-depth (policy level)
        // This allows non-admins to reach the controller, where they SHOULD be blocked by authorize()
        $this->withoutMiddleware([\App\Http\Middleware\RoleMiddleware::class]);

        // Mock AI services to prevent network calls/errors
        $this->mock(\App\Services\AI\YalihanCortex::class, function ($mock) {
            $mock->shouldReceive('requestCustomerRecommendations')->andReturn([]);
        });
        $this->mock(\App\Services\CRMIntelligenceService::class, function ($mock) {
            $mock->shouldReceive('calculateLeadPriority')->andReturn(50);
            $mock->shouldReceive('getRecommendedListings')->andReturn([]);
        });

        // Create roles manually (factories might not exist for modules, and mass assignment might be restricted)
        $this->adminRole = Role::where('name', 'admin')->first();
        if (!$this->adminRole) {
            $this->adminRole = new Role();
            $this->adminRole->name = 'admin';
            $this->adminRole->save();
        }

        $this->danismanRole = Role::where('name', 'danisman')->first();
        if (!$this->danismanRole) {
            $this->danismanRole = new Role();
            $this->danismanRole->name = 'danisman';
            $this->danismanRole->save();
        }

        // Create users
        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id, 'name' => 'Admin User']);
        $this->danisman1 = User::factory()->create(['role_id' => $this->danismanRole->id, 'name' => 'Danisman 1']);
        $this->danisman2 = User::factory()->create(['role_id' => $this->danismanRole->id, 'name' => 'Danisman 2']);
    }

    /** @test */
    public function index_respects_policy_and_scoping()
    {
        // Arrange: Danisman 1 has 5 kisiler, Danisman 2 has 3 kisiler
        Kisi::factory()->count(5)->create(['danisman_id' => $this->danisman1->id]);
        Kisi::factory()->count(3)->create(['danisman_id' => $this->danisman2->id]);

        // Act & Assert: Danisman 1 sees only their own
        $this->actingAs($this->danisman1)
            ->get(route('admin.kisiler.index'))
            ->assertStatus(200)
            ->assertViewHas('kisiler', function ($kisiler) {
                return $kisiler->count() === 5;
            });

        // Act & Assert: Admin sees all
        $this->actingAs($this->admin)
            ->get(route('admin.kisiler.index'))
            ->assertStatus(200)
            ->assertViewHas('kisiler', function ($kisiler) {
                return $kisiler->total() === 8;
            });
    }

    /** @test */
    public function show_blocks_cross_tenant_access()
    {
        // Arrange: Kisi belonging to Danisman 2
        $kisi = Kisi::factory()->create(['danisman_id' => $this->danisman2->id]);

        // Act & Assert: Danisman 1 tries to view Danisman 2's kisi
        // Should return 404/403 due to repository scoping + policy
        $this->actingAs($this->danisman1)
            ->getJson(route('admin.kisiler.show', ['kisiId' => $kisi->id]))
            ->assertStatus(404); // Repository findOrFail/resolve throws 404 if not found in scope

        $this->actingAs($this->danisman2)
            ->getJson(route('admin.kisiler.show', ['kisiId' => $kisi->id]))
            ->assertStatus(200);

        // Act & Assert: Admin can view any kisi
        $this->actingAs($this->admin)
            ->getJson(route('admin.kisiler.show', ['kisiId' => $kisi->id]))
            ->assertStatus(200);
    }

    /** @test */
    public function edit_blocks_cross_tenant_access()
    {
        $kisi = Kisi::factory()->create(['danisman_id' => $this->danisman2->id]);

        $this->actingAs($this->danisman1)
            ->getJson(route('admin.kisiler.edit', ['kisiId' => $kisi->id]))
            ->assertStatus(404);

        $this->actingAs($this->admin)
            ->getJson(route('admin.kisiler.edit', ['kisiId' => $kisi->id]))
            ->assertStatus(200);
    }

    /** @test */
    public function update_blocks_cross_tenant_access()
    {
        $kisi = Kisi::factory()->create(['danisman_id' => $this->danisman2->id, 'ad' => 'Old Name']);

        // Act & Assert: Danisman 1 tries to update Danisman 2's kisi
        $this->actingAs($this->danisman1)
            ->putJson(route('admin.kisiler.update', ['kisiId' => $kisi->id]), [
                'ad' => 'New Name',
                'soyad' => 'Test',
                'kisi_tipi' => 'alici',
                'aktiflik_durumu' => 1,
                'crm_surec_asamasi' => 'yeni'
            ])
            ->assertStatus(404);

        $this->assertEquals('Old Name', $kisi->fresh()->ad);
    }

    /** @test */
    public function destroy_blocks_cross_tenant_access()
    {
        $kisi = Kisi::factory()->create(['danisman_id' => $this->danisman2->id]);

        $this->actingAs($this->danisman1)
            ->deleteJson(route('admin.kisiler.destroy', ['kisiId' => $kisi->id]))
            ->assertStatus(404);

        $this->assertFalse($kisi->fresh()->trashed());

        $this->actingAs($this->danisman2)
            ->deleteJson(route('admin.kisiler.destroy', ['kisiId' => $kisi->id]))
            ->assertStatus(200); // JSON response returns 200 on success

        $this->assertTrue($kisi->fresh()->trashed());
    }

    /** @test */
    public function bulk_action_blocks_unauthorized_ids()
    {
        // Arrange: Kisi 1 (Danisman 1), Kisi 2 (Danisman 2)
        $kisi1 = Kisi::factory()->create(['danisman_id' => $this->danisman1->id]);
        $kisi2 = Kisi::factory()->create(['danisman_id' => $this->danisman2->id]);

        // Act & Assert: Danisman 1 tries to delete BOTH (one is not theirs)
        // Should fail due to per-record check in controller
        $this->actingAs($this->danisman1)
            ->post(route('admin.kisiler.kisi.bulk.action'), [
                'action' => 'delete',
                'ids' => [$kisi1->id, $kisi2->id]
            ])
            ->assertStatus(403);

        $this->assertFalse($kisi1->fresh()->trashed());
        $this->assertFalse($kisi2->fresh()->trashed());

        // Act & Assert: Admin can delete both
        $this->actingAs($this->admin)
            ->post(route('admin.kisiler.kisi.bulk.action'), [
                'action' => 'delete',
                'ids' => [$kisi1->id, $kisi2->id]
            ])
            ->assertStatus(200);

        $this->assertTrue($kisi1->fresh()->trashed());
        $this->assertTrue($kisi2->fresh()->trashed());
    }

    /** @test */
    public function search_returns_only_owned_results()
    {
        Kisi::factory()->create(['danisman_id' => $this->danisman1->id, 'ad' => 'SedatSearch']);
        Kisi::factory()->create(['danisman_id' => $this->danisman2->id, 'ad' => 'SedatSearch']);

        $this->actingAs($this->danisman1)
            ->get(route('admin.kisiler.search', ['q' => 'SedatSearch']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'items');
    }
}

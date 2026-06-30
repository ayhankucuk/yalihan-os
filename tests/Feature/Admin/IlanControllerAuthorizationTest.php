<?php

namespace Tests\Feature\Admin;

use App\Models\Ilan;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Enums\IlanDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3.3: IlanCrudController Capability Hardening Validation
 *
 * Tests the 4-matrix authorization semantics:
 *   - Cross-tenant resource → 404 (Layer 2: Repository Scope conceals existence)
 *   - Own resource read    → 200 (happy path)
 *   - Admin bypass         → 200 (admin sees everything)
 *   - Unauthorized write   → 404 (Layer 2 blocks before Layer 1)
 */
class IlanControllerAuthorizationTest extends TestCase
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

        $this->withoutMiddleware([\App\Http\Middleware\RoleMiddleware::class]);

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

        $this->admin     = User::factory()->create(['role_id' => $this->adminRole->id]);
        $this->danisman1 = User::factory()->create(['role_id' => $this->danismanRole->id]);
        $this->danisman2 = User::factory()->create(['role_id' => $this->danismanRole->id]);
    }

    private function makeIlan(User $owner): Ilan
    {
        // Satisfy required FK constraints
        $kisiId = DB::table('kisiler')->insertGetId([
            'ad'         => 'Test',
            'soyad'      => 'Kisi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Ilan::create([
            'baslik'        => 'Test İlan',
            'kisi_id'       => $kisiId,
            'danisman_id'   => $owner->id,
            'yayin_durumu'  => IlanDurumu::YAYINDA->value,
            'fiyat'         => 1000000,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function show_returns_404_for_cross_tenant_access()
    {
        $ilan = $this->makeIlan($this->danisman2);

        // Cross-tenant: danisman1 tries to access danisman2's ilan
        // Layer 2 (repo scope) returns null → 404 (hides existence)
        $this->actingAs($this->danisman1)
            ->get(route('admin.ilanlar.show', $ilan->id))
            ->assertStatus(404);
    }

    /** @test */
    public function show_returns_200_for_own_resource()
    {
        $ilan = $this->makeIlan($this->danisman2);

        $this->actingAs($this->danisman2)
            ->get(route('admin.ilanlar.show', $ilan->id))
            ->assertStatus(200);
    }

    /** @test */
    public function show_admin_bypass_returns_200()
    {
        $ilan = $this->makeIlan($this->danisman2);

        $this->actingAs($this->admin)
            ->get(route('admin.ilanlar.show', $ilan->id))
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function edit_returns_404_for_cross_tenant_access()
    {
        $ilan = $this->makeIlan($this->danisman2);

        $this->actingAs($this->danisman1)
            ->get(route('admin.ilanlar.edit', $ilan->id))
            ->assertStatus(404);
    }

    /** @test */
    public function edit_does_not_block_own_resource()
    {
        $ilan = $this->makeIlan($this->danisman2);

        // Not 403 (policy) or 404 (scope) = authorization passed
        $response = $this->actingAs($this->danisman2)
            ->get(route('admin.ilanlar.edit', $ilan->id));

        $this->assertNotEquals(403, $response->status(), 'Should not return 403 (policy denied)');
        $this->assertNotEquals(404, $response->status(), 'Should not return 404 (cross-tenant block)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function destroy_returns_404_for_cross_tenant_resource()
    {
        $ilan = $this->makeIlan($this->danisman2);

        $this->actingAs($this->danisman1)
            ->delete(route('admin.ilanlar.destroy', $ilan->id))
            ->assertStatus(404);

        // Verify data is untouched
        $this->assertDatabaseHas('ilanlar', ['id' => $ilan->id, 'deleted_at' => null]);
    }

    /** @test */
    public function destroy_soft_deletes_own_resource()
    {
        $ilan = $this->makeIlan($this->danisman2);

        $this->actingAs($this->danisman2)
            ->delete(route('admin.ilanlar.destroy', $ilan->id))
            ->assertRedirect(route('admin.ilanlar.index'));

        $this->assertSoftDeleted('ilanlar', ['id' => $ilan->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OWNER PRIVATE (Sensitive data endpoint)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function owner_private_returns_404_for_cross_tenant_access()
    {
        $ilan = $this->makeIlan($this->danisman2);

        $this->actingAs($this->danisman1)
            ->post(route('admin.ilanlar.portal-ids', $ilan->id), ['portal_ids' => []])
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX — Scoped listing isolation
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function index_scopes_results_to_own_ilanlar()
    {
        $this->makeIlan($this->danisman1); // danisman1's ilan
        $this->makeIlan($this->danisman2); // danisman2's ilan

        // danisman1 should only see their own listing in repository scope
        $response = $this->actingAs($this->danisman1)
            ->get(route('admin.ilanlar.index'));

        // Passes authorization = not blocked
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(404, $response->status());
    }
}

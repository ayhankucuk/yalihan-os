<?php

namespace Tests\Feature\Admin;

use App\Enums\IlanDurumu;
use App\Models\IlanKategori;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use App\Modules\Auth\Models\Role;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SAAB Strict Brokerage Model — StoreIlanRequest danisman_id contract.
 *
 * Decision: Every admin-wizard listing must have a responsible advisor.
 *   - Default: auth()->id() when not provided
 *   - Tenant scope: advisor must belong to same tenant
 *   - Required: null / missing / empty → 422
 *   - Cross-tenant advisor → 422
 *
 * 7 scenarios covering Step 2/4 Person & GIS contract.
 */
class StoreIlanDanismanBrokerageTest extends TestCase
{
    private User $advisor;
    private User $admin;
    private User $crossTenantAdvisor;
    private ?Role $adminRole = null;
    private ?Role $danismanRole = null;
    private int $kisiId;
    private int $kategoriId;
    private int $yayinTipiId;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles — Role model has no $fillable for 'name', must use property assignment
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

        // Tenant A (default tenant from TestCase::injectDefaultTenantContext)
        $tenantA = Tenant::first() ?? Tenant::create([
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'name'   => 'Tenant A',
            'domain' => 'tenant-a.test',
            'status' => 'active',
        ]);

        // Tenant B (cross-tenant)
        $tenantB = Tenant::where('domain', 'tenant-b.test')->first() ?? Tenant::create([
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'name'   => 'Tenant B',
            'domain' => 'tenant-b.test',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role_id'   => $this->adminRole->id,
            'tenant_id' => $tenantA->id,
        ]);

        $this->advisor = User::factory()->create([
            'role_id'   => $this->danismanRole->id,
            'tenant_id' => $tenantA->id,
        ]);

        $this->crossTenantAdvisor = User::factory()->create([
            'role_id'   => $this->danismanRole->id,
            'tenant_id' => $tenantB->id,
        ]);

        // Disposable kategori + yayın tipi fixtures
        $this->kategoriId = $this->ensureKategori('konut-brokerage', ['seviye' => 0])->id;
        $altKat = $this->ensureKategori('daire-brokerage', [
            'seviye'    => 1,
            'parent_id' => $this->kategoriId,
        ]);

        $sablon = $this->ensureYayinTipi('satilik-brokerage', [
            'kategori_id'   => $this->kategoriId,
            'yayin_tipi_id' => 1,
        ]);
        $this->yayinTipiId = $sablon->id;

        // Disposable kisi (ilan_sahibi_id)
        $this->kisiId = DB::table('kisiler')->insertGetId([
            'ad'               => 'Brokerage',
            'soyad'            => 'TestKisi',
            'tenant_id'        => $tenantA->id,
            'aktiflik_durumu'  => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Authorize without middleware friction for request contract tests
        $this->withoutMiddleware([
            \App\Http\Middleware\RoleMiddleware::class,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Minimal valid payload for StoreIlanRequest.
     */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'baslik'               => 'Brokerage Test İlanı',
            'aciklama'             => 'Test açıklaması.',
            'fiyat_gosterim_modu'  => 'exact',
            'fiyat'                => 1500000,
            'para_birimi'          => 'TRY',
            'ana_kategori_id'      => $this->kategoriId,
            'alt_kategori_id'      => $this->ensureKategori('daire-brokerage')->id,
            'yayin_tipi_id'        => $this->yayinTipiId,
            'ilan_sahibi_id'       => $this->kisiId,
            'yayin_durumu'         => IlanDurumu::TASLAK->value,
        ], $overrides);
    }

    // ── Scenario 1: No danisman_id → defaults to auth()->id() ────────────────

    public function test_missing_danisman_id_defaults_to_authenticated_user(): void
    {
        $this->actingAs($this->advisor);

        $payload = $this->basePayload(); // no danisman_id key

        $response = $this->postJson(route('admin.ilanlar.store'), $payload);

        // Should not fail validation on danisman_id
        $response->assertJsonMissingValidationErrors(['danisman_id']);

        // The created ilan should have danisman_id = advisor->id
        if ($response->status() === 200 || $response->status() === 201 || $response->status() === 302) {
            $ilanId = $response->json('id') ?? $response->json('ilan.id');
            if ($ilanId) {
                $this->assertDatabaseHas('ilanlar', [
                    'id'          => $ilanId,
                    'danisman_id' => $this->advisor->id,
                ]);
            }
        }
    }

    // ── Scenario 2: Explicit same-tenant advisor → passes ────────────────────

    public function test_explicit_same_tenant_danisman_id_passes_validation(): void
    {
        $this->actingAs($this->admin);

        $payload = $this->basePayload(['danisman_id' => $this->advisor->id]);

        $response = $this->postJson(route('admin.ilanlar.store'), $payload);

        $response->assertJsonMissingValidationErrors(['danisman_id']);
    }

    // ── Scenario 3: Cross-tenant advisor → 422 ───────────────────────────────

    public function test_cross_tenant_danisman_id_rejected_with_422(): void
    {
        $this->actingAs($this->admin);

        $payload = $this->basePayload(['danisman_id' => $this->crossTenantAdvisor->id]);

        $response = $this->postJson(route('admin.ilanlar.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['danisman_id']);
    }

    // ── Scenario 4: Non-existent user ID → 422 ───────────────────────────────

    public function test_nonexistent_danisman_id_rejected_with_422(): void
    {
        $this->actingAs($this->admin);

        $payload = $this->basePayload(['danisman_id' => 999999]);

        $response = $this->postJson(route('admin.ilanlar.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['danisman_id']);
    }

    // ── Scenario 5: Null danisman_id (explicit null) → 422 ───────────────────

    public function test_null_danisman_id_rejected_as_required(): void
    {
        $this->actingAs($this->admin);

        $payload = $this->basePayload(['danisman_id' => null]);

        $response = $this->postJson(route('admin.ilanlar.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['danisman_id']);
    }

    // ── Scenario 6: Empty string danisman_id → 422 ───────────────────────────

    public function test_empty_string_danisman_id_rejected_as_required(): void
    {
        $this->actingAs($this->admin);

        $payload = $this->basePayload(['danisman_id' => '']);

        $response = $this->postJson(route('admin.ilanlar.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['danisman_id']);
    }

    // ── Scenario 7: Admin assigns to same-tenant advisor (not themselves) ─────

    public function test_admin_can_assign_same_tenant_advisor(): void
    {
        $this->actingAs($this->admin);

        // advisor is different user, same tenant as admin
        $payload = $this->basePayload(['danisman_id' => $this->advisor->id]);

        $response = $this->postJson(route('admin.ilanlar.store'), $payload);

        $response->assertJsonMissingValidationErrors(['danisman_id']);
    }
}

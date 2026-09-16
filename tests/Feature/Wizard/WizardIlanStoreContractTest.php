<?php

namespace Tests\Feature\Wizard;

use App\Models\Kisi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Tests\Helpers\TestFixtureHelper;

/**
 * A3 — Wizard 422 Contract Tests
 *
 * Verifies StoreIlanRequest enforces ilan_sahibi_id and danisman_id.
 * These are P0 relationship fields — missing them would create orphan records.
 *
 * TC-A3-01: Missing ilan_sahibi_id → 422
 * TC-A3-02: Missing danisman_id   → 422
 * TC-A3-03: Both missing          → 422 with both keys
 * TC-A3-04: Non-existent ilan_sahibi_id (FK) → 422
 * TC-A3-05: Non-existent danisman_id (FK)    → 422
 * TC-A3-06: Cross-tenant danisman_id         → 422 (SAAB strict brokerage)
 */
class WizardIlanStoreContractTest extends TestCase
{
    use TestFixtureHelper;

    private User $admin;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        Gate::before(fn () => true);

        $this->ensureKategori('konut', ['id' => 1, 'seviye' => 0]);
        $this->ensureKategori('daire', ['id' => 7, 'seviye' => 1, 'parent_id' => 1]);
        $this->ensureYayinTipi('satilik', ['id' => 1, 'kategori_id' => 1, 'yayin_tipi_id' => 1]);
    }

    private function makeStoreRequest(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        $owner    = Kisi::factory()->create(['tenant_id' => $this->tenantId]);
        $danisman = User::factory()->create(['tenant_id' => $this->tenantId]);

        $base = [
            'baslik'          => 'Test İlan A3',
            'aciklama'        => 'Test açıklama',
            'fiyat'           => 500000,
            'para_birimi'     => 'TRY',
            'ana_kategori_id' => 1,
            'alt_kategori_id' => 7,
            'yayin_tipi_id'   => 1,
            'ilan_sahibi_id'  => $owner->id,
            'danisman_id'     => $danisman->id,
            'yayin_durumu'    => 'taslak',
        ];

        return $this->withoutMiddleware([
            \App\Http\Middleware\RoleMiddleware::class,
            \App\Http\Middleware\SAB\GlobalWriteGuard::class,
            \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ])->actingAs($this->admin)->post(route('admin.ilanlar.store'), array_merge($base, $overrides));
    }

    /** TC-A3-01: Missing ilan_sahibi_id → 422 */
    public function test_missing_ilan_sahibi_id_yields_422(): void
    {
        $this->makeStoreRequest(['ilan_sahibi_id' => null])
            ->assertSessionHasErrors(['ilan_sahibi_id']);
    }

    /** TC-A3-02: Missing danisman_id → 422 */
    public function test_missing_danisman_id_yields_422(): void
    {
        $this->makeStoreRequest(['danisman_id' => null])
            ->assertSessionHasErrors(['danisman_id']);
    }

    /** TC-A3-03: Both missing → 422 with both keys */
    public function test_missing_both_owner_and_danisman_yields_422(): void
    {
        $this->makeStoreRequest(['ilan_sahibi_id' => null, 'danisman_id' => null])
            ->assertSessionHasErrors(['ilan_sahibi_id', 'danisman_id']);
    }

    /** TC-A3-04: Non-existent ilan_sahibi_id (kisiler.id FK) → 422 */
    public function test_nonexistent_ilan_sahibi_id_yields_422(): void
    {
        $this->makeStoreRequest(['ilan_sahibi_id' => 99999])
            ->assertSessionHasErrors(['ilan_sahibi_id']);
    }

    /** TC-A3-05: Non-existent danisman_id (users.id FK) → 422 */
    public function test_nonexistent_danisman_id_yields_422(): void
    {
        $this->makeStoreRequest(['danisman_id' => 99999])
            ->assertSessionHasErrors(['danisman_id']);
    }

    /** TC-A3-06: Cross-tenant danisman → 422 (SAAB strict brokerage model) */
    public function test_cross_tenant_danisman_id_yields_422(): void
    {
        $otherTenantDanisman = User::factory()->create(['tenant_id' => 999]);

        $this->makeStoreRequest(['danisman_id' => $otherTenantDanisman->id])
            ->assertSessionHasErrors(['danisman_id']);
    }
}

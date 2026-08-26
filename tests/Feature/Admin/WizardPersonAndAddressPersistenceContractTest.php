<?php

namespace Tests\Feature\Admin;

use App\Models\Kisi;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Http\Requests\Admin\Ilan\StoreIlanRequest;
use App\Http\Requests\Admin\Ilan\UpdateIlanRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * WizardPersonAndAddressPersistenceContractTest
 *
 * Contract: Step 2 "İlgili Kişi" (ilgili_kisi_id) must be validated and
 * persisted to ilanlar.ilgili_kisi_id. Silent data-loss must not occur.
 *
 * Scenarios:
 *   1. StoreIlanRequest accepts same-tenant ilgili_kisi_id
 *   2. StoreIlanRequest rejects cross-tenant ilgili_kisi_id
 *   3. StoreIlanRequest accepts null ilgili_kisi_id (field is optional)
 *   4. UpdateIlanRequest accepts same-tenant ilgili_kisi_id
 *   5. UpdateIlanRequest rejects cross-tenant ilgili_kisi_id
 *   6. UpdateIlanRequest accepts null ilgili_kisi_id
 *   7. IlanCrudService persists ilgili_kisi_id to the database row
 *   8. IlanCrudService clears ilgili_kisi_id when explicitly set to null
 *   9. IlanCrudService does not overwrite ilgili_kisi_id when key absent from data
 */
class WizardPersonAndAddressPersistenceContractTest extends TestCase
{
    use DatabaseTransactions;

    private int $tenantA = 1;
    private int $tenantB = 2;

    private function makeRole(string $name): Role
    {
        $role = Role::where('name', $name)->first();
        if (!$role) {
            $role = new Role();
            $role->name = $name;
            $role->save();
        }
        return $role;
    }

    private function makeDanisman(int $tenantId): User
    {
        $role = $this->makeRole('danisman');
        return User::factory()->create([
            'role_id'         => $role->id,
            'tenant_id'       => $tenantId,
            'aktiflik_durumu' => 1,
        ]);
    }

    private function makeKisi(int $tenantId): Kisi
    {
        return Kisi::factory()->create([
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Build validator for StoreIlanRequest with only ilgili_kisi_id in scope.
     */
    private function runStoreIlgiliKisiGuard(User $authUser, ?int $ilgiliKisiId): \Illuminate\Support\MessageBag
    {
        $factory   = app(\Illuminate\Contracts\Validation\Factory::class);
        $validator = $factory->make(
            ['ilgili_kisi_id' => $ilgiliKisiId],
            ['ilgili_kisi_id' => ['nullable', \Illuminate\Validation\Rule::exists('kisiler', 'id')]]
        );

        $req = new StoreIlanRequest(['ilgili_kisi_id' => $ilgiliKisiId]);
        $req->setUserResolver(fn () => $authUser);
        $req->withValidator($validator);

        $validator->passes();

        return $validator->errors();
    }

    /**
     * Build validator for UpdateIlanRequest with only ilgili_kisi_id in scope.
     */
    private function runUpdateIlgiliKisiGuard(User $authUser, ?int $ilgiliKisiId): \Illuminate\Support\MessageBag
    {
        $factory   = app(\Illuminate\Contracts\Validation\Factory::class);
        $validator = $factory->make(
            ['ilgili_kisi_id' => $ilgiliKisiId],
            ['ilgili_kisi_id' => ['nullable', \Illuminate\Validation\Rule::exists('kisiler', 'id')]]
        );

        $req = new UpdateIlanRequest(['ilgili_kisi_id' => $ilgiliKisiId]);
        $req->setUserResolver(fn () => $authUser);
        $req->withValidator($validator);

        $validator->passes();

        return $validator->errors();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 1 — Store: same-tenant kisi passes
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_store_accepts_same_tenant_ilgili_kisi(): void
    {
        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);

        $errors = $this->runStoreIlgiliKisiGuard($auth, $kisi->id);

        $this->assertFalse(
            $errors->has('ilgili_kisi_id'),
            'Same-tenant ilgili_kisi_id must pass StoreIlanRequest validation.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 2 — Store: cross-tenant kisi rejected
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_store_rejects_cross_tenant_ilgili_kisi(): void
    {
        $auth           = $this->makeDanisman($this->tenantA);
        $crossTenantKisi = $this->makeKisi($this->tenantB);

        $errors = $this->runStoreIlgiliKisiGuard($auth, $crossTenantKisi->id);

        $this->assertTrue(
            $errors->has('ilgili_kisi_id'),
            'Cross-tenant ilgili_kisi_id must be rejected by StoreIlanRequest.'
        );
        $this->assertStringContainsString(
            'organizasyona ait değil',
            $errors->first('ilgili_kisi_id')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 3 — Store: null passes (field is optional)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_store_accepts_null_ilgili_kisi(): void
    {
        $auth = $this->makeDanisman($this->tenantA);

        $errors = $this->runStoreIlgiliKisiGuard($auth, null);

        $this->assertFalse(
            $errors->has('ilgili_kisi_id'),
            'Null ilgili_kisi_id must pass StoreIlanRequest validation (field is optional).'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 4 — Update: same-tenant kisi passes
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_update_accepts_same_tenant_ilgili_kisi(): void
    {
        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);

        $errors = $this->runUpdateIlgiliKisiGuard($auth, $kisi->id);

        $this->assertFalse(
            $errors->has('ilgili_kisi_id'),
            'Same-tenant ilgili_kisi_id must pass UpdateIlanRequest validation.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 5 — Update: cross-tenant kisi rejected
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_update_rejects_cross_tenant_ilgili_kisi(): void
    {
        $auth            = $this->makeDanisman($this->tenantA);
        $crossTenantKisi = $this->makeKisi($this->tenantB);

        $errors = $this->runUpdateIlgiliKisiGuard($auth, $crossTenantKisi->id);

        $this->assertTrue(
            $errors->has('ilgili_kisi_id'),
            'Cross-tenant ilgili_kisi_id must be rejected by UpdateIlanRequest.'
        );
        $this->assertStringContainsString(
            'organizasyona ait değil',
            $errors->first('ilgili_kisi_id')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 6 — Update: null passes
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_update_accepts_null_ilgili_kisi(): void
    {
        $auth = $this->makeDanisman($this->tenantA);

        $errors = $this->runUpdateIlgiliKisiGuard($auth, null);

        $this->assertFalse(
            $errors->has('ilgili_kisi_id'),
            'Null ilgili_kisi_id must pass UpdateIlanRequest validation.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 7 — IlanCrudService persists ilgili_kisi_id
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_crud_service_persists_ilgili_kisi_id(): void
    {
        $auth    = $this->makeDanisman($this->tenantA);
        $kisi    = $this->makeKisi($this->tenantA);
        $ilanSahibi = $this->makeKisi($this->tenantA);

        $this->actingAs($auth);

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        $ilan = $service->store([
            'baslik'              => 'İlgili Kişi Persist Test',
            'danisman_id'         => $auth->id,
            'ilan_sahibi_id'      => $ilanSahibi->id,
            'ilgili_kisi_id'      => $kisi->id,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'yayin_durumu'        => 'taslak',
        ]);

        $this->assertSame(
            $kisi->id,
            $ilan->fresh()->ilgili_kisi_id,
            'IlanCrudService must persist ilgili_kisi_id to the ilanlar row.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 8 — IlanCrudService clears ilgili_kisi_id when set to null
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_crud_service_clears_ilgili_kisi_id_when_null(): void
    {
        $auth       = $this->makeDanisman($this->tenantA);
        $kisi       = $this->makeKisi($this->tenantA);
        $ilanSahibi = $this->makeKisi($this->tenantA);

        $this->actingAs($auth);

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        // First create with a kisi
        $ilan = $service->store([
            'baslik'              => 'Clear İlgili Kişi Test',
            'danisman_id'         => $auth->id,
            'ilan_sahibi_id'      => $ilanSahibi->id,
            'ilgili_kisi_id'      => $kisi->id,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'yayin_durumu'        => 'taslak',
        ]);

        // Then update with null — must clear the field
        $updated = $service->update($ilan, [
            'baslik'              => 'Clear İlgili Kişi Test',
            'danisman_id'         => $auth->id,
            'ilan_sahibi_id'      => $ilanSahibi->id,
            'ilgili_kisi_id'      => null,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'yayin_durumu'        => 'taslak',
        ]);

        $this->assertNull(
            $updated->fresh()->ilgili_kisi_id,
            'IlanCrudService must clear ilgili_kisi_id when explicitly set to null.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 9 — IlanCrudService does not overwrite when key absent
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_crud_service_does_not_overwrite_when_key_absent(): void
    {
        $auth       = $this->makeDanisman($this->tenantA);
        $kisi       = $this->makeKisi($this->tenantA);
        $ilanSahibi = $this->makeKisi($this->tenantA);

        $this->actingAs($auth);

        $service = app(\App\Services\Ilan\IlanCrudService::class);

        // Create with ilgili_kisi_id set
        $ilan = $service->store([
            'baslik'              => 'No-Overwrite Test',
            'danisman_id'         => $auth->id,
            'ilan_sahibi_id'      => $ilanSahibi->id,
            'ilgili_kisi_id'      => $kisi->id,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'yayin_durumu'        => 'taslak',
        ]);

        // Update WITHOUT ilgili_kisi_id key in payload — must preserve existing value
        $updated = $service->update($ilan, [
            'baslik'              => 'No-Overwrite Test Updated',
            'danisman_id'         => $auth->id,
            'ilan_sahibi_id'      => $ilanSahibi->id,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'yayin_durumu'        => 'taslak',
        ]);

        $this->assertSame(
            $kisi->id,
            $updated->fresh()->ilgili_kisi_id,
            'IlanCrudService must not overwrite ilgili_kisi_id when key is absent from update payload.'
        );
    }
}

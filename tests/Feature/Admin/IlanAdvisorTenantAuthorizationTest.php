<?php

namespace Tests\Feature\Admin;

use App\Enums\IlanDurumu;
use App\Http\Requests\Admin\Ilan\StoreIlanRequest;
use App\Http\Requests\Admin\Ilan\UpdateIlanRequest;
use App\Models\Ilan;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * IlanAdvisorTenantAuthorizationTest
 *
 * SAAB Brokerage Model — FormRequest layer + service-layer advisor/tenant guard coverage.
 *
 * Scenarios:
 *   1. Same-tenant active advisor passes StoreIlanRequest validation
 *   2. Cross-tenant advisor rejected (danisman_id error) by StoreIlanRequest withValidator
 *   3. Inactive advisor rejected by StoreIlanRequest withValidator (aktiflik_durumu gate)
 *   4. IlanPolicy::create returns true for any authenticated user
 *   5. IlanPolicy::update allows owner, denies non-owner non-admin
 *   6. UpdateIlanRequest rejects cross-tenant advisor (withValidator parity)
 *   7. UpdateIlanRequest rejects inactive advisor
 *   8. UpdateIlanRequest passes for null danisman_id (nullable field)
 *   9. IlanCrudService::store throws DomainException for cross-tenant danisman_id
 *  10. IlanCrudService::store throws DomainException for inactive danisman_id
 */
class IlanAdvisorTenantAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private ?Role $adminRole    = null;
    private ?Role $danismanRole = null;

    // Tenant IDs used throughout
    private int $tenantA = 1;
    private int $tenantB = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([\App\Http\Middleware\RoleMiddleware::class]);

        // Ensure roles exist — Role model guards $fillable, use find-or-new pattern
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeDanisman(int $tenantId, int $aktiflik = 1): User
    {
        return User::factory()->create([
            'role_id'          => $this->danismanRole->id,
            'tenant_id'        => $tenantId,
            'aktiflik_durumu'  => $aktiflik,
        ]);
    }

    private function makeAdmin(int $tenantId): User
    {
        return User::factory()->create([
            'role_id'   => $this->adminRole->id,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Run StoreIlanRequest's tenant-scope withValidator against the given data.
     * Returns the MessageBag (empty = passed).
     */
    private function runStoreTenantGuard(User $authUser, int $danismanId): \Illuminate\Support\MessageBag
    {
        $factory   = app(\Illuminate\Contracts\Validation\Factory::class);
        $validator = $factory->make(
            ['danisman_id' => $danismanId],
            ['danisman_id' => ['required', \Illuminate\Validation\Rule::exists('users', 'id')]]
        );

        $req = new StoreIlanRequest(['danisman_id' => $danismanId]);
        $req->setUserResolver(fn () => $authUser);
        $req->withValidator($validator);

        $validator->passes(); // triggers after-callbacks

        return $validator->errors();
    }

    /**
     * Run UpdateIlanRequest's tenant-scope withValidator against the given data.
     * Returns the MessageBag (empty = passed).
     */
    private function runUpdateTenantGuard(User $authUser, ?int $danismanId): \Illuminate\Support\MessageBag
    {
        $factory   = app(\Illuminate\Contracts\Validation\Factory::class);
        $validator = $factory->make(
            ['danisman_id' => $danismanId],
            ['danisman_id' => ['nullable', \Illuminate\Validation\Rule::exists('users', 'id')]]
        );

        $req = new UpdateIlanRequest(['danisman_id' => $danismanId]);
        $req->setUserResolver(fn () => $authUser);
        $req->withValidator($validator);

        $validator->passes(); // triggers after-callbacks

        return $validator->errors();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 1 — Same-tenant active advisor passes store validation
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_same_tenant_advisor_passes_store_validation(): void
    {
        $auth    = $this->makeDanisman($this->tenantA);
        $advisor = $this->makeDanisman($this->tenantA); // same tenant, active

        $errors = $this->runStoreTenantGuard($auth, $advisor->id);

        $this->assertFalse(
            $errors->has('danisman_id'),
            'Same-tenant active advisor must not produce a danisman_id validation error.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 2 — Cross-tenant advisor rejected by StoreIlanRequest
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_cross_tenant_advisor_rejected_by_store_validation(): void
    {
        $auth               = $this->makeDanisman($this->tenantA);
        $crossTenantAdvisor = $this->makeDanisman($this->tenantB); // different tenant

        $errors = $this->runStoreTenantGuard($auth, $crossTenantAdvisor->id);

        $this->assertTrue(
            $errors->has('danisman_id'),
            'Cross-tenant advisor must be rejected by StoreIlanRequest withValidator.'
        );
        $this->assertStringContainsString(
            'organizasyona ait değil',
            $errors->first('danisman_id')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 3 — Inactive advisor rejected by StoreIlanRequest (aktiflik gate)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_inactive_advisor_rejected_by_store_validation(): void
    {
        $auth            = $this->makeDanisman($this->tenantA);
        $inactiveAdvisor = $this->makeDanisman($this->tenantA, aktiflik: 0);

        $errors = $this->runStoreTenantGuard($auth, $inactiveAdvisor->id);

        $this->assertTrue(
            $errors->has('danisman_id'),
            'Inactive advisor must be rejected by StoreIlanRequest withValidator.'
        );
        $this->assertStringContainsString(
            'aktif değil',
            $errors->first('danisman_id')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 4 — IlanPolicy::create allows any authenticated user
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_ilan_policy_create_allows_authenticated_user(): void
    {
        $danisman = $this->makeDanisman($this->tenantA);

        $this->actingAs($danisman);
        $allowed = $danisman->can('create', Ilan::class);

        $this->assertTrue($allowed, 'IlanPolicy::create must return true for any authenticated user.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 5 — IlanPolicy::update: owner allowed, non-owner denied
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_ilan_policy_update_allows_owner_denies_other(): void
    {
        $owner  = $this->makeDanisman($this->tenantA);
        $other  = $this->makeDanisman($this->tenantA);

        $kisiId = DB::table('kisiler')->insertGetId([
            'ad'         => 'Test',
            'soyad'      => 'Kisi',
            'tenant_id'  => $this->tenantA,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // user_id is legacy/not in $fillable — insert via DB to bypass guard, then reload
        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik'       => 'Test İlan',
            'slug'         => 'test-ilan-' . uniqid(),
            'kisi_id'      => $kisiId,
            'danisman_id'  => $owner->id,
            'user_id'      => $owner->id,  // IlanPolicy::update checks this column
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'fiyat'        => 500000,
            'tenant_id'    => $this->tenantA,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
        $ilan = Ilan::findOrFail($ilanId);

        // Owner can update (user_id matches)
        $this->actingAs($owner);
        $this->assertTrue(
            $owner->can('update', $ilan),
            'Owner must be allowed to update their own ilan.'
        );

        // Non-owner cannot update (user_id does not match)
        $this->actingAs($other);
        $this->assertFalse(
            $other->can('update', $ilan),
            'Non-owner must not be allowed to update another user\'s ilan.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 6 — UpdateIlanRequest rejects cross-tenant advisor
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_update_request_rejects_cross_tenant_advisor(): void
    {
        $auth               = $this->makeDanisman($this->tenantA);
        $crossTenantAdvisor = $this->makeDanisman($this->tenantB);

        $errors = $this->runUpdateTenantGuard($auth, $crossTenantAdvisor->id);

        $this->assertTrue(
            $errors->has('danisman_id'),
            'UpdateIlanRequest must reject a cross-tenant advisor via withValidator.'
        );
        $this->assertStringContainsString(
            'organizasyona ait değil',
            $errors->first('danisman_id')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 7 — UpdateIlanRequest rejects inactive advisor
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_update_request_rejects_inactive_advisor(): void
    {
        $auth            = $this->makeDanisman($this->tenantA);
        $inactiveAdvisor = $this->makeDanisman($this->tenantA, aktiflik: 0);

        $errors = $this->runUpdateTenantGuard($auth, $inactiveAdvisor->id);

        $this->assertTrue(
            $errors->has('danisman_id'),
            'UpdateIlanRequest must reject an inactive advisor.'
        );
        $this->assertStringContainsString(
            'aktif değil',
            $errors->first('danisman_id')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 8 — UpdateIlanRequest passes when danisman_id is null (nullable)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_update_request_passes_when_danisman_id_is_null(): void
    {
        $auth = $this->makeDanisman($this->tenantA);

        $errors = $this->runUpdateTenantGuard($auth, null);

        $this->assertFalse(
            $errors->has('danisman_id'),
            'UpdateIlanRequest must allow null danisman_id (field is nullable on update).'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 9 — IlanCrudService throws DomainException for cross-tenant danisman_id
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_crud_service_throws_for_cross_tenant_danisman(): void
    {
        $auth               = $this->makeDanisman($this->tenantA);
        $crossTenantAdvisor = $this->makeDanisman($this->tenantB);

        $this->actingAs($auth);

        $service = app(IlanCrudService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/organizasyona ait değil/');

        // Provide minimum required data to reach mapCoreData — it will throw before save
        $service->store([
            'baslik'              => 'Cross-Tenant Test',
            'danisman_id'         => $crossTenantAdvisor->id,
            'ilan_sahibi_id'      => null,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'ana_kategori_id'     => null,
            'alt_kategori_id'     => null,
            'yayin_tipi_id'       => null,
            'yayin_durumu'        => 'taslak',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 10 — IlanCrudService throws DomainException for inactive danisman_id
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_crud_service_throws_for_inactive_danisman(): void
    {
        $auth            = $this->makeDanisman($this->tenantA);
        $inactiveAdvisor = $this->makeDanisman($this->tenantA, aktiflik: 0);

        $this->actingAs($auth);

        $service = app(IlanCrudService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/aktif değil/');

        $service->store([
            'baslik'              => 'Inactive Advisor Test',
            'danisman_id'         => $inactiveAdvisor->id,
            'ilan_sahibi_id'      => null,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'ana_kategori_id'     => null,
            'alt_kategori_id'     => null,
            'yayin_tipi_id'       => null,
            'yayin_durumu'        => 'taslak',
        ]);
    }
}

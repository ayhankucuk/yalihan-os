<?php

namespace Tests\Feature\CRM;

use App\Enums\TalepDurumu;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\SaaS\Tenant;
use App\Models\Talep;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CrmOperationalLifecycleTest
 *
 * TASK_ID: CRM_OPERATIONAL_LIFECYCLE_01
 *
 * Verifies complete CRM person/customer-request lifecycle at MODEL level.
 * Uses manual transaction management instead of RefreshDatabase to avoid hangs.
 */
class CrmOperationalLifecycleTest extends TestCase
{
    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;

    protected function setUp(): void
    {
        parent::setUp();

        DB::beginTransaction();

        $this->withoutMiddleware([
            \App\Http\Middleware\RoleMiddleware::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // 1. Establish Tenants
        $this->tenantA = Tenant::create([
            'id'     => 1001,
            'name'   => 'Tenant Alpha',
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'id'     => 2002,
            'name'   => 'Tenant Beta',
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'active',
        ]);

        // 2. Admin Role
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->name = 'admin';
            $adminRole->save();
        }

        // 3. Users
        $this->userA = User::factory()->create([
            'name'      => 'Admin User Tenant A',
            'email'     => 'admin.a.' . uniqid() . '@tenant-alpha.test',
            'role_id'   => $adminRole->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->userB = User::factory()->create([
            'name'      => 'Admin User Tenant B',
            'email'     => 'admin.b.' . uniqid() . '@tenant-beta.test',
            'role_id'   => $adminRole->id,
            'tenant_id' => $this->tenantB->id,
        ]);

        // 4. Geographic reference
        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => '48'],
            ['id' => 48, 'il_adi' => 'Muğla', 'aktiflik_durumu' => 1]
        );
        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'ilce_adi' => 'Bodrum'],
            ['id' => 4801, 'aktiflik_durumu' => 1]
        );
        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'mahalle_adi' => 'Yalıkavak'],
            ['id' => 480101, 'aktiflik_durumu' => 1]
        );
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    // =========================================================================
    // STEP 1: PERSON_CREATE
    // =========================================================================

    public function test_person_create_persists_kisi(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisi = Kisi::create([
            'ad'        => 'Ahmet',
            'soyad'     => 'Demir',
            'telefon'   => '05321112233',
            'eposta'    => 'ahmet.demir@test.com',
            'kisi_tipi' => 'lead',
        ]);

        $this->assertDatabaseHas('kisiler', [
            'id'    => $kisi->id,
            'ad'    => 'Ahmet',
            'soyad' => 'Demir',
        ]);

        $this->assertEquals($this->tenantA->id, $kisi->tenant_id);
    }

    public function test_person_create_factory(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);
        $kisi = Kisi::factory()->create(['tenant_id' => $this->tenantA->id]);

        $this->assertDatabaseHas('kisiler', ['id' => $kisi->id]);
        $this->assertEquals($this->tenantA->id, $kisi->tenant_id);
    }

    // =========================================================================
    // STEP 2: PERSON_UPDATE
    // =========================================================================

    public function test_person_update_modifies(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisi = Kisi::create([
            'ad'        => 'Original',
            'soyad'     => 'Name',
            'telefon'   => '05320000000',
            'kisi_tipi' => 'lead',
        ]);

        $kisi->update(['ad' => 'Updated', 'telefon' => '05329999999']);

        $this->assertDatabaseHas('kisiler', [
            'id'      => $kisi->id,
            'ad'      => 'Updated',
            'telefon' => '05329999999',
        ]);
    }

    // =========================================================================
    // STEP 3: REQUEST_CREATE
    // =========================================================================

    public function test_request_create_persists_talep(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisi = Kisi::create([
            'ad'        => 'Talep Creator',
            'soyad'     => 'Test',
            'telefon'   => '05323333333',
            'kisi_tipi' => 'lead',
        ]);

        $talep = Talep::create([
            'baslik'       => 'Test Talep',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisi->id,
            'danisman_id'  => $this->userA->id,
            'il_id'        => $this->il->id,
            'ilce_id'      => $this->ilce->id,
            'mahalle_id'   => $this->mahalle->id,
            'min_fiyat'    => 1000000,
            'max_fiyat'    => 2000000,
        ]);

        $this->assertDatabaseHas('talepler', [
            'id'      => $talep->id,
            'baslik'  => 'Test Talep',
            'kisi_id' => $kisi->id,
        ]);

        $this->assertEquals($this->tenantA->id, $talep->tenant_id);
    }

    // =========================================================================
    // STEP 4: REQUEST_LINK
    // =========================================================================

    public function test_request_links_to_customer(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisi = Kisi::create([
            'ad'        => 'Link Test',
            'soyad'     => 'Customer',
            'telefon'   => '05325555555',
            'kisi_tipi' => 'lead',
        ]);

        $talep = Talep::create([
            'baslik'       => 'Link Test Talep',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisi->id,
            'danisman_id'  => $this->userA->id,
        ]);

        $this->assertEquals($kisi->id, $talep->kisi->id);
        $this->assertEquals($talep->id, $kisi->talepler->first()->id);
    }

    public function test_request_rebind_to_different_customer(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisiA = Kisi::create([
            'ad'        => 'Customer',
            'soyad'     => 'A',
            'telefon'   => '05326666666',
            'kisi_tipi' => 'lead',
        ]);

        $kisiB = Kisi::create([
            'ad'        => 'Customer',
            'soyad'     => 'B',
            'telefon'   => '05327777777',
            'kisi_tipi' => 'lead',
        ]);

        $talep = Talep::create([
            'baslik'       => 'Rebind Test',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisiA->id,
            'danisman_id'  => $this->userA->id,
        ]);

        $talep->update(['kisi_id' => $kisiB->id]);

        $this->assertEquals($kisiB->id, $talep->fresh()->kisi_id);
    }

    // =========================================================================
    // STEP 5: REQUEST_UPDATE
    // =========================================================================

    public function test_request_update_status(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisi = Kisi::create([
            'ad'        => 'Status Update',
            'soyad'     => 'Test',
            'telefon'   => '05328888888',
            'kisi_tipi' => 'lead',
        ]);

        $talep = Talep::create([
            'baslik'       => 'Status Update Test',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisi->id,
            'danisman_id'  => $this->userA->id,
        ]);

        $talep->update(['talep_durumu' => TalepDurumu::KARSIILANDI]);

        $this->assertEquals(TalepDurumu::KARSIILANDI, $talep->fresh()->talep_durumu);
    }

    // =========================================================================
    // STEP 6: TENANT_ISOLATION
    // =========================================================================

    public function test_tenant_isolation_same_tenant_allowed(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisiA = Kisi::create([
            'ad'        => 'Tenant A',
            'soyad'     => 'Customer',
            'telefon'   => '05320000001',
            'kisi_tipi' => 'lead',
        ]);

        $talepA = Talep::create([
            'baslik'       => 'Tenant A Talep',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisiA->id,
            'danisman_id'  => $this->userA->id,
        ]);

        $this->assertEquals($this->tenantA->id, $kisiA->tenant_id);
        $this->assertEquals($this->tenantA->id, $talepA->tenant_id);

        $kisiler = Kisi::all();
        $talepler = Talep::all();

        $this->assertTrue($kisiler->contains('id', $kisiA->id));
        $this->assertTrue($talepler->contains('id', $talepA->id));
    }

    public function test_tenant_isolation_cross_tenant_blocked(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisiA = Kisi::create([
            'ad'        => 'Cross Tenant',
            'soyad'     => 'Test A',
            'telefon'   => '05320000002',
            'kisi_tipi' => 'lead',
        ]);

        app(TenantContextService::class)->setTenant($this->tenantB);

        $kisiB = Kisi::create([
            'ad'        => 'Cross Tenant',
            'soyad'     => 'Test B',
            'telefon'   => '05320000003',
            'kisi_tipi' => 'lead',
        ]);

        app(TenantContextService::class)->setTenant($this->tenantA);

        $visibleKisiler = Kisi::all();
        $this->assertFalse($visibleKisiler->contains('id', $kisiB->id));
        $this->assertTrue($visibleKisiler->contains('id', $kisiA->id));
    }

    public function test_tenant_isolation_talep_cross_tenant(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisiA = Kisi::create([
            'ad'        => 'Talep Isolation',
            'soyad'     => 'A',
            'telefon'   => '05320000004',
            'kisi_tipi' => 'lead',
        ]);

        app(TenantContextService::class)->setTenant($this->tenantB);

        $talepB = Talep::create([
            'baslik'       => 'Tenant B Talep',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisiA->id,
            'danisman_id'  => $this->userB->id,
        ]);

        app(TenantContextService::class)->setTenant($this->tenantA);

        $visibleTalepler = Talep::all();
        $this->assertFalse($visibleTalepler->contains('id', $talepB->id));
    }

    // =========================================================================
    // STEP 7: AUTHORIZATION (Model Level)
    // =========================================================================

    public function test_authorization_model_requires_correct_tenant(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisiA = Kisi::create([
            'ad'        => 'Auth Test',
            'soyad'     => 'A',
            'telefon'   => '05320000006',
            'kisi_tipi' => 'lead',
        ]);

        $talepA = Talep::create([
            'baslik'       => 'Auth Test Talep',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisiA->id,
            'danisman_id'  => $this->userA->id,
        ]);

        app(TenantContextService::class)->setTenant($this->tenantB);

        $foundTalep = Talep::find($talepA->id);
        $this->assertNull($foundTalep, 'Tenant B should NOT find Tenant A Talep');

        $unscopedTalep = Talep::withoutGlobalScopes()->find($talepA->id);
        $this->assertNotNull($unscopedTalep);
        $this->assertEquals($this->tenantA->id, $unscopedTalep->tenant_id);
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    public function test_without_tenant_scope_returns_all(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisiA = Kisi::create([
            'ad'        => 'All Tenants',
            'soyad'     => 'A',
            'telefon'   => '05320000008',
            'kisi_tipi' => 'lead',
        ]);

        app(TenantContextService::class)->setTenant($this->tenantB);

        $kisiB = Kisi::create([
            'ad'        => 'All Tenants',
            'soyad'     => 'B',
            'telefon'   => '05320000009',
            'kisi_tipi' => 'lead',
        ]);

        $allKisiler = Kisi::withoutGlobalScopes()->get();

        $this->assertTrue($allKisiler->contains('id', $kisiA->id));
        $this->assertTrue($allKisiler->contains('id', $kisiB->id));
    }

    public function test_talep_belongs_to_kisi_relationship(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisi = Kisi::create([
            'ad'        => 'Relationship',
            'soyad'     => 'Test',
            'telefon'   => '05320000010',
            'kisi_tipi' => 'lead',
        ]);

        $talep1 = Talep::create([
            'baslik'       => 'Talep 1',
            'talep_tipi'   => 'satis',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisi->id,
            'danisman_id'  => $this->userA->id,
        ]);

        $talep2 = Talep::create([
            'baslik'       => 'Talep 2',
            'talep_tipi'   => 'kira',
            'talep_durumu' => TalepDurumu::AKTIF,
            'kisi_id'      => $kisi->id,
            'danisman_id'  => $this->userA->id,
        ]);

        $this->assertEquals(2, $kisi->talepler->count());
        $this->assertTrue($kisi->talepler->contains('id', $talep1->id));
        $this->assertTrue($kisi->talepler->contains('id', $talep2->id));
        $this->assertEquals($kisi->id, $talep1->kisi->id);
    }

    public function test_kisi_belongs_to_tenant_trait_active(): void
    {
        app(TenantContextService::class)->setTenant($this->tenantA);

        $kisi = Kisi::create([
            'ad'        => 'Tenant Trait',
            'soyad'     => 'Test',
            'telefon'   => '05320000011',
            'kisi_tipi' => 'lead',
        ]);

        $this->assertEquals($this->tenantA->id, $kisi->tenant_id);

        $allKisiler = Kisi::all();
        $this->assertTrue($allKisiler->every(fn($k) => $k->tenant_id === $this->tenantA->id));
    }
}

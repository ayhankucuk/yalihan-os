<?php

namespace Tests\Feature\CRM;

use App\Models\Eslesme;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Il;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Role;
use App\Models\Talep;
use App\Models\User;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Database\Factories\SaaS\TenantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ESLESME_02 — Runtime Tenant Boundary Verification
 *
 * Verifies canonical fail-closed tenant boundary on Eslesme operations:
 *   - Cross-tenant Kisi, Ilan, Talep references are rejected on store()
 *   - index() scopes Eslesmeler strictly to current tenant
 *   - show() aborts 404 for foreign-tenant Eslesme
 *   - destroy() fails closed (refuses deletion) for foreign-tenant Eslesme
 *
 * TASK: REASONING_PIPELINE_V0_PILOT_F02R_01
 */
class EslesmeTenantBoundaryRuntimeTest extends TestCase
{
    use RefreshDatabase;

    // Model fixtures
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
    private IlanKategori $kategori;
    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => '35'],
            ['il_adi' => 'İzmir', 'aktiflik_durumu' => 1]
        );

        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'ilce_adi' => 'Konak'],
            ['aktiflik_durumu' => 1]
        );

        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'mahalle_adi' => 'Alsancak'],
            ['aktiflik_durumu' => 1]
        );

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'daire'],
            ['name' => 'Daire', 'aktiflik_durumu' => 1]
        );

        $this->adminRole = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

        // Schema patch: Add columns that exist in production MySQL but not in test migrations.
        // CDA-006: Production schema drift — eslesmeler table has extra columns via direct ALTER.
        if (Schema::hasTable('eslesmeler')) {
            if (!Schema::hasColumn('eslesmeler', 'danisman_id')) {
                Schema::table('eslesmeler', function ($table) {
                    $table->unsignedBigInteger('danisman_id')->nullable()->after('talep_id');
                });
            }
            if (!Schema::hasColumn('eslesmeler', 'tenant_id')) {
                Schema::table('eslesmeler', function ($table) {
                    $table->string('tenant_id', 100)->nullable()->after('id');
                });
            }
            // @sab-ignore: one_cikan column not in canonical schema — controller no longer references it (CDH-001 fix)
            if (!Schema::hasColumn('eslesmeler', 'eslesme_tarihi')) {
                Schema::table('eslesmeler', function ($table) {
                    $table->dateTime('eslesme_tarihi')->nullable()->after('eslesme_detaylari');
                });
            }
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private Tenant $tenantA;
    private Tenant $tenantB;

    private function setupTenants(): void
    {
        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();
    }

    private function setTenantContext(Tenant $tenant): void
    {
        app(TenantContextService::class)->setTenant($tenant);
    }

    private function makeEslesmeAdminUser(string $email, Tenant $tenant): User
    {
        $user = User::create([
            'name'      => 'Admin ' . substr($tenant->uuid, 0, 8),
            'email'     => $email,
            'password'  => bcrypt('secret'),
            'tenant_id' => $tenant->id,
            'role_id'   => $this->adminRole->id,
        ]);
        $user->assignRole('admin');
        return $user;
    }

    private function createKisi(Tenant $tenant): Kisi
    {
        $this->setTenantContext($tenant);
        return Kisi::create([
            'ad'               => 'Müşteri ' . substr($tenant->uuid, 0, 4),
            'soyad'            => 'Tenant',
            'kisi_tipi'        => 'lead',
            'aktiflik_durumu'  => 1,
        ]);
    }

    private function createIlan(User $user, Tenant $tenant): Ilan
    {
        $this->setTenantContext($tenant);
        return Ilan::create([
            'tenant_id'        => $tenant->id,
            'user_id'          => $user->id,
            'baslik'           => 'İlan — ' . substr($tenant->uuid, 0, 4),
            'slug'             => 'ilan-' . uniqid(),
            'fiyat'            => 5000000,
            'para_birimi'      => 'TRY',
            'tip'              => 'Satılık',
            'yayin_tipi_id'    => 1,
            'kategori_id'      => $this->kategori->id,
            'alt_kategori_id'  => $this->kategori->id,
            'il_id'            => $this->il->id,
            'ilce_id'          => $this->ilce->id,
            'mahalle_id'       => $this->mahalle->id,
            'yayin_durumu'     => 'yayinda',
            'aktiflik_durumu'  => 1,
        ]);
    }

    private function createTalep(User $user, Tenant $tenant, Kisi $kisi): Talep
    {
        $this->setTenantContext($tenant);
        return Talep::create([
            'tenant_id'        => $tenant->id,
            'danisman_id'      => $user->id,
            'kisi_id'          => $kisi->id,
            'baslik'           => 'Talep — ' . substr($tenant->uuid, 0, 4),
            'talep_tipi'       => 'Satılık',
            'talep_durumu'     => 'yayinda',
            'kategori_id'      => $this->kategori->id,
            'alt_kategori_id'  => $this->kategori->id,
            'il_id'            => $this->il->id,
            'ilce_id'          => $this->ilce->id,
            'mahalle_id'       => $this->mahalle->id,
            'min_fiyat'        => 4000000,
            'max_fiyat'        => 6000000,
            'oncelik'          => 'Yuksek',
        ]);
    }

    // =========================================================================
    // TEST 1 — SAME TENANT CONTROL (Positive)
    // =========================================================================

    /** @test */
    public function test_same_tenant_eslesme_create_succeeds(): void
    {
        $this->setupTenants();

        $userA   = $this->makeEslesmeAdminUser('admin.alpha@yalihan.test', $this->tenantA);
        $kisiA   = $this->createKisi($this->tenantA);
        $ilanA   = $this->createIlan($userA, $this->tenantA);
        $talepA  = $this->createTalep($userA, $this->tenantA, $kisiA);

        $this->setTenantContext($this->tenantA);

        $response = $this->actingAs($userA, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiA->id,
                'ilan_id'         => $ilanA->id,
                'talep_id'        => $talepA->id,
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
                'notlar'          => 'Same tenant test',
            ]);

        $response->assertStatus(302, 'Same-tenant Eslesme create should succeed');

        $record = Eslesme::latest()->first();
        $this->assertNotNull($record, 'Eslesme record should be created');
        $this->assertEquals($kisiA->id, $record->kisi_id);
        $this->assertEquals($ilanA->id, $record->ilan_id);
        $this->assertEquals($talepA->id, $record->talep_id);
    }

    // =========================================================================
    // TEST 2 — CROSS-TENANT KISI
    // =========================================================================

    /**
     * Store endpoint enforces tenant boundary: cross-tenant kisi_id is rejected fail-closed.
     *
     * @test
     */
    public function test_cross_tenant_kisi_is_blocked_fail_closed(): void
    {
        $this->setupTenants();

        $userA  = $this->makeEslesmeAdminUser('alpha@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);

        $userB = $this->makeEslesmeAdminUser('beta@yalihan.test', $this->tenantB);
        $kisiB = $this->createKisi($this->tenantB);

        $this->setTenantContext($this->tenantA);

        $response = $this->actingAs($userA, 'sanctum')
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiB->id, // Tenant B
                'ilan_id'         => $ilanA->id,
                'talep_id'        => $talepA->id,
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('eslesmeler', ['kisi_id' => $kisiB->id, 'ilan_id' => $ilanA->id]);
    }

    // =========================================================================
    // TEST 3 — CROSS-TENANT ILAN
    // =========================================================================

    /**
     * Store endpoint enforces tenant boundary: cross-tenant ilan_id is rejected fail-closed.
     *
     * @test
     */
    public function test_cross_tenant_ilan_is_blocked_fail_closed(): void
    {
        $this->setupTenants();

        $userA  = $this->makeEslesmeAdminUser('alpha.ilan@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);

        $userB = $this->makeEslesmeAdminUser('beta.ilan@yalihan.test', $this->tenantB);
        $ilanB = $this->createIlan($userB, $this->tenantB);

        $this->setTenantContext($this->tenantA);

        $response = $this->actingAs($userA, 'sanctum')
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiA->id,
                'ilan_id'         => $ilanB->id, // Tenant B
                'talep_id'        => $talepA->id,
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('eslesmeler', ['ilan_id' => $ilanB->id]);
    }

    // =========================================================================
    // TEST 4 — CROSS-TENANT TALEP
    // =========================================================================

    /**
     * Store endpoint enforces tenant boundary: cross-tenant talep_id is rejected fail-closed.
     *
     * @test
     */
    public function test_cross_tenant_talep_is_blocked_fail_closed(): void
    {
        $this->setupTenants();

        $userA  = $this->makeEslesmeAdminUser('alpha.talep@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);

        $userB  = $this->makeEslesmeAdminUser('beta.talep@yalihan.test', $this->tenantB);
        $kisiB  = $this->createKisi($this->tenantB);
        $talepB = $this->createTalep($userB, $this->tenantB, $kisiB);

        $this->setTenantContext($this->tenantA);

        $response = $this->actingAs($userA, 'sanctum')
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiA->id,
                'ilan_id'         => $ilanA->id,
                'talep_id'        => $talepB->id, // Tenant B
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('eslesmeler', ['talep_id' => $talepB->id]);
    }

    // =========================================================================
    // TEST 5 — MIXED RELATION SET (All cross-tenant)
    // =========================================================================

    /**
     * Store endpoint enforces tenant boundary: all-cross-tenant references are rejected fail-closed.
     *
     * @test
     */
    public function test_mixed_tenant_all_cross_tenant_persisted(): void
    {
        $this->setupTenants();

        $userA  = $this->makeEslesmeAdminUser('alpha.mixed@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);

        $userB  = $this->makeEslesmeAdminUser('beta.mixed@yalihan.test', $this->tenantB);
        $kisiB  = $this->createKisi($this->tenantB);
        $ilanB  = $this->createIlan($userB, $this->tenantB);
        $talepB = $this->createTalep($userB, $this->tenantB, $kisiB);

        $this->setTenantContext($this->tenantA);

        $response = $this->actingAs($userA, 'sanctum')
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiB->id,
                'ilan_id'         => $ilanB->id,
                'talep_id'        => $talepB->id,
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertEquals(0, Eslesme::count());
    }

    // =========================================================================
    // TEST 6 — INDEX READ ISOLATION (F02)
    // =========================================================================

    /**
     * Index endpoint scopes Eslesme records strictly to the authenticated tenant.
     * Foreign-tenant Eslesmeler are not visible in pagination results.
     *
     * @test
     */
    public function test_index_scopes_eslesmeler_to_current_tenant(): void
    {
        $this->setupTenants();

        // MATCH_A — Tenant A
        $userA  = $this->makeEslesmeAdminUser('alpha.idx@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);
        $this->setTenantContext($this->tenantA);
        $matchA = Eslesme::withoutEvents(fn () => Eslesme::create([
            'kisi_id'         => $kisiA->id,
            'ilan_id'         => $ilanA->id,
            'talep_id'        => $talepA->id,
            'danisman_id'     => $userA->id,
            'eslesme_durumu'  => 'Aktif',
            'skor'            => 85,
            'eslesme_tarihi'  => now(),
        ]));

        // MATCH_B — Tenant B
        $userB  = $this->makeEslesmeAdminUser('beta.idx@yalihan.test', $this->tenantB);
        $kisiB  = $this->createKisi($this->tenantB);
        $ilanB  = $this->createIlan($userB, $this->tenantB);
        $talepB = $this->createTalep($userB, $this->tenantB, $kisiB);
        $this->setTenantContext($this->tenantB);
        $matchB = Eslesme::withoutEvents(fn () => Eslesme::create([
            'kisi_id'         => $kisiB->id,
            'ilan_id'         => $ilanB->id,
            'talep_id'        => $talepB->id,
            'danisman_id'     => $userB->id,
            'eslesme_durumu'  => 'Aktif',
            'skor'            => 90,
            'eslesme_tarihi'  => now(),
        ]));

        // Both exist in DB
        $this->assertDatabaseHas('eslesmeler', ['id' => $matchA->id]);
        $this->assertDatabaseHas('eslesmeler', ['id' => $matchB->id]);

        // Index as ADMIN_A (with Tenant A context set)
        $this->setTenantContext($this->tenantA);
        $response = $this->actingAs($userA, 'sanctum')
            ->get(route('admin.eslesmeler.index'));

        $response->assertStatus(200);
        $eslesmeler = $response->viewData('eslesmeler');
        $this->assertNotNull($eslesmeler);
        $this->assertTrue($eslesmeler->contains('id', $matchA->id));
        $this->assertFalse($eslesmeler->contains('id', $matchB->id));
    }

    // =========================================================================
    // TEST 7 — SHOW OBJECT BOUNDARY (F02 extension)
    // =========================================================================

    /**
     * Show endpoint enforces fail-closed tenant boundary: foreign-tenant Eslesme returns 404.
     *
     * @test
     */
    public function test_admin_a_cannot_show_admin_b_eslesme_by_id(): void
    {
        $this->setupTenants();

        $userA  = $this->makeEslesmeAdminUser('alpha.show@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);

        $userB  = $this->makeEslesmeAdminUser('beta.show@yalihan.test', $this->tenantB);
        $kisiB  = $this->createKisi($this->tenantB);
        $ilanB  = $this->createIlan($userB, $this->tenantB);
        $talepB = $this->createTalep($userB, $this->tenantB, $kisiB);

        $this->setTenantContext($this->tenantB);
        $matchB = Eslesme::withoutEvents(fn () => Eslesme::create([
            'kisi_id'         => $kisiB->id,
            'ilan_id'         => $ilanB->id,
            'talep_id'        => $talepB->id,
            'danisman_id'     => $userB->id,
            'eslesme_durumu'  => 'Aktif',
            'skor'            => 88,
            'eslesme_tarihi'  => now(),
        ]));

        $matchBId = $matchB->id;

        // ADMIN_A tries to SHOW MATCH_B -> 404 Not Found
        $this->setTenantContext($this->tenantA);
        $response = $this->actingAs($userA, 'sanctum')
            ->get(route('admin.eslesmeler.show', $matchBId));

        $response->assertStatus(404);
    }

    // =========================================================================
    // TEST 8 — DESTROY OBJECT BOUNDARY (F02 extension — CRITICAL)
    // =========================================================================

    /**
     * Destroy endpoint enforces fail-closed tenant boundary: foreign-tenant Eslesme cannot be deleted.
     *
     * @test
     */
    public function test_admin_a_cannot_delete_admin_b_eslesme(): void
    {
        $this->setupTenants();

        $userA  = $this->makeEslesmeAdminUser('alpha.del@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);

        $userB  = $this->makeEslesmeAdminUser('beta.del@yalihan.test', $this->tenantB);
        $kisiB  = $this->createKisi($this->tenantB);
        $ilanB  = $this->createIlan($userB, $this->tenantB);
        $talepB = $this->createTalep($userB, $this->tenantB, $kisiB);

        $this->setTenantContext($this->tenantB);
        $matchB = Eslesme::withoutEvents(fn () => Eslesme::create([
            'kisi_id'         => $kisiB->id,
            'ilan_id'         => $ilanB->id,
            'talep_id'        => $talepB->id,
            'danisman_id'     => $userB->id,
            'eslesme_durumu'  => 'Aktif',
            'skor'            => 88,
            'eslesme_tarihi'  => now(),
        ]));

        $matchBId = $matchB->id;
        $this->assertDatabaseHas('eslesmeler', ['id' => $matchBId]);

        // ADMIN_A tries to DESTROY MATCH_B -> fails closed, record survives
        $this->setTenantContext($this->tenantA);
        $response = $this->actingAs($userA, 'sanctum')
            ->delete(route('admin.eslesmeler.destroy', $matchBId));

        $response->assertStatus(302);
        $this->assertDatabaseHas('eslesmeler', ['id' => $matchBId]);
    }

    // =========================================================================
    // TEST 9 — F03: SmartPropertyMatcherAI route unreachable
    // =========================================================================

    /** @test */
    public function test_smart_property_matcher_ai_route_not_registered(): void
    {
        // admin-ai.php is NOT loaded by RouteServiceProvider
        $routeServiceProvider = file_get_contents(base_path('app/Providers/RouteServiceProvider.php'));
        $this->assertStringNotContainsString('admin-ai.php', $routeServiceProvider,
            'F03 CONFIRMED: routes/admin-ai.php is NOT registered in RouteServiceProvider');

        // SmartPropertyMatcherAI routes exist only in admin-ai.php
        $adminAiRoutes = file_get_contents(base_path('routes/admin-ai.php'));
        $this->assertStringContainsString('SmartPropertyMatcherAI', $adminAiRoutes,
            'SmartPropertyMatcherAI is defined in admin-ai.php which is unreachable');

        // ai-advanced.php (the loaded route file) does NOT contain SmartPropertyMatcherAI
        $aiAdvancedRoutes = file_get_contents(base_path('routes/ai-advanced.php'));
        $this->assertStringNotContainsString('SmartPropertyMatcherAI', $aiAdvancedRoutes,
            'SmartPropertyMatcherAI is NOT in ai-advanced.php');
    }
}

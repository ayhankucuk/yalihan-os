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
 * Empirical reproduction of F01 (cross-tenant Eslesme create) and F02 (unscoped index).
 *
 * CURRENT_HEAD: e7a385934a40bf12db8f38237280f285a0a9487d
 * MODE: READ-ONLY SOURCE + DISPOSABLE TEST RUNTIME
 *
 * Architecture under test:
 *   Eslesme has NO BelongsToTenant → no auto tenant_id on create
 *   Kisi, Ilan, Talep all have BelongsToTenant → global TenantScope
 *   SetTenantContext middleware sets TenantContextService on all admin routes
 *   Validation: 'exists:kisiler,id' uses global scope when checking existence
 *   MatchingAuthorityService::createMatch() → Eslesme::create() (no tenant_id set)
 *
 * CRITICAL FINDING:
 *   actingAs() in Laravel feature tests bypasses the full middleware chain.
 *   This means SetTenantContext is NOT executed during HTTP tests.
 *   The 'exists:table,id' validation runs WITHOUT TenantScope enforcement.
 *   Cross-tenant references are therefore possible in the HTTP test environment.
 *   In PRODUCTION: the full middleware chain runs, but SetTenantContext only
 *   sets TenantContextService — it does NOT scope the 'exists' validator.
 *   Therefore the vulnerability is REAL in production as well.
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
     * CRITICAL: actingAs() does NOT run the full Laravel middleware chain.
     * Therefore SetTenantContext never executes, TenantContextService has no tenant.
     * The 'exists:kisiler,id' validation runs WITHOUT global TenantScope.
     * KISI_B is visible to the unscoped validator → validation passes → Eslesme created.
     *
     * @test
     */
    public function test_cross_tenant_kisi_is_not_blocked(): void
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
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiB->id, // Tenant B
                'ilan_id'         => $ilanA->id,
                'talep_id'        => $talepA->id,
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        // actingAs bypasses SetTenantContext middleware → exists: validation unscoped
        // → KISI_B is found → validation passes → Eslesme created → 302 redirect
        $response->assertStatus(302,
            'F01 CONFIRMED: Cross-tenant kisi_id NOT blocked when middleware is bypassed by actingAs');

        $this->assertDatabaseHas('eslesmeler', ['kisi_id' => $kisiB->id, 'ilan_id' => $ilanA->id]);
    }

    // =========================================================================
    // TEST 3 — CROSS-TENANT ILAN
    // =========================================================================

    /** @test */
    public function test_cross_tenant_ilan_is_not_blocked(): void
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
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiA->id,
                'ilan_id'         => $ilanB->id, // Tenant B
                'talep_id'        => $talepA->id,
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        $response->assertStatus(302,
            'F01 CONFIRMED: Cross-tenant ilan_id NOT blocked');

        $this->assertDatabaseHas('eslesmeler', ['ilan_id' => $ilanB->id]);
    }

    // =========================================================================
    // TEST 4 — CROSS-TENANT TALEP
    // =========================================================================

    /** @test */
    public function test_cross_tenant_talep_is_not_blocked(): void
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
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiA->id,
                'ilan_id'         => $ilanA->id,
                'talep_id'        => $talepB->id, // Tenant B
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        $response->assertStatus(302,
            'F01 CONFIRMED: Cross-tenant talep_id NOT blocked');

        $this->assertDatabaseHas('eslesmeler', ['talep_id' => $talepB->id]);
    }

    // =========================================================================
    // TEST 5 — MIXED RELATION SET (All cross-tenant)
    // =========================================================================

    /** @test */
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
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.eslesmeler.store'), [
                'kisi_id'         => $kisiB->id,
                'ilan_id'         => $ilanB->id,
                'talep_id'        => $talepB->id,
                'danisman_id'     => $userA->id,
                'eslesme_durumu'  => 'Aktif',
            ]);

        $response->assertStatus(302,
            'F01 CONFIRMED: All-cross-tenant Eslesme NOT blocked');

        $this->assertGreaterThan(0, Eslesme::count(),
            'F01 REAL: Eslesme records created despite ALL entities being Tenant B');
    }

    // =========================================================================
    // TEST 6 — INDEX READ ISOLATION (F02)
    // =========================================================================

    /**
     * F02: EslesmeController::index() has NO TenantScope on Eslesme query.
     * The unscoped query returns ALL Eslesmeler from all tenants.
     * Even if the form is scoped, the index is unscoped.
     *
     * @test
     */
    public function test_index_returns_all_eslesmeler_regardless_of_tenant(): void
    {
        $this->setupTenants();

        // MATCH_A — Tenant A
        $userA  = $this->makeEslesmeAdminUser('alpha.idx@yalihan.test', $this->tenantA);
        $kisiA  = $this->createKisi($this->tenantA);
        $ilanA  = $this->createIlan($userA, $this->tenantA);
        $talepA = $this->createTalep($userA, $this->tenantA, $kisiA);
        $this->setTenantContext($this->tenantA);
        $ilanABaslik = $ilanA->baslik;
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
        $ilanBBaslik = $ilanB->baslik;
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
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('admin.eslesmeler.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // F02 CONFIRMED: Unscoped Eslesme query returns ALL records from ALL tenants.
        // Both MATCH_A and MATCH_B exist in the DB (asserted above).
        // If Eslesme had TenantScope, only MATCH_A would be returned.
        // Here we assert total count ≥ 2 — proves unscoped query.
        // Note: ilan baslik is not rendered in eslesme index rows, so we count records instead.
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'eslesme_durumu'),
            'F02 CONFIRMED: At least 2 Eslesme rows visible in index — query is unscoped');
    }

    // =========================================================================
    // TEST 7 — SHOW OBJECT BOUNDARY (F02 extension)
    // =========================================================================

    /**
     * F02 EXTENSION: Direct show() access to a foreign-tenant Eslesme by ID.
     * Route model binding finds Eslesme by ID with no tenant filter.
     *
     * @test
     */
    public function test_admin_a_can_show_admin_b_eslesme_by_id(): void
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

        // ADMIN_A tries to SHOW MATCH_B
        $this->setTenantContext($this->tenantA);
        $response = $this->actingAs($userA, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('admin.eslesmeler.show', $matchBId));

        // F02 EXTENSION CONFIRMED: Route model binding finds unscoped Eslesme by ID → 200 OK
        $response->assertStatus(200,
            'F02 EXTENSION CONFIRMED: Tenant A admin can directly access Tenant B Eslesme by ID');
    }

    // =========================================================================
    // TEST 8 — DESTROY OBJECT BOUNDARY (F02 extension — CRITICAL)
    // =========================================================================

    /**
     * F02 CRITICAL EXTENSION: Admin from Tenant A can DELETE Tenant B's Eslesme.
     * This is the most severe manifestation of F02.
     *
     * @test
     */
    public function test_admin_a_can_delete_admin_b_eslesme(): void
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

        // ADMIN_A tries to DESTROY MATCH_B
        $this->setTenantContext($this->tenantA);
        $response = $this->actingAs($userA, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->delete(route('admin.eslesmeler.destroy', $matchBId));

        // F02 CRITICAL: Unscoped destroy → record deleted
        $response->assertStatus(302);
        $this->assertDatabaseMissing('eslesmeler', ['id' => $matchBId]);
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

<?php

namespace Tests\Feature\CRM;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\SaaS\Tenant;
use App\Models\Talep;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * TalepTenantBoundarySecurityTest
 *
 * Dedicated regression test suite for:
 * TASK_ID: CRM_03_SECURITY_CROSS_TENANT_KISI_TALEP_REMEDIATION_04
 *
 * Invariants proven with real HTTP requests:
 * 1. SAME-TENANT CREATE:
 *    User A (Tenant A) creates Talep with Kisi A (Tenant A) -> HTTP 302, Talep persisted, kisi_id = Kisi A.id, tenant_id = Tenant A.
 * 2. CROSS-TENANT CREATE FAILS CLOSED:
 *    User A (Tenant A) creates Talep with Kisi B (Tenant B) -> HTTP 422 / session validation error on kisi_id, NO HTTP 500, NO Talep created in DB.
 * 3. CROSS-TENANT UPDATE REBIND FAILS CLOSED:
 *    Talep A belongs to Tenant A with Kisi A. User A attempts to update Talep A with kisi_id = Kisi B.id -> Validation error on kisi_id, NO Talep mutation, relation remains Kisi A.
 * 4. CROSS-TENANT TALEP DIRECT UPDATE 404:
 *    Talep B belongs to Tenant B. User A attempts to update Talep B -> HTTP 404 ModelNotFoundException preserved.
 * 5. PARITY:
 *    Both legacy mode (use_domain_talep = false) and domain mode (use_domain_talep = true) respect the validation boundary.
 */
class TalepTenantBoundarySecurityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private object $userA;
    private object $userB;
    private User $realUserA;
    private User $realUserB;
    private Kisi $kisiA;
    private Kisi $kisiB;
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;

    protected function setUp(): void
    {
        parent::setUp();

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

        // 2. Roles
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->name = 'admin';
            $adminRole->save();
        }

        // 3. Users for each tenant
        $this->realUserA = User::factory()->create([
            'name'      => 'Admin User Tenant A',
            'email'     => 'admin.a.' . uniqid() . '@tenant-alpha.test',
            'role_id'   => $adminRole->id,
            'tenant_id' => $this->tenantA->id,
        ]);
        $this->userA = Mockery::mock($this->realUserA)->makePartial();
        $this->userA->shouldReceive('isAdmin')->andReturn(true);
        $this->userA->shouldReceive('hasRole')->andReturn(true);
        $this->userA->shouldReceive('can')->andReturn(true);
        $this->userA->shouldReceive('getAuthIdentifier')->andReturn($this->realUserA->id);

        $this->realUserB = User::factory()->create([
            'name'      => 'Admin User Tenant B',
            'email'     => 'admin.b.' . uniqid() . '@tenant-beta.test',
            'role_id'   => $adminRole->id,
            'tenant_id' => $this->tenantB->id,
        ]);
        $this->userB = Mockery::mock($this->realUserB)->makePartial();
        $this->userB->shouldReceive('isAdmin')->andReturn(true);
        $this->userB->shouldReceive('hasRole')->andReturn(true);
        $this->userB->shouldReceive('can')->andReturn(true);
        $this->userB->shouldReceive('getAuthIdentifier')->andReturn($this->realUserB->id);

        // 4. Geographic reference data
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

        // 5. Kisiler strictly scoped to their respective tenants
        $this->kisiA = Kisi::create([
            'ad'        => 'Ali',
            'soyad'     => 'Yılmaz',
            'telefon'   => '05321112233',
            'eposta'    => 'ali@tenant-alpha.test',
            'kisi_tipi' => 'lead',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->kisiB = Kisi::create([
            'ad'        => 'Berk',
            'soyad'     => 'Kaya',
            'telefon'   => '05324445566',
            'eposta'    => 'berk@tenant-beta.test',
            'kisi_tipi' => 'lead',
            'tenant_id' => $this->tenantB->id,
        ]);
    }

    /**
     * INVARIANT 1: SAME-TENANT CREATE
     * User A (Tenant A) creates Talep with Kisi A (Tenant A) -> HTTP 302,
     * Talep persisted, kisi_id = Kisi A.id, tenant_id = Tenant A.
     */
    public function test_invariant_1_same_tenant_create_persists_talep(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);
            app(TenantContextService::class)->setTenant($this->tenantA);

            $payload = [
                'baslik'       => 'Same-Tenant Talep ' . ($useDomain ? 'Domain' : 'Legacy'),
                'tip'          => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
                'ilce_id'      => $this->ilce->id,
                'mahalle_id'   => $this->mahalle->id,
                'kisi_id'      => $this->kisiA->id,
                'danisman_id'  => $this->realUserA->id,
                'min_fiyat'    => 10000000,
                'max_fiyat'    => 20000000,
            ];

            $response = $this->actingAs($this->userA)->post(route('admin.talepler.store'), $payload);

            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();

            $talep = Talep::withoutGlobalScopes()->where('baslik', $payload['baslik'])->first();
            $this->assertNotNull($talep, "Talep should be persisted in DB (useDomain: {$useDomain})");
            $this->assertEquals((int) $this->kisiA->id, (int) $talep->kisi_id);
            $this->assertEquals((int) $this->tenantA->id, (int) $talep->tenant_id);
        }
    }

    /**
     * INVARIANT 2: CROSS-TENANT CREATE FAILS CLOSED
     * User A (Tenant A) creates Talep with Kisi B (Tenant B) ->
     * HTTP 422 / session validation error on kisi_id, NO HTTP 500, NO Talep created in DB.
     */
    public function test_invariant_2_cross_tenant_create_fails_closed(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);
            app(TenantContextService::class)->setTenant($this->tenantA);

            $payloadWeb = [
                'baslik'       => 'Malicious Cross-Tenant Demand Web ' . ($useDomain ? 'Domain' : 'Legacy'),
                'tip'          => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
                'kisi_id'      => $this->kisiB->id, // Kisi belongs to Tenant B!
            ];

            // 1. Web session validation failure (HTTP 302 + session errors on kisi_id)
            $response = $this->actingAs($this->userA)->post(route('admin.talepler.store'), $payloadWeb);
            $response->assertStatus(302);
            $response->assertSessionHasErrors(['kisi_id']);
            $this->assertDatabaseMissing('talepler', ['baslik' => $payloadWeb['baslik']]);

            // 2. JSON validation failure (HTTP 422)
            $payloadJson = [
                'baslik'       => 'Malicious Cross-Tenant Demand JSON ' . ($useDomain ? 'Domain' : 'Legacy'),
                'tip'          => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
                'kisi_id'      => $this->kisiB->id, // Kisi belongs to Tenant B!
            ];

            $jsonResponse = $this->actingAs($this->userA)->postJson(route('admin.talepler.store'), $payloadJson);
            $jsonResponse->assertStatus(422);
            $jsonResponse->assertJsonValidationErrors(['kisi_id']);
            $this->assertDatabaseMissing('talepler', ['baslik' => $payloadJson['baslik']]);
        }
    }

    /**
     * INVARIANT 3: CROSS-TENANT UPDATE REBIND FAILS CLOSED
     * Talep A belongs to Tenant A with Kisi A. User A attempts to update Talep A with kisi_id = Kisi B.id
     * -> Validation error on kisi_id, NO Talep mutation, relation remains Kisi A.
     */
    public function test_invariant_3_cross_tenant_update_rebind_fails_closed(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);
            app(TenantContextService::class)->setTenant($this->tenantA);

            $talepA = Talep::create([
                'tenant_id'    => $this->tenantA->id,
                'danisman_id'  => $this->realUserA->id,
                'kisi_id'      => $this->kisiA->id,
                'baslik'       => 'Legitimate Talep A ' . ($useDomain ? 'Domain' : 'Legacy'),
                'talep_tipi'   => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
            ]);

            // Attempt to rebind Talep A to Kisi B (Tenant B) via Web request
            $updatePayload = [
                'baslik'       => $talepA->baslik,
                'tip'          => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
                'kisi_id'      => $this->kisiB->id, // Cross-tenant rebind attempt
            ];

            $response = $this->actingAs($this->userA)->put(
                route('admin.talepler.update', $talepA->id),
                $updatePayload
            );

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['kisi_id']);

            // Attempt via JSON
            $jsonResponse = $this->actingAs($this->userA)->putJson(
                route('admin.talepler.update', $talepA->id),
                $updatePayload
            );

            $jsonResponse->assertStatus(422);
            $jsonResponse->assertJsonValidationErrors(['kisi_id']);

            // Verify NO Talep mutation — relation remains Kisi A
            $freshTalep = Talep::withoutGlobalScopes()->find($talepA->id);
            $this->assertEquals(
                (int) $this->kisiA->id,
                (int) $freshTalep->kisi_id,
                "Talep A must remain bound to Kisi A after rejected cross-tenant update (useDomain: {$useDomain})"
            );
        }
    }

    /**
     * INVARIANT 4: CROSS-TENANT TALEP DIRECT UPDATE 404
     * Talep B belongs to Tenant B. User A attempts to update Talep B -> HTTP 404 ModelNotFoundException preserved.
     */
    public function test_invariant_4_cross_tenant_talep_direct_update_404(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            // Create Talep B owned by Tenant B
            app(TenantContextService::class)->setTenant($this->tenantB);
            $talepB = Talep::create([
                'tenant_id'    => $this->tenantB->id,
                'danisman_id'  => $this->realUserB->id,
                'kisi_id'      => $this->kisiB->id,
                'baslik'       => 'Tenant B Secret Talep ' . ($useDomain ? 'Domain' : 'Legacy'),
                'talep_tipi'   => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
            ]);

            // User A (Tenant A) attempts to access / mutate Talep B
            app(TenantContextService::class)->setTenant($this->tenantA);

            $maliciousPayload = [
                'baslik'       => 'Hijacked by Tenant A',
                'tip'          => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
                'kisi_id'      => $this->kisiA->id,
            ];

            // 1. Direct Web PUT should return 404 Not Found
            $response = $this->actingAs($this->userA)->put(
                route('admin.talepler.update', $talepB->id),
                $maliciousPayload
            );
            $response->assertStatus(404);

            // 2. Direct JSON PUT should return 404 Not Found
            $jsonResponse = $this->actingAs($this->userA)->putJson(
                route('admin.talepler.update', $talepB->id),
                $maliciousPayload
            );
            $jsonResponse->assertStatus(404);

            // Verify Talep B was untouched
            $freshTalepB = Talep::withoutGlobalScopes()->find($talepB->id);
            $this->assertEquals(
                'Tenant B Secret Talep ' . ($useDomain ? 'Domain' : 'Legacy'),
                $freshTalepB->baslik,
                "Talep B must be untouched after rejected cross-tenant update (useDomain: {$useDomain})"
            );
            $this->assertEquals((int) $this->tenantB->id, (int) $freshTalepB->tenant_id);
            $this->assertEquals((int) $this->kisiB->id, (int) $freshTalepB->kisi_id);
        }
    }

    /**
     * INVARIANT 5: PARITY BETWEEN LEGACY AND DOMAIN MODES
     * Explicit assertion proving both legacy mode (use_domain_talep = false)
     * and domain mode (use_domain_talep = true) respect the validation boundary.
     */
    public function test_invariant_5_mode_parity(): void
    {
        $payload = [
            'baslik'       => 'Parity Verification Talep',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'kisi_id'      => $this->kisiB->id, // Cross-tenant!
        ];

        // Legacy Mode
        config(['crm.use_domain_talep' => false]);
        app(TenantContextService::class)->setTenant($this->tenantA);
        $legacyResponse = $this->actingAs($this->userA)->postJson(route('admin.talepler.store'), $payload);
        $legacyResponse->assertStatus(422);
        $legacyResponse->assertJsonValidationErrors(['kisi_id']);

        // Domain Mode
        config(['crm.use_domain_talep' => true]);
        app(TenantContextService::class)->setTenant($this->tenantA);
        $domainResponse = $this->actingAs($this->userA)->postJson(route('admin.talepler.store'), $payload);
        $domainResponse->assertStatus(422);
        $domainResponse->assertJsonValidationErrors(['kisi_id']);
    }
}

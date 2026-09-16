<?php

namespace Tests\Feature\Security;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\V2\Ilan as V2Ilan;
use App\Enums\IlanDurumu;
use App\Services\SaaS\TenantContextService;
use Tests\TestCase;

/**
 * V2 Ilan Authorization — 5 Senaryo Boundary Test Suite
 *
 * Canonical pattern:
 * 1. TenantContextService::setTenant($tenant) — her test senaryosundan ONCE
 * 2. actingAs($user, 'sanctum') — HTTP isteği ile
 *
 * Scope chain (route binding → V2\Ilan → CountryScope):
 *   ulke_id match  → model bulunur → controller auth check
 *   ulke_id miss   → boş model (exists=false) → 404 (model.exists kontrolü sonrası)
 *
 * Scope chain (UpdateIlanAction → App\Models\Ilan → TenantScope):
 *   tenant_id match → kayıt bulunur → güncelleme devam eder
 *   tenant_id miss → 404 (TenantScope kayıt bulamaz)
 */
class V2IlanAuthorizationBoundaryTest extends TestCase
{
    private const TEST_ULKE_ID = 77;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected User $userC; // Aynı tenantA, AYNÜ ülke TEST_ULKE_ID, farklı kullanıcı
    protected V2Ilan $ilanA;      // tenantA, ulke=TEST_ULKE_ID, danisman=userA
    protected V2Ilan $ilanB;       // tenantB, ulke=TEST_ULKE_ID — cross-tenant
    protected V2Ilan $ilanCrossCountry; // tenantA, ulke=88
    protected V2Ilan $ilanLegacy;  // tenantA, ulke_id=NULL

    // ─────────────────────────────────────────────────────────────
    // SETUP — tenant context kurulumu + veri oluşturma
    // ─────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tenants
        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'boundary-a.local'],
            ['name' => 'Boundary Tenant A']
        );
        $this->tenantB = Tenant::firstOrCreate(
            ['domain' => 'boundary-b.local'],
            ['name' => 'Boundary Tenant B']
        );

        // 2. Users — factory create (factory BelongsToTenant hook tenant_id'yi set eder)
        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
        ]);

        app(TenantContextService::class)->setTenant($this->tenantB);
        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
        ]);

        // userC: tenantA, ulke_id=TEST_ULKE_ID, farklı kullanıcı (S2 için)
        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->userC = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
        ]);

        // 3. Ilanlar — withoutEvents ile creating callback bypass
        // BelongsToTenant ve HasCountryScope creating hook'ları auth user'dan
        // override etmeye çalışır — bypass için withoutEvents kullanılır
        $this->ilanA = $this->makeIlan($this->tenantA->id, $this->userA->id, self::TEST_ULKE_ID);
        $this->ilanB = $this->makeIlan($this->tenantB->id, $this->userB->id, self::TEST_ULKE_ID);
        $this->ilanCrossCountry = $this->makeIlan($this->tenantA->id, $this->userA->id, 88);
        $this->ilanLegacy = $this->makeIlan($this->tenantA->id, $this->userA->id, null);
    }

    /**
     * Create V2\Ilan with explicit tenant_id + ulke_id.
     * Uses withoutEvents to bypass BelongsToTenant::creating and HasCountryScope::creating
     * callbacks which would override our explicit values from Auth::user().
     */
    private function makeIlan(int|null $tenantId, int $danismanId, int|null $ulkeId): V2Ilan
    {
        return V2Ilan::withoutEvents(function () use ($tenantId, $danismanId, $ulkeId) {
            return V2Ilan::create([
                'tenant_id' => $tenantId,
                'ulke_id' => $ulkeId,
                'user_id' => $danismanId,
                'danisman_id' => $danismanId,
                'baslik' => 'Boundary Test Ilan ' . uniqid(),
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
                'ilan_no' => rand(100000, 999999),
                'slug' => 'boundary-test-' . uniqid(),
            ]);
        });
    }

    protected function tearDown(): void
    {
        // Cleanup
        foreach (['ilanA', 'ilanB', 'ilanCrossCountry', 'ilanLegacy'] as $key) {
            if (isset($this->$key)) {
                V2Ilan::withoutGlobalScopes()->forceDelete($this->$key->id);
            }
        }
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // Helper: tenant context + actingAs birlikte
    // ─────────────────────────────────────────────────────────────

    private function actingAsWithTenant(User $user, Tenant $tenant): self
    {
        app(TenantContextService::class)->setTenant($tenant);
        return $this->actingAs($user, 'sanctum');
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 1: Pozitif — Aynı tenant + aynı ülke + aynı danisman
    // ─────────────────────────────────────────────────────────────

    public function test_s1_same_tenant_same_country_same_danisman_returns_200(): void
    {
        $this->actingAsWithTenant($this->userA, $this->tenantA);

        $response = $this->putJson("/api/v1/ilanlar/{$this->ilanA->id}", [
            'baslik' => 'S1 Updated',
        ]);

        $this->assertNotEquals(403, $response->status(),
            "S1: Scope buluyorsa auth check 403 vermemeli.");
        $this->assertEquals(200, $response->status(),
            "S1: Kullanıcı kendi ilanını güncelleyebilmeli. Status: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 2: Negatif — Aynı tenant + aynı ülke + farklı danisman
    // Beklenti: 403 (kullanıcı kendi ilanı değil)
    // ─────────────────────────────────────────────────────────────

    public function test_s2_same_tenant_same_country_different_danisman_returns_403(): void
    {
        // ilanA, userA'nın (danisman_id = userA->id)
        // userC, tenantA ve ulke=TEST_ULKE_ID ama farklı kullanıcı
        $this->actingAsWithTenant($this->userC, $this->tenantA);

        $response = $this->putJson("/api/v1/ilanlar/{$this->ilanA->id}", [
            'baslik' => 'S2 Should Be Blocked',
        ]);

        // Scope'lar kayıt bulur (ulke ve tenant eşleşir) ama danisman_id farklı → 403
        $this->assertEquals(403, $response->status(),
            "S2: Farklı danisman → scope bulur ama yetki reddeder → 403. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 3: Negatif — Cross-tenant
    // Beklenti: 404 veya 403
    // TenantScope kayıt bulamaz → 404; veya auth check reddeder → 403
    // ─────────────────────────────────────────────────────────────

    public function test_s3_cross_tenant_returns_404_or_403(): void
    {
        // userA (tenantA), ilanB (tenantB) — cross-tenant
        $this->actingAsWithTenant($this->userA, $this->tenantA);

        $response = $this->putJson("/api/v1/ilanlar/{$this->ilanB->id}", [
            'baslik' => 'S3 Cross Tenant',
        ]);

        // TenantScope veya CountryScope kayıt bulamaz → 404
        // VEYA scope bulur ama auth check reddeder → 403
        $this->assertNotEquals(200, $response->status(),
            "S3: Cross-tenant → kesinlikle reddedilmeli. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 4: Negatif — Cross-country (farklı ülke)
    // Beklenti: 404
    // CountryScope: ilan ulke_id(88) ≠ user ulke_id(77) → kayıt bulunamadı → 404
    // ─────────────────────────────────────────────────────────────

    public function test_s4_cross_country_returns_404(): void
    {
        // userA (ulke_id=77), ilanCrossCountry (ulke_id=88)
        $this->actingAsWithTenant($this->userA, $this->tenantA);

        $response = $this->putJson("/api/v1/ilanlar/{$this->ilanCrossCountry->id}", [
            'baslik' => 'S4 Cross Country',
        ]);

        // CountryScope: user.ulke_id(77) ≠ ilan.ulke_id(88) → kayıt bulunamadı → 404
        $this->assertEquals(404, $response->status(),
            "S4: Farklı ülke → CountryScope kayıt bulamaz → 404. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 5: Negatif — Legacy ilan (ulke_id = NULL)
    // Beklenti: 404
    // CountryScope: ilan.ulke_id(NULL) ≠ user.ulke_id(77) → kayıt bulunamadı → 404
    // ─────────────────────────────────────────────────────────────

    public function test_s5_legacy_null_ulke_id_returns_404(): void
    {
        // userA (ulke_id=77), ilanLegacy (ulke_id=NULL)
        $this->actingAsWithTenant($this->userA, $this->tenantA);

        $response = $this->putJson("/api/v1/ilanlar/{$this->ilanLegacy->id}", [
            'baslik' => 'S5 Legacy',
        ]);

        // CountryScope: NULL ≠ 77 → kayıt bulunamadı → 404
        $this->assertEquals(404, $response->status(),
            "S5: Legacy NULL ilan → CountryScope bulamaz → 404. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 6: Pozitif — Same-tenant, yetkili danisman, DELETE
    // ─────────────────────────────────────────────────────────────

    public function test_s6_same_tenant_authorized_delete_returns_204(): void
    {
        // Yeni bir ilan oluştur (silme testi için)
        $ilanToDelete = V2Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->userA->id,
            'danisman_id' => $this->userA->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]);

        $this->actingAsWithTenant($this->userA, $this->tenantA);

        $response = $this->deleteJson("/api/v1/ilanlar/{$ilanToDelete->id}");

        $this->assertEquals(204, $response->status(),
            "S6: Yetkili kullanıcı kendi ilanını silebilmeli. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 7: Negatif — Cross-tenant DELETE
    // ─────────────────────────────────────────────────────────────

    public function test_s7_cross_tenant_delete_returns_404_or_403(): void
    {
        // userA (tenantA), ilanB (tenantB) — cross-tenant delete
        $this->actingAsWithTenant($this->userA, $this->tenantA);

        $response = $this->deleteJson("/api/v1/ilanlar/{$this->ilanB->id}");

        $this->assertNotEquals(200, $response->status(),
            "S7: Cross-tenant delete reddedilmeli. Got: {$response->status()}");
    }
}

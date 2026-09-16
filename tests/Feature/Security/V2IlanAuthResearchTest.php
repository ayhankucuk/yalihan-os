<?php

namespace Tests\Feature\Security;

use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * V2 Ilan Authorization Research — Production-Like Token Auth Test
 *
 * Bu test sadece araştırma amaçlıdır. Production kodunu DEĞİŞTIRMEZ.
 *
 * @see IlanCrossTenantIsolationTest — mevcut tenant isolation testleri
 * @see TenantIsolationSafetyTest — güvenlik açıkları araştırması
 */
class V2IlanAuthResearchTest extends TestCase
{
    protected Tenant $tenantA;
    protected User $userA;
    protected Ilan $ilanA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'v2-auth-test.local'],
            ['name' => 'V2 Auth Research Tenant']
        );

        // User A: tüm context alanları atanmış
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => 1,
            'aktiflik_durumu' => 1,
        ]);

        // Ilan A: aynı tenant + açık danisman_id
        $this->ilanA = Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => 1,
            'user_id' => $this->userA->id,
            'danisman_id' => $this->userA->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]);
    }

    protected function tearDown(): void
    {
        Ilan::withoutGlobalScopes()
            ->whereIn('id', [$this->ilanA->id ?? 0])
            ->forceDelete();
        parent::tearDown();
    }

    /**
     * GET /api/v1/ilanlar/{id} — read operasyonu kontrolü
     */
    public function test_v2_show_own_listing(): void
    {
        $token = $this->userA->createToken('research-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson("/api/v1/ilanlar/{$this->ilanA->id}");

        dump([
            'GET /ilanlar/{id}' => [
                'status' => $response->status(),
                'body' => $response->json(),
            ],
        ]);

        $this->assertEquals(200, $response->status(), 'GET own listing should succeed');
    }

    /**
     * PUT /api/v1/ilanlar/{id} — güncelleme yetkilendirmesi
     *
     * Soru: Route binding + TenantScope + CountryScope etkileşimi pozitif senaryoyu engelliyor mu?
     */
    public function test_v2_update_own_listing(): void
    {
        $token = $this->userA->createToken('research-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/ilanlar/{$this->ilanA->id}", [
            'baslik' => 'Research Test Update',
        ]);

        // ── Step 1: Verify token directly via auth('sanctum')->id()
        $authUserViaSanctum = auth('sanctum')->user();
        $sanctumId = auth('sanctum')->id();

        // ── Step 2: Check DB directly
        $routeIlan = DB::table('ilanlar')->where('id', $this->ilanA->id)->first();

        // ── Step 3: Simulate the exact same auth check as the controller
        $controllerAuthCheck = $sanctumId !== null && $routeIlan ? ($routeIlan->danisman_id !== $sanctumId) : 'CANNOT EVALUATE';

        dump([
            'TOKEN_CREATED_FOR_USER_ID' => $this->userA->id,
            'AUTH_SANCTUM_USER' => $authUserViaSanctum ? [
                'id' => $authUserViaSanctum->id,
                'tenant_id' => $authUserViaSanctum->tenant_id,
                'ulke_id' => $authUserViaSanctum->ulke_id,
                'class' => get_class($authUserViaSanctum),
            ] : 'NULL',
            'AUTH_SANCTUM_ID' => $sanctumId,
            'DB_RECORD' => $routeIlan ? [
                'ilan_id' => $routeIlan->id,
                'danisman_id' => $routeIlan->danisman_id,
                'danisman_id_TYPE' => gettype($routeIlan->danisman_id),
                'tenant_id' => $routeIlan->tenant_id ?? 'NULL',
                'ulke_id' => isset($routeIlan->ulke_id) ? $routeIlan->ulke_id : 'COLUMN_MISSING',
            ] : 'NOT FOUND',
            'CONTROLLER_AUTH_CHECK' => [
                '$ilan->danisman_id' => $routeIlan->danisman_id ?? 'N/A',
                'auth_id_TYPE' => gettype($sanctumId),
                'danisman_id_TYPE' => $routeIlan ? gettype($routeIlan->danisman_id) : 'N/A',
                'danisman_id !== auth_id' => $controllerAuthCheck,
                'SHOULD_RETURN_403' => $controllerAuthCheck === true,
            ],
            'RESPONSE' => [
                'status' => $response->status(),
                'body' => $response->json(),
            ],
        ]);

        $this->assertEquals(200, $response->status(),
            "Kullanıcı kendi ilanını güncelleyebilmeli. Status: {$response->status()}");
    }
}

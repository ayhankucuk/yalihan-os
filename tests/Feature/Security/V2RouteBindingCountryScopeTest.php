<?php

namespace Tests\Feature\Security;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\V2\Ilan as V2Ilan;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Route Model Binding + CountryScope Chain Isolation Test
 *
 * Kesinleştirilen sorular:
 * 1. CountryScope → kayıt bulamazsa 404 mü döner (Laravel default)?
 * 2. Controller auth check'e ulaşabilir mi?
 * 3. Auth check'te 403 mü döner?
 * 4. Model binding boş instance mı verir yoksa exception mı?
 */
class V2RouteBindingCountryScopeTest extends TestCase
{
    protected Tenant $tenantA;
    protected User $userA;
    protected V2Ilan $ilanA; // V2\Ilan — controller modeli

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'binding-test.local'],
            ['name' => 'Binding Test Tenant']
        );

        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => 99, // Explicit test country ID
            'aktiflik_durumu' => 1,
        ]);

        // V2\Ilan factory kullanarak — controller modeliyle aynı
        $this->ilanA = V2Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->userA->id,
            'danisman_id' => $this->userA->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ulke_id' => 99, // Matches user's ulke_id → scope should FIND it
        ]);
    }

    protected function tearDown(): void
    {
        V2Ilan::withoutGlobalScopes()
            ->whereIn('id', [$this->ilanA->id ?? 0])
            ->forceDelete();
        parent::tearDown();
    }

    /**
     * Senaryo A: ulke_id EŞLEŞİYOR
     * Beklenti: Scope kaydı bulur → auth check'e ulaşır → 200 veya 403 (danisman check'e bağlı)
     */
    public function test_v2_update_with_matching_ulke_id(): void
    {
        $token = $this->userA->createToken('binding-test')->plainTextToken;

        // DB'de gerçekten var mı?
        $dbRecord = DB::table('ilanlar')
            ->where('id', $this->ilanA->id)
            ->first();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/ilanlar/{$this->ilanA->id}", [
            'baslik' => 'Ulke Matched Test',
        ]);

        dump([
            'SCENARIO' => 'ulke_id MATCHED (user:99, ilan:99)',
            'DB_RECORD' => $dbRecord ? [
                'id' => $dbRecord->id,
                'danisman_id' => $dbRecord->danisman_id,
                'ulke_id' => $dbRecord->ulke_id,
            ] : 'NOT FOUND',
            'STATUS' => $response->status(),
            'BODY' => $response->json(),
        ]);

        // Beklenti: 200 (authorization katmaninda onaylandi)
        // Route Model Binding kaydı buldu, yetki denetimi geçti (danisman_id = auth user).
        // Bu test CountryScope + Route Binding + Auth zincirinin doğru çalıştığını doğrular.
        $this->assertNotEquals(404, $response->status(),
            "Scope kaydı BULAMAZSA 404 döner. 404 = CountryScope engelledi.");
        $this->assertEquals(200, $response->status(),
            "Scope buldu ve yetki onayladı. Auth katmanina ulasildi — 200 = dogru davranis.");
    }

    /**
     * Senaryo B: ulke_id UYUŞMUYOR
     * Beklenti: Scope kaydı BULAMAZ → 404 (ModelNotFoundException)
     */
    public function test_v2_update_with_mismatched_ulke_id(): void
    {
        // İlanı farklı ülkeye taşı
        V2Ilan::withoutGlobalScopes()
            ->where('id', $this->ilanA->id)
            ->update(['ulke_id' => 1]); // User: 99, Ilan: 1 → MISMATCH

        $token = $this->userA->createToken('binding-test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/ilanlar/{$this->ilanA->id}", [
            'baslik' => 'Ulke Mismatched Test',
        ]);

        dump([
            'SCENARIO' => 'ulke_id MISMATCHED (user:99, ilan:1)',
            'STATUS' => $response->status(),
            'BODY' => $response->json(),
        ]);

        // Beklenti: 404 (CountryScope kaydı bulamaz → Route Model Binding 404)
        // CountryScope ulke_id mismatch → WHERE filtresi kaydı bulamaz → 404
        $this->assertEquals(404, $response->status(),
            "Scope kaydı bulamaz → 404. CountryScope + Route Binding zinciri dogru calisiyor.");
    }

    /**
     * Senaryo C: ulke_id = NULL (legacy ilan)
     * Beklenti: Scope WHERE ulke_id=99 → NULL ile eşleşmez → 404
     */
    public function test_v2_update_with_null_ulke_id_legacy_ilan(): void
    {
        V2Ilan::withoutGlobalScopes()
            ->where('id', $this->ilanA->id)
            ->update(['ulke_id' => null]); // Legacy: ulke_id = NULL

        $token = $this->userA->createToken('binding-test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/ilanlar/{$this->ilanA->id}", [
            'baslik' => 'Legacy NULL Ulke Test',
        ]);

        dump([
            'SCENARIO' => 'ulke_id = NULL (legacy)',
            'STATUS' => $response->status(),
            'BODY' => $response->json(),
        ]);

        // Beklenti: 404 (CountryScope NULL ≠ 99 → kayıt bulamaz → 404)
        // CountryScope WHERE ulke_id=99 → NULL ile eşleşmez → 404
        $this->assertEquals(404, $response->status(),
            "NULL ≠ 99 scope bulamaz → 404. CountryScope + Route Binding zinciri dogru calisiyor.");
    }
}

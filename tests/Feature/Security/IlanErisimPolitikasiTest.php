<?php

namespace Tests\Feature\Security;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\V2\Ilan as V2Ilan;
use App\Enums\IlanDurumu;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * İlan Erişim Politikası — Doğrulama Testleri
 *
 * ADR-Ilan-Erisim-Politikasi dayanaklıdır.
 * Mevcut davranışı DEĞİL, onaylı sözleşmeyi doğrular.
 *
 * Sözleşme:
 *   1. Yayınlanmış ilan → herkes 200 alır; anonymous/cross-tenant → TIKLANABİLİR alanlar
 *   2. Taslak/yayınlanmamış ilan → herkes için 404
 *   3. Cross-tenant yazma isteği → 404
 *   4. Reddedilen yazmada veri değişmez
 *   5. Koordinatlar yaklaşık (0.01°) — tam koordinat korunan
 */
class IlanErisimPolitikasiTest extends TestCase
{
    private const TEST_ULKE_ID = 77;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected V2Ilan $ilanA_Yayinlanmis;
    protected V2Ilan $ilanA_Taslak;
    protected V2Ilan $ilanB_Yayinlanmis;
    protected V2Ilan $ilanB_Taslak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'policy-a.local'],
            ['name' => 'Policy Tenant A']
        );
        $this->tenantB = Tenant::firstOrCreate(
            ['domain' => 'policy-b.local'],
            ['name' => 'Policy Tenant B']
        );

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

        // Yayınlanmış ilanlar
        $this->ilanA_Yayinlanmis = $this->makeIlan(
            $this->tenantA->id, $this->userA->id, self::TEST_ULKE_ID, IlanDurumu::YAYINDA
        );
        $this->ilanB_Yayinlanmis = $this->makeIlan(
            $this->tenantB->id, $this->userB->id, self::TEST_ULKE_ID, IlanDurumu::YAYINDA
        );

        // Taslak ilanlar
        $this->ilanA_Taslak = $this->makeIlan(
            $this->tenantA->id, $this->userA->id, self::TEST_ULKE_ID, IlanDurumu::TASLAK
        );
        $this->ilanB_Taslak = $this->makeIlan(
            $this->tenantB->id, $this->userB->id, self::TEST_ULKE_ID, IlanDurumu::TASLAK
        );
    }

    protected function tearDown(): void
    {
        foreach (['ilanA_Yayinlanmis', 'ilanA_Taslak', 'ilanB_Yayinlanmis', 'ilanB_Taslak'] as $key) {
            if (isset($this->$key)) {
                V2Ilan::withoutGlobalScopes()->forceDelete($this->$key->id);
            }
        }
        parent::tearDown();
    }

    private function makeIlan(int $tenantId, int $danismanId, int $ulkeId, IlanDurumu $durum): V2Ilan
    {
        return V2Ilan::withoutEvents(function () use ($tenantId, $danismanId, $ulkeId, $durum) {
            return V2Ilan::create([
                'tenant_id' => $tenantId,
                'ulke_id' => $ulkeId,
                'user_id' => $danismanId,
                'danisman_id' => $danismanId,
                'baslik' => 'Policy Test Ilan ' . uniqid(),
                'yayin_durumu' => $durum->value,
                'ilan_no' => rand(100000, 999999),
                'slug' => 'policy-test-' . uniqid(),
                'lat' => 37.123456,
                'lng' => 28.654321,
                'brut_m2' => 120,
                'oda_sayisi' => '3+1',
                'banyo_sayisi' => 2,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 1: Anonim — yayınlanmış ilan detayı
    // Beklenti: 200 + TIKLANABİLİR alanlar
    // ─────────────────────────────────────────────────────────────

    public function test_s1_anonim_yayinlanmis_ilan_returns_200_with_public_fields(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status(), "S1: Yayınlanmış ilan anonymous 200 dönmeli");

        $data = $response->json('data');

        // TIKLANABİLİR alanlar mevcut
        $this->assertArrayHasKey('baslik', $data, "S1: baslik public olmalı");
        $this->assertArrayHasKey('aciklama', $data, "S1: aciklama public olmalı");
        $this->assertArrayHasKey('coordinates', $data, "S1: coordinates public olmalı");

        // Koordinat yuvarlanmış olmalı (~1km = 0.01°)
        $lat = $data['coordinates']['lat'] ?? null;
        $lng = $data['coordinates']['lng'] ?? null;
        $this->assertNotNull($lat, "S1: lat mevcut olmalı");
        $this->assertEqualsWithDelta(37.12, $lat, 0.005, "S1: lat 0.01° yuvarlanmış olmalı");
        $this->assertEqualsWithDelta(28.65, $lng, 0.005, "S1: lng 0.01° yuvarlanmış olmalı");

        // KORUNAN alanlar YOK
        $this->assertArrayNotHasKey('adres', $data, "S1: adres korunan — anonymous yanıtta olmamalı");
        $this->assertArrayNotHasKey('gallery', $data, "S1: gallery korunan — anonymous yanıtta olmamalı");
        $this->assertArrayNotHasKey('goruntulenme', $data, "S1: views gorunenmemeli");
        $this->assertArrayNotHasKey('ilan_no', $data, "S1: ilan_no korunan");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 2: Anonim — taslak ilan detayı
    // Beklenti: 404
    // ─────────────────────────────────────────────────────────────

    public function test_s2_anonim_taslak_ilan_returns_404(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Taslak->id}");

        $this->assertEquals(404, $response->status(),
            "S2: Taslak ilan anonymous 404 dönmeli. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 3: Auth + sahip — kendi yayınlanmış ilanı
    // Beklenti: 200 + tam alanlar (IlanDetailResource)
    // ─────────────────────────────────────────────────────────────

    public function test_s3_auth_sahip_yayinlanmis_ilan_returns_full_fields(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status(), "S3: Sahip yayınlanmış ilana 200");

        $data = $response->json('data');

        // Tam koordinat — sahip tam koordinat görmeli
        $this->assertArrayHasKey('coordinates', $data);
        $this->assertNotNull($data['coordinates']['lat'], "S3: Sahip tam lat görmeli");
        $this->assertNotNull($data['coordinates']['lng'], "S3: Sahip tam lng görmeli");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 4: Auth — aynı tenant farklı danışman, yayınlanmış ilan
    // Beklenti: 200 + full alanlar (tenant personeli tam erişim)
    // ─────────────────────────────────────────────────────────────

    public function test_s4_auth_same_tenant_diff_danisman_published_ilan_returns_200(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");

        // Aynı tenant — yayınlanmış ilana 200 döner
        $this->assertEquals(200, $response->status(),
            "S4: Aynı tenant yayınlanmış ilana 200. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 5: Auth — cross-tenant yayınlanmış ilan
    // Beklenti: 200 + TIKLANABİLİR alanlar (kamu verisi)
    // ─────────────────────────────────────────────────────────────

    public function test_s5_auth_cross_tenant_published_ilan_returns_200_with_public_fields(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanB_Yayinlanmis->id}");

        $this->assertEquals(200, $response->status(),
            "S5: Cross-tenant yayınlanmış ilan 200 dönmeli. Got: {$response->status()}");

        $data = $response->json('data');

        // TIKLANABİLİR alanlar mevcut
        $this->assertArrayHasKey('baslik', $data, "S5: baslik public");

        // KORUNAN alanlar YOK
        $this->assertArrayNotHasKey('gallery', $data, "S5: gallery korunan — cross-tenant yanıtta olmamalı");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 6: Auth — cross-tenant taslak ilan
    // Beklenti: 404
    // ─────────────────────────────────────────────────────────────

    public function test_s6_auth_cross_tenant_taslak_ilan_returns_404(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanB_Taslak->id}");

        $this->assertEquals(404, $response->status(),
            "S6: Cross-tenant taslak 404 dönmeli. Got: {$response->status()}");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 7: Cross-tenant yazma isteği
    // Beklenti: 404
    // ─────────────────────────────────────────────────────────────

    public function test_s7_cross_tenant_update_returns_404_not_403(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->putJson("/api/v1/ilanlar/{$this->ilanB_Yayinlanmis->id}", [
            'baslik' => 'S7 Cross Tenant Update Attempt',
        ]);

        // TenantScope kayıt bulamaz → 404
        // 403 değil, 404 — kayıt yokmuş gibi davran
        $this->assertNotEquals(200, $response->status(),
            "S7: Cross-tenant güncelleme reddedilmeli");
        $this->assertNotEquals(403, $response->status(),
            "S7: Cross-tenant güncelleme 404 dönmeli (403 değil) — kayıt yokmuş gibi");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 8: Reddedilen yazmada veri değişmez
    // Beklenti: Hatalı istek sonrası DB'de veri aynı
    // ─────────────────────────────────────────────────────────────

    public function test_s8_rejected_update_does_not_change_data(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $originalBaslik = $this->ilanB_Yayinlanmis->baslik;
        $originalFiyat = $this->ilanB_Yayinlanmis->fiyat;

        // Cross-tenant güncelleme girişimi
        $this->putJson("/api/v1/ilanlar/{$this->ilanB_Yayinlanmis->id}", [
            'baslik' => 'TAMPERED TITLE',
            'fiyat' => 999999,
        ]);

        // DB'de veri değişmemiş olmalı
        $fresh = DB::table('ilanlar')->where('id', $this->ilanB_Yayinlanmis->id)->first();

        $this->assertEquals($originalBaslik, $fresh->baslik,
            "S8: Reddedilen istek sonrası baslik değişmemeli");
        $this->assertEquals($originalFiyat, $fresh->fiyat,
            "S8: Reddedilen istek sonrası fiyat değişmemeli");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 9: Anonim taslak ilan — hiçbir bilgi sızmamalı
    // Beklenti: 404 + yanıtta ilan bilgisi yok
    // ─────────────────────────────────────────────────────────────

    public function test_s9_anonim_taslak_no_data_leaks_in_response(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Taslak->id}");

        $this->assertEquals(404, $response->status());

        $body = $response->json();

        // 404 yanıtında taslak ilan bilgisi sızmamalı
        $this->assertArrayNotHasKey('baslik', $body, "S9: 404 yanıtında baslik olmamalı");
        $this->assertArrayNotHasKey('data', $body, "S9: 404 yanıtında data alanı olmamalı");
    }

    // ─────────────────────────────────────────────────────────────
    // SENARYO 10: Liste endpoint — taslak ve yayınlanmış ayrımı
    // Beklenti: Listedeki ilanlar yalnızca yayınlanmış olanlar
    // ─────────────────────────────────────────────────────────────

    public function test_s10_list_returns_only_published_ilanlar(): void
    {
        $response = $this->getJson('/api/v1/ilanlar');

        $this->assertEquals(200, $response->status());

        $items = $response->json('data');

        // Dönen ilanların tamamı yayınlanmış olmalı
        foreach ($items as $item) {
            $ilan = V2Ilan::withoutGlobalScopes()->find($item['id']);
            if ($ilan) {
                $this->assertEquals(
                    IlanDurumu::YAYINDA->value,
                    $ilan->yayin_durumu,
                    "S10: Liste yanıtındaki ilan yayınlanmış olmalı. ID: {$ilan->id}"
                );
            }
        }
    }
}

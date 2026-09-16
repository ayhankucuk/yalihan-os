<?php

namespace Tests\Feature\Security;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Models\V2\Ilan as V2Ilan;
use App\Enums\IlanDurumu;
use App\Services\SaaS\TenantContextService;
use Tests\TestCase;

/**
 * İlan API Sözleşmesi — Alan Kanonik Uyumluluk Testleri
 *
 * Üç çıktı yolunun her birinde döndürülen alanları doğrular:
 *   Path A: IlanDetailResource  (owner/same-tenant auth)
 *   Path B: IlanPublicDetailResource (anonim/cross-tenant auth)
 *   Path C: IlanListResource    (liste endpoint)
 *
 * Ayrıca meşru karşıtı kontrat ihlallerini test eder —
 * yani bir test başarısız olursa kontrat karşıtı bir değişiklik
 * yapılmış demektir.
 */
class IlanApiContractTest extends TestCase
{
    private const TEST_ULKE_ID = 77;

    protected Tenant $tenantA;
    protected User $userA;
    protected V2Ilan $ilanYayinlanmis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'contract-a.local'],
            ['name' => 'Contract Tenant A']
        );

        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ulke_id' => self::TEST_ULKE_ID,
            'aktiflik_durumu' => 1,
        ]);

        $this->ilanYayinlanmis = V2Ilan::withoutEvents(function () {
            return V2Ilan::create([
                'tenant_id' => $this->tenantA->id,
                'ulke_id' => self::TEST_ULKE_ID,
                'user_id' => $this->userA->id,
                'danisman_id' => $this->userA->id,
                'baslik' => 'Contract Test Ilan ' . uniqid(),
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
                'ilan_no' => rand(100000, 999999),
                'slug' => 'contract-test-' . uniqid(),
                'lat' => 37.123456,
                'lng' => 28.654321,
                'brut_m2' => 120,
                'oda_sayisi' => '3+1',
                'banyo_sayisi' => 2,
            ]);
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->ilanYayinlanmis)) {
            V2Ilan::withoutGlobalScopes()->forceDelete($this->ilanYayinlanmis->id);
        }
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // PATH A: IlanDetailResource — tam alanlar (owner/same-tenant auth)
    // ─────────────────────────────────────────────────────────────────────

    public function test_path_a_detail_resource_has_expected_fields(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $this->assertEquals(200, $response->status());

        $data = $response->json('data');

        // Zorunlu alanlar mevcut olmalı
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('location', $data);
        $this->assertArrayHasKey('attributes', $data);
        $this->assertArrayHasKey('gallery', $data);
        $this->assertArrayHasKey('agent', $data);
        $this->assertArrayHasKey('meta', $data);

        // location.coordinates tam koordinat olmalı
        $coords = $data['location']['coordinates'];
        $this->assertArrayHasKey('lat', $coords);
        $this->assertArrayHasKey('lng', $coords);
        $this->assertEqualsWithDelta(37.123456, $coords['lat'], 0.0001);

        // Gallery mevcut — tam boyutlu fotoğraflar
        $this->assertIsArray($data['gallery']);
    }

    public function test_path_a_detail_resource_hides_sensitive_address(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $data = $response->json('data');

        // Detay kaynağında adres tam olarak mevcut OLMAMALI
        // (IlanDetailResource'da adres yok — sadece il/ilce/mahalle)
        $this->assertArrayNotHasKey('adres', $data,
            'Path A: Detay kaynağında adres tam olarak dönmemeli');
    }

    public function test_path_a_agent_has_full_contact_on_same_tenant(): void
    {
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $agent = $response->json('data.agent');

        // Same tenant auth → tam agent bilgisi
        $this->assertArrayHasKey('phone', $agent);
        $this->assertArrayHasKey('email', $agent);
        $this->assertArrayHasKey('whatsapp', $agent);
    }

    // ─────────────────────────────────────────────────────────────────────
    // PATH B: IlanPublicDetailResource — TIKLANABİLİR alanlar (anonim)
    // ─────────────────────────────────────────────────────────────────────

    public function test_path_b_public_detail_resource_has_expected_fields(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $this->assertEquals(200, $response->status());

        $data = $response->json('data');

        // Public detay kaynağı zorunlu alanları
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('baslik', $data);
        $this->assertArrayHasKey('coordinates', $data);
        $this->assertArrayHasKey('ozellikler', $data);

        // coordinates — yaklaşık koordinat
        $coords = $data['coordinates'];
        $this->assertEquals(floor(37.123456 * 100) / 100, $coords['lat'],
            'Path B: Koordinat 0.01° yuvarlanmış olmalı');
        $this->assertEquals(floor(28.654321 * 100) / 100, $coords['lng'],
            'Path B: Koordinat 0.01° yuvarlanmış olmalı');

        // Tam koordinat YOK
        $this->assertNotEquals(37.123456, $coords['lat']);
    }

    public function test_path_b_public_detail_resource_hides_sensitive_fields(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $data = $response->json('data');

        // Hassas alanlar KORUNAN (public detay kaynağında)
        $forbidden = ['adres', 'telefon', 'email', 'whatsapp', 'sanal_tur_url',
            'youtube_video_url', 'goruntulenme_sayisi', 'ilan_no', 'gallery', 'kapak_resmi'];

        foreach ($forbidden as $field) {
            $this->assertArrayNotHasKey($field, $data,
                "Path B: {$field} alanı public detay kaynağında olmamalı");
        }

        // BLIND SPOT KAPATILDI: danisman içindeki hassas alanlar da denetlenmeli.
        // Madde 20.6: danisman_id kesinlikle yasak. danisman altındaki telefon/email/whatsapp
        // da onaylı kontratta yok — IlanPublicDetailResource kodunu okuyarak doğrulanmalı.
        $this->assertArrayHasKey('danisman', $data, 'Path B: danisman alanı mevcut olmalı');
        $danisman = $data['danisman'];
        $this->assertArrayNotHasKey('phone', $danisman,
            'Path B: danisman içinde telefon olmamalı');
        $this->assertArrayNotHasKey('email', $danisman,
            'Path B: danisman içinde email olmamalı');
        $this->assertArrayNotHasKey('whatsapp', $danisman,
            'Path B: danisman içinde whatsapp olmamalı');
        $this->assertArrayNotHasKey('title', $danisman,
            'Path B: danisman içinde title olmamalı');
    }

    public function test_path_b_public_detail_resource_has_no_agent_section(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $data = $response->json('data');

        // Public kaynakta agent bölümü YOK
        $this->assertArrayNotHasKey('agent', $data,
            'Path B: Public detay kaynağında agent bölümü olmamalı');
    }

    public function test_path_b_public_detail_resource_contains_precision_note(): void
    {
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $coords = $response->json('data.coordinates');

        // Precision notu mevcut olmalı
        $this->assertArrayHasKey('_precision_note', $coords,
            'Path B: Koordinat yanıtında _precision_note olmalı');
        $this->assertStringContainsString('1km', $coords['_precision_note'],
            'Path B: _precision_note 1km yaklaşık ifadesini içermeli');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PATH C: IlanListResource — liste görünümü (anonim)
    // ─────────────────────────────────────────────────────────────────────

    public function test_path_c_list_resource_returns_published_only(): void
    {
        $response = $this->getJson('/api/v1/ilanlar');

        $this->assertEquals(200, $response->status());

        $items = $response->json('data');

        if (count($items) === 0) {
            $this->markTestSkipped('Liste boş — test için en az bir yayınlanmış ilan gerekli');
            return;
        }

        foreach ($items as $item) {
            // Yayınlanmamış ilan listede olmamalı
            $ilan = V2Ilan::withoutGlobalScopes()->find($item['id']);
            if ($ilan) {
                $this->assertEquals(
                    IlanDurumu::YAYINDA->value,
                    $ilan->yayin_durumu,
                    'Path C: Listedeki ilan yayınlanmış olmalı'
                );
            }
        }
    }

    public function test_path_c_list_resource_hides_sensitive_fields(): void
    {
        $response = $this->getJson('/api/v1/ilanlar');

        $this->assertEquals(200, $response->status());

        $items = $response->json('data');

        if (count($items) === 0) {
            $this->markTestSkipped('Liste boş');
            return;
        }

        $item = $items[0];

        // Hassas alanlar KORUNAN (liste kaynağında)
        $forbidden = ['adres', 'telefon', 'email', 'whatsapp', 'sanal_tur_url',
            'gallery', 'goruntulenme_sayisi', 'ilan_no', 'favori_sayisi'];

        foreach ($forbidden as $field) {
            $this->assertArrayNotHasKey($field, $item,
                "Path C: Liste kaynağında {$field} olmamalı");
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // kontrat KARŞITI senaryolar — meşru negatif testler
    // Bir test başarısız olursa kontrat bozulmuş demektir.
    // ─────────────────────────────────────────────────────────────────────

    public function test_contract_violation_full_coords_not_in_public_path(): void
    {
        // KOŞUL: Public koordinat tam değer içeriyorsa → KONTRAKT İHLALİ
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $coords = $response->json('data.coordinates');

        // Yaklaşık koordinat ile tam koordinat farklı olmalı
        $approxLat = floor(37.123456 * 100) / 100;
        $this->assertNotEquals(37.123456, $approxLat,
            'Test verisi: tam ve yaklaşık koordinat farklı olmalı');

        // Ayrıştırıcı değer: 37.126 → floor=37.12, round=37.13.
        // Kaynak floor() kullanıyorsa 37.12, round() kullanıyorsa 37.13 döner.
        // Assertion floor'a sabitlenmeli — kaynak kod okunarak onaylanmalı.
        $this->assertEquals(37.12, $coords['lat'],
            'KONTRAKT İHLALİ: Public path koordinatı 37.12 olmalı (floor yöntemi ile)');

        // Ayrıştırıcı değer 28.654321 → floor=28.65, round=28.65 (eşit, risk düşük ama sabit assertion daha güçlü)
        $this->assertEquals(28.65, $coords['lng'],
            'KONTRAKT İHLALİ: Public path koordinatı 28.65 olmalı (floor yöntemi ile)');
    }

    public function test_contract_violation_phone_not_in_anonim_path(): void
    {
        // Anonim kullanıcıya telefon sızmamalı
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $data = $response->json('data');

        // Koşullu alan: telefon doğrudan veya iç içe nesne içinde
        $flat = json_encode($data);
        $this->assertStringNotContainsString('+90555', $flat,
            'KONTRAKT İHLALİ: Anonim path telefon numarası sızdırıyor!');
    }

    public function test_contract_violation_gallery_not_in_public_detail(): void
    {
        // Public detail'da galeri fotoğrafları olmamalı
        $response = $this->getJson("/api/v1/ilanlar/{$this->ilanYayinlanmis->id}");

        $data = $response->json('data');

        $this->assertArrayNotHasKey('gallery', $data,
            'KONTRAKT İHLALİ: Public detail\'da gallery mevcut!');
        $this->assertArrayNotHasKey('fotograflar', $data,
            'KONTRAKT İHLALİ: Public detail\'da fotograflar mevcut!');
    }

    public function test_contract_unpublished_returns_404_not_empty_200(): void
    {
        // Yayınlanmamış ilan 200 BOŞ değil 404 dönmeli
        $taslak = V2Ilan::withoutEvents(function () {
            return V2Ilan::create([
                'tenant_id' => $this->tenantA->id,
                'ulke_id' => self::TEST_ULKE_ID,
                'user_id' => $this->userA->id,
                'danisman_id' => $this->userA->id,
                'baslik' => 'Draft Contract ' . uniqid(),
                'yayin_durumu' => IlanDurumu::TASLAK->value,
                'ilan_no' => rand(100000, 999999),
                'slug' => 'draft-contract-' . uniqid(),
                'lat' => 37.123456,
                'lng' => 28.654321,
                'brut_m2' => 120,
                'oda_sayisi' => '3+1',
                'banyo_sayisi' => 2,
            ]);
        });

        try {
            $response = $this->getJson("/api/v1/ilanlar/{$taslak->id}");

            $this->assertEquals(404, $response->status(),
                'KONTRAKT İHLALİ: Taslak ilan 404 değil!');

            // 404 yanıtında data olmamalı
            $this->assertArrayNotHasKey('data', $response->json(),
                'KONTRAKT İHLALİ: 404 yanıtında data alanı var!');
        } finally {
            V2Ilan::withoutGlobalScopes()->forceDelete($taslak->id);
        }
    }
}

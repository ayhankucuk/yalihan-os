<?php

namespace Tests\Feature;

use App\Services\Location\SpatialScoutService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpatialScoutServiceTest extends TestCase
{

    private SpatialScoutService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip: pois tablosu migration'ı henüz oluşturulmadı
        $this->markTestSkipped('Legacy SpatialScoutServiceTest: pois tablosu migration eksik');

        $this->service = app(SpatialScoutService::class);
    }

    public function test_plaj_cok_yakin_oldugunda_konum_etki_skoru_yuksek_olur()
    {
        // Merkez koordinat
        $lat = 37.000000;
        $lng = 27.000000;

        // Yaklaşık 200m ötede plaj
        $offset = 0.0018; // ~200m

        DB::table('pois')->insert([
            'poi_adi' => 'Test Plaj',
            'poi_turu' => 'beach',
            'poi_kategorisi' => 'Deniz',
            'lat' => $lat + $offset,
            'lng' => $lng,
            'rating' => 4.5,
            'ek_veri' => json_encode([]),
            'gosterim_sirasi' => 1,
            'aktiflik_durumu' => true,
        ]);

        $sonuc = $this->service->hesapla($lat, $lng, null, 1.0);

        $this->assertArrayHasKey('konum_etki_skoru', $sonuc);
        $this->assertArrayHasKey('poi_analiz_matrisi', $sonuc);
        $this->assertArrayHasKey('deger_odagi_mesafesi', $sonuc);
        $this->assertArrayHasKey('firsat_adayi', $sonuc);

        $this->assertIsFloat($sonuc['konum_etki_skoru']);
        $this->assertGreaterThan(60.0, $sonuc['konum_etki_skoru']);
        $this->assertTrue($sonuc['firsat_adayi']);
        $this->assertNotEmpty($sonuc['poi_analiz_matrisi']);
        $this->assertIsInt($sonuc['deger_odagi_mesafesi']);
    }

    public function test_sanayi_merkezde_ise_konum_etki_skoru_duser()
    {
        $lat = 37.000000;
        $lng = 27.000000;

        // Yaklaşık 1000m ötede sanayi bölgesi
        $offset = 0.009; // ~1000m

        DB::table('pois')->insert([
            'poi_adi' => 'Sanayi Bölgesi',
            'poi_turu' => 'industrial_zone',
            'poi_kategorisi' => 'Sanayi',
            'lat' => $lat + $offset,
            'lng' => $lng,
            'rating' => 0.0,
            'ek_veri' => json_encode([]),
            'gosterim_sirasi' => 1,
            'aktiflik_durumu' => true,
        ]);

        $sonuc = $this->service->hesapla($lat, $lng, null, 2.0);

        $this->assertLessThanOrEqual(50.0, $sonuc['konum_etki_skoru']);
        $this->assertFalse($sonuc['firsat_adayi']);
        $this->assertNotEmpty($sonuc['poi_analiz_matrisi']);
    }

    public function test_skor_her_zaman_sifir_ile_yuz_arasinda()
    {
        $lat = 37.000000;
        $lng = 27.000000;

        // Çeşitli POI kombinasyonları ekle
        DB::table('pois')->insert([
            [
                'poi_adi' => 'Hastane',
                'poi_turu' => 'amenity.hospital',
                'poi_kategorisi' => 'Sağlık',
                'lat' => $lat + 0.005,
                'lng' => $lng,
                'rating' => 4.0,
                'ek_veri' => json_encode([]),
                'gosterim_sirasi' => 1,
                'aktiflik_durumu' => true,
            ],
            [
                'poi_adi' => 'AVM',
                'poi_turu' => 'mall',
                'poi_kategorisi' => 'Alışveriş',
                'lat' => $lat,
                'lng' => $lng + 0.01,
                'rating' => 4.2,
                'ek_veri' => json_encode([]),
                'gosterim_sirasi' => 2,
                'aktiflik_durumu' => true,
            ],
        ]);

        $sonuc = $this->service->hesapla($lat, $lng, null, 2.0);

        $this->assertGreaterThanOrEqual(0.0, $sonuc['konum_etki_skoru']);
        $this->assertLessThanOrEqual(100.0, $sonuc['konum_etki_skoru']);
    }
}

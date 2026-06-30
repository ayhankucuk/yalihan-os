<?php

namespace Database\Seeders;

use App\Models\Ilan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Ticari İlan + Arsa Polygon Demo Seeder
 *
 * 1) 5 ticari ilan: Ofis, Dükkan, Fabrika, Depo (İşyeri alt kategorileri)
 * 2) Mevcut 5 arsa ilanına GeoJSON polygon + geometry_type güncelleme
 *
 * Toplam ilan sayısı: 25 → 30
 */
class TicariIlanVeArsaPolygonSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTicariIlanlar();
        $this->updateArsaPolygons();
    }

    private function seedTicariIlanlar(): void
    {
        $ilanlar = [
            // Ticari 1: Ofis — Konacık merkez
            [
                'baslik' => 'Konacık Midtown AVM Karşısı 120m² Ofis',
                'aciklama' => 'Midtown AVM karşısında, ana cadde üzeri, 120m² net kullanım alanlı ofis. Açık otopark, 24 saat güvenlik, klima dahil.',
                'fiyat' => 4500000,
                'ana_kategori_id' => 2, // İşyeri
                'alt_kategori_id' => 11, // Ofis
                'mahalle_id' => 33, // Konacık
                'mahalle' => 'Konacık',
                'lat' => 37.0505,
                'lng' => 27.4110,
                'net_m2' => 120,
                'brut_m2' => 140,
                'kat' => 2,
                'toplam_kat' => 4,
                'bina_yasi' => 2021,
                'isitma' => 'Klima',
            ],

            // Ticari 2: Dükkan — Bodrum Çarşı
            [
                'baslik' => 'Bodrum Çarşı İçi Köşe Başı Dükkan',
                'aciklama' => 'Bodrum çarşı merkezinde, köşe başı konumda 80m² dükkan. Yüksek yaya trafiği, turist bölgesi.',
                'fiyat' => 8500000,
                'ana_kategori_id' => 2,
                'alt_kategori_id' => 12, // Dükkan
                'mahalle_id' => 10, // Çarşı
                'mahalle' => 'Çarşı',
                'lat' => 37.0348,
                'lng' => 27.4298,
                'net_m2' => 80,
                'brut_m2' => 95,
                'kat' => 0,
                'toplam_kat' => 2,
                'bina_yasi' => 2005,
                'isitma' => 'Klima',
            ],

            // Ticari 3: Dükkan — Turgutreis cadde üzeri
            [
                'baslik' => 'Turgutreis Ana Cadde Üzeri Kiralık Dükkan',
                'aciklama' => 'Turgutreis ana caddesinde, 150m² geniş cepheli dükkan. Market, eczane veya restoran konseptine uygun.',
                'fiyat' => 3200000,
                'ana_kategori_id' => 2,
                'alt_kategori_id' => 12, // Dükkan
                'mahalle_id' => 47, // Turgutreis
                'mahalle' => 'Turgutreis',
                'lat' => 37.0095,
                'lng' => 27.2620,
                'net_m2' => 150,
                'brut_m2' => 170,
                'kat' => 0,
                'toplam_kat' => 1,
                'bina_yasi' => 2018,
                'isitma' => 'Klima',
            ],

            // Ticari 4: Depo — Mumcular sanayi yakını
            [
                'baslik' => 'Mumcular Sanayi Bölgesi 500m² Depo',
                'aciklama' => 'Mumcular yolu üzerinde, ana artere cepheli 500m² kapalı depo alanı. TIR giriş-çıkışına uygun, 6m tavan yüksekliği.',
                'fiyat' => 5000000,
                'ana_kategori_id' => 2,
                'alt_kategori_id' => 14, // Depo
                'mahalle_id' => 39, // Mumcular
                'mahalle' => 'Mumcular',
                'lat' => 37.1370,
                'lng' => 27.5640,
                'net_m2' => 500,
                'brut_m2' => 550,
                'kat' => 0,
                'toplam_kat' => 1,
                'bina_yasi' => 2015,
                'isitma' => 'Yok',
            ],

            // Ticari 5: Ofis — Yalıkavak marina
            [
                'baslik' => 'Yalıkavak Palmarina Yakını Prestijli Ofis',
                'aciklama' => 'Palmarina\'ya 200m, deniz manzaralı 90m² prestijli ofis. Turizm ve yat acenteleri için ideal konum.',
                'fiyat' => 7200000,
                'ana_kategori_id' => 2,
                'alt_kategori_id' => 11, // Ofis
                'mahalle_id' => 53, // Yalıkavak
                'mahalle' => 'Yalıkavak',
                'lat' => 37.1010,
                'lng' => 27.2975,
                'net_m2' => 90,
                'brut_m2' => 105,
                'kat' => 3,
                'toplam_kat' => 5,
                'bina_yasi' => 2023,
                'isitma' => 'Klima',
            ],
        ];

        $created = 0;

        foreach ($ilanlar as $data) {
            $data['slug'] = Str::slug($data['baslik']) . '-' . Str::random(5);
            $data['para_birimi'] = 'TRY';
            $data['il_id'] = 48; // Muğla
            $data['ilce_id'] = 1; // Bodrum
            $data['il'] = 'Muğla';
            $data['ilce'] = 'Bodrum';
            $data['country_code'] = 'TR';
            $data['yayin_durumu'] = \App\Enums\IlanDurumu::YAYINDA;
            $data['geometry_type'] = 'point';

            Ilan::create($data);
            $created++;
        }

        $this->command->info("✅ Ticari İlan: {$created} ilan oluşturuldu (toplam: " . Ilan::count() . ")");
    }

    private function updateArsaPolygons(): void
    {
        // Realistic Bodrum parcel polygons (GeoJSON format: [lng, lat])
        $polygons = [
            // Arsa 1 (ID 34): Yalıkavak — 500m² parsel
            34 => [
                'type' => 'Polygon',
                'coordinates' => [[[27.2945, 37.1037], [27.2955, 37.1037], [27.2955, 37.1043], [27.2945, 37.1043], [27.2945, 37.1037]]],
            ],
            // Arsa 2 (ID 35): Turgutreis — 750m²
            35 => [
                'type' => 'Polygon',
                'coordinates' => [[[27.2588, 37.0106], [27.2602, 37.0106], [27.2602, 37.0114], [27.2588, 37.0114], [27.2588, 37.0106]]],
            ],
            // Arsa 3 (ID 36): Gümüşlük — 1000m² (irregular parcel)
            36 => [
                'type' => 'Polygon',
                'coordinates' => [[[27.2332, 37.0555], [27.2348, 37.0555], [27.2352, 37.0562], [27.2340, 37.0568], [27.2330, 37.0562], [27.2332, 37.0555]]],
            ],
            // Arsa 4 (ID 37): Konacık — 400m² (ana yola cepheli dikdörtgen)
            37 => [
                'type' => 'Polygon',
                'coordinates' => [[[27.4082, 37.0512], [27.4094, 37.0512], [27.4094, 37.0518], [27.4082, 37.0518], [27.4082, 37.0512]]],
            ],
            // Arsa 5 (ID 38): Mumcular — 5000m² (büyük tarla)
            38 => [
                'type' => 'Polygon',
                'coordinates' => [[[27.5635, 37.1380], [27.5665, 37.1380], [27.5668, 37.1395], [27.5660, 37.1402], [27.5638, 37.1400], [27.5635, 37.1380]]],
            ],
        ];

        $updated = 0;

        foreach ($polygons as $ilanId => $geojson) {
            $ilan = Ilan::find($ilanId);
            if (!$ilan) {
                $this->command->warn("  ⚠️ İlan bulunamadı: ID {$ilanId}");
                continue;
            }

            // Calculate centroid from polygon
            $coords = $geojson['coordinates'][0];
            $latSum = 0;
            $lngSum = 0;
            $count = count($coords) - 1; // Exclude closing point

            for ($i = 0; $i < $count; $i++) {
                $lngSum += $coords[$i][0];
                $latSum += $coords[$i][1];
            }

            $ilan->update([
                'geometry_type' => 'polygon',
                'geometry' => $geojson,
                'lat' => round($latSum / $count, 6),
                'lng' => round($lngSum / $count, 6),
            ]);
            $updated++;
        }

        $this->command->info("✅ Arsa Polygon: {$updated} ilan GeoJSON ile güncellendi.");
    }
}

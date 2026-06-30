<?php

namespace Database\Seeders;

use App\Models\Ilan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * SAB Investor Demo Seeder
 *
 * 3 demo ilanı — MIE v5 Action Mode senaryoları:
 *   A) Güçlü Fırsat (Score 80+, BUY) — Bodrum Merkez, POI yoğun, uygun fiyat
 *   B) Nötr / İzle (Score 50-65, WAIT) — Turgutreis, orta POI, piyasa fiyatı
 *   C) Riskli (Score <40, AVOID) — Mumcular kırsal, düşük POI, yüksek fiyat
 */
class InvestorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = $this->getScenarios();
        $created = 0;

        foreach ($scenarios as $data) {
            $data['slug'] = Str::slug($data['baslik']) . '-demo-' . Str::random(4);
            $data['para_birimi'] = 'TRY';
            $data['il_id'] = 48; // Muğla
            $data['ilce_id'] = 1; // Bodrum
            $data['il'] = 'Muğla';
            $data['ilce'] = 'Bodrum';
            $data['country_code'] = 'TR';
            $data['yayin_durumu'] = \App\Enums\IlanDurumu::YAYINDA;

            foreach (['yola_cephe', 'altyapi_elektrik', 'altyapi_su', 'altyapi_dogalgaz'] as $boolField) {
                if (isset($data[$boolField])) {
                    $data[$boolField] = $data[$boolField] ? 1 : 0;
                }
            }

            Ilan::create($data);
            $created++;
        }

        $this->command->info("✅ Investor Demo Seeder: {$created} senaryo ilanı oluşturuldu.");
    }

    private function getScenarios(): array
    {
        return [
            // ═══════════════════════════════════════════════
            // SENARYO A: GÜÇLÜ FIRSAT — Bodrum Merkez
            // Beklenen: Score 75-85, CTA=BUY, Level=hot
            // Neden: POI yoğun merkez + uygun fiyat + altyapı tam
            // ═══════════════════════════════════════════════
            [
                'baslik' => '[DEMO-A] Bodrum Merkez İmarlı Arsa — Güçlü Fırsat',
                'aciklama' => 'Bodrum merkezde okul, hastane, market ve ulaşıma yakın, konut imarlı 600m² arsa. Tüm altyapı mevcut. Yatırıma son derece uygun lokasyon.',
                'fiyat' => 2200000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 35, // Kumbahçe (Bodrum Merkez)
                'mahalle' => 'Kumbahçe',
                'lat' => 37.0345,
                'lng' => 27.4295,
                'alan_m2' => 600,
                'kaks' => 0.50,
                'taks' => 0.25,
                'yola_cephe' => true,
                'altyapi_elektrik' => true,
                'altyapi_su' => true,
                'altyapi_dogalgaz' => true,
                'imar_statusu' => 'Konut',
                'geometry_type' => 'polygon',
                'geometry' => json_encode([
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [27.4290, 37.0342],
                            [27.4300, 37.0342],
                            [27.4300, 37.0348],
                            [27.4290, 37.0348],
                            [27.4290, 37.0342],
                        ],
                    ],
                ]),
            ],

            // ═══════════════════════════════════════════════
            // SENARYO B: NÖTR / İZLE — Turgutreis
            // Beklenen: Score 45-60, CTA=WATCH, Level=balanced
            // Neden: Orta POI erişimi + piyasa fiyatı
            // ═══════════════════════════════════════════════
            [
                'baslik' => '[DEMO-B] Turgutreis Konut İmarlı Arsa — Nötr Bölge',
                'aciklama' => 'Turgutreis merkezine 1.5km, temel hizmetlere orta mesafede 500m² arsa. Konut imarlı, yola cepheli.',
                'fiyat' => 3800000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 47, // Turgutreis
                'mahalle' => 'Turgutreis',
                'lat' => 37.0135,
                'lng' => 27.2610,
                'alan_m2' => 500,
                'kaks' => 0.30,
                'taks' => 0.15,
                'yola_cephe' => true,
                'altyapi_elektrik' => true,
                'altyapi_su' => true,
                'altyapi_dogalgaz' => false,
                'imar_statusu' => 'Konut',
                'geometry_type' => 'polygon',
                'geometry' => json_encode([
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [27.2605, 37.0132],
                            [27.2615, 37.0132],
                            [27.2615, 37.0138],
                            [27.2605, 37.0138],
                            [27.2605, 37.0132],
                        ],
                    ],
                ]),
            ],

            // ═══════════════════════════════════════════════
            // SENARYO C: RİSKLİ — Mumcular Kırsal
            // Beklenen: Score 20-35, CTA=AVOID, Level=risky/avoid
            // Neden: Düşük POI + yüksek fiyat + altyapı eksik
            // ═══════════════════════════════════════════════
            [
                'baslik' => '[DEMO-C] Mumcular Kırsal Arazi — Riskli Lokasyon',
                'aciklama' => 'Mumcular kırsalında, hizmet noktalarına uzak, 2000m² arazi. Doğalgaz ve su altyapısı yok. Zeytin ağaçlı.',
                'fiyat' => 18000000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 39, // Mumcular
                'mahalle' => 'Mumcular',
                'lat' => 37.1420,
                'lng' => 27.5700,
                'alan_m2' => 2000,
                'kaks' => 0.05,
                'taks' => 0.05,
                'yola_cephe' => false,
                'altyapi_elektrik' => true,
                'altyapi_su' => false,
                'altyapi_dogalgaz' => false,
                'imar_statusu' => 'Tarla',
                'geometry_type' => 'point',
                'geometry' => null,
            ],
        ];
    }
}

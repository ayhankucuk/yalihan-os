<?php

namespace Database\Seeders;

use App\Models\Ilan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Bodrum Bölgesi Demo İlan Seeder
 *
 * MIE V4 Location Intelligence test verisi.
 * 25 ilan: 10 villa, 10 daire, 5 arsa
 * Farklı lokasyonlarda, farklı fiyatlarda.
 */
class DemoIlanSeeder extends Seeder
{
    public function run(): void
    {
        $ilanlar = $this->getIlanData();
        $created = 0;

        foreach ($ilanlar as $data) {
            $data['slug'] = Str::slug($data['baslik']) . '-' . Str::random(5);
            $data['para_birimi'] = $data['para_birimi'] ?? 'TRY';
            $data['il_id'] = 48; // Muğla
            $data['ilce_id'] = 1; // Bodrum
            $data['il'] = 'Muğla';
            $data['ilce'] = 'Bodrum';
            $data['country_code'] = 'TR';

            // Enum-safe values
            $data['yayin_durumu'] = \App\Enums\IlanDurumu::YAYINDA;

            // Boolean fields → integer (avoid enum cast issues)
            foreach (['havuz_var','yola_cephe','altyapi_elektrik','altyapi_su','altyapi_dogalgaz'] as $boolField) {
                if (isset($data[$boolField])) {
                    $data[$boolField] = $data[$boolField] ? 1 : 0;
                }
            }

            Ilan::create($data);
            $created++;
        }

        $this->command->info("✅ Demo İlan Seeder: {$created} ilan oluşturuldu.");
    }

    private function getIlanData(): array
    {
        return [
            // ═══════════════════════════════════════
            // VİLLALAR (10 adet) — ana_kategori: 1(Konut), alt_kategori: 8(Villa)
            // ═══════════════════════════════════════

            // Villa 1: Yalıkavak lüks — yüksek fiyat, marina yakını
            [
                'baslik' => 'Yalıkavak Marina Yakını Müstesna Villa',
                'aciklama' => 'Palmarina\'ya 500 metre mesafede, deniz manzaralı, 4+1 lüks villa. Özel havuz, bahçe ve otopark.',
                'fiyat' => 28500000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 53, // Yalıkavak
                'mahalle' => 'Yalıkavak',
                'lat' => 37.1015,
                'lng' => 27.2970,
                'oda_sayisi' => 4,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 3,
                'net_m2' => 280,
                'brut_m2' => 320,
                'toplam_kat' => 2,
                'isitma' => 'Yerden Isıtma',
                'havuz_var' => true,
            ],

            // Villa 2: Yalıkavak tepede — farklı konum aynı bölge
            [
                'baslik' => 'Yalıkavak Tepe Konumlu Deniz Manzaralı Villa',
                'aciklama' => 'Yalıkavak tepelerinde, 180 derece deniz manzaralı, 5+2 villa. Sonsuzluk havuzu, tam müstakil.',
                'fiyat' => 35000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 53, // Yalıkavak
                'mahalle' => 'Yalıkavak',
                'lat' => 37.1045,
                'lng' => 27.2935,
                'oda_sayisi' => 5,
                'salon_sayisi' => 2,
                'banyo_sayisi' => 4,
                'net_m2' => 350,
                'brut_m2' => 420,
                'toplam_kat' => 3,
                'isitma' => 'Yerden Isıtma',
                'havuz_var' => true,
            ],

            // Villa 3: Türkbükü — premium bölge
            [
                'baslik' => 'Türkbükü Sahil Yakını Taş Villa',
                'aciklama' => 'Türkbükü koyuna yürüme mesafesinde, restore edilmiş bodrum taş evi. 3+1, bahçeli.',
                'fiyat' => 42000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 48, // Türkbükü
                'mahalle' => 'Türkbükü',
                'lat' => 37.0865,
                'lng' => 27.3770,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 200,
                'brut_m2' => 240,
                'toplam_kat' => 2,
                'isitma' => 'Kombi',
                'havuz_var' => true,
            ],

            // Villa 4: Gümüşlük — orta segment
            [
                'baslik' => 'Gümüşlük Antik Kent Yakını Villa',
                'aciklama' => 'Gümüşlük Antik Kenti\'ne 300 metre, sakin lokasyon, 3+1 müstakil villa. Deniz manzarası.',
                'fiyat' => 15000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 24, // Gümüşlük
                'mahalle' => 'Gümüşlük',
                'lat' => 37.0548,
                'lng' => 27.2355,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 180,
                'brut_m2' => 210,
                'toplam_kat' => 2,
                'isitma' => 'Kombi',
                'havuz_var' => true,
            ],

            // Villa 5: Bodrum Merkez — şehir içi villa
            [
                'baslik' => 'Bodrum Merkez Kale Yakını Villa',
                'aciklama' => 'Bodrum Kalesi\'ne 800 metre, şehir merkezinde 4+1 villa. Havuzlu, bahçeli, garajlı.',
                'fiyat' => 22000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 35, // Kumbahçe
                'mahalle' => 'Kumbahçe',
                'lat' => 37.0340,
                'lng' => 27.4310,
                'oda_sayisi' => 4,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 3,
                'net_m2' => 250,
                'brut_m2' => 290,
                'toplam_kat' => 2,
                'isitma' => 'Yerden Isıtma',
                'havuz_var' => true,
            ],

            // Villa 6: Bitez — plaja yakın
            [
                'baslik' => 'Bitez Plaja 200m Müstakil Villa',
                'aciklama' => 'Bitez plajına yürüme mesafesinde, 3+1 müstakil villa. Geniş bahçe, özel havuz.',
                'fiyat' => 18500000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 5, // Bitez
                'mahalle' => 'Bitez',
                'lat' => 37.0318,
                'lng' => 27.4070,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 190,
                'brut_m2' => 220,
                'toplam_kat' => 2,
                'isitma' => 'Kombi',
                'havuz_var' => true,
            ],

            // Villa 7: Turgutreis — uygun fiyat
            [
                'baslik' => 'Turgutreis Gün Batımı Manzaralı Villa',
                'aciklama' => 'Turgutreis\'te eşsiz gün batımı manzaralı, 4+1 villa. Havuzlu, denize 500m.',
                'fiyat' => 12000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 47, // Turgutreis
                'mahalle' => 'Turgutreis',
                'lat' => 37.0090,
                'lng' => 27.2615,
                'oda_sayisi' => 4,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 200,
                'brut_m2' => 240,
                'toplam_kat' => 2,
                'isitma' => 'Kombi',
                'havuz_var' => true,
            ],

            // Villa 8: Ortakent/Yahşi — aile odaklı
            [
                'baslik' => 'Ortakent Yahşi Sahili Yakını Aile Villası',
                'aciklama' => 'Yahşi plajına 400m, aile dostu lokasyon. 5+1 tripleks villa, geniş bahçe ve havuz.',
                'fiyat' => 16000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 51, // Yahşi
                'mahalle' => 'Yahşi',
                'lat' => 37.0388,
                'lng' => 27.3610,
                'oda_sayisi' => 5,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 3,
                'net_m2' => 300,
                'brut_m2' => 360,
                'toplam_kat' => 3,
                'isitma' => 'Kombi',
                'havuz_var' => true,
            ],

            // Villa 9: Gündoğan — sakin bölge
            [
                'baslik' => 'Gündoğan Koy Manzaralı Villa',
                'aciklama' => 'Gündoğan koyuna hakim tepe konumda, 3+1 müstakil villa. Doğayla iç içe.',
                'fiyat' => 14000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 25, // Gündoğan
                'mahalle' => 'Gündoğan',
                'lat' => 37.0930,
                'lng' => 27.3395,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 170,
                'brut_m2' => 200,
                'toplam_kat' => 2,
                'isitma' => 'Kombi',
                'havuz_var' => true,
            ],

            // Villa 10: Konacık — şehre yakın, ulaşım kolay
            [
                'baslik' => 'Konacık Midtown AVM Yakını Modern Villa',
                'aciklama' => 'Konacık\'ta merkezi konumda, alışveriş ve ulaşıma yakın, 4+1 modern villa.',
                'fiyat' => 19500000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 8,
                'mahalle_id' => 33, // Konacık
                'mahalle' => 'Konacık',
                'lat' => 37.0510,
                'lng' => 27.4095,
                'oda_sayisi' => 4,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 3,
                'net_m2' => 230,
                'brut_m2' => 270,
                'toplam_kat' => 2,
                'isitma' => 'Yerden Isıtma',
                'havuz_var' => true,
            ],

            // ═══════════════════════════════════════
            // DAİRELER (10 adet) — ana_kategori: 1(Konut), alt_kategori: 7(Daire)
            // ═══════════════════════════════════════

            // Daire 1: Bodrum Merkez — en çok POI
            [
                'baslik' => 'Bodrum Merkez Marina Manzaralı 3+1 Daire',
                'aciklama' => 'Bodrum marina ve kaleye yürüme mesafesinde, 3+1 lüks daire. Deniz manzaralı, asansörlü bina.',
                'fiyat' => 8500000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 35, // Kumbahçe
                'mahalle' => 'Kumbahçe',
                'lat' => 37.0335,
                'lng' => 27.4330,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 130,
                'brut_m2' => 150,
                'kat' => 3,
                'toplam_kat' => 5,
                'bina_yasi' => 2020,
                'isitma' => 'Kombi',
            ],

            // Daire 2: Bodrum Merkez — farklı fiyat aynı bölge (MIE karşılaştırma)
            [
                'baslik' => 'Bodrum Merkez Çarşı Yakını 2+1 Daire',
                'aciklama' => 'Bodrum çarşısına 200m, 2+1 bakımlı daire. Yakında hastane, market ve okul.',
                'fiyat' => 5800000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 10, // Çarşı
                'mahalle' => 'Çarşı',
                'lat' => 37.0350,
                'lng' => 27.4305,
                'oda_sayisi' => 2,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 1,
                'net_m2' => 95,
                'brut_m2' => 110,
                'kat' => 2,
                'toplam_kat' => 4,
                'bina_yasi' => 2015,
                'isitma' => 'Kombi',
            ],

            // Daire 3: Gümbet — turistik bölge
            [
                'baslik' => 'Gümbet Plaja Yakın 2+1 Yatırımlık Daire',
                'aciklama' => 'Gümbet plajına 300m tamamlanmış site. 2+1 daire, site havuzu mevcut.',
                'fiyat' => 4200000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 23, // Gümbet
                'mahalle' => 'Gümbet',
                'lat' => 37.0282,
                'lng' => 27.4190,
                'oda_sayisi' => 2,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 1,
                'net_m2' => 85,
                'brut_m2' => 100,
                'kat' => 1,
                'toplam_kat' => 3,
                'bina_yasi' => 2018,
                'isitma' => 'Klima',
            ],

            // Daire 4: Turgutreis — uygun fiyat
            [
                'baslik' => 'Turgutreis Merkez 3+1 Aile Dairesi',
                'aciklama' => 'Turgutreis merkezinde, okul ve hastaneye yakın, 3+1 ferah daire. Otopark ve asansör mevcut.',
                'fiyat' => 3500000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 47, // Turgutreis
                'mahalle' => 'Turgutreis',
                'lat' => 37.0100,
                'lng' => 27.2600,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 1,
                'net_m2' => 120,
                'brut_m2' => 140,
                'kat' => 2,
                'toplam_kat' => 4,
                'bina_yasi' => 2019,
                'isitma' => 'Kombi',
            ],

            // Daire 5: Yalıkavak — premium daire
            [
                'baslik' => 'Yalıkavak Marina Sitesi 3+1 Lüks Daire',
                'aciklama' => 'Yalıkavak marina bölgesinde, site içinde, havuzlu ve güvenlikli 3+1 lüks daire.',
                'fiyat' => 12000000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 53, // Yalıkavak
                'mahalle' => 'Yalıkavak',
                'lat' => 37.1020,
                'lng' => 27.2968,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 140,
                'brut_m2' => 165,
                'kat' => 4,
                'toplam_kat' => 5,
                'bina_yasi' => 2022,
                'isitma' => 'Yerden Isıtma',
            ],

            // Daire 6: Bitez — sakin bölge
            [
                'baslik' => 'Bitez Mandalina Bahçeleri 2+1 Daire',
                'aciklama' => 'Bitez\'in yeşil dokusunda, mandalina bahçeleri arasında 2+1 daire. Plaja 500m.',
                'fiyat' => 4800000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 5, // Bitez
                'mahalle' => 'Bitez',
                'lat' => 37.0328,
                'lng' => 27.4060,
                'oda_sayisi' => 2,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 1,
                'net_m2' => 90,
                'brut_m2' => 105,
                'kat' => 2,
                'toplam_kat' => 3,
                'bina_yasi' => 2017,
                'isitma' => 'Klima',
            ],

            // Daire 7: Konacık — ulaşım hub
            [
                'baslik' => 'Konacık AVM Yakını Modern 3+1 Daire',
                'aciklama' => 'Midtown AVM\'ye 300m, ana yola cepheli, 3+1 sıfır daire. Otopark, jeneratör, güvenlik.',
                'fiyat' => 6200000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 33, // Konacık
                'mahalle' => 'Konacık',
                'lat' => 37.0508,
                'lng' => 27.4100,
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 2,
                'net_m2' => 125,
                'brut_m2' => 145,
                'kat' => 3,
                'toplam_kat' => 5,
                'bina_yasi' => 2023,
                'isitma' => 'Kombi',
            ],

            // Daire 8: Gümüşlük — tatil dairesi
            [
                'baslik' => 'Gümüşlük Deniz Manzaralı 1+1 Tatil Dairesi',
                'aciklama' => 'Gümüşlük koyunda, deniz manzaralı 1+1 kompakt daire. Yatırımlık, kiralama potansiyeli yüksek.',
                'fiyat' => 3200000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 24, // Gümüşlük
                'mahalle' => 'Gümüşlük',
                'lat' => 37.0545,
                'lng' => 27.2358,
                'oda_sayisi' => 1,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 1,
                'net_m2' => 55,
                'brut_m2' => 65,
                'kat' => 2,
                'toplam_kat' => 3,
                'bina_yasi' => 2016,
                'isitma' => 'Klima',
            ],

            // Daire 9: Torba — lüks modern
            [
                'baslik' => 'Torba Koyu Manzaralı 2+1 Rezidans Daire',
                'aciklama' => 'Torba koyunda, modern rezidans projede 2+1 daire. Havuz, spor salonu, SPA.',
                'fiyat' => 9500000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 46, // Torba
                'mahalle' => 'Torba',
                'lat' => 37.0590,
                'lng' => 27.4520,
                'oda_sayisi' => 2,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 1,
                'net_m2' => 100,
                'brut_m2' => 120,
                'kat' => 5,
                'toplam_kat' => 7,
                'bina_yasi' => 2024,
                'isitma' => 'Yerden Isıtma',
            ],

            // Daire 10: Mumcular — kırsal, zayıf POI (düşük location score beklenir)
            [
                'baslik' => 'Mumcular Doğa İçinde Ekonomik Daire',
                'aciklama' => 'Mumcular\'da doğayla iç içe, ekonomik fiyatlı 2+1 daire. Bahçe katı.',
                'fiyat' => 1800000,
                'ana_kategori_id' => 1,
                'alt_kategori_id' => 7,
                'mahalle_id' => 39, // Mumcular
                'mahalle' => 'Mumcular',
                'lat' => 37.1382,
                'lng' => 27.5662,
                'oda_sayisi' => 2,
                'salon_sayisi' => 1,
                'banyo_sayisi' => 1,
                'net_m2' => 80,
                'brut_m2' => 95,
                'kat' => 0,
                'toplam_kat' => 2,
                'bina_yasi' => 2010,
                'isitma' => 'Soba',
            ],

            // ═══════════════════════════════════════
            // ARSALAR (5 adet) — ana_kategori: 3(Arsa&Arazi), alt_kategori: 15(Arsa Konut/Villa)
            // ═══════════════════════════════════════

            // Arsa 1: Yalıkavak — lüks bölge arsa
            [
                'baslik' => 'Yalıkavak Deniz Manzaralı İmarlı Arsa',
                'aciklama' => 'Yalıkavak\'ta, marina\'ya 1km, konut imarlı 500m² arsa. KAKS: 0.40, TAKS: 0.20.',
                'fiyat' => 9000000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 53, // Yalıkavak
                'mahalle' => 'Yalıkavak',
                'lat' => 37.1040,
                'lng' => 27.2950,
                'alan_m2' => 500,
                'kaks' => 0.40,
                'taks' => 0.20,
                'yola_cephe' => true,
                'altyapi_elektrik' => true,
                'altyapi_su' => true,
                'altyapi_dogalgaz' => false,
                'imar_statusu' => 'Konut',
            ],

            // Arsa 2: Turgutreis — uygun fiyat
            [
                'baslik' => 'Turgutreis Merkeze Yakın 750m² Arsa',
                'aciklama' => 'Turgutreis merkezine 500m, altyapısı hazır konut imarlı arsa. Yola cepheli.',
                'fiyat' => 4500000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 47, // Turgutreis
                'mahalle' => 'Turgutreis',
                'lat' => 37.0110,
                'lng' => 27.2595,
                'alan_m2' => 750,
                'kaks' => 0.30,
                'taks' => 0.15,
                'yola_cephe' => true,
                'altyapi_elektrik' => true,
                'altyapi_su' => true,
                'altyapi_dogalgaz' => false,
                'imar_statusu' => 'Konut',
            ],

            // Arsa 3: Gümüşlük — doğal güzellik
            [
                'baslik' => 'Gümüşlük Zeytinlikli 1000m² Arazi',
                'aciklama' => 'Gümüşlük\'te zeytinlikler arasında, deniz manzaralı arazi. SİT alanı yakını.',
                'fiyat' => 6000000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 24, // Gümüşlük
                'mahalle' => 'Gümüşlük',
                'lat' => 37.0560,
                'lng' => 27.2340,
                'alan_m2' => 1000,
                'kaks' => 0.20,
                'taks' => 0.10,
                'yola_cephe' => false,
                'altyapi_elektrik' => true,
                'altyapi_su' => false,
                'altyapi_dogalgaz' => false,
                'imar_statusu' => 'Konut',
            ],

            // Arsa 4: Konacık — ticari potansiyel
            [
                'baslik' => 'Konacık Ana Yola Cepheli İmarlı Arsa',
                'aciklama' => 'Konacık\'ta ana yola cepheli, ticaret+konut imarlı 400m² arsa. Yatırıma uygun.',
                'fiyat' => 7500000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 33, // Konacık
                'mahalle' => 'Konacık',
                'lat' => 37.0515,
                'lng' => 27.4088,
                'alan_m2' => 400,
                'kaks' => 0.60,
                'taks' => 0.30,
                'yola_cephe' => true,
                'altyapi_elektrik' => true,
                'altyapi_su' => true,
                'altyapi_dogalgaz' => true,
                'imar_statusu' => 'Ticaret+Konut',
            ],

            // Arsa 5: Mumcular — kırsal, düşük POI
            [
                'baslik' => 'Mumcular Zeytinli Tarla 5000m²',
                'aciklama' => 'Mumcular\'da 200 zeytin ağacı bulunan, suyu olan 5 dönüm tarla. Yola cepheli.',
                'fiyat' => 2000000,
                'ana_kategori_id' => 3,
                'alt_kategori_id' => 15,
                'mahalle_id' => 39, // Mumcular
                'mahalle' => 'Mumcular',
                'lat' => 37.1390,
                'lng' => 27.5650,
                'alan_m2' => 5000,
                'kaks' => 0.05,
                'taks' => 0.05,
                'yola_cephe' => true,
                'altyapi_elektrik' => true,
                'altyapi_su' => true,
                'altyapi_dogalgaz' => false,
                'imar_statusu' => 'Tarla',
            ],
        ];
    }
}

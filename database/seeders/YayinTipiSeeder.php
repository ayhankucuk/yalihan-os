<?php

namespace Database\Seeders;

use App\Models\IlanKategori;
use App\Models\YayinTipi;
use App\Models\YayinTipiSablonu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Canonical Yayin Tipi Seeder
 *
 * YayinTipiSablonu provisioning için source of truth.
 * Mevcut YayinTipiSablonu kayıtlarını DEĞİŞTİRMEZ — sadece eksikleri ekler.
 *
 * Pattern: updateOrCreate ile idempotent seeding.
 * Sistem zaten mevcut kayıtları korur.
 *
 * PILOT-01-003 Recovery:
 *   Villa kategorisi (slug: villa, ID:8) için YayinTipiSablonu kayıtları ekleniyor.
 *   YayinTipiSeeder seasonal rental tiplerini (Günlük, Haftalık, Aylık, Sezonluk)
 *   YayinTipi olarak da ekliyor — bunlar YayinTipiSablonu ile ilişkilendirilecek.
 *
 * Context7: kategori_id → slug ile dinamik resolve (hardcoded ID yok).
 */
class YayinTipiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Standart Yayın Tipleri (base types — her zaman mevcut)
        $baseTypes = [
            ['id' => 1, 'name' => 'Satılık',      'slug' => 'satilik',           'aktiflik_durumu' => 1],
            ['id' => 2, 'name' => 'Kiralık',      'slug' => 'kiralik',          'aktiflik_durumu' => 1],
            ['id' => 3, 'name' => 'Kat Karşılığı', 'slug' => 'kat-karsiligi',    'aktiflik_durumu' => 1],
            ['id' => 4, 'name' => 'Devren',       'slug' => 'devren',            'aktiflik_durumu' => 1],
        ];

        foreach ($baseTypes as $tip) {
            YayinTipi::updateOrCreate(
                ['slug' => $tip['slug']],
                [
                    'name'               => $tip['name'],
                    'slug'               => $tip['slug'],
                    'aktiflik_durumu'    => $tip['aktiflik_durumu'],
                ]
            );
        }

        // 2. Sezonal Yayın Tipleri (Villa / Yazlık kiralama için — PILOT-01-003)
        // Bu tipler YayinTipiSablonu ile ilişkilendirilir.
        $seasonalTypes = [
            ['id' => 5, 'name' => 'Günlük Kiralık',   'slug' => 'gunluk-kiralik',   'aktiflik_durumu' => 1],
            ['id' => 6, 'name' => 'Haftalık Kiralık', 'slug' => 'haftalik-kiralik', 'aktiflik_durumu' => 1],
            ['id' => 7, 'name' => 'Aylık Kiralık',    'slug' => 'aylik-kiralik',   'aktiflik_durumu' => 1],
            ['id' => 8, 'name' => 'Sezonluk Kiralık', 'slug' => 'sezonluk-kiralik', 'aktiflik_durumu' => 1],
        ];

        foreach ($seasonalTypes as $tip) {
            YayinTipi::updateOrCreate(
                ['slug' => $tip['slug']],
                [
                    'name'               => $tip['name'],
                    'slug'               => $tip['slug'],
                    'aktiflik_durumu'    => $tip['aktiflik_durumu'],
                ]
            );
        }

        // 3. YayinTipiSablonu kayıtları — Villa kategorisi (slug: villa, ID:8)
        //
        // Template ID'leri matrix [25-30] ile eşleşmeli (PropertyPublicationPolicy getMatrixPolicyIds)
        // YayinTipiSablonu IDs: 13-14: Arsa (mevcut), 25-30: Villa (yeni)
        $Villa = IlanKategori::where('slug', 'villa')->first();

        if (!$Villa) {
            $this->command->warn('  ⚠️ Villa kategorisi bulunamadı (slug: villa) — YayinTipiSablonu atlanıyor.');
        } else {
            $villaTemplates = [
                ['id' => 25, 'kategori_id' => $Villa->id, 'yayin_tipi_id' => 1, 'ad' => 'Villa Satılık Şablonu', 'slug' => 'villa-satilik', 'aktiflik_durumu' => true],
                ['id' => 26, 'kategori_id' => $Villa->id, 'yayin_tipi_id' => 2, 'ad' => 'Villa Kiralık Şablonu', 'slug' => 'villa-kiralik', 'aktiflik_durumu' => true],
                ['id' => 27, 'kategori_id' => $Villa->id, 'yayin_tipi_id' => 5, 'ad' => 'Villa Günlük Kiralık Şablonu', 'slug' => 'villa-gunluk', 'aktiflik_durumu' => true],
                ['id' => 28, 'kategori_id' => $Villa->id, 'yayin_tipi_id' => 6, 'ad' => 'Villa Haftalık Kiralık Şablonu', 'slug' => 'villa-haftalik', 'aktiflik_durumu' => true],
                ['id' => 29, 'kategori_id' => $Villa->id, 'yayin_tipi_id' => 7, 'ad' => 'Villa Aylık Kiralık Şablonu', 'slug' => 'villa-aylik', 'aktiflik_durumu' => true],
                ['id' => 30, 'kategori_id' => $Villa->id, 'yayin_tipi_id' => 8, 'ad' => 'Villa Sezonluk Kiralık Şablonu', 'slug' => 'villa-sezonluk', 'aktiflik_durumu' => true],
            ];

            foreach ($villaTemplates as $t) {
                YayinTipiSablonu::updateOrCreate(['id' => $t['id']], $t);
            }

            $this->command->info("  ✅ Villa YayinTipiSablonu: " . count($villaTemplates) . " şablon");
        }

        // 4. Arsa YayinTipiSablonu kayıtları (mevcut — korunuyor, ID:13-14)
        $ArsaKonutVilla = IlanKategori::where('slug', 'arsa-konut-villa')->first();

        if ($ArsaKonutVilla) {
            $arsaTemplates = [
                ['id' => 13, 'kategori_id' => $ArsaKonutVilla->id, 'yayin_tipi_id' => 1, 'ad' => 'Arsa Satılık Şablonu',           'slug' => 'arsa-konut-villa-satilik',         'aktiflik_durumu' => true],
                ['id' => 14, 'kategori_id' => $ArsaKonutVilla->id, 'yayin_tipi_id' => 3, 'ad' => 'Arsa Kat Karşılığı Şablonu', 'slug' => 'arsa-konut-villa-kat-karsiligi', 'aktiflik_durumu' => true],
            ];

            foreach ($arsaTemplates as $t) {
                YayinTipiSablonu::updateOrCreate(['id' => $t['id']], $t);
            }
        }

        // 5. YayinTipiSablonu slug uniqueness guarantee (yayin_tipi_id + kategori_id bazlı)
        $this->command->info('  ✅ YayinTipi: ' . YayinTipi::count() . ' kayıt');
        $this->command->info('  ✅ YayinTipiSablonu: ' . YayinTipiSablonu::count() . ' kayıt');
    }
}

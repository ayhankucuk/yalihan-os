<?php

namespace Database\Seeders;

use App\Models\YayinTipi;
use App\Models\YayinTipiSablonu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 🚀 YALIHAN EMLAK - Canonical Yayin Tipi Seeder
 *
 * Sorumluluk: Standart yayın tipleri ve Baseline mappingleri.
 * Note: Depends on IlanKategoriSeeder (ID 15)
 */
class YayinTipiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Standart Yayın Tiplerini Oluştur
        $yayinTipleri = [
            ['id' => 1, 'name' => 'Satılık', 'slug' => 'satilik', 'aktiflik_durumu' => 1],
            ['id' => 2, 'name' => 'Kiralık', 'slug' => 'kiralik', 'aktiflik_durumu' => 1],
            ['id' => 3, 'name' => 'Kat Karşılığı', 'slug' => 'kat-karsiligi', 'aktiflik_durumu' => 1],
            ['id' => 4, 'name' => 'Devren', 'slug' => 'devren', 'aktiflik_durumu' => 1],
        ];

        foreach ($yayinTipleri as $tip) {
            DB::table('yayin_tipleri')->updateOrInsert(['id' => $tip['id']], $tip);
        }

        // 2. Baseline Wizard Mapping (Arsa Category Baseline - E2E Support)
        // Arsa (Konut/Villa) ID: 15
        
        // a) Pivot mapping
        $arsaJunctions = [
            ['alt_kategori_id' => 15, 'yayin_tipi_id' => 1, 'id' => 13], // Satılık
            ['alt_kategori_id' => 15, 'yayin_tipi_id' => 3, 'id' => 14], // Kat Karşılığı
        ];

        foreach ($arsaJunctions as $j) {
            DB::table('alt_kategori_yayin_tipi')->updateOrInsert(['id' => $j['id']], $j);
        }

        // b) Wizard Context Templates
        // NOTE: tenant_id has default 'SYSTEM' in schema.
        $arsaTemplates = [
            [
                'id' => 13,
                'kategori_id' => 15,
                'yayin_tipi_id' => 1,
                'ad' => 'Arsa Satılık Şablonu',
                'slug' => 'arsa-konut-villa-satilik',
                'aktiflik_durumu' => 1,
            ],
            [
                'id' => 14,
                'kategori_id' => 15,
                'yayin_tipi_id' => 3,
                'ad' => 'Arsa Kat Karşılığı Şablonu',
                'slug' => 'arsa-konut-villa-kat-karsiligi',
                'aktiflik_durumu' => 1,
            ],
        ];

        foreach ($arsaTemplates as $t) {
            YayinTipiSablonu::updateOrCreate(['id' => $t['id']], $t);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\OzellikKategori;
use Illuminate\Database\Seeder;

/**
 * Özellik Kategori Seeder
 *
 * ComprehensiveFeatureSeeder'ın beklediği OzellikKategori kayıtlarını oluşturur.
 * Bu seeder ComprehensiveFeatureSeeder'dan ÖNCE çalıştırılmalıdır.
 *
 * Context7: C7-OZELLIK-KATEGORI-2026-02-20
 */
class OzellikKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriler = [
            [
                'name'            => 'Temel Bilgiler',
                'slug'            => 'temel-bilgiler',
                'display_order'   => 1,
                'aktiflik_durumu' => true,
            ],
            [
                'name'            => 'Oda ve Alan',
                'slug'            => 'oda-ve-alan',
                'display_order'   => 2,
                'aktiflik_durumu' => true,
            ],
            [
                'name'            => 'Ek Özellikler',
                'slug'            => 'ek-ozellikler',
                'display_order'   => 3,
                'aktiflik_durumu' => true,
            ],
            [
                'name'            => 'Konum ve Çevre',
                'slug'            => 'konum-ve-cevre',
                'display_order'   => 4,
                'aktiflik_durumu' => true,
            ],
            [
                'name'            => 'Fiyat ve Ödeme',
                'slug'            => 'fiyat-ve-odeme',
                'display_order'   => 5,
                'aktiflik_durumu' => true,
            ],
        ];

        foreach ($kategoriler as $kategori) {
            OzellikKategori::firstOrCreate(
                ['slug' => $kategori['slug']],
                $kategori
            );
        }

        $this->command->info('✅ OzellikKategori: ' . count($kategoriler) . ' kategori oluşturuldu.');
    }
}

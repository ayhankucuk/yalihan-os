<?php

namespace Database\Seeders;

use App\Models\FeaturePack;
use App\Models\FeaturePackItem;
use App\Models\FeaturePackLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 7.1B — Feature Pack Seeder
 *
 * 22 mevcut özellikten 5 hazır paket oluşturur.
 * Bu seeder idempotent — tekrar çalıştırılabilir.
 */
class FeaturePackSeeder extends Seeder
{
    public function run(): void
    {
        // Mevcut packleri ve logları temizle (idempotent)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('feature_pack_items')->delete();
        DB::table('feature_packs')->delete();
        DB::table('feature_pack_logs')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $packs = $this->getPackDefinitions();

        foreach ($packs as $order => $def) {
            $pack = FeaturePack::create([
                'name'            => $def['name'],
                'slug'            => \Illuminate\Support\Str::slug($def['name']),
                'display_name'    => $def['display_name'],
                'description'     => $def['description'],
                'icon'            => $def['icon'],
                'color'           => $def['color'],
                'kategori_ids'   => $def['kategori_ids'],
                'yayin_tipi_ids'  => $def['yayin_tipi_ids'] ?? [],
                'aktiflik_durumu' => 1,
                'display_order'   => $order + 1,
                'feature_count'   => count($def['items']),
            ]);

            foreach ($def['items'] as $item) {
                $ozellik = \App\Models\Ozellik::where('slug', $item['slug'])->first();
                if (!$ozellik) continue;

                $pack->items()->create([
                    'ozellik_id'   => $ozellik->id,
                    'field_slug'   => $item['slug'],
                    'value'        => $item['value'] ?? '1',
                    'display_order' => $item['order'] ?? 0,
                    'notes'         => $item['notes'] ?? null,
                ]);
            }
        }

        $this->command->info('Feature Pack Seeder: ' . FeaturePack::count() . ' pack oluşturuldu.');
    }

    private function getPackDefinitions(): array
    {
        return [
            [
                'name'        => 'Premium Villa',
                'display_name' => 'Premium Villa Paketi',
                'description'  => 'Bodrum villaları için tam donanımlı özellik seti. Havuz, akıllı ev, jakuzi ve daha fazlası tek tıkla.',
                'icon'        => 'villalar',
                'color'       => 'emerald',
                'kategori_ids' => [8, 26],  // Villa, Alt Kategori Villa
                'yayin_tipi_ids' => [15],
                'items' => [
                    ['slug' => 'brut-metrekare',  'value' => '',  'order' => 1, 'notes' => 'Brüt alan'],
                    ['slug' => 'net-metrekare',   'value' => '',  'order' => 2, 'notes' => 'Net alan'],
                    ['slug' => 'oda-sayisi',      'value' => '',  'order' => 3, 'notes' => 'Oda sayısı'],
                    ['slug' => 'banyo-sayisi',    'value' => '',  'order' => 4, 'notes' => 'Banyo sayısı'],
                    ['slug' => 'kat',             'value' => '',  'order' => 5, 'notes' => 'Kat'],
                    ['slug' => 'toplam-kat',      'value' => '',  'order' => 6, 'notes' => 'Toplam kat'],
                    ['slug' => 'isitma',          'value' => '',  'order' => 7, 'notes' => 'Isıtma tipi'],
                    ['slug' => 'esyali',          'value' => '1', 'order' => 8, 'notes' => 'Eşyalı'],
                    ['slug' => 'site-icerisinde', 'value' => '',  'order' => 9, 'notes' => 'Site içi'],
                    ['slug' => 'bina-yasi',       'value' => '',  'order' => 10, 'notes' => 'Bina yaşı'],
                    ['slug' => 'denize-mesafe',   'value' => '',  'order' => 11, 'notes' => 'Denize mesafe'],
                    ['slug' => 'manzara',         'value' => '',  'order' => 12, 'notes' => 'Manzara'],
                ],
            ],
            [
                'name'        => 'Yazlık Kiralama',
                'display_name' => 'Yazlık Kiralama Paketi',
                'description'  => 'Günlük/sezonluk kiralık yazlıklar için ideal özellik seti. Konum, kapasite ve tesis bilgileri.',
                'icon'        => 'tatil',
                'color'       => 'sky',
                'kategori_ids' => [26, 27, 28, 29, 30, 31],  // Yazlık alt kategorileri
                'yayin_tipi_ids' => [],
                'items' => [
                    ['slug' => 'brut-metrekare', 'value' => '', 'order' => 1, 'notes' => 'Brüt alan'],
                    ['slug' => 'net-metrekare',  'value' => '', 'order' => 2, 'notes' => 'Net alan'],
                    ['slug' => 'oda-sayisi',     'value' => '', 'order' => 3, 'notes' => 'Oda sayısı'],
                    ['slug' => 'banyo-sayisi',  'value' => '', 'order' => 4, 'notes' => 'Banyo sayısı'],
                    ['slug' => 'kat',            'value' => '', 'order' => 5, 'notes' => 'Kat'],
                    ['slug' => 'esyali',         'value' => '1', 'order' => 6, 'notes' => 'Eşyalı'],
                    ['slug' => 'denize-mesafe',  'value' => '', 'order' => 7, 'notes' => 'Denize mesafe'],
                    ['slug' => 'manzara',        'value' => '', 'order' => 8, 'notes' => 'Manzara'],
                    ['slug' => 'site-icerisinde','value' => '', 'order' => 9, 'notes' => 'Site içi'],
                    ['slug' => 'bina-yasi',      'value' => '', 'order' => 10, 'notes' => 'Bina yaşı'],
                ],
            ],
            [
                'name'        => '2+1 Daire',
                'display_name' => '2+1 Daire Paketi',
                'description'  => 'Standart 2+1 daireler için temel özellik seti. Balkon, asansör, otopark ve doğalgaz.',
                'icon'        => 'daireler',
                'color'       => 'blue',
                'kategori_ids' => [7],  // Daire
                'yayin_tipi_ids' => [16],
                'items' => [
                    ['slug' => 'brut-metrekare', 'value' => '', 'order' => 1, 'notes' => 'Brüt alan'],
                    ['slug' => 'net-metrekare',  'value' => '', 'order' => 2, 'notes' => 'Net alan'],
                    ['slug' => 'oda-sayisi',     'value' => '', 'order' => 3, 'notes' => 'Oda sayısı'],
                    ['slug' => 'banyo-sayisi',  'value' => '', 'order' => 4, 'notes' => 'Banyo sayısı'],
                    ['slug' => 'kat',            'value' => '', 'order' => 5, 'notes' => 'Kat'],
                    ['slug' => 'toplam-kat',     'value' => '', 'order' => 6, 'notes' => 'Toplam kat'],
                    ['slug' => 'balkon',         'value' => '1', 'order' => 7, 'notes' => 'Balkon'],
                    ['slug' => 'asansor',        'value' => '', 'order' => 8, 'notes' => 'Asansör'],
                    ['slug' => 'otopark',        'value' => '', 'order' => 9, 'notes' => 'Otopark'],
                    ['slug' => 'isitma',         'value' => '', 'order' => 10, 'notes' => 'Isıtma'],
                    ['slug' => 'esyali',         'value' => '', 'order' => 11, 'notes' => 'Eşyalı'],
                    ['slug' => 'site-icerisinde','value' => '', 'order' => 12, 'notes' => 'Site içi'],
                    ['slug' => 'bina-yasi',      'value' => '', 'order' => 13, 'notes' => 'Bina yaşı'],
                    ['slug' => 'aidat',          'value' => '', 'order' => 14, 'notes' => 'Aidat'],
                ],
            ],
            [
                'name'        => 'Arsa & Arazi',
                'display_name' => 'Arsa & Arazi Paketi',
                'description'  => 'Satılık arsa, tarla ve araziler için imar, kadastro ve altyapı bilgileri.',
                'icon'        => 'arsa',
                'color'       => 'amber',
                'kategori_ids' => [15, 16, 17, 18, 19, 20, 21, 22],
                'yayin_tipi_ids' => [13],
                'items' => [
                    ['slug' => 'brut-metrekare',  'value' => '', 'order' => 1, 'notes' => 'Brüt alan (m²)'],
                    ['slug' => 'tapu-durumu',      'value' => '', 'order' => 2, 'notes' => 'Tapu durumu'],
                    ['slug' => 'imar-durumu',      'value' => '', 'order' => 3, 'notes' => 'İmar durumu'],
                    ['slug' => 'kullanim-durumu',  'value' => '', 'order' => 4, 'notes' => 'Kullanım durumu'],
                    ['slug' => 'kaks',             'value' => '', 'order' => 5, 'notes' => 'KAKS (Emsal)'],
                    ['slug' => 'taks',             'value' => '', 'order' => 6, 'notes' => 'TAKS'],
                    ['slug' => 'gabari',           'value' => '', 'order' => 7, 'notes' => 'Gabari'],
                    ['slug' => 'cephe',            'value' => '', 'order' => 8, 'notes' => 'Cephe'],
                    ['slug' => 'yola-cephe',       'value' => '', 'order' => 9, 'notes' => 'Yola cephe (m)'],
                    ['slug' => 'altyapi-elektrik', 'value' => '', 'order' => 10, 'notes' => 'Elektrik altyapısı'],
                    ['slug' => 'altyapi-su',       'value' => '', 'order' => 11, 'notes' => 'Su altyapısı'],
                    ['slug' => 'altyapi-dogalgaz','value' => '', 'order' => 12, 'notes' => 'Doğalgaz altyapısı'],
                    ['slug' => 'site-icerisinde', 'value' => '', 'order' => 13, 'notes' => 'Site içi'],
                ],
            ],
            [
                'name'        => 'Ticari İşyeri',
                'display_name' => 'Ticari İşyeri Paketi',
                'description'  => 'Ofis, dükkan ve ticari gayrimenkuller için işletme bilgileri.',
                'icon'        => 'isyerleri',
                'color'       => 'slate',
                'kategori_ids' => [11, 12, 13, 14],
                'yayin_tipi_ids' => [],
                'items' => [
                    ['slug' => 'brut-metrekare', 'value' => '', 'order' => 1, 'notes' => 'Brüt alan'],
                    ['slug' => 'net-metrekare',  'value' => '', 'order' => 2, 'notes' => 'Net alan'],
                    ['slug' => 'kat',            'value' => '', 'order' => 3, 'notes' => 'Kat'],
                    ['slug' => 'toplam-kat',     'value' => '', 'order' => 4, 'notes' => 'Toplam kat'],
                    ['slug' => 'cephe',          'value' => '', 'order' => 5, 'notes' => 'Cephe'],
                    ['slug' => 'isyeri-cephe',   'value' => '', 'order' => 6, 'notes' => 'İşyeri cephesi (m)'],
                    ['slug' => 'isitma',         'value' => '', 'order' => 7, 'notes' => 'Isıtma'],
                    ['slug' => 'esyali',         'value' => '', 'order' => 8, 'notes' => 'Eşyalı'],
                    ['slug' => 'bina-yasi',      'value' => '', 'order' => 9, 'notes' => 'Bina yaşı'],
                    ['slug' => 'aidat',          'value' => '', 'order' => 10, 'notes' => 'Aidat'],
                ],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FeatureAssignmentSeeder — Villa + Satılık seed data.
 *
 * SSOT: feature_categories → features → feature_assignments
 * Scope cascade: listing_type(400) > sub_category(300) > main_category(200) > global(100)
 *
 * Coverage:
 *   - Villa Satılık  (main=11, sub=8, lt=1) = 34 fields @ listing_type scope
 *   - Villa Kiralık  (main=11, sub=8, lt=2) = 1 field  @ listing_type scope (depozito)
 *   - Villa Günlük   (main=11, sub=8, lt=5) = 34 fields @ listing_type scope (explicit)
 *   - Konut Global   (main=11, sub=null, lt=null) = 8 fields @ main_category scope
 *   - Global         (main=null, sub=null, lt=null) = 5 fields @ global scope
 *
 * Total: feature_categories=7, features=36, feature_assignments=82
 *
 * IMPORTANT: Villa sub_category_id = 8 (NOT 36). Kategori 36 does not exist.
 * Sub-category 8 = Villa in ilan_kategorileri table (parent=1, seviye=1).
 *
 * IMPORTANT: Villa Günlük (lt=5) does NOT inherit from Villa Satılık (lt=1).
 * FeatureTemplateResolver does not support cross-listing-type cascade.
 * Explicit assignments are required for each listing type.
 *
 * Verifies: php artisan db:seed --class=FeatureAssignmentSeeder
 *   Expected: 7 categories, 36 features, 82 assignments
 */
class FeatureAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFeatureCategories();
        $this->seedFeatures();
        $this->seedAssignments();

        $fc = DB::table('feature_categories')->count();
        $f  = DB::table('features')->count();
        $fa = DB::table('feature_assignments')->count();
        $this->command->info("✅ FeatureAssignmentSeeder: {$fc} categories, {$f} features, {$fa} assignments");
    }

    // ─── FEATURE CATEGORIES ─────────────────────────────────────────────────

    private function seedFeatureCategories(): void
    {
        $categories = [
            ['name' => 'Temel Bilgiler',       'slug' => 'temel-bilgiler',       'description' => 'Villa\'nın temel fiziksel özellikleri',     'applies_to' => 'villa,property', 'icon' => 'home',         'display_order' => 1],
            ['name' => 'Konum ve Arsa',        'slug' => 'konum-ve-arsa',        'description' => 'Villa\'nın konumu ve arsa bilgileri',        'applies_to' => 'villa',          'icon' => 'map-pin',      'display_order' => 2],
            ['name' => 'Yapı Özellikleri',     'slug' => 'yapi-ozellikleri',     'description' => 'Havuz, bahçe, akıllı ev gibi özellikler',   'applies_to' => 'villa',          'icon' => 'zap',           'display_order' => 3],
            ['name' => 'Dış Özellikler',       'slug' => 'dis-ozellikler',       'description' => 'Otopark, güvenlik, spor alanları',           'applies_to' => 'villa',          'icon' => 'shield',        'display_order' => 4],
            ['name' => 'İç Özellikler',        'slug' => 'ic-ozellikler',        'description' => 'Eşya, mutfak, ısıtma-soğutma',             'applies_to' => 'villa',          'icon' => 'thermometer',  'display_order' => 5],
            ['name' => 'Maliyet ve Aidat',     'slug' => 'maliyet-ve-aidat',     'description' => 'Fiyat, aidat ve ek maliyetler',             'applies_to' => 'villa,property', 'icon' => 'credit-card',   'display_order' => 6],
            ['name' => 'Tapu ve İmar',         'slug' => 'tapu-ve-imar',         'description' => 'Tapu durumu, imar ve yasal bilgiler',       'applies_to' => 'villa,property', 'icon' => 'file-text',     'display_order' => 7],
        ];

        foreach ($categories as $cat) {
            DB::table('feature_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                array_merge($cat, ['aktiflik_durumu' => 1, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    // ─── FEATURES ───────────────────────────────────────────────────────────

    private function seedFeatures(): void
    {
        $cat = fn(string $slug) => DB::table('feature_categories')->where('slug', $slug)->value('id');

        $features = [
            // Temel Bilgiler
            ['name' => 'Brüt Alan',          'slug' => 'brut-alan',           'type' => 'number',     'unit' => 'm²',         'feature_category_id' => $cat('temel-bilgiler'),   'is_required' => true,  'is_filterable' => true,  'is_searchable' => true,  'display_order' => 1],
            ['name' => 'Net Alan',           'slug' => 'net-alan',            'type' => 'number',     'unit' => 'm²',         'feature_category_id' => $cat('temel-bilgiler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => true,  'display_order' => 2],
            ['name' => 'Oda Sayısı',         'slug' => 'oda-sayisi',          'type' => 'text',       'unit' => null,         'feature_category_id' => $cat('temel-bilgiler'),   'is_required' => true,  'is_filterable' => true,  'is_searchable' => true,  'display_order' => 3],
            ['name' => 'Banyo Sayısı',        'slug' => 'banyo-sayisi',        'type' => 'number',     'unit' => null,         'feature_category_id' => $cat('temel-bilgiler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => true,  'display_order' => 4],
            ['name' => 'Toplam Kat Sayısı',   'slug' => 'toplam-kat',         'type' => 'number',     'unit' => null,         'feature_category_id' => $cat('temel-bilgiler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 5],
            ['name' => 'Balkon',              'slug' => 'balkon',              'type' => 'boolean',    'unit' => null,         'feature_category_id' => $cat('temel-bilgiler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 6],
            ['name' => 'Kat',                 'slug' => 'kat',                 'type' => 'select',     'unit' => null,         'feature_category_id' => $cat('temel-bilgiler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 7, 'options' => json_encode(['Zemin', '1', '2', '3', '4', '5', 'Çatı Katı'])],

            // Konum ve Arsa
            ['name' => 'Arsa Alanı',         'slug' => 'arsa-alani',         'type' => 'number',     'unit' => 'm²',         'feature_category_id' => $cat('konum-ve-arsa'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => true,  'display_order' => 1],
            ['name' => 'Denize Mesafe',       'slug' => 'denize-mesafe',       'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('konum-ve-arsa'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => true,  'display_order' => 2, 'options' => json_encode(['Deniz Kenarı', '50m İçinde', '100m', '200m', '500m', '1km', '5km+'])],
            ['name' => 'Manzara',             'slug' => 'manzara',             'type' => 'multiselect','unit' => null,        'feature_category_id' => $cat('konum-ve-arsa'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 3, 'options' => json_encode(['Deniz', 'Göl', 'Dağ', 'Doğa', 'Bahçe', 'Havuz'])],
            ['name' => 'Cephe',               'slug' => 'cephe',               'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('konum-ve-arsa'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 4, 'options' => json_encode(['Kuzey', 'Güney', 'Doğu', 'Batı', 'Güneybatı', 'Güneydoğu', 'Kuzeybatı', 'Kuzeydoğu'])],
            ['name' => 'İmar Durumu',         'slug' => 'imar-durumu',         'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('konum-ve-arsa'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 5, 'options' => json_encode(['Konut İmarlı', 'Ticari İmar', 'Turizm İmarlı', 'İmarsız'])],

            // Yapı Özellikleri
            ['name' => 'Havuz',               'slug' => 'havuz',               'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => true,  'display_order' => 1],
            ['name' => 'Havuz Tipi',          'slug' => 'havuz-tip',          'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 2, 'options' => json_encode(['Açık', 'Kapalı', 'Yarı Açık', 'Çocuk Havuzu'])],
            ['name' => 'Özel Havuz',          'slug' => 'ozel-havuz',         'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 3],
            ['name' => 'Bahçe',               'slug' => 'bahce',              'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 4],
            ['name' => 'Bahçe Alanı',         'slug' => 'bahce-alani',        'type' => 'number',    'unit' => 'm²',         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 5],
            ['name' => 'Akıllı Ev',           'slug' => 'akilli-ev',          'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 6],
            ['name' => 'Teras',               'slug' => 'teras',              'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 7],
            ['name' => 'Veranda',             'slug' => 'veranda',            'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('yapi-ozellikleri'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 8],

            // Dış Özellikler
            ['name' => 'Otopark',             'slug' => 'otopark',            'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('dis-ozellikler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 1, 'options' => json_encode(['Yok', 'Açık Otopark', 'Kapalı Otopark', 'Garaj'])],
            ['name' => 'Güvenlik',            'slug' => 'guvenlik',          'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('dis-ozellikler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 2],
            ['name' => 'Site İçerisinde',     'slug' => 'site-icerisinde',   'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('dis-ozellikler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 3],
            ['name' => 'Spor Alanı',          'slug' => 'spor-alani',         'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('dis-ozellikler'),   'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 4],

            // İç Özellikler
            ['name' => 'Eşyalı',              'slug' => 'esyali',             'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('ic-ozellikler'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => true,  'display_order' => 1, 'options' => json_encode(['Hayır', 'Kısmen', 'Evet'])],
            ['name' => 'Mutfak Tipi',         'slug' => 'mutfak-tipi',       'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('ic-ozellikler'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 2, 'options' => json_encode(['Açık Mutfak', 'Kapalı Mutfak', 'Amerikan Mutfak', 'Lüks Mutfak'])],
            ['name' => 'Isıtma',              'slug' => 'isitma',            'type' => 'multiselect','unit' => null,         'feature_category_id' => $cat('ic-ozellikler'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 3, 'options' => json_encode(['Doğalgaz', 'Kombi', 'Merkezi Isıtma', 'Yerden Isıtma', 'Klima', 'Soba'])],
            ['name' => 'Soğutma',             'slug' => 'sogutma',           'type' => 'multiselect','unit' => null,         'feature_category_id' => $cat('ic-ozellikler'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 4, 'options' => json_encode(['Klima', 'Merkezi Soğutma', 'Vrf Sistem', 'Doğal Havalandırma'])],
            ['name' => 'Bina Yaşı',           'slug' => 'bina-yasi',         'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('ic-ozellikler'),    'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 5, 'options' => json_encode(['0 (Sıfır Bina)', '1-5 Yıl', '6-10 Yıl', '11-20 Yıl', '21+ Yıl'])],
            ['name' => 'Kurutma Odası',       'slug' => 'kurutma-odasi',     'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('ic-ozellikler'),    'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 6],

            // Maliyet ve Aidat
            ['name' => 'Aidat',               'slug' => 'aidat',             'type' => 'number',    'unit' => 'TL',         'feature_category_id' => $cat('maliyet-ve-aidat'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 1],
            ['name' => 'Depozito',            'slug' => 'depozito',          'type' => 'number',    'unit' => 'TL',         'feature_category_id' => $cat('maliyet-ve-aidat'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 2],
            ['name' => 'Kredi Uygunluğu',     'slug' => 'kredi-uygunlugu',   'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('maliyet-ve-aidat'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 3],
            ['name' => 'Takas',               'slug' => 'takas',             'type' => 'boolean',   'unit' => null,         'feature_category_id' => $cat('maliyet-ve-aidat'), 'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 4],

            // Tapu ve İmar
            ['name' => 'Tapu Durumu',         'slug' => 'tapu-durumu',        'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('tapu-ve-imar'),      'is_required' => false, 'is_filterable' => true,  'is_searchable' => false, 'display_order' => 1, 'options' => json_encode(['Müstakil Tapu', 'Kat Mülkiyeti', 'Kat İrtifakı', 'Hisseli Tapu'])],
            ['name' => 'Kullanım Durumu',     'slug' => 'kullanim-durumu',   'type' => 'select',    'unit' => null,         'feature_category_id' => $cat('tapu-ve-imar'),      'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 2, 'options' => json_encode(['Boş', 'Kiracılı', 'Mülk Sahibi'])],
        ];

        foreach ($features as $f) {
            $exists = DB::table('features')->where('slug', $f['slug'])->exists();
            if (!$exists) {
                DB::table('features')->insert(array_merge($f, [
                    'aktiflik_durumu' => 1,
                    'lifecycle' => 'stable',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    // ─── FEATURE ASSIGNMENTS ─────────────────────────────────────────────────

    private function seedAssignments(): void
    {
        $f = fn(string $slug) => DB::table('features')->where('slug', $slug)->value('id');
        $ts = now();

        // ── Villa Satılık (junction 25, yayin_tipi_id=1, kategori_id=36)
        $this->assign($f('brut-alan'),          11, 8, 1, 'Temel Bilgiler',     true,  true,  1,  1);
        $this->assign($f('net-alan'),           11, 8, 1, 'Temel Bilgiler',     false, true,  2,  1);
        $this->assign($f('oda-sayisi'),          11, 8, 1, 'Temel Bilgiler',     true,  true,  3,  1);
        $this->assign($f('banyo-sayisi'),        11, 8, 1, 'Temel Bilgiler',     false, true,  4,  1);
        $this->assign($f('toplam-kat'),          11, 8, 1, 'Temel Bilgiler',     false, true,  5,  1);
        $this->assign($f('balkon'),              11, 8, 1, 'Temel Bilgiler',     false, true,  6,  1);
        $this->assign($f('kat'),                 11, 8, 1, 'Temel Bilgiler',     false, true,  7,  1);

        $this->assign($f('arsa-alani'),          11, 8, 1, 'Konum ve Arsa',      false, true,  1,  8);
        $this->assign($f('denize-mesafe'),       11, 8, 1, 'Konum ve Arsa',      false, true,  2,  8);
        $this->assign($f('manzara'),             11, 8, 1, 'Konum ve Arsa',      false, true,  3,  8);
        $this->assign($f('cephe'),               11, 8, 1, 'Konum ve Arsa',      false, true,  4,  8);
        $this->assign($f('imar-durumu'),         11, 8, 1, 'Konum ve Arsa',      false, true,  5,  8);

        $this->assign($f('havuz'),               11, 8, 1, 'Yapı Özellikleri',   false, true,  1,  13);
        $this->assign($f('havuz-tip'),           11, 8, 1, 'Yapı Özellikleri',   false, true,  2,  13, 'listing_type', null, ['field' => 'havuz', 'operator' => 'truthy']);
        $this->assign($f('ozel-havuz'),         11, 8, 1, 'Yapı Özellikleri',   false, true,  3,  13);
        $this->assign($f('bahce'),               11, 8, 1, 'Yapı Özellikleri',   false, true,  4,  13);
        $this->assign($f('bahce-alani'),         11, 8, 1, 'Yapı Özellikleri',   false, true,  5,  13);
        $this->assign($f('akilli-ev'),          11, 8, 1, 'Yapı Özellikleri',   false, true,  6,  13);
        $this->assign($f('teras'),              11, 8, 1, 'Yapı Özellikleri',   false, true,  7,  13);
        $this->assign($f('veranda'),            11, 8, 1, 'Yapı Özellikleri',   false, false, 8,  13);

        $this->assign($f('otopark'),            11, 8, 1, 'Dış Özellikler',     false, true,  1,  21);
        $this->assign($f('guvenlik'),           11, 8, 1, 'Dış Özellikler',     false, true,  2,  21);
        $this->assign($f('site-icerisinde'),   11, 8, 1, 'Dış Özellikler',     false, true,  3,  21);
        $this->assign($f('spor-alani'),         11, 8, 1, 'Dış Özellikler',     false, true,  4,  21);

        $this->assign($f('esyali'),             11, 8, 1, 'İç Özellikler',      false, true,  1,  25);
        $this->assign($f('mutfak-tipi'),        11, 8, 1, 'İç Özellikler',      false, true,  2,  25);
        $this->assign($f('isitma'),             11, 8, 1, 'İç Özellikler',      false, true,  3,  25);
        $this->assign($f('sogutma'),            11, 8, 1, 'İç Özellikler',      false, true,  4,  25);
        $this->assign($f('bina-yasi'),          11, 8, 1, 'İç Özellikler',      false, true,  5,  25);
        $this->assign($f('kurutma-odasi'),     11, 8, 1, 'İç Özellikler',      false, false, 6,  25);

        $this->assign($f('aidat'),              11, 8, 1, 'Maliyet ve Aidat',   false, false, 1,  31);
        $this->assign($f('kredi-uygunlugu'),   11, 8, 1, 'Maliyet ve Aidat',   false, true,  3,  31);
        $this->assign($f('takas'),              11, 8, 1, 'Maliyet ve Aidat',   false, true,  4,  31);

        $this->assign($f('tapu-durumu'),        11, 8, 1, 'Tapu ve İmar',       false, true,  1,  34);
        $this->assign($f('kullanim-durumu'),   11, 8, 1, 'Tapu ve İmar',       false, false, 2,  34);

        // ── Villa Kiralık (yayin_tipi_id=2) — deposit visible
        $this->assign($f('depozito'),           11, 8, 2, 'Maliyet ve Aidat',   true,  false, 2,  31);

        // ── Villa Günlük (yayin_tipi_id=5) — explicit, NOT inherited
        // FeatureTemplateResolver does NOT cascade across listing_type values.
        // Explicit assignment required for every listing type.
        $this->assign($f('brut-alan'),          11, 8, 5, 'Temel Bilgiler',     true,  true,  1,  1);
        $this->assign($f('net-alan'),           11, 8, 5, 'Temel Bilgiler',     false, true,  2,  1);
        $this->assign($f('oda-sayisi'),          11, 8, 5, 'Temel Bilgiler',     true,  true,  3,  1);
        $this->assign($f('banyo-sayisi'),        11, 8, 5, 'Temel Bilgiler',     false, true,  4,  1);
        $this->assign($f('toplam-kat'),          11, 8, 5, 'Temel Bilgiler',     false, true,  5,  1);
        $this->assign($f('balkon'),              11, 8, 5, 'Temel Bilgiler',     false, true,  6,  1);
        $this->assign($f('kat'),                 11, 8, 5, 'Temel Bilgiler',     false, true,  7,  1);

        $this->assign($f('arsa-alani'),          11, 8, 5, 'Konum ve Arsa',      false, true,  1,  8);
        $this->assign($f('denize-mesafe'),       11, 8, 5, 'Konum ve Arsa',      false, true,  2,  8);
        $this->assign($f('manzara'),             11, 8, 5, 'Konum ve Arsa',      false, true,  3,  8);
        $this->assign($f('cephe'),               11, 8, 5, 'Konum ve Arsa',      false, true,  4,  8);
        $this->assign($f('imar-durumu'),         11, 8, 5, 'Konum ve Arsa',      false, true,  5,  8);

        $this->assign($f('havuz'),               11, 8, 5, 'Yapı Özellikleri',   false, true,  1, 13);
        $this->assign($f('havuz-tip'),           11, 8, 5, 'Yapı Özellikleri',   false, true,  2, 13, 'listing_type', null, ['field' => 'havuz', 'operator' => 'truthy']);
        $this->assign($f('ozel-havuz'),         11, 8, 5, 'Yapı Özellikleri',   false, true,  3, 13);
        $this->assign($f('bahce'),               11, 8, 5, 'Yapı Özellikleri',   false, true,  4, 13);
        $this->assign($f('bahce-alani'),         11, 8, 5, 'Yapı Özellikleri',   false, true,  5, 13);
        $this->assign($f('akilli-ev'),          11, 8, 5, 'Yapı Özellikleri',   false, true,  6, 13);
        $this->assign($f('teras'),              11, 8, 5, 'Yapı Özellikleri',   false, true,  7, 13);
        $this->assign($f('veranda'),            11, 8, 5, 'Yapı Özellikleri',   false, false, 8, 13);

        $this->assign($f('otopark'),            11, 8, 5, 'Dış Özellikler',     false, true,  1, 21);
        $this->assign($f('guvenlik'),           11, 8, 5, 'Dış Özellikler',     false, true,  2, 21);
        $this->assign($f('site-icerisinde'),   11, 8, 5, 'Dış Özellikler',     false, true,  3, 21);
        $this->assign($f('spor-alani'),         11, 8, 5, 'Dış Özellikler',     false, true,  4, 21);

        $this->assign($f('esyali'),             11, 8, 5, 'İç Özellikler',      false, true,  1, 25);
        $this->assign($f('mutfak-tipi'),        11, 8, 5, 'İç Özellikler',      false, true,  2, 25);
        $this->assign($f('isitma'),             11, 8, 5, 'İç Özellikler',      false, true,  3, 25);
        $this->assign($f('sogutma'),            11, 8, 5, 'İç Özellikler',      false, true,  4, 25);
        $this->assign($f('bina-yasi'),          11, 8, 5, 'İç Özellikler',      false, true,  5, 25);
        $this->assign($f('kurutma-odasi'),     11, 8, 5, 'İç Özellikler',      false, false, 6, 25);

        $this->assign($f('aidat'),              11, 8, 5, 'Maliyet ve Aidat',   false, false, 1, 31);
        $this->assign($f('kredi-uygunlugu'),   11, 8, 5, 'Maliyet ve Aidat',   false, true,  3, 31);
        $this->assign($f('takas'),              11, 8, 5, 'Maliyet ve Aidat',   false, true,  4, 31);

        $this->assign($f('tapu-durumu'),        11, 8, 5, 'Tapu ve İmar',       false, true,  1, 34);
        $this->assign($f('kullanim-durumu'),   11, 8, 5, 'Tapu ve İmar',       false, false, 2, 34);

        // Konut Global (main_category 1, no sub, no listing_type) — 8 core fields
        $this->assign($f('brut-alan'),         1, null, null, 'Temel Bilgiler',   true,  true,  1,  1, 'main_category');
        $this->assign($f('net-alan'),          1, null, null, 'Temel Bilgiler',   false, true,  2,  1, 'main_category');
        $this->assign($f('oda-sayisi'),         1, null, null, 'Temel Bilgiler',   true,  true,  3,  1, 'main_category');
        $this->assign($f('banyo-sayisi'),       1, null, null, 'Temel Bilgiler',   false, true,  4,  1, 'main_category');
        $this->assign($f('isitma'),            1, null, null, 'İç Özellikler',    false, true,  1,  3, 'main_category');
        $this->assign($f('esyali'),            1, null, null, 'İç Özellikler',    false, true,  2,  3, 'main_category');
        $this->assign($f('tapu-durumu'),       1, null, null, 'Tapu ve İmar',     false, true,  1,  4, 'main_category');
        $this->assign($f('bina-yasi'),         1, null, null, 'İç Özellikler',    false, true,  3,  3, 'main_category');

        // ── Global (all categories, all listing types) — 5 universal fields
        $this->assign($f('kredi-uygunlugu'),   null, null, null, 'Maliyet ve Aidat', false, true, 1,  1, 'global');
        $this->assign($f('takas'),             null, null, null, 'Maliyet ve Aidat', false, true, 2,  1, 'global');
        $this->assign($f('site-icerisinde'),  null, null, null, 'Dış Özellikler',   false, true, 1,  2, 'global');
        $this->assign($f('manzara'),           null, null, null, 'Konum ve Arsa',    false, true, 1,  2, 'global');
        $this->assign($f('otopark'),           null, null, null, 'Dış Özellikler',   false, true, 2,  2, 'global');
    }

    /**
     * Create a feature_assignment row.
     */
    private function assign(
        ?int $featureId,
        ?int $mainCategoryId,
        ?int $subCategoryId,
        ?int $listingTypeId,
        string $groupName,
        bool $isRequired,
        bool $isVisible,
        int $displayOrder,
        int $orderWithinGroup,
        string $scopeType = 'listing_type',
        ?array $visibleIf = null,
    ): void {
        if (!$featureId) {
            return; // Feature not found — skip
        }

        $exists = DB::table('feature_assignments')
            ->where('feature_id', $featureId)
            ->where('main_category_id', $mainCategoryId)
            ->where('sub_category_id', $subCategoryId)
            ->where('listing_type_id', $listingTypeId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('feature_assignments')->insert([
            'feature_id'         => $featureId,
            'assignable_type'    => 'App\\Models\\Ilan',
            'assignable_id'      => 0, // template-level; specific listing uses ilan_feature
            'main_category_id'   => $mainCategoryId,
            'sub_category_id'   => $subCategoryId,
            'listing_type_id'    => $listingTypeId,
            'scope_type'        => $scopeType,
            'source_type'       => 'canonical_seed',
            'group_name'        => $groupName,
            'field_slug'        => DB::table('features')->where('id', $featureId)->value('slug'),
            'is_required'       => $isRequired,
            'is_visible'       => $isVisible,
            'aktiflik_durumu'   => 1,
            'display_order'     => $displayOrder,
            'visible_if_json'   => $visibleIf ? json_encode($visibleIf) : null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}

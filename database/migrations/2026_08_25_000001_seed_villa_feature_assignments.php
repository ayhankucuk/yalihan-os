<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed villa-specific feature data into feature_categories, features,
 * and feature_assignments tables.
 *
 * Coverage:
 *   Villa Satilik  (main=11, sub=36, listing_type=1) → 34 fields
 *   Villa Kiralik  (main=11, sub=36, listing_type=2) →  1 field  (depozito)
 *   Villa Gunluk   (main=11, sub=36, listing_type=5) →  0 new (reuses Satilik)
 *   Konut Global  (main=11, sub=null, lt=null)       →  8 fields
 *   Global        (main=null, sub=null, lt=null)     →  5 fields
 *
 * Total: feature_categories=7, features=36, feature_assignments=48
 *
 * Run: php artisan migrate
 * Rollback: php artisan migrate:rollback --step=1
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->seedFeatureCategories();
        $this->seedFeatures();
        $this->seedAssignments();
    }

    public function down(): void
    {
        DB::table('feature_assignments')
            ->where('source_type', 'canonical_seed')
            ->delete();
        DB::table('features')
            ->where('id', '>=', 1)
            ->where('id', '<=', 36)
            ->delete();
        DB::table('feature_categories')
            ->where('id', '>=', 1)
            ->where('id', '<=', 7)
            ->delete();
    }

    private function seedFeatureCategories(): void
    {
        $rows = [
            [1, 'Temel Bilgiler',    'temel-bilgiler',     "Villa'nın temel fiziksel özellikleri",      'villa,property', 'home',         1],
            [2, 'Konum ve Arsa',     'konum-ve-arsa',      "Villa'nın konumu ve arsa bilgileri",         'villa',          'map-pin',      2],
            [3, 'Yapı Özellikleri',  'yapi-ozellikleri',   'Havuz, bahçe, akıllı ev gibi özellikler',   'villa',          'zap',           3],
            [4, 'Dış Özellikler',    'dis-ozellikler',     'Otopark, güvenlik, spor alanları',          'villa',          'shield',        4],
            [5, 'İç Özellikler',    'ic-ozellikler',      'Eşya, mutfak, ısıtma-soğutma',             'villa',          'thermometer',   5],
            [6, 'Maliyet ve Aidat', 'maliyet-ve-aidat',   'Fiyat, aidat ve ek maliyetler',             'villa,property', 'credit-card',   6],
            [7, 'Tapu ve İmar',      'tapu-ve-imar',       'Tapu durumu, imar ve yasal bilgiler',       'villa,property', 'file-text',     7],
        ];

        foreach ($rows as $r) {
            DB::table('feature_categories')->updateOrInsert(
                ['id' => $r[0]],
                [
                    'name'              => $r[1],
                    'slug'              => $r[2],
                    'description'       => $r[3],
                    'applies_to'        => $r[4],
                    'icon'              => $r[5],
                    'display_order'     => $r[6],
                    'aktiflik_durumu'   => 1,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]
            );
        }
    }

    private function seedFeatures(): void
    {
        $rows = [
            // [id, name, slug, type, unit, cat_id, opts, required, filterable, searchable, order]
            [1,  'Brüt Alan',        'brut-alan',       'number',     'm²',  1, null,                       true,   true,  true,  1],
            [2,  'Net Alan',         'net-alan',         'number',     'm²',  1, null,                       false,  true,  true,  2],
            [3,  'Oda Sayısı',       'oda-sayisi',       'text',       null,  1, null,                       true,   true,  true,  3],
            [4,  'Banyo Sayısı',     'banyo-sayisi',     'number',     null,  1, null,                       false,  true,  true,  4],
            [5,  'Toplam Kat',       'toplam-kat',       'number',     null,  1, null,                       false,  true,  false, 5],
            [6,  'Balkon',           'balkon',           'boolean',    null,  1, null,                       false,  true,  false, 6],
            [7,  'Kat',              'kat',              'select',     null,  1, '["Zemin","1","2","3","4","5","Çatı Katı"]', false, true, false, 7],
            [8,  'Arsa Alanı',       'arsa-alani',       'number',     'm²',  2, null,                       false,  true,  true,  1],
            [9,  'Denize Mesafe',    'denize-mesafe',    'select',     null,  2, '["Deniz Kenarı","50m İçinde","100m","200m","500m","1km","5km+"]', false, true, true, 2],
            [10, 'Manzara',          'manzara',          'multiselect',null,  2, '["Deniz","Göl","Dağ","Doğa","Bahçe","Havuz"]', false, true, false, 3],
            [11, 'Cephe',            'cephe',            'select',     null,  2, '["Kuzey","Güney","Doğu","Batı","Güneybatı","Güneydoğu","Kuzeybatı","Kuzeydoğu"]', false, true, false, 4],
            [12, 'İmar Durumu',       'imar-durumu',      'select',     null,  2, '["Konut İmarlı","Ticari İmar","Turizm İmarlı","İmarsız"]', false, true, false, 5],
            [13, 'Havuz',            'havuz',            'boolean',    null,  3, null,                       false,  true,  true,  1],
            [14, 'Havuz Tipi',       'havuz-tip',        'select',     null,  3, '["Açık","Kapalı","Yarı Açık","Çocuk Havuzu"]', false, true, false, 2],
            [15, 'Özel Havuz',        'ozel-havuz',      'boolean',    null,  3, null,                       false,  true,  false, 3],
            [16, 'Bahçe',            'bahce',            'boolean',    null,  3, null,                       false,  true,  false, 4],
            [17, 'Bahçe Alanı',      'bahce-alani',      'number',     'm²',  3, null,                       false,  true,  false, 5],
            [18, 'Akıllı Ev',        'akilli-ev',        'boolean',    null,  3, null,                       false,  true,  false, 6],
            [19, 'Teras',            'teras',            'boolean',    null,  3, null,                       false,  true,  false, 7],
            [20, 'Veranda',          'veranda',          'boolean',    null,  3, null,                       false,  false, false, 8],
            [21, 'Otopark',          'otopark',          'select',     null,  4, '["Yok","Açık Otopark","Kapalı Otopark","Garaj"]', false, true, false, 1],
            [22, 'Güvenlik',          'guvenlik',         'boolean',    null,  4, null,                       false,  true,  false, 2],
            [23, 'Site İçerisinde', 'site-icerisinde',  'boolean',    null,  4, null,                       false,  true,  false, 3],
            [24, 'Spor Alanı',       'spor-alani',        'boolean',    null,  4, null,                       false,  true,  false, 4],
            [25, 'Eşyalı',           'esyali',           'select',     null,  5, '["Hayır","Kısmen","Evet"]', false,  true,  true,  1],
            [26, 'Mutfak Tipi',      'mutfak-tipi',      'select',     null,  5, '["Açık Mutfak","Kapalı Mutfak","Amerikan Mutfak","Lüks Mutfak"]', false, true, false, 2],
            [27, 'Isıtma',           'isitma',           'multiselect',null,  5, '["Doğalgaz","Kombi","Merkezi Isıtma","Yerden Isıtma","Klima","Soba"]', false, true, false, 3],
            [28, 'Soğutma',          'sogutma',          'multiselect',null,  5, '["Klima","Merkezi Soğutma","Vrf Sistem","Doğal Havalandırma"]', false, true, false, 4],
            [29, 'Bina Yaşı',        'bina-yasi',        'select',     null,  5, '["0 (Sıfır Bina)","1-5 Yıl","6-10 Yıl","11-20 Yıl","21+ Yıl"]', false, true, false, 5],
            [30, 'Kurutma Odası',   'kurutma-odasi',   'boolean',    null,  5, null,                       false,  false, false, 6],
            [31, 'Aidat',             'aidat',            'number',     'TL',  6, null,                       false,  false, false, 1],
            [32, 'Depozito',          'depozito',         'number',     'TL',  6, null,                       false,  false, false, 2],
            [33, 'Kredi Uygunluğu', 'kredi-uygunlugu', 'boolean',    null,  6, null,                       false,  true,  false, 3],
            [34, 'Takas',             'takas',            'boolean',    null,  6, null,                       false,  true,  false, 4],
            [35, 'Tapu Durumu',      'tapu-durumu',      'select',     null,  7, '["Müstakil Tapu","Kat Mülkiyeti","Kat İrtifakı","Hisseli Tapu"]', false, true, false, 1],
            [36, 'Kullanım Durumu',   'kullanim-durumu',  'select',     null,  7, '["Boş","Kiracılı","Mülk Sahibi"]', false, false, false, 2],
        ];

        foreach ($rows as $r) {
            [$id, $name, $slug, $type, $unit, $catId, $opts, $req, $fil, $sea, $ord] = $r;
            DB::table('features')->updateOrInsert(
                ['id' => $id],
                [
                    'name'                 => $name,
                    'slug'                 => $slug,
                    'lifecycle'            => 'stable',
                    'type'                  => $type,
                    'unit'                 => $unit,
                    'feature_category_id'  => $catId,
                    'options'              => $opts,
                    'is_required'          => $req,
                    'is_filterable'        => $fil,
                    'is_searchable'        => $sea,
                    'display_order'        => $ord,
                    'aktiflik_durumu'     => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]
            );
        }
    }

    private function seedAssignments(): void
    {
        // [feature_id, main_cat, sub_cat, listing_type, group_name, required, visible, order, scope_type]
        $rows = [
            // Villa Satılık (junction=25, yayin_tipi=1, kategori=36) — 34 fields
            [1,  11, 36, 1, 'Temel Bilgiler',    true,   true,   1],
            [2,  11, 36, 1, 'Temel Bilgiler',    false,  true,   2],
            [3,  11, 36, 1, 'Temel Bilgiler',    true,   true,   3],
            [4,  11, 36, 1, 'Temel Bilgiler',    false,  true,   4],
            [5,  11, 36, 1, 'Temel Bilgiler',    false,  true,   5],
            [6,  11, 36, 1, 'Temel Bilgiler',    false,  true,   6],
            [7,  11, 36, 1, 'Temel Bilgiler',    false,  true,   7],
            [8,  11, 36, 1, 'Konum ve Arsa',    false,  true,   1],
            [9,  11, 36, 1, 'Konum ve Arsa',   false,  true,   2],
            [10, 11, 36, 1, 'Konum ve Arsa',     false,  true,   3],
            [11, 11, 36, 1, 'Konum ve Arsa',     false,  true,   4],
            [12, 11, 36, 1, 'Konum ve Arsa',    false,  true,   5],
            [13, 11, 36, 1, 'Yapı Özellikleri', false,  true,   1],
            [14, 11, 36, 1, 'Yapı Özellikleri', false,  true,   2],
            [15, 11, 36, 1, 'Yapı Özellikleri', false,  true,   3],
            [16, 11, 36, 1, 'Yapı Özellikleri', false,  true,   4],
            [17, 11, 36, 1, 'Yapı Özellikleri', false,  true,   5],
            [18, 11, 36, 1, 'Yapı Özellikleri', false,  true,   6],
            [19, 11, 36, 1, 'Yapı Özellikleri', false,  true,   7],
            [20, 11, 36, 1, 'Yapı Özellikleri', false,  false,  8],
            [21, 11, 36, 1, 'Dış Özellikler',   false,  true,   1],
            [22, 11, 36, 1, 'Dış Özellikler',   false,  true,   2],
            [23, 11, 36, 1, 'Dış Özellikler',   false,  true,   3],
            [24, 11, 36, 1, 'Dış Özellikler',   false,  true,   4],
            [25, 11, 36, 1, 'İç Özellikler',    false,  true,   1],
            [26, 11, 36, 1, 'İç Özellikler',    false,  true,   2],
            [27, 11, 36, 1, 'İç Özellikler',    false,  true,   3],
            [28, 11, 36, 1, 'İç Özellikler',    false,  true,   4],
            [29, 11, 36, 1, 'İç Özellikler',    false,  true,   5],
            [30, 11, 36, 1, 'İç Özellikler',    false,  false,  6],
            [31, 11, 36, 1, 'Maliyet ve Aidat', false,  false,  1],
            [33, 11, 36, 1, 'Maliyet ve Aidat', false,  true,   3],
            [34, 11, 36, 1, 'Maliyet ve Aidat', false,  true,   4],
            [35, 11, 36, 1, 'Tapu ve İmar',    false,  true,   1],
            [36, 11, 36, 1, 'Tapu ve İmar',    false,  false,  2],

            // Villa Kiralık (junction=26, yayin_tipi=2) — 1 field (depozito)
            [32, 11, 36, 2, 'Maliyet ve Aidat', true,   false,  2],

            // Konut Global (main=11, sub=null, lt=null) — 8 fields
            [1,  11, null, null, 'Temel Bilgiler', true,   true,   1, 'main_category'],
            [2,  11, null, null, 'Temel Bilgiler', false,  true,   2, 'main_category'],
            [3,  11, null, null, 'Temel Bilgiler', true,   true,   3, 'main_category'],
            [4,  11, null, null, 'Temel Bilgiler', false,  true,   4, 'main_category'],
            [27, 11, null, null, 'İç Özellikler', false,  true,   1, 'main_category'],
            [25, 11, null, null, 'İç Özellikler', false,  true,   2, 'main_category'],
            [35, 11, null, null, 'Tapu ve İmar',  false,  true,   1, 'main_category'],
            [29, 11, null, null, 'İç Özellikler', false,  true,   3, 'main_category'],

            // Global (all categories) — 5 fields
            [33, null, null, null, 'Maliyet ve Aidat', false,  true,   1, 'global'],
            [34, null, null, null, 'Maliyet ve Aidat', false,  true,   2, 'global'],
            [23, null, null, null, 'Dış Özellikler', false,  true,   1, 'global'],
            [10, null, null, null, 'Konum ve Arsa',   false,  true,   1, 'global'],
            [21, null, null, null, 'Dış Özellikler', false,  true,   2, 'global'],
        ];

        $ts = now()->toDateTimeString();

        foreach ($rows as $i => $r) {
            $fi = $r[0];
            $mc = $r[1];
            $sc = $r[2];
            $lt = $r[3];
            $gn = $r[4];
            $req = $r[5];
            $vis = $r[6];
            $ord = $r[7];
            $scope = $r[8] ?? 'listing_type';

            // Generate deterministic ID to avoid uniqueness conflicts
            $pk = $i + 1;

            DB::table('feature_assignments')->updateOrInsert(
                [
                    'feature_id'        => $fi,
                    'main_category_id'  => $mc,
                    'sub_category_id'   => $sc,
                    'listing_type_id'   => $lt,
                ],
                [
                    'id'                => $pk,
                    'assignable_type'   => 'App\\Models\\Ilan',
                    'assignable_id'     => 0,
                    'scope_type'        => $scope,
                    'source_type'       => 'canonical_seed',
                    'group_name'        => $gn,
                    'field_slug'        => DB::table('features')->where('id', $fi)->value('slug'),
                    'is_required'      => $req,
                    'is_visible'       => $vis,
                    'aktiflik_durumu'  => 1,
                    'display_order'     => $ord,
                    'created_at'       => $ts,
                    'updated_at'       => $ts,
                ]
            );
        }
    }
};

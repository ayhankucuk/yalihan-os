<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FeatureAssignmentSeeder — Canonical Feature Assignment Seed Data
 *
 * OWNERSHIP CONTRACT: This seeder uses the canonical assignable_type:
 *   - Villa Tiers (G3/G4/G5):  assignable_type = 'App\Models\YayinTipiSablonu'
 *   - Konut Tier (G2):          assignable_type = 'App\Models\IlanKategori'
 *   - Global Tier (G1):         assignable_type = 'App\Models\IlanKategori'
 *
 * ROLLBACK SAFETY: This seeder writes source_type = 'canonical_seed'.
 * The migration's down() method deletes only source_type = 'canonical_seed'.
 * If seeder + migration both run, updateOrInsert prevents duplicates.
 *
 * Scope cascade: listing_type(400) > sub_category(300) > main_category(200) > global(100)
 *
 * Coverage:
 *   - Villa Satılık  (main=1, sub=8, lt=1) = 35 fields @ YayinTipiSablonu
 *   - Villa Kiralık  (main=1, sub=8, lt=2) = 36 fields @ YayinTipiSablonu (G3 scope + aidat + depozito)
 *   - Villa Günlük   (main=1, sub=8, lt=5) = 35 fields @ YayinTipiSablonu (explicit)
 *   - Konut Global   (main=1, sub=null, lt=null) = 8 fields @ IlanKategori
 *   - Global         (main=null, sub=null, lt=null) = 5 fields @ IlanKategori
 *
 * Total: 119 assignments (G1/G2/G3/G4/G5 — canonical complete set)
 *
 * NOTE: Migration seeds only G3/G4/G5 (Villa tiers) = 106.
 * Seeder covers G1/G2 + G3/G4/G5 = 119. Migration and seeder are separate provenance tracks.
 *
 * IMPORTANT: Villa sub_category_id = 8 (NOT 36). Kategori 36 does not exist.
 * Sub-category 8 = Villa in ilan_kategorileri table (parent=1, seviye=1).
 *
 * IMPORTANT: Villa Günlük (lt=5) does NOT inherit from Villa Satılık (lt=1).
 * FeatureTemplateResolver does not support cross-listing-type cascade.
 * Explicit assignments are required for each listing type.
 *
 * Verifies: php artisan db:seed --class=FeatureAssignmentSeeder
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
        if ($this->command) {
            $this->command->info("✅ FeatureAssignmentSeeder: {$fc} categories, {$f} features, {$fa} assignments");
        }
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

    /**
     * Resolve YayinTipiSablonu ID from DB.
     * Returns null if the template does not exist yet.
     */
    private function resolveTemplate(?int $kategoriId, ?int $yayinTipiId): ?int
    {
        return DB::table('yayin_tipi_sablonlari')
            ->when($kategoriId !== null, fn($q) => $q->where('kategori_id', $kategoriId))
            ->when($kategoriId === null, fn($q) => $q->whereNull('kategori_id'))
            ->when($yayinTipiId !== null, fn($q) => $q->where('yayin_tipi_id', $yayinTipiId))
            ->when($yayinTipiId === null, fn($q) => $q->whereNull('yayin_tipi_id'))
            ->value('id');
    }

    private function seedAssignments(): void
    {
        $f = fn(string $slug) => DB::table('features')->where('slug', $slug)->value('id');

        // ── VILLA TIER (G3/G4/G5): assignable_type = YayinTipiSablonu ──

        // Villa Satılık (kategori_id=8, yayin_tipi_id=1)
        $villaSatilikTemplate = $this->resolveTemplate(8, 1);
        if ($villaSatilikTemplate) {
            $this->assignToTemplate($villaSatilikTemplate, [
                [$f('brut-alan'),        'Temel Bilgiler',     true,  true,  1],
                [$f('net-alan'),         'Temel Bilgiler',     false, true,  2],
                [$f('oda-sayisi'),        'Temel Bilgiler',     true,  true,  3],
                [$f('banyo-sayisi'),      'Temel Bilgiler',     false, true,  4],
                [$f('toplam-kat'),        'Temel Bilgiler',     false, true,  5],
                [$f('balkon'),           'Temel Bilgiler',     false, true,  6],
                [$f('kat'),              'Temel Bilgiler',     false, true,  7],
                [$f('arsa-alani'),        'Konum ve Arsa',     false, true,  1],
                [$f('denize-mesafe'),     'Konum ve Arsa',     false, true,  2],
                [$f('manzara'),          'Konum ve Arsa',     false, true,  3],
                [$f('cephe'),            'Konum ve Arsa',     false, true,  4],
                [$f('imar-durumu'),       'Konum ve Arsa',     false, true,  5],
                [$f('havuz'),            'Yapı Özellikleri', false, true,  1],
                [$f('havuz-tip'),        'Yapı Özellikleri', false, true,  2],
                [$f('ozel-havuz'),       'Yapı Özellikleri', false, true,  3],
                [$f('bahce'),            'Yapı Özellikleri', false, true,  4],
                [$f('bahce-alani'),      'Yapı Özellikleri', false, true,  5],
                [$f('akilli-ev'),        'Yapı Özellikleri', false, true,  6],
                [$f('teras'),           'Yapı Özellikleri', false, true,  7],
                [$f('veranda'),         'Yapı Özellikleri', false, false, 8],
                [$f('otopark'),         'Dış Özellikler',   false, true,  1],
                [$f('guvenlik'),        'Dış Özellikler',   false, true,  2],
                [$f('site-icerisinde'), 'Dış Özellikler',   false, true,  3],
                [$f('spor-alani'),      'Dış Özellikler',   false, true,  4],
                [$f('esyali'),          'İç Özellikler',    false, true,  1],
                [$f('mutfak-tipi'),     'İç Özellikler',    false, true,  2],
                [$f('isitma'),          'İç Özellikler',    false, true,  3],
                [$f('sogutma'),         'İç Özellikler',    false, true,  4],
                [$f('bina-yasi'),       'İç Özellikler',    false, true,  5],
                [$f('kurutma-odasi'),  'İç Özellikler',    false, false, 6],
                [$f('aidat'),           'Maliyet ve Aidat', false, false, 1],
                [$f('kredi-uygunlugu'),'Maliyet ve Aidat', false, true,  3],
                [$f('takas'),           'Maliyet ve Aidat', false, true,  4],
                [$f('tapu-durumu'),     'Tapu ve İmar',     false, true,  1],
                [$f('kullanim-durumu'), 'Tapu ve İmar',     false, false, 2],
            ], 'listing_type');
        }

        // Villa Kiralık (kategori_id=8, yayin_tipi_id=2) — full scope: G3 Villa + aidat + depozito
        // G4 canonical: Villa Satılık scope + aidat (suggested, required=false) + depozito (required=true)
        $villaKiralikTemplate = $this->resolveTemplate(8, 2);
        if ($villaKiralikTemplate) {
            $this->assignToTemplate($villaKiralikTemplate, [
                // Temel Bilgiler (7 fields)
                [$f('brut-alan'),        'Temel Bilgiler',     true,  true,  1],
                [$f('net-alan'),         'Temel Bilgiler',     false, true,  2],
                [$f('oda-sayisi'),        'Temel Bilgiler',     true,  true,  3],
                [$f('banyo-sayisi'),      'Temel Bilgiler',     false, true,  4],
                [$f('toplam-kat'),        'Temel Bilgiler',     false, true,  5],
                [$f('balkon'),           'Temel Bilgiler',     false, true,  6],
                [$f('kat'),              'Temel Bilgiler',     false, true,  7],
                // Konum ve Arsa (5 fields)
                [$f('arsa-alani'),        'Konum ve Arsa',     false, true,  1],
                [$f('denize-mesafe'),     'Konum ve Arsa',     false, true,  2],
                [$f('manzara'),          'Konum ve Arsa',     false, true,  3],
                [$f('cephe'),            'Konum ve Arsa',     false, true,  4],
                [$f('imar-durumu'),       'Konum ve Arsa',     false, true,  5],
                // Yapı Özellikleri (8 fields, kurutma-odasi=false)
                [$f('havuz'),            'Yapı Özellikleri', false, true,  1],
                [$f('havuz-tip'),        'Yapı Özellikleri', false, true,  2],
                [$f('ozel-havuz'),       'Yapı Özellikleri', false, true,  3],
                [$f('bahce'),            'Yapı Özellikleri', false, true,  4],
                [$f('bahce-alani'),      'Yapı Özellikleri', false, true,  5],
                [$f('akilli-ev'),        'Yapı Özellikleri', false, true,  6],
                [$f('teras'),           'Yapı Özellikleri', false, true,  7],
                [$f('veranda'),         'Yapı Özellikleri', false, false, 8],
                // Dış Özellikler (4 fields)
                [$f('otopark'),         'Dış Özellikler',   false, true,  1],
                [$f('guvenlik'),        'Dış Özellikler',   false, true,  2],
                [$f('site-icerisinde'), 'Dış Özellikler',   false, true,  3],
                [$f('spor-alani'),      'Dış Özellikler',   false, true,  4],
                // İç Özellikler (6 fields, kurutma-odasi=false)
                [$f('esyali'),          'İç Özellikler',    false, true,  1],
                [$f('mutfak-tipi'),     'İç Özellikler',    false, true,  2],
                [$f('isitma'),          'İç Özellikler',    false, true,  3],
                [$f('sogutma'),         'İç Özellikler',    false, true,  4],
                [$f('bina-yasi'),       'İç Özellikler',    false, true,  5],
                [$f('kurutma-odasi'),  'İç Özellikler',    false, false, 6],
                // Maliyet ve Aidat (4 fields: aidat + depozito + kredi + takas)
                [$f('aidat'),           'Maliyet ve Aidat', false, false, 1], // suggested, domain kararı bekleniyor
                [$f('depozito'),        'Maliyet ve Aidat', true,  false, 2], // required=true (G4-specific)
                [$f('kredi-uygunlugu'),'Maliyet ve Aidat', false, true,  3],
                [$f('takas'),           'Maliyet ve Aidat', false, true,  4],
                // Tapu ve İmar (2 fields)
                [$f('tapu-durumu'),     'Tapu ve İmar',     false, true,  1],
                [$f('kullanim-durumu'), 'Tapu ve İmar',     false, false, 2],
            ], 'listing_type');
        }

        // Villa Günlük (kategori_id=8, yayin_tipi_id=5) — explicit, NOT inherited
        $villaGunlukTemplate = $this->resolveTemplate(8, 5);
        if ($villaGunlukTemplate) {
            $this->assignToTemplate($villaGunlukTemplate, [
                [$f('brut-alan'),        'Temel Bilgiler',     true,  true,  1],
                [$f('net-alan'),         'Temel Bilgiler',     false, true,  2],
                [$f('oda-sayisi'),        'Temel Bilgiler',     true,  true,  3],
                [$f('banyo-sayisi'),      'Temel Bilgiler',     false, true,  4],
                [$f('toplam-kat'),        'Temel Bilgiler',     false, true,  5],
                [$f('balkon'),           'Temel Bilgiler',     false, true,  6],
                [$f('kat'),              'Temel Bilgiler',     false, true,  7],
                [$f('arsa-alani'),        'Konum ve Arsa',     false, true,  1],
                [$f('denize-mesafe'),     'Konum ve Arsa',     false, true,  2],
                [$f('manzara'),          'Konum ve Arsa',     false, true,  3],
                [$f('cephe'),            'Konum ve Arsa',     false, true,  4],
                [$f('imar-durumu'),       'Konum ve Arsa',     false, true,  5],
                [$f('havuz'),            'Yapı Özellikleri', false, true,  1],
                [$f('havuz-tip'),        'Yapı Özellikleri', false, true,  2],
                [$f('ozel-havuz'),       'Yapı Özellikleri', false, true,  3],
                [$f('bahce'),            'Yapı Özellikleri', false, true,  4],
                [$f('bahce-alani'),      'Yapı Özellikleri', false, true,  5],
                [$f('akilli-ev'),        'Yapı Özellikleri', false, true,  6],
                [$f('teras'),           'Yapı Özellikleri', false, true,  7],
                [$f('veranda'),         'Yapı Özellikleri', false, false, 8],
                [$f('otopark'),         'Dış Özellikler',   false, true,  1],
                [$f('guvenlik'),        'Dış Özellikler',   false, true,  2],
                [$f('site-icerisinde'), 'Dış Özellikler',   false, true,  3],
                [$f('spor-alani'),      'Dış Özellikler',   false, true,  4],
                [$f('esyali'),          'İç Özellikler',    false, true,  1],
                [$f('mutfak-tipi'),     'İç Özellikler',    false, true,  2],
                [$f('isitma'),          'İç Özellikler',    false, true,  3],
                [$f('sogutma'),         'İç Özellikler',    false, true,  4],
                [$f('bina-yasi'),       'İç Özellikler',    false, true,  5],
                [$f('kurutma-odasi'),  'İç Özellikler',    false, false, 6],
                [$f('aidat'),           'Maliyet ve Aidat', false, false, 1],
                [$f('kredi-uygunlugu'),'Maliyet ve Aidat', false, true,  3],
                [$f('takas'),           'Maliyet ve Aidat', false, true,  4],
                [$f('tapu-durumu'),     'Tapu ve İmar',     false, true,  1],
                [$f('kullanim-durumu'), 'Tapu ve İmar',     false, false, 2],
            ], 'listing_type');
        }

        // ── G2 KONUT TIER: assignable_type = IlanKategori (Konut root) ──
        // Assign to root IlanKategori id=1 (Konut)
        $konutRootId = DB::table('ilan_kategorileri')->where('id', 1)->value('id');
        if ($konutRootId) {
            $this->assignToKategori($konutRootId, [
                [$f('brut-alan'),        'Temel Bilgiler',   true,  true,  1],
                [$f('net-alan'),         'Temel Bilgiler',   false, true,  2],
                [$f('oda-sayisi'),        'Temel Bilgiler',   true,  true,  3],
                [$f('banyo-sayisi'),      'Temel Bilgiler',   false, true,  4],
                [$f('isitma'),           'İç Özellikler',   false, true,  1],
                [$f('esyali'),           'İç Özellikler',   false, true,  2],
                [$f('tapu-durumu'),      'Tapu ve İmar',    false, true,  1],
                [$f('bina-yasi'),        'İç Özellikler',   false, true,  3],
            ], 'main_category');
        }

        // ── G1 GLOBAL TIER: assignable_type = IlanKategori (all 6 real root categories) ──
        // SAAB 1B: Global features should cascade to all root kategoriler
        // G1 scope: main_category_id=NULL, sub_category_id=NULL, listing_type_id=NULL
        $rootKategoriIds = DB::table('ilan_kategorileri')
            ->whereNull('parent_id')
            ->where('seviye', 0)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->pluck('id')
            ->toArray();

        foreach ($rootKategoriIds as $rootId) {
            $this->assignGlobalFeatures($rootId, [
                [$f('kredi-uygunlugu'), 'Maliyet ve Aidat', false, true, 1],
                [$f('takas'),           'Maliyet ve Aidat', false, true, 2],
                [$f('site-icerisinde'), 'Dış Özellikler',   false, true, 1],
                [$f('manzara'),         'Konum ve Arsa',    false, true, 1],
                [$f('otopark'),         'Dış Özellikler',   false, true, 2],
            ]);
        }
    }

    /**
     * Assign multiple features to a YayinTipiSablonu template.
     */
    private function assignToTemplate(int $templateId, array $features, string $scopeType): void
    {
        // Get template to extract kategori_id and yayin_tipi_id
        $template = DB::table('yayin_tipi_sablonlari')->where('id', $templateId)->first();

        foreach ($features as [$featureId, $groupName, $required, $visible, $order]) {
            if (!$featureId) continue;

            DB::table('feature_assignments')->updateOrInsert(
                [
                    'feature_id'       => $featureId,
                    'assignable_type'  => 'App\\Models\\YayinTipiSablonu',
                    'assignable_id'    => $templateId,
                ],
                [
                    'main_category_id'  => $template->kategori_id ?? null,
                    'sub_category_id'   => null,
                    'listing_type_id'   => $template->yayin_tipi_id ?? null,
                    'scope_type'        => $scopeType,
                    'source_type'       => 'canonical_seed',
                    'group_name'        => $groupName,
                    'field_slug'        => DB::table('features')->where('id', $featureId)->value('slug'),
                    'is_required'       => $required,
                    'is_visible'        => $visible,
                    'aktiflik_durumu'  => 1,
                    'display_order'     => $order,
                    'updated_at'       => now(),
                ]
            );
            // Only set created_at on insert
            if (!DB::table('feature_assignments')->where('feature_id', $featureId)->where('assignable_id', $templateId)->where('assignable_type', 'App\\Models\\YayinTipiSablonu')->where('created_at', null)->exists()) {
                DB::table('feature_assignments')->where('feature_id', $featureId)->where('assignable_id', $templateId)->where('assignable_type', 'App\\Models\\YayinTipiSablonu')->update(['created_at' => now()]);
            }
        }
    }

    /**
     * Assign multiple features to an IlanKategori.
     */
    private function assignToKategori(int $kategoriId, array $features, string $scopeType): void
    {
        // Get kategori to extract parent_id for scope columns
        $kategori = DB::table('ilan_kategorileri')->where('id', $kategoriId)->first();
        $parentId = $kategori->parent_id ?? null;

        foreach ($features as [$featureId, $groupName, $required, $visible, $order]) {
            if (!$featureId) continue;

            DB::table('feature_assignments')->updateOrInsert(
                [
                    'feature_id'       => $featureId,
                    'assignable_type'  => 'App\\Models\\IlanKategori',
                    'assignable_id'    => $kategoriId,
                ],
                [
                    'main_category_id'  => $parentId ?? $kategoriId, // RootKategori uses own ID
                    'sub_category_id'   => $parentId ? $kategoriId : null, // Child uses own ID, root uses null
                    'listing_type_id'   => null,
                    'scope_type'        => $scopeType,
                    'source_type'       => 'canonical_seed',
                    'group_name'        => $groupName,
                    'field_slug'        => DB::table('features')->where('id', $featureId)->value('slug'),
                    'is_required'       => $required,
                    'is_visible'        => $visible,
                    'aktiflik_durumu'  => 1,
                    'display_order'     => $order,
                    'updated_at'       => now(),
                ]
            );
            if (!DB::table('feature_assignments')->where('feature_id', $featureId)->where('assignable_id', $kategoriId)->where('assignable_type', 'App\\Models\\IlanKategori')->where('created_at', null)->exists()) {
                DB::table('feature_assignments')->where('feature_id', $featureId)->where('assignable_id', $kategoriId)->where('assignable_type', 'App\\Models\\IlanKategori')->update(['created_at' => now()]);
            }
        }
    }

    /**
     * Assign global features (G1) to a root IlanKategori.
     * Global features have: main_category_id=NULL, sub_category_id=NULL, listing_type_id=NULL
     * But are assigned to the root kategori via assignable_id for cascade purposes.
     */
    private function assignGlobalFeatures(int $kategoriId, array $features): void
    {
        foreach ($features as [$featureId, $groupName, $required, $visible, $order]) {
            if (!$featureId) continue;

            DB::table('feature_assignments')->updateOrInsert(
                [
                    'feature_id'       => $featureId,
                    'assignable_type'  => 'App\\Models\\IlanKategori',
                    'assignable_id'    => $kategoriId,
                ],
                [
                    'main_category_id'  => null, // G1: all NULL
                    'sub_category_id'   => null,
                    'listing_type_id'   => null,
                    'scope_type'        => 'global',
                    'source_type'       => 'canonical_seed',
                    'group_name'        => $groupName,
                    'field_slug'        => DB::table('features')->where('id', $featureId)->value('slug'),
                    'is_required'       => $required,
                    'is_visible'        => $visible,
                    'aktiflik_durumu'  => 1,
                    'display_order'     => $order,
                    'updated_at'       => now(),
                ]
            );
            if (!DB::table('feature_assignments')->where('feature_id', $featureId)->where('assignable_id', $kategoriId)->where('assignable_type', 'App\\Models\\IlanKategori')->where('created_at', null)->exists()) {
                DB::table('feature_assignments')->where('feature_id', $featureId)->where('assignable_id', $kategoriId)->where('assignable_type', 'App\\Models\\IlanKategori')->update(['created_at' => now()]);
            }
        }
    }
}

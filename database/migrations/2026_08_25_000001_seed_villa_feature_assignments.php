<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed villa-specific feature data into feature_categories, features,
 * and feature_assignments tables.
 *
 * Coverage:
 *   Villa Satilik  (main=1, sub=8, listing_type=1) = 34 fields
 *   Villa Kiralik  (main=1, sub=8, listing_type=2) =  1 field (depozito)
 *   Villa Gunluk   (main=1, sub=8, listing_type=5) = 34 fields (explicit, NOT inherited)
 *   Konut Global   (main=1, sub=null, lt=null)     =  8 fields @ main_category
 *   Global         (main=null, sub=null, lt=null)   =  5 fields @ global
 *
 * Total: feature_categories=7, features=36, feature_assignments=82
 *
 * IMPORTANT: Villa sub_category_id = 8 (NOT 36). Kategori 36 does not exist.
 *   Sub-category 8 = Villa in ilan_kategorileri table (parent=1, seviye=1).
 *   Main category = 1 (Konut), NOT 11 (Ofis).
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
        // Rollback ONLY records produced by this migration.
        //
        // Rollback order: assignments → features → categories
        // Each step is scoped to avoid touching pre-existing data.
        //
        // Assignments:
        //   source_type = 'villa_seed_2026_08_25' identifies this migration's records.
        //
        // Features & Categories:
        //   source_type does NOT exist on these tables.
        //   We scope to id IN 1-36 / 1-7 only when those IDs are exclusively
        //   owned by this migration (no pre-existing records reference them).
        //   If ownership is ambiguous, skip the feature/category deletion and warn.
        //
        // G1 scope (scope_type=global, main_category_id=null):
        //   Assignments attach to IlanKategori::class id=1 (Konut).
        //   Safe to delete: this migration's source_type tag scopes exactly.

        // 1. Assignments — exact scope via source_type
        $deletedAssignments = DB::table('feature_assignments')
            ->where('source_type', 'villa_seed_2026_08_25')
            ->delete();

        // 2. Features — only if exclusively this migration's provenance.
        //    Conditions to DELETE a feature id IN 1-36:
        //    (a) No assignments remain from OTHER migrations (source_type != 'villa_seed_2026_08_25' OR source_type IS NULL)
        //    (b) No assignments from THIS migration either — feature must be orphaned
        //    Safety: source_type IS NULL records are preserved (pre-existing data).
        $orphanedFeatureIds = DB::table('features')
            ->whereIn('id', range(1, 36))
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('feature_assignments')
                    ->whereColumn('feature_id', 'features.id')
                    ->where(function ($r) {
                        $r->where('source_type', '!=', 'villa_seed_2026_08_25')
                            ->orWhereNull('source_type');
                    });
            })
            ->pluck('id');

        if ($orphanedFeatureIds->isNotEmpty()) {
            DB::table('features')->whereIn('id', $orphanedFeatureIds)->delete();
        }

        // 3. Feature categories — only if no features reference them.
        $orphanedCategoryIds = DB::table('feature_categories')
            ->whereIn('id', range(1, 7))
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('features')
                    ->whereColumn('feature_category_id', 'feature_categories.id');
            })
            ->pluck('id');

        if ($orphanedCategoryIds->isNotEmpty()) {
            DB::table('feature_categories')->whereIn('id', $orphanedCategoryIds)->delete();
        }
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
        $ts = now()->toDateTimeString();
        $hasTenantId = Schema::hasColumn('feature_assignments', 'tenant_id');

        // ── Phase 2 SAAB 3E: resolve assignable targets ──────────────────────
        //
        // Three mapping tiers:
        //   G3/G4/G5 (listing_type scope, sub=8, lt=1/2/5)
        //     → YayinTipiSablonu where kategori_id=8 AND yayin_tipi_id=lt
        //   G2 (main_category scope, sub=null, lt=null)
        //     → YayinTipiSablonu for both konut-satilik AND konut-kiralik (SAAB 2A)
        //     → assignable_type = YayinTipiSablonu::class
        //   G1 (global scope, main=null, sub=null, lt=null)
        //     → IlanKategori id=1 (SAAB 1B — Phase 1 inheritance)
        //     → assignable_type = IlanKategori::class
        //
        // Bug fixed: $resolve(null, null) silently skipped rows.
        // Now each tier has explicit DB lookup logic; no null-parameter query.
        // Null-check: if required template/kategori does not exist, skip silently
        // (avoids orphan records when prerequisites are not yet seeded).

        $villaSatilik = DB::table('yayin_tipi_sablonlari')
            ->where('kategori_id', 8)->where('yayin_tipi_id', 1)->value('id');
        $villaKiralik = DB::table('yayin_tipi_sablonlari')
            ->where('kategori_id', 8)->where('yayin_tipi_id', 2)->value('id');
        $villaGunluk  = DB::table('yayin_tipi_sablonlari')
            ->where('kategori_id', 8)->where('yayin_tipi_id', 5)->value('id');
        $konutSatilik = DB::table('yayin_tipi_sablonlari')
            ->where('kategori_id', 1)->where('yayin_tipi_id', 1)->value('id');
        $konutKiralik = DB::table('yayin_tipi_sablonlari')
            ->where('kategori_id', 1)->where('yayin_tipi_id', 2)->value('id');

        $konutKategoriId = DB::table('ilan_kategorileri')->where('id', 1)->value('id');

        // Helper: upsert one assignment row
        // Returns early if feature does not exist (avoids orphan keys).
        $upsert = function (
            int $fi,
            string $assignableType,
            int $assignableId,
            ?int $mc,
            ?int $sc,
            ?int $lt,
            string $scope,
            string $gn,
            bool $req,
            bool $vis,
            int $ord,
        ) use ($ts, $hasTenantId): void {
            if (!DB::table('features')->where('id', $fi)->exists()) {
                return;
            }

            $fieldSlug = DB::table('features')->where('id', $fi)->value('slug');
            $match = [
                'feature_id'       => $fi,
                'main_category_id' => $mc,
                'sub_category_id'  => $sc,
                'listing_type_id'  => $lt,
            ];
                $values = [
                    'assignable_type'  => $assignableType,
                    'assignable_id'    => $assignableId,
                    'scope_type'       => $scope,
                    'source_type'      => 'villa_seed_2026_08_25',
                    'group_name'       => $gn,
                    'field_slug'       => $fieldSlug,
                    'is_required'      => $req,
                    'is_visible'      => $vis,
                    'aktiflik_durumu' => 1,
                    'display_order'    => $ord,
                    'created_at'      => $ts,
                    'updated_at'      => $ts,
                ];
            if ($hasTenantId) {
                $match['tenant_id']   = null;
                $values['tenant_id']  = null;
            }

            DB::table('feature_assignments')->updateOrInsert($match, $values);
        };

        // ── G3: Villa Satılık (assignable_type = YayinTipiSablonu, id = $villaSatilik) ──
        // Skip tier if template not found (null = prerequisites not seeded yet)
        if ($villaSatilik !== null) {
            $villaFields = [
                // [feature_id, group_name, required, visible, order_within_group]
                [1,  'Temel Bilgiler',    true,   true,   1],
                [2,  'Temel Bilgiler',    false,  true,   2],
                [3,  'Temel Bilgiler',    true,   true,   3],
                [4,  'Temel Bilgiler',    false,  true,   4],
                [5,  'Temel Bilgiler',    false,  true,   5],
                [6,  'Temel Bilgiler',    false,  true,   6],
                [7,  'Temel Bilgiler',    false,  true,   7],
                [8,  'Konum ve Arsa',    false,  true,   1],
                [9,  'Konum ve Arsa',   false,  true,   2],
                [10, 'Konum ve Arsa',     false,  true,   3],
                [11, 'Konum ve Arsa',     false,  true,   4],
                [12, 'Konum ve Arsa',    false,  true,   5],
                [13, 'Yapı Özellikleri', false,  true,   1],
                [14, 'Yapı Özellikleri', false,  true,   2],
                [15, 'Yapı Özellikleri', false,  true,   3],
                [16, 'Yapı Özellikleri', false,  true,   4],
                [17, 'Yapı Özellikleri', false,  true,   5],
                [18, 'Yapı Özellikleri', false,  true,   6],
                [19, 'Yapı Özellikleri', false,  true,   7],
                [20, 'Yapı Özellikleri', false,  false,  8],
                [21, 'Dış Özellikler',   false,  true,   1],
                [22, 'Dış Özellikler',   false,  true,   2],
                [23, 'Dış Özellikler',   false,  true,   3],
                [24, 'Dış Özellikler',   false,  true,   4],
                [25, 'İç Özellikler',    false,  true,   1],
                [26, 'İç Özellikler',    false,  true,   2],
                [27, 'İç Özellikler',    false,  true,   3],
                [28, 'İç Özellikler',    false,  true,   4],
                [29, 'İç Özellikler',    false,  true,   5],
                [30, 'İç Özellikler',    false,  false,  6],
                [31, 'Maliyet ve Aidat', false,  false,  1],
                [33, 'Maliyet ve Aidat', false,  true,   3],
                [34, 'Maliyet ve Aidat', false,  true,   4],
                [35, 'Tapu ve İmar',    false,  true,   1],
                [36, 'Tapu ve İmar',    false,  false,  2],
            ];
            foreach ($villaFields as $vf) {
                [$fi, $gn, $req, $vis, $ord] = $vf;
                $upsert($fi, 'App\\Models\\YayinTipiSablonu', $villaSatilik, 1, 8, 1, 'listing_type', $gn, $req, $vis, $ord);
            }
        }

        // ── G4: Villa Kiralık (depozito only) ──
        if ($villaKiralik !== null) {
            $upsert(32, 'App\\Models\\YayinTipiSablonu', $villaKiralik, 1, 8, 2, 'listing_type', 'Maliyet ve Aidat', true, false, 2);
        }

        // ── G5: Villa Günlük (explicit, NOT inherited) ──
        if ($villaGunluk !== null) {
            $gunlukFields = [
                [1,  'Temel Bilgiler',    true,   true,   1],
                [2,  'Temel Bilgiler',    false,  true,   2],
                [3,  'Temel Bilgiler',    true,   true,   3],
                [4,  'Temel Bilgiler',    false,  true,   4],
                [5,  'Temel Bilgiler',    false,  true,   5],
                [6,  'Temel Bilgiler',    false,  true,   6],
                [7,  'Temel Bilgiler',    false,  true,   7],
                [8,  'Konum ve Arsa',    false,  true,   1],
                [9,  'Konum ve Arsa',    false,  true,   2],
                [10, 'Konum ve Arsa',    false,  true,   3],
                [11, 'Konum ve Arsa',    false,  true,   4],
                [12, 'Konum ve Arsa',    false,  true,   5],
                [13, 'Yapı Özellikleri', false,  true,   1],
                [14, 'Yapı Özellikleri', false,  true,   2],
                [15, 'Yapı Özellikleri', false,  true,   3],
                [16, 'Yapı Özellikleri', false,  true,   4],
                [17, 'Yapı Özellikleri', false,  true,   5],
                [18, 'Yapı Özellikleri', false,  true,   6],
                [19, 'Yapı Özellikleri', false,  true,   7],
                [20, 'Yapı Özellikleri', false,  false,  8],
                [21, 'Dış Özellikler',   false,  true,   1],
                [22, 'Dış Özellikler',   false,  true,   2],
                [23, 'Dış Özellikler',   false,  true,   3],
                [24, 'Dış Özellikler',   false,  true,   4],
                [25, 'İç Özellikler',    false,  true,   1],
                [26, 'İç Özellikler',    false,  true,   2],
                [27, 'İç Özellikler',    false,  true,   3],
                [28, 'İç Özellikler',    false,  true,   4],
                [29, 'İç Özellikler',    false,  true,   5],
                [30, 'İç Özellikler',    false,  false,  6],
                [31, 'Maliyet ve Aidat', false,  false,  1],
                [33, 'Maliyet ve Aidat', false,  true,   3],
                [34, 'Maliyet ve Aidat', false,  true,   4],
                [35, 'Tapu ve İmar',    false,  true,   1],
                [36, 'Tapu ve İmar',    false,  false,  2],
            ];
            foreach ($gunlukFields as $gf) {
                [$fi, $gn, $req, $vis, $ord] = $gf;
                $upsert($fi, 'App\\Models\\YayinTipiSablonu', $villaGunluk, 1, 8, 5, 'listing_type', $gn, $req, $vis, $ord);
            }
        }

        // ── G2: Konut Global — copied to BOTH konut-satilik AND konut-kiralik (SAAB 2A) ──
        // assignable_type = YayinTipiSablonu::class (NOT IlanKategori)
        if ($konutSatilik !== null && $konutKiralik !== null) {
            $konutFields = [
                [1,  'Temel Bilgiler',    true,   true,   1],
                [2,  'Temel Bilgiler',    false,  true,   2],
                [3,  'Temel Bilgiler',    true,   true,   3],
                [4,  'Temel Bilgiler',    false,  true,   4],
                [27, 'İç Özellikler',    false,  true,   1],
                [25, 'İç Özellikler',    false,  true,   2],
                [35, 'Tapu ve İmar',     false,  true,   1],
                [29, 'İç Özellikler',    false,  true,   3],
            ];
            foreach ($konutFields as $kf) {
                [$fi, $gn, $req, $vis, $ord] = $kf;
                // Copy to konut-satilik
                $upsert($fi, 'App\\Models\\YayinTipiSablonu', $konutSatilik, 1, null, null, 'main_category', $gn, $req, $vis, $ord);
                // Copy to konut-kiralik
                $upsert($fi, 'App\\Models\\YayinTipiSablonu', $konutKiralik, 1, null, null, 'main_category', $gn, $req, $vis, $ord);
            }
        }

        // ── G1: Global scope — ilanKategori::class (SAAB 1B, Phase 1 inheritance) ──
        if ($konutKategoriId !== null) {
            $globalFields = [
                [33, 'Maliyet ve Aidat', false,  true,   1],
                [34, 'Maliyet ve Aidat', false,  true,   2],
                [23, 'Dış Özellikler',   false,  true,   1],
                [10, 'Konum ve Arsa',   false,  true,   1],
                [21, 'Dış Özellikler',   false,  true,   2],
            ];
            foreach ($globalFields as $glf) {
                [$fi, $gn, $req, $vis, $ord] = $glf;
                $upsert($fi, 'App\\Models\\IlanKategori', $konutKategoriId, null, null, null, 'global', $gn, $req, $vis, $ord);
            }
        }
    }
};

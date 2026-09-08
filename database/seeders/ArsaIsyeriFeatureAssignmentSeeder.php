<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ArsaIsyeriFeatureAssignmentSeeder — BACKLOG-01 Gap Fix
 *
 * Bridges the gap identified in audits/backlog-01-category-matrix-gap-analysis.md:
 * The Wizard Step 2 engine (Wizard\FeatureTemplateResolver) queries feature_assignments
 * table, which was only seeded for Villa/Konut. This seeder adds Arsa and İşyeri
 * features + assignments so those categories show dynamic fields in Wizard Step 2.
 *
 * Source data: CategoryFieldSchemaSeeder (Sistem C — category_field_schema, dead table)
 * Target: features + feature_assignments (Sistem A — aktif Wizard SSOT)
 *
 * Coverage:
 *   - Arsa Satılık (main=arsa-arazi): 14 fields (ada_no, parsel_no, pafta_no,
 *     imar_durumu, kaks, taks, gabari, yola_cephe, altyapi_su/elektrik/dogalgaz/
 *     kanalizasyon/yol, tapu_durumu)
 *   - İşyeri Satılık (main=isyeri): 6 fields (isyeri_tipi, net_m2, bulundugu_kat,
 *     cephe, personel_kapasitesi, aidat)
 *   - İşyeri Kiralık (main=isyeri): 4 fields (isyeri_tipi, net_m2, depozito, aidat)
 *
 * Idempotent: Uses updateOrInsert — safe to run multiple times.
 * Rollback: Deletes only source_type='backlog_01_seed' records.
 *
 * Verifies: php artisan db:seed --class=ArsaIsyeriFeatureAssignmentSeeder
 */
class ArsaIsyeriFeatureAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFeatureCategories();
        $this->seedFeatures();
        $this->seedArsaSatilikAssignments();
        $this->seedIsyeriSatilikAssignments();
        $this->seedIsyeriKiralikAssignments();

        $arsaCount = DB::table('feature_assignments')
            ->where('source_type', 'backlog_01_seed')
            ->where('main_category_id', $this->getKategoriId('arsa-arazi'))
            ->count();
        $isyeriCount = DB::table('feature_assignments')
            ->where('source_type', 'backlog_01_seed')
            ->where('main_category_id', $this->getKategoriId('isyeri'))
            ->count();

        if ($this->command) {
            $this->command->info("✅ ArsaIsyeriFeatureAssignmentSeeder:");
            $this->command->info("   Arsa assignments: {$arsaCount}");
            $this->command->info("   İşyeri assignments: {$isyeriCount}");
        }
    }

    // ─── FEATURE CATEGORIES ─────────────────────────────────────────────────

    private function seedFeatureCategories(): void
    {
        $categories = [
            ['name' => 'Arsa Temel', 'slug' => 'arsa-temel', 'description' => 'Ada, parsel, imar durumu', 'applies_to' => 'arsa', 'icon' => 'map-pin', 'display_order' => 1],
            ['name' => 'Arsa Fiziksel', 'slug' => 'arsa-fiziksel', 'description' => 'KAKS, TAKS, gabari, yola cephe', 'applies_to' => 'arsa', 'icon' => 'ruler', 'display_order' => 2],
            ['name' => 'Altyapı', 'slug' => 'altyapi', 'description' => 'Su, elektrik, doğalgaz, kanalizasyon, yol', 'applies_to' => 'arsa,isyeri', 'icon' => 'zap', 'display_order' => 3],
            ['name' => 'Arsa Finansal', 'slug' => 'arsa-finansal', 'description' => 'Tapu durumu', 'applies_to' => 'arsa', 'icon' => 'file-text', 'display_order' => 4],
            ['name' => 'İşyeri Temel', 'slug' => 'isyeri-temel', 'description' => 'İşyeri tipi', 'applies_to' => 'isyeri', 'icon' => 'building', 'display_order' => 5],
            ['name' => 'İşyeri Fiziksel', 'slug' => 'isyeri-fiziksel', 'description' => 'Net m², kat, cephe', 'applies_to' => 'isyeri', 'icon' => 'briefcase', 'display_order' => 6],
            ['name' => 'İşyeri Detay', 'slug' => 'isyeri-detay', 'description' => 'Personel kapasitesi', 'applies_to' => 'isyeri', 'icon' => 'users', 'display_order' => 7],
            ['name' => 'Finansal', 'slug' => 'finansal', 'description' => 'Aidat, depozito', 'applies_to' => 'isyeri', 'icon' => 'credit-card', 'display_order' => 8],
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
            // Arsa Temel
            ['name' => 'Ada No', 'slug' => 'ada_no', 'type' => 'text', 'unit' => null, 'feature_category_id' => $cat('arsa-temel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 1],
            ['name' => 'Parsel No', 'slug' => 'parsel_no', 'type' => 'text', 'unit' => null, 'feature_category_id' => $cat('arsa-temel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 2],
            ['name' => 'Pafta No', 'slug' => 'pafta_no', 'type' => 'text', 'unit' => null, 'feature_category_id' => $cat('arsa-temel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 3],
            ['name' => 'İmar Durumu', 'slug' => 'imar_durumu', 'type' => 'select', 'unit' => null, 'feature_category_id' => $cat('arsa-temel'), 'is_required' => true, 'is_filterable' => true, 'is_searchable' => true, 'display_order' => 4, 'options' => json_encode(['Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Tarla', 'Zeytinlik', 'Bağ & Bahçe', 'Turizm İmarlı', 'İmarsız'])],

            // Arsa Fiziksel
            ['name' => 'KAKS (Emsal)', 'slug' => 'kaks', 'type' => 'number', 'unit' => null, 'feature_category_id' => $cat('arsa-fiziksel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 1],
            ['name' => 'TAKS', 'slug' => 'taks', 'type' => 'number', 'unit' => null, 'feature_category_id' => $cat('arsa-fiziksel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 2],
            ['name' => 'Gabari (Kat)', 'slug' => 'gabari', 'type' => 'number', 'unit' => null, 'feature_category_id' => $cat('arsa-fiziksel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 3],
            ['name' => 'Yola Cephe', 'slug' => 'yola_cephe', 'type' => 'number', 'unit' => 'm', 'feature_category_id' => $cat('arsa-fiziksel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 4],

            // Altyapı (Arsa)
            ['name' => 'Su', 'slug' => 'altyapi_su', 'type' => 'boolean', 'unit' => null, 'feature_category_id' => $cat('altyapi'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 1],
            ['name' => 'Elektrik', 'slug' => 'altyapi_elektrik', 'type' => 'boolean', 'unit' => null, 'feature_category_id' => $cat('altyapi'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 2],
            ['name' => 'Doğalgaz', 'slug' => 'altyapi_dogalgaz', 'type' => 'boolean', 'unit' => null, 'feature_category_id' => $cat('altyapi'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 3],
            ['name' => 'Kanalizasyon', 'slug' => 'altyapi_kanalizasyon', 'type' => 'boolean', 'unit' => null, 'feature_category_id' => $cat('altyapi'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 4],
            ['name' => 'Yol', 'slug' => 'altyapi_yol', 'type' => 'boolean', 'unit' => null, 'feature_category_id' => $cat('altyapi'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 5],

            // Arsa Finansal
            ['name' => 'Tapu Durumu', 'slug' => 'tapu_durumu_arsa', 'type' => 'select', 'unit' => null, 'feature_category_id' => $cat('arsa-finansal'), 'is_required' => false, 'is_filterable' => true, 'is_searchable' => false, 'display_order' => 1, 'options' => json_encode(['Müstakil Tapu', 'Hisseli Tapu', 'Zilliyet', 'Tahsisli'])],

            // İşyeri Temel
            ['name' => 'İşyeri Tipi', 'slug' => 'isyeri_tipi', 'type' => 'select', 'unit' => null, 'feature_category_id' => $cat('isyeri-temel'), 'is_required' => true, 'is_filterable' => true, 'is_searchable' => true, 'display_order' => 1, 'options' => json_encode(['Dükkan', 'Mağaza', 'Ofis', 'Büro', 'Depo', 'Fabrika', 'Atölye', 'Showroom', 'Plaza Katı'])],

            // İşyeri Fiziksel
            ['name' => 'Net m²', 'slug' => 'net_m2', 'type' => 'number', 'unit' => 'm²', 'feature_category_id' => $cat('isyeri-fiziksel'), 'is_required' => true, 'is_filterable' => true, 'is_searchable' => true, 'display_order' => 1],
            ['name' => 'Bulunduğu Kat', 'slug' => 'bulundugu_kat', 'type' => 'select', 'unit' => null, 'feature_category_id' => $cat('isyeri-fiziksel'), 'is_required' => false, 'is_filterable' => true, 'is_searchable' => false, 'display_order' => 2, 'options' => json_encode(['Bodrum', 'Zemin', '1', '2', '3', '4', '5+', 'Çatı Katı'])],
            ['name' => 'Cephe', 'slug' => 'cephe', 'type' => 'select', 'unit' => null, 'feature_category_id' => $cat('isyeri-fiziksel'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 3, 'options' => json_encode(['Cadde Cepheli', 'Sokak Cepheli', 'AVM İçi', 'İç Cephe'])],

            // İşyeri Detay
            ['name' => 'Personel Kapasitesi', 'slug' => 'personel_kapasitesi', 'type' => 'number', 'unit' => null, 'feature_category_id' => $cat('isyeri-detay'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 1],

            // Finansal (İşyeri)
            ['name' => 'Aidat', 'slug' => 'aidat_isyeri', 'type' => 'number', 'unit' => 'TL/ay', 'feature_category_id' => $cat('finansal'), 'is_required' => false, 'is_filterable' => true, 'is_searchable' => false, 'display_order' => 1],
            ['name' => 'Depozito', 'slug' => 'depozito_isyeri', 'type' => 'number', 'unit' => 'TL', 'feature_category_id' => $cat('finansal'), 'is_required' => false, 'is_filterable' => false, 'is_searchable' => false, 'display_order' => 2],
        ];

        foreach ($features as $f) {
            DB::table('features')->updateOrInsert(
                ['slug' => $f['slug']],
                array_merge($f, ['aktiflik_durumu' => 1, 'lifecycle' => 'stable', 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    // ─── ASSIGNMENTS ─────────────────────────────────────────────────────────

    /**
     * Get kategori ID by slug.
     */
    private function getKategoriId(string $slug): ?int
    {
        return DB::table('ilan_kategorileri')->where('slug', $slug)->value('id');
    }

    /**
     * Get yayin_tipi ID by slug from yayin_tipleri table.
     */
    private function getYayinTipiId(string $slug): ?int
    {
        return DB::table('yayin_tipleri')->where('slug', $slug)->value('id');
    }

    /**
     * Get feature ID by slug.
     */
    private function getFeatureId(string $slug): ?int
    {
        return DB::table('features')->where('slug', $slug)->value('id');
    }

    /**
     * Create a feature_assignment record (idempotent via updateOrInsert).
     * Uses main_category_id + listing_type_id scope for Wizard-scoped resolver.
     */
    private function assignFeature(
        int $featureId,
        int $mainCategoryId,
        ?int $listingTypeId,
        string $groupName,
        bool $required,
        bool $visible,
        int $displayOrder
    ): void {
        if (!$featureId) return;

        $slug = DB::table('features')->where('id', $featureId)->value('slug');

        DB::table('feature_assignments')->updateOrInsert(
            [
                'feature_id'      => $featureId,
                'main_category_id' => $mainCategoryId,
                'sub_category_id'  => null,
                'listing_type_id'  => $listingTypeId,
            ],
            [
                'assignable_type'  => 'App\\Models\\IlanKategori',
                'assignable_id'    => $mainCategoryId,
                'scope_type'       => $listingTypeId ? 'listing_type' : 'main_category',
                'source_type'      => 'backlog_01_seed',
                'group_name'       => $groupName,
                'field_slug'       => $slug,
                'is_required'      => $required,
                'is_visible'       => $visible,
                'aktiflik_durumu' => 1,
                'display_order'   => $displayOrder,
                'updated_at'       => now(),
            ]
        );

        // Set created_at on insert only
        if (!DB::table('feature_assignments')
            ->where('feature_id', $featureId)
            ->where('main_category_id', $mainCategoryId)
            ->where('listing_type_id', $listingTypeId)
            ->whereNull('created_at')
            ->exists()
        ) {
            DB::table('feature_assignments')
                ->where('feature_id', $featureId)
                ->where('main_category_id', $mainCategoryId)
                ->where('listing_type_id', $listingTypeId)
                ->update(['created_at' => now()]);
        }
    }

    /**
     * Arsa Satılık — 14 fields
     */
    private function seedArsaSatilikAssignments(): void
    {
        $arsaId = $this->getKategoriId('arsa-arazi');
        $satilikId = $this->getYayinTipiId('satilik');

        if (!$arsaId || !$satilikId) {
            if ($this->command) $this->command->warn('Arsa or Satılık kategori/yayin_tipi not found, skipping Arsa assignments.');
            return;
        }

        $fields = [
            ['ada_no', 'Arsa Temel', false, true, 1],
            ['parsel_no', 'Arsa Temel', false, true, 2],
            ['pafta_no', 'Arsa Temel', false, true, 3],
            ['imar_durumu', 'Arsa Temel', true, true, 4],
            ['kaks', 'Arsa Fiziksel', false, true, 1],
            ['taks', 'Arsa Fiziksel', false, true, 2],
            ['gabari', 'Arsa Fiziksel', false, true, 3],
            ['yola_cephe', 'Arsa Fiziksel', false, true, 4],
            ['altyapi_su', 'Altyapı', false, true, 1],
            ['altyapi_elektrik', 'Altyapı', false, true, 2],
            ['altyapi_dogalgaz', 'Altyapı', false, true, 3],
            ['altyapi_kanalizasyon', 'Altyapı', false, true, 4],
            ['altyapi_yol', 'Altyapı', false, true, 5],
            ['tapu_durumu_arsa', 'Arsa Finansal', false, true, 1],
        ];

        foreach ($fields as [$slug, $group, $required, $visible, $order]) {
            $fid = $this->getFeatureId($slug);
            $this->assignFeature($fid, $arsaId, $satilikId, $group, $required, $visible, $order);
        }
    }

    /**
     * İşyeri Satılık — 6 fields
     */
    private function seedIsyeriSatilikAssignments(): void
    {
        $isyeriId = $this->getKategoriId('isyeri');
        $satilikId = $this->getYayinTipiId('satilik');

        if (!$isyeriId || !$satilikId) {
            if ($this->command) $this->command->warn('İşyeri or Satılık kategori/yayin_tipi not found, skipping İşyeri Satılık assignments.');
            return;
        }

        $fields = [
            ['isyeri_tipi', 'İşyeri Temel', true, true, 1],
            ['net_m2', 'İşyeri Fiziksel', true, true, 1],
            ['bulundugu_kat', 'İşyeri Fiziksel', false, true, 2],
            ['cephe', 'İşyeri Fiziksel', false, true, 3],
            ['personel_kapasitesi', 'İşyeri Detay', false, true, 1],
            ['aidat_isyeri', 'Finansal', false, true, 1],
        ];

        foreach ($fields as [$slug, $group, $required, $visible, $order]) {
            $fid = $this->getFeatureId($slug);
            $this->assignFeature($fid, $isyeriId, $satilikId, $group, $required, $visible, $order);
        }
    }

    /**
     * İşyeri Kiralık — 4 fields
     */
    private function seedIsyeriKiralikAssignments(): void
    {
        $isyeriId = $this->getKategoriId('isyeri');
        $kiralikId = $this->getYayinTipiId('kiralik');

        if (!$isyeriId || !$kiralikId) {
            if ($this->command) $this->command->warn('İşyeri or Kiralık kategori/yayin_tipi not found, skipping İşyeri Kiralık assignments.');
            return;
        }

        $fields = [
            ['isyeri_tipi', 'İşyeri Temel', true, true, 1],
            ['net_m2', 'İşyeri Fiziksel', true, true, 1],
            ['depozito_isyeri', 'Finansal', false, true, 1],
            ['aidat_isyeri', 'Finansal', false, true, 2],
        ];

        foreach ($fields as [$slug, $group, $required, $visible, $order]) {
            $fid = $this->getFeatureId($slug);
            $this->assignFeature($fid, $isyeriId, $kiralikId, $group, $required, $visible, $order);
        }
    }
}

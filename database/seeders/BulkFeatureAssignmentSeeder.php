<?php

namespace Database\Seeders;

use App\Models\YayinTipiSablonu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * BulkFeatureAssignmentSeeder — Tüm 0/35 şablonlara Villa Satılık özelliklerini kopyalar.
 *
 * Kaynak: YayinTipiSablonu id=22 (Villa Satılık) — 35 feature_assignment
 * Hedef: feature_assignments_count = 0 olan tüm YayinTipiSablonu kayıtları
 *
 * Scope dönüşümü:
 *   Villa (listing_type scope, sub=8, lt=1) → Tüm şablonlar (main_category scope)
 *   main_category_id = template.kategori_id
 *   sub_category_id  = null
 *   listing_type_id  = null
 *   scope_type       = 'main_category'
 *   is_inherited     = true
 *   source_type      = 'bulk_seed_2026_09_14'
 *
 * Idempotent: sadece eksik kayıtları ekler (UNIQUE constraint koruması).
 *
 * Kullanım:
 *   php artisan db:seed --class=BulkFeatureAssignmentSeeder --force
 */
class BulkFeatureAssignmentSeeder extends Seeder
{
    private const SOURCE_TEMPLATE_ID = 22; // Villa Satılık
    private const SOURCE_TYPE = 'bulk_seed_2026_09_14';

    public function run(): void
    {
        // 1. Kaynak şablonun atamalarını al
        $sourceAssignments = DB::table('feature_assignments')
            ->where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', self::SOURCE_TEMPLATE_ID)
            ->where('aktiflik_durumu', 1)
            ->orderBy('display_order')
            ->get();

        if ($sourceAssignments->isEmpty()) {
            $this->command?->error('Kaynak şablon (Villa Satılık id=22) atanmış özellik bulunamadı.');
            return;
        }

        $this->command?->info("Kaynak: Villa Satılık (id=22) — {$sourceAssignments->count()} özellik");

        // 2. 0 atanmış şablonları bul
        $emptyTemplates = YayinTipiSablonu::query()
            ->where('aktiflik_durumu', 1)
            ->whereDoesntHave('featureAssignments')
            ->where('id', '!=', self::SOURCE_TEMPLATE_ID)
            ->get(['id', 'ad', 'kategori_id']);

        if ($emptyTemplates->isEmpty()) {
            $this->command?->info('Kopyalanacak boş şablon bulunamadı (zaten tüm şablonlar dolu).');
            return;
        }

        $this->command?->info("Hedef: {$emptyTemplates->count()} boş şablon");
        $this->command?->info('  → ' . $emptyTemplates->pluck('ad')->join(', '));

        // 3. Her boş şablona kaynak atamalarını kopyala
        $totalInserted = 0;
        $now = now();

        foreach ($emptyTemplates as $template) {
            $insertRows = [];

            foreach ($sourceAssignments as $src) {
                $insertRows[] = [
                    'feature_id'           => $src->feature_id,
                    'assignable_type'      => YayinTipiSablonu::class,
                    'assignable_id'        => $template->id,
                    'main_category_id'     => $template->kategori_id,
                    'sub_category_id'      => null,
                    'listing_type_id'      => null,
                    'scope_type'           => 'main_category',
                    'value'                => null,
                    'label_override'       => null,
                    'field_slug'           => $src->field_slug,
                    'field_type'           => $src->field_type,
                    'is_required'          => $src->is_required,
                    'is_visible'           => $src->is_visible,
                    'is_inherited'         => true,
                    'origin_category_name' => $src->origin_category_name,
                    'source_type'          => self::SOURCE_TYPE,
                    'tenant_id'            => $template->tenant_id,
                    'metadata'             => null,
                    'display_order'        => $src->display_order,
                    'conditional_logic'    => null,
                    'visible_if_json'      => null,
                    'required_if_json'      => null,
                    'enabled_if_json'      => null,
                    'options_json'         => null,
                    'rolled_back_at'       => null,
                    'created_by'           => null,
                    'updated_by'           => null,
                    'group_name'           => $src->group_name,
                    'aktiflik_durumu'      => 1,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }

            // Chunked insert — duplicate key hatasını UNIQUE constraint korur
            $inserted = 0;
            foreach (array_chunk($insertRows, 50) as $chunk) {
                try {
                    DB::table('feature_assignments')->insertOrIgnore($chunk);
                    $inserted += count($chunk);
                } catch (\Exception $e) {
                    $this->command?->warn("  ⚠️  {$template->ad}: {$e->getMessage()}");
                }
            }

            $totalInserted += $inserted;
            $this->command?->info("  ✅ {$template->ad} (id={$template->id}) — {$inserted} özellik kopyalandı");
        }

        // 4. Özet
        $fa = DB::table('feature_assignments')->count();
        $this->command?->info("─────────────────────────────────");
        $this->command?->info("✅ BulkFeatureAssignmentSeeder tamamlandı");
        $this->command?->info("   Toplam yeni atama: {$totalInserted}");
        $this->command?->info("   Toplam feature_assignments: {$fa}");
    }
}

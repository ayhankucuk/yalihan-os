<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Yalıhan Bekçi: Wizard Features Empty Detector
 *
 * Automatically detects and diagnoses why frontend-features returns empty
 *
 * Usage: php artisan bekci:features-empty {kategori_slug} {yayin_tipi_id}
 */
class BekciFeatureEmptyDetectorCommand extends Command
{
    protected $signature = 'bekci:features-empty {kategori_slug} {yayin_tipi_id}';
    protected $description = '🔍 Yalıhan Bekçi: Diagnose why features are empty for a category';

    public function handle(): int
    {
        $kategoriSlug = $this->argument('kategori_slug');
        $yayinTipiId = $this->argument('yayin_tipi_id');

        $this->info("🔍 Bekçi: Diagnosing features for {$kategoriSlug} + yayin_tipi_id={$yayinTipiId}");
        $this->newLine();

        $issues = [];

        // Step 1: Check category exists
        $this->info('[1/5] Checking category existence...');
        $category = DB::table('ilan_kategorileri')
            ->where('slug', $kategoriSlug)
            ->first();

        if (!$category) {
            $this->error("❌ Category '{$kategoriSlug}' not found!");
            $issues[] = "Category does not exist";
            return self::FAILURE;
        }

        $this->info("✅ Category found: {$category->name} (ID: {$category->id})");

        // Step 2: Check YayinTipiSablonu schema
        $this->info('[2/5] Checking YayinTipiSablonu schema...');
        if (Schema::hasTable('yayin_tipi_sablonlari')) {
            $this->info("✅ Table 'yayin_tipi_sablonlari' exists");
        } else {
            $this->error("❌ Table 'yayin_tipi_sablonlari' not found!");
            $issues[] = "Critical table 'yayin_tipi_sablonlari' missing";
        }

        // Step 3: Check yayin_tipleri existence
        $this->info('[3/5] Checking yayin_tipleri data...');
        $yayinTipleri = DB::table('yayin_tipleri')
            ->where('aktiflik_durumu', true)
            ->get();

        if ($yayinTipleri->isEmpty()) {
            $this->error("❌ No active yayin_tipleri found in database!");
            $issues[] = "yayin_tipleri table is empty - seed data needed";
        } else {
            $this->info("✅ Found {$yayinTipleri->count()} active yayin_tipleri");
            $this->table(['ID', 'Name'], $yayinTipleri->map(fn($yt) => [$yt->id, $yt->name])->toArray());
        }

        // Step 4: Check feature_assignments
        $this->info('[4/5] Checking feature_assignments...');
        $assignments = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\IlanKategori')
            ->where('assignable_id', $category->id)
            ->where('is_visible', true)
            ->count();

        if ($assignments === 0) {
            $this->error("❌ No feature assignments found for category {$category->name}!");
            $issues[] = "No features assigned to this category - template/assignment needed";
        } else {
            $this->info("✅ Found {$assignments} feature assignment(s)");
        }

        // Step 5: Test API endpoint
        $this->info('[5/5] Testing API endpoint...');
        try {
            $response = Http::get("http://127.0.0.1:8002/api/v1/admin/category/{$kategoriSlug}/frontend-features", [
                'yayin_tipi_id' => $yayinTipiId
            ]);

            $data = $response->json();

            if (isset($data['data']['metadata']['total_features'])) {
                $totalFeatures = $data['data']['metadata']['total_features'];

                if ($totalFeatures === 0) {
                    $this->error("❌ API returned 0 features!");
                    $issues[] = "API returns empty feature list";
                } else {
                    $this->info("✅ API returned {$totalFeatures} feature(s)");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ API test failed: " . $e->getMessage());
            $issues[] = "API endpoint error: " . $e->getMessage();
        }

        // Summary
        $this->newLine();
        $this->info('============================================');
        $this->info('DIAGNOSIS SUMMARY');
        $this->info('============================================');

        if (empty($issues)) {
            $this->info('✅ No issues detected - features should load correctly');
            return self::SUCCESS;
        } else {
            $this->error('❌ ' . count($issues) . ' issue(s) detected:');
            foreach ($issues as $i => $issue) {
                $this->warn('  ' . ($i + 1) . '. ' . $issue);
            }

            $this->newLine();
            $this->info('🔧 RECOMMENDED FIXES:');

            if (in_array('yayin_tipleri table is empty - seed data needed', $issues)) {
                $this->line('  → Run: php artisan db:seed --class=YayinTipleriSeeder');
            }

            if (in_array('No features assigned to this category - template/assignment needed', $issues)) {
                $this->line('  → Create feature assignments via UPS Template Manager');
            }

            if (str_contains(implode(' ', $issues), 'schema mismatch')) {
                $this->line('  → Fix IlanFeatureService.php to use correct column name');
            }

            return self::FAILURE;
        }
    }
}

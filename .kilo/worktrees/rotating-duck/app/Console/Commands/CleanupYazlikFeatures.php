<?php

namespace App\Console\Commands;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupYazlikFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:yazlik-features {--dry-run : Sadece göster, silme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Yazlık Features sisteminden duplicate özellikleri ve boş kategorileri temizle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🧹 Yazlık Features Temizliği Başlatılıyor...');
        $this->newLine();

        // 1. Duplicate özellikleri kaldır
        $this->cleanupDuplicateFeatures($dryRun);

        // 2. Boş kategorileri kaldır
        $this->cleanupEmptyCategories($dryRun);

        $this->newLine();
        $this->info('✅ Temizlik tamamlandı!');
    }

    /**
     * Duplicate özellikleri kaldır
     */
    private function cleanupDuplicateFeatures(bool $dryRun): void
    {
        $this->info('📋 Duplicate Özellikler Kontrol Ediliyor...');

        // Field Dependencies'de zaten var olan özellikler
        $duplicateSlugs = [
            'gunluk_fiyat',
            'haftalik_fiyat',
            'aylik_fiyat',
            'sezonluk_fiyat',
            'minimum-konaklama',
            'minimum_konaklama',
            'min_konaklama',
            'maksimum-misafir',
            'max_misafir',
            'denize_uzaklik',
            'temizlik_ucreti',
            'depozito',
            'satis_fiyati', // Yazlık için uygun değil
            'm2_fiyati', // Yazlık için uygun değil
        ];

        $featuresToDelete = [];
        foreach ($duplicateSlugs as $slug) {
            $found = Feature::where('slug', 'LIKE', '%' . $slug . '%')->get(['id', 'name', 'slug']);
            foreach ($found as $f) {
                $featuresToDelete[$f->id] = [
                    'name' => $f->name,
                    'slug' => $f->slug,
                ];
            }
        }

        if (empty($featuresToDelete)) {
            $this->warn('  ⚠️  Kaldırılacak duplicate özellik bulunamadı.');
            return;
        }

        $this->info('  📊 Bulunan duplicate özellikler: ' . count($featuresToDelete));
        foreach ($featuresToDelete as $id => $feature) {
            $this->line("    - {$feature['name']} ({$feature['slug']}) [ID: {$id}]");
        }

        if ($dryRun) {
            $this->warn('  ⚠️  DRY-RUN: Özellikler kaldırılmayacak.');
            return;
        }

        DB::beginTransaction();
        try {
            // Önce FeatureAssignment'ları kaldır
            $assignmentCount = FeatureAssignment::whereIn('feature_id', array_keys($featuresToDelete))->delete();
            $this->info("  ✅ {$assignmentCount} FeatureAssignment kaldırıldı.");

            // Sonra Feature'ları kaldır
            $featureCount = Feature::whereIn('id', array_keys($featuresToDelete))->delete();
            $this->info("  ✅ {$featureCount} Feature kaldırıldı.");

            DB::commit();
            $this->info('  ✅ Duplicate özellikler başarıyla kaldırıldı.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Boş kategorileri kaldır
     */
    private function cleanupEmptyCategories(bool $dryRun): void
    {
        $this->info('📁 Boş Kategoriler Kontrol Ediliyor...');

        $emptyCategoryNames = [
            'Alt Yapı',
            'Konum',
            'Manzara',
            'Kurallar',
        ];

        $emptyCategories = FeatureCategory::whereIn('name', $emptyCategoryNames)
            ->whereDoesntHave('features')
            ->get(['id', 'name', 'slug']);

        if ($emptyCategories->isEmpty()) {
            $this->warn('  ⚠️  Kaldırılacak boş kategori bulunamadı.');
            return;
        }

        $this->info('  📊 Bulunan boş kategoriler: ' . $emptyCategories->count());
        foreach ($emptyCategories as $category) {
            $this->line("    - {$category->name} ({$category->slug}) [ID: {$category->id}]");
        }

        if ($dryRun) {
            $this->warn('  ⚠️  DRY-RUN: Kategoriler kaldırılmayacak.');
            return;
        }

        DB::beginTransaction();
        try {
            $categoryIds = $emptyCategories->pluck('id')->toArray();
            $categoryCount = FeatureCategory::whereIn('id', $categoryIds)->delete();
            $this->info("  ✅ {$categoryCount} boş kategori kaldırıldı.");

            DB::commit();
            $this->info('  ✅ Boş kategoriler başarıyla kaldırıldı.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Feature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixArsaFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'features:fix-arsa {--dry-run : Sadece göster, düzeltme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Arsa Features sistemindeki tutarsızlıkları düzelt';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🏞️  Arsa Features Tutarlılık Düzeltmesi Başlatılıyor...');
        $this->newLine();

        // 1. Parsel No type düzeltmesi (number → text)
        $this->fixParselNoType($dryRun);

        // 2. Duplicate özellikleri kaldır
        $this->removeDuplicateFeatures($dryRun);

        $this->newLine();
        $this->info('✅ Arsa Features tutarlılık düzeltmesi tamamlandı!');
    }

    /**
     * Parsel No type düzeltmesi (number → text)
     */
    private function fixParselNoType(bool $dryRun): void
    {
        $this->info('📋 Parsel No Type Düzeltmesi...');

        $parselNoNumber = Feature::where('slug', 'parsel-no')
            ->where('type', 'number')
            ->first(['id', 'name', 'slug', 'type']);

        if (!$parselNoNumber) {
            $this->warn('  ⚠️  Parsel No (number type) bulunamadı.');
            return;
        }

        $this->info('  📊 Bulunan: ' . $parselNoNumber->name . ' (' . $parselNoNumber->slug . ')');
        $this->line('    Mevcut Type: ' . $parselNoNumber->type);
        $this->line('    Hedef Type: text (numara olduğu için)');

        if ($dryRun) {
            $this->warn('  ⚠️  DRY-RUN: Type güncellenmeyecek.');
            return;
        }

        DB::beginTransaction();
        try {
            $parselNoNumber->update(['type' => 'text']);
            $this->info('  ✅ Parsel No type text\'e çevrildi.');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Duplicate özellikleri kaldır
     */
    private function removeDuplicateFeatures(bool $dryRun): void
    {
        $this->info('🧹 Duplicate Özellikleri Kaldırma...');

        // Duplicate slug'ları tespit et
        $duplicates = [
            'ada-no' => 'ada_no', // ada-no → ada_no tutulacak
            'parsel-no' => 'parsel_no', // parsel-no → parsel_no tutulacak (text olan)
            'imar-statusu' => 'imar_durumu', // imar-statusu → imar_durumu tutulacak
        ];

        $featuresToDelete = [];
        foreach ($duplicates as $oldSlug => $newSlug) {
            $oldFeature = Feature::where('slug', $oldSlug)->first(['id', 'name', 'slug']);
            $newFeature = Feature::where('slug', $newSlug)->first(['id', 'name', 'slug']);

            if ($oldFeature && $newFeature) {
                $featuresToDelete[$oldFeature->id] = [
                    'old' => $oldFeature->name . ' (' . $oldFeature->slug . ')',
                    'new' => $newFeature->name . ' (' . $newFeature->slug . ')',
                ];
                $this->line("    - {$oldFeature->name} ({$oldFeature->slug}) → {$newFeature->name} ({$newFeature->slug}) tutulacak");
            }
        }

        if (empty($featuresToDelete)) {
            $this->warn('  ⚠️  Kaldırılacak duplicate özellik bulunamadı.');
            return;
        }

        if ($dryRun) {
            $this->warn('  ⚠️  DRY-RUN: Duplicate özellikler kaldırılmayacak.');
            return;
        }

        DB::beginTransaction();
        try {
            // Önce FeatureAssignment'ları kaldır
            $assignmentCount = DB::table('feature_assignments')
                ->whereIn('feature_id', array_keys($featuresToDelete))
                ->delete();
            $this->info("  ✅ {$assignmentCount} FeatureAssignment kaldırıldı.");

            // Sonra Feature'ları kaldır
            $featureCount = Feature::whereIn('id', array_keys($featuresToDelete))->delete();
            $this->info("  ✅ {$featureCount} duplicate Feature kaldırıldı.");

            DB::commit();
            $this->info('  ✅ Duplicate özellikler başarıyla kaldırıldı.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }
}

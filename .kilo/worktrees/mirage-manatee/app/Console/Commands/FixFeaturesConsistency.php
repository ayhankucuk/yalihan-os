<?php

namespace App\Console\Commands;

use App\Models\Feature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixFeaturesConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'features:fix-consistency {--dry-run : Sadece göster, düzeltme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Features sistemindeki tutarsızlıkları düzelt (type, unit)';

    /**
     * Unit mapping for number/decimal features
     */
    private array $unitMapping = [
        'parsel-no' => null, // Parsel No - birim yok (numara)
        'salon-sayisi' => 'adet',
        'banyo-sayisi' => 'adet',
        'balkon-sayisi' => 'adet',
        'kat-numarasi' => null, // Kat Numarası - birim yok (numara)
        'bina-kat-sayisi' => 'kat',
        'yatak-sayisi' => 'adet',
        'ebeveyn-yatak-odasi-yazlik-12' => 'adet',
        'dusakabin-yazlik-13' => 'adet',
        'bebek-yatagi-yazlik-18' => 'adet',
        'kat_sayisi' => 'kat',
        'oda_sayisi' => 'adet',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔧 Features Tutarlılık Düzeltmesi Başlatılıyor...');
        $this->newLine();

        // 1. Checkbox → Boolean düzeltmesi
        $this->fixCheckboxToBoolean($dryRun);

        // 2. Unit eksikliklerini tamamla
        $this->fixMissingUnits($dryRun);

        // 3. Mesafe özelliklerini standartlaştır
        $this->fixDistanceUnits($dryRun);

        // 4. Boolean/Select özelliklerde unit temizliği
        $this->removeInvalidUnits($dryRun);

        $this->newLine();
        $this->info('✅ Tutarlılık düzeltmesi tamamlandı!');
    }

    /**
     * Checkbox type'ı boolean'a çevir
     */
    private function fixCheckboxToBoolean(bool $dryRun): void
    {
        $this->info('📋 Checkbox → Boolean Düzeltmesi...');

        $checkboxFeatures = Feature::where('type', 'checkbox')->get(['id', 'name', 'slug', 'type']);

        if ($checkboxFeatures->isEmpty()) {
            $this->warn('  ⚠️  Checkbox type özellik bulunamadı.');
            return;
        }

        $this->info('  📊 Bulunan checkbox özellikler: ' . $checkboxFeatures->count());

        if ($dryRun) {
            foreach ($checkboxFeatures->take(10) as $feature) {
                $this->line("    - {$feature->name} ({$feature->slug}) [ID: {$feature->id}]");
            }
            if ($checkboxFeatures->count() > 10) {
                $this->line("    ... ve " . ($checkboxFeatures->count() - 10) . " özellik daha");
            }
            $this->warn('  ⚠️  DRY-RUN: Checkbox özellikler boolean\'a çevrilmeyecek.');
            return;
        }

        DB::beginTransaction();
        try {
            $count = Feature::where('type', 'checkbox')->update(['type' => 'boolean']);
            $this->info("  ✅ {$count} checkbox özellik boolean'a çevrildi.");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Unit eksikliklerini tamamla
     */
    private function fixMissingUnits(bool $dryRun): void
    {
        $this->info('📏 Unit Eksikliklerini Tamamlama...');

        $featuresWithoutUnit = Feature::whereIn('type', ['number', 'decimal'])
            ->whereNull('unit')
            ->get(['id', 'name', 'slug', 'type']);

        if ($featuresWithoutUnit->isEmpty()) {
            $this->warn('  ⚠️  Unit eksik özellik bulunamadı.');
            return;
        }

        $this->info('  📊 Bulunan unit eksik özellikler: ' . $featuresWithoutUnit->count());

        $updates = [];
        foreach ($featuresWithoutUnit as $feature) {
            $unit = $this->unitMapping[$feature->slug] ?? $this->guessUnit($feature->name, $feature->slug);

            if ($unit !== null) {
                $updates[$feature->id] = [
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'unit' => $unit,
                ];
                $this->line("    - {$feature->name}: unit = '{$unit}'");
            } else {
                $this->warn("    ⚠️  {$feature->name}: unit belirlenemedi (null kalacak)");
            }
        }

        if (empty($updates)) {
            $this->warn('  ⚠️  Güncellenecek özellik bulunamadı.');
            return;
        }

        if ($dryRun) {
            $this->warn('  ⚠️  DRY-RUN: Unit\'ler güncellenmeyecek.');
            return;
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($updates as $id => $data) {
                Feature::where('id', $id)->update(['unit' => $data['unit']]);
                $count++;
            }
            $this->info("  ✅ {$count} özelliğe unit eklendi.");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mesafe özelliklerini standartlaştır
     */
    private function fixDistanceUnits(bool $dryRun): void
    {
        $this->info('📏 Mesafe Özelliklerini Standartlaştırma...');

        // Mesafe özelliklerini bul
        $distanceFeatures = Feature::where(function ($q) {
            $q->where('name', 'LIKE', '%mesafe%')
                ->orWhere('name', 'LIKE', '%uzaklık%')
                ->orWhere('name', 'LIKE', '%uzaklik%')
                ->orWhere('slug', 'LIKE', '%mesafe%')
                ->orWhere('slug', 'LIKE', '%uzaklik%');
        })
            ->where('type', 'number')
            ->get(['id', 'name', 'slug', 'type', 'unit']);

        if ($distanceFeatures->isEmpty()) {
            $this->warn('  ⚠️  Mesafe özellik bulunamadı.');
            return;
        }

        $this->info('  📊 Bulunan mesafe özellikler: ' . $distanceFeatures->count());

        $updates = [];
        foreach ($distanceFeatures as $feature) {
            $currentUnit = $feature->unit;
            $targetUnit = 'm'; // Standart: metre

            if ($currentUnit !== $targetUnit) {
                $updates[$feature->id] = [
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'current_unit' => $currentUnit ?? '(boş)',
                    'target_unit' => $targetUnit,
                ];
                $this->line("    - {$feature->name}: '{$currentUnit}' → '{$targetUnit}'");
            }
        }

        if (empty($updates)) {
            $this->info('  ✅ Tüm mesafe özellikler zaten standart (m).');
            return;
        }

        if ($dryRun) {
            $this->warn('  ⚠️  DRY-RUN: Mesafe unit\'leri güncellenmeyecek.');
            return;
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($updates as $id => $data) {
                Feature::where('id', $id)->update(['unit' => $data['target_unit']]);
                $count++;
            }
            $this->info("  ✅ {$count} mesafe özelliğine unit eklendi/düzeltildi (m).");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Boolean ve Select özelliklerde unit temizliği
     */
    private function removeInvalidUnits(bool $dryRun): void
    {
        $this->info('🧹 Boolean/Select Özelliklerde Unit Temizliği...');

        $featuresWithInvalidUnit = Feature::whereIn('type', ['boolean', 'select', 'checkbox'])
            ->whereNotNull('unit')
            ->get(['id', 'name', 'slug', 'type', 'unit']);

        if ($featuresWithInvalidUnit->isEmpty()) {
            $this->warn('  ⚠️  Unit\'li boolean/select özellik bulunamadı.');
            return;
        }

        $this->info('  📊 Bulunan unit\'li boolean/select özellikler: ' . $featuresWithInvalidUnit->count());

        if ($dryRun) {
            foreach ($featuresWithInvalidUnit->take(10) as $feature) {
                $this->line("    - {$feature->name} ({$feature->type}, unit: {$feature->unit}) [ID: {$feature->id}]");
            }
            if ($featuresWithInvalidUnit->count() > 10) {
                $this->line("    ... ve " . ($featuresWithInvalidUnit->count() - 10) . " özellik daha");
            }
            $this->warn('  ⚠️  DRY-RUN: Unit\'ler kaldırılmayacak.');
            return;
        }

        DB::beginTransaction();
        try {
            $count = Feature::whereIn('type', ['boolean', 'select', 'checkbox'])
                ->whereNotNull('unit')
                ->update(['unit' => null]);
            $this->info("  ✅ {$count} özellikten unit kaldırıldı.");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ❌ Hata: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * İsim ve slug'a göre unit tahmin et
     */
    private function guessUnit(string $name, string $slug): ?string
    {
        $nameLower = mb_strtolower($name);
        $slugLower = mb_strtolower($slug);

        // Sayı içeren özellikler
        if (str_contains($nameLower, 'sayı') || str_contains($nameLower, 'sayısı') || str_contains($slugLower, 'sayi')) {
            return 'adet';
        }

        // Kat içeren özellikler (numara değilse)
        if ((str_contains($nameLower, 'kat') || str_contains($slugLower, 'kat'))
            && !str_contains($nameLower, 'numara') && !str_contains($slugLower, 'numara')
        ) {
            return 'kat';
        }

        // Oda içeren özellikler
        if (str_contains($nameLower, 'oda') || str_contains($slugLower, 'oda')) {
            return 'adet';
        }

        // Yatak içeren özellikler
        if (str_contains($nameLower, 'yatak') || str_contains($slugLower, 'yatak')) {
            return 'adet';
        }

        // Banyo içeren özellikler
        if (str_contains($nameLower, 'banyo') || str_contains($slugLower, 'banyo')) {
            return 'adet';
        }

        // Balkon içeren özellikler
        if (str_contains($nameLower, 'balkon') || str_contains($slugLower, 'balkon')) {
            return 'adet';
        }

        // Salon içeren özellikler
        if (str_contains($nameLower, 'salon') || str_contains($slugLower, 'salon')) {
            return 'adet';
        }

        // Numara içeren özellikler (birim yok)
        if (str_contains($nameLower, 'numara') || str_contains($nameLower, 'no') || str_contains($slugLower, 'numara') || str_contains($slugLower, '-no')) {
            return null;
        }

        // Varsayılan: adet
        return 'adet';
    }
}

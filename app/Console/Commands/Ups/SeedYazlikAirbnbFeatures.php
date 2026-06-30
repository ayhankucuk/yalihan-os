<?php

namespace App\Console\Commands\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureCategory;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2.3: Yazlık Kiralık Airbnb Feature Pack
 *
 * Context7: UPS SSOT preserved, idempotent seeding
 * Observer mode: No FeatureAssignment mutations outside this seed
 */
class SeedYazlikAirbnbFeatures extends Command
{
    protected $signature = 'ups:seed:yazlik-airbnb {--scope=yazlik-kiralik}';
    protected $description = '✅ Phase 2.3: Seed Airbnb-style features for Yazlık Kiralık category';

    /**
     * Canonical feature set (slug => type)
     */
    protected array $featureSet = [
        // Fiyat/Kural
        'gunluk_fiyat' => 'number',
        'minimum_konaklama' => 'number',
        'temizlik_ucreti' => 'number',
        'depozito_tutari' => 'number',
        'giris_saati' => 'text',
        'cikis_saati' => 'text',

        // Sezon
        'sezon_baslangic' => 'date',
        'sezon_bitis' => 'date',
        'sezonluk_fiyat' => 'number',

        // Ev Kuralları
        'evcil_hayvan' => 'boolean',
        'sigara_icin_uygun' => 'boolean',
        'parti_etkinlik_yasak' => 'boolean',
        'maksimum_misafir' => 'number',
        'kimlik_zorunlu' => 'boolean',

        // Olanaklar
        'wifi' => 'boolean',
        'klima' => 'boolean',
        'havuz' => 'boolean',
        'barbeku' => 'boolean',
        'otopark' => 'boolean',
        'denize_yakin' => 'boolean',
    ];

    public function handle()
    {
        $this->info('✅ Phase 2.3: Yazlık Kiralık Airbnb Feature Pack');
        $this->info('');

        $scope = $this->option('scope');

        // Get kategori
        $kategori = IlanKategori::where('slug', $scope)->first();

        if (!$kategori) {
            $this->error("❌ Kategori bulunamadı: {$scope}");
            return 1;
        }

        $this->info("📍 Kategori: {$kategori->name} (ID: {$kategori->id})");

        // Get yayin tipleri
        $yayinTipleri = YayinTipiSablonu::where('kategori_id', $kategori->id)
            ->where('aktiflik_durumu', true)
            ->get();

        $this->info("📝 Yayın tipleri: {$yayinTipleri->count()}");
        foreach ($yayinTipleri as $yt) {
            $this->info("  - {$yt->yayin_tipi} (ID: {$yt->id})");
        }
        $this->newLine();

        DB::beginTransaction();

        try {
            // Step 1: Cleanup duplicate minimum-konaklama
            $this->cleanupDuplicates();

            // Step 2: Upsert features
            $stats = $this->upsertFeatures();

            // Step 3: Type/aktiflik fixes
            $this->applyTypeFixes();

            // Step 4: Template assignments
            $assignmentStats = $this->assignToTemplates($kategori, $yayinTipleri);

            DB::commit();

            $this->newLine();
            $this->info('✅ Seed completed successfully!');
            $this->info('');
            $this->info("Features: {$stats['created']} created, {$stats['updated']} updated");
            $this->info("Assignments: {$assignmentStats['created']} created, {$assignmentStats['skipped']} skipped");
            $this->info('');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: {$e->getMessage()}");
            $this->error("Stack: {$e->getTraceAsString()}");
            return 1;
        }
    }

    /**
     * Cleanup duplicate minimum-konaklama
     */
    protected function cleanupDuplicates(): void
    {
        $this->info('🧹 Step 1: Duplicate cleanup...');

        $duplicate = Feature::where('slug', 'minimum-konaklama')->first();

        if ($duplicate) {
            $this->warn("⚠️  Found duplicate: minimum-konaklama (ID: {$duplicate->id})");

            // Check assignments
            $assignmentCount = FeatureAssignment::where('feature_id', $duplicate->id)->count();

            if ($assignmentCount > 0) {
                $this->warn("  - Has {$assignmentCount} assignments, migrating to canonical...");

                // Get or create canonical
                $canonical = Feature::firstOrCreate(
                    ['slug' => 'minimum_konaklama'],
                    [
                        'name' => 'Minimum Konaklama',
                        'type' => 'number',
                        'unit' => 'gece',
                        'aktiflik_durumu' => true,
                        'description' => 'Minimum konaklama süresi',
                    ]
                );

                // Migrate assignments
                FeatureAssignment::where('feature_id', $duplicate->id)
                    ->update(['feature_id' => $canonical->id]);

                $this->info("✅ Migrated {$assignmentCount} assignments to canonical");
            }

            // Disable duplicate
            $duplicate->update(['aktiflik_durumu' => false]);
            $this->info('✅ Disabled duplicate: minimum-konaklama');
        } else {
            $this->info('✅ No duplicate found');
        }
    }

    /**
     * Upsert features
     */
    protected function upsertFeatures(): array
    {
        $this->info('\n🔨 Step 2: Upsert features...');

        $created = 0;
        $updated = 0;

        // Get or create category
        $category = FeatureCategory::firstOrCreate(
            ['slug' => 'airbnb-features'],
            [
                'name' => 'Airbnb Özellikleri',
                'description' => 'Kısa süreli kiralama özellikleri',
                'display_order' => 100,
            ]
        );

        foreach ($this->featureSet as $slug => $type) {
            // ✅ Explicit check to avoid race condition
            $existing = Feature::where('slug', $slug)->first();

            // ✅ Only use columns that exist in DB
            $data = [
                'feature_category_id' => $category->id,
                'name' => $this->getFeatureName($slug),
                'type' => $type,
                'unit' => $this->getFeatureUnit($slug),
                'aktiflik_durumu' => true, // ✅ SAB: status → aktiflik_durumu
                'description' => $this->getFeatureDescription($slug),
                'is_filterable' => in_array($slug, ['maksimum_misafir', 'wifi', 'havuz']),
            ];

            if ($existing) {
                // Update existing
                $existing->update($data);
                $updated++;
                $this->line("  ✅ Updated: {$slug}");
            } else {
                // Create new (with try/catch for race condition)
                try {
                    Feature::create(array_merge(['slug' => $slug], $data));
                    $created++;
                    $this->line("  ✨ Created: {$slug}");
                } catch (\Illuminate\Database\QueryException $e) {
                    // If duplicate (race condition), update instead
                    if ($e->getCode() == 23000) {
                        $existing = Feature::where('slug', $slug)->first();
                        if ($existing) {
                            $existing->update($data);
                            $updated++;
                            $this->line("  ✅ Updated (race): {$slug}");
                        }
                    } else {
                        throw $e;
                    }
                }
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Apply type/aktiflik fixes
     */
    protected function applyTypeFixes(): void
    {
        $this->info('\n🔧 Step 3: Type/aktiflik fixes...');

        // sezon_bitis: text → date
        $sezonBitis = Feature::where('slug', 'sezon_bitis')->first();
        if ($sezonBitis && $sezonBitis->type !== 'date') {
            $sezonBitis->update(['type' => 'date']);
            $this->info('✅ sezon_bitis: type changed to date');
        }

        // sezon_baslangic: aktiflik_durumu = true
        Feature::where('slug', 'sezon_baslangic')->update(['aktiflik_durumu' => true]);
        $this->info('✅ sezon_baslangic: aktiflik_durumu = true');

        // sezonluk_fiyat: aktiflik_durumu = true
        Feature::where('slug', 'sezonluk_fiyat')->update(['aktiflik_durumu' => true]);
        $this->info('✅ sezonluk_fiyat: aktiflik_durumu = true');
    }

    /**
     * Assign features to templates (idempotent)
     */
    protected function assignToTemplates(IlanKategori $kategori, $yayinTipleri): array
    {
        $this->info('\n🔗 Step 4: Template assignments (idempotent)...');

        $created = 0;
        $skipped = 0;
        $displayOrder = 100; // Start from 100 to avoid conflicts

        foreach ($yayinTipleri as $yayinTipi) {
            $this->line("  Processing: {$yayinTipi->yayin_tipi}");

            foreach ($this->featureSet as $slug => $type) {
                $feature = Feature::where('slug', $slug)->first();

                if (!$feature) {
                    $this->warn("    ⚠️  Feature not found: {$slug}");
                    continue;
                }

                // Check existing assignment
                $existing = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                    ->where('assignable_id', $yayinTipi->id)
                    ->where('feature_id', $feature->id)
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Create assignment
                FeatureAssignment::create([
                    'feature_id' => $feature->id,
                    'assignable_type' => YayinTipiSablonu::class,
                    'assignable_id' => $yayinTipi->id,
                    'is_required' => in_array($slug, ['gunluk_fiyat', 'minimum_konaklama']),
                    'is_visible' => true,
                    'display_order' => $displayOrder++,
                ]);

                $created++;
            }
        }

        $this->info("✅ Assignments: {$created} created, {$skipped} skipped");

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Get feature display name
     */
    protected function getFeatureName(string $slug): string
    {
        return match ($slug) {
            'gunluk_fiyat' => 'Günlük Fiyat',
            'minimum_konaklama' => 'Minimum Konaklama',
            'temizlik_ucreti' => 'Temizlik Ücreti',
            'depozito_tutari' => 'Depozito Tutarı',
            'giris_saati' => 'Giriş Saati',
            'cikis_saati' => 'Çıkış Saati',
            'sezon_baslangic' => 'Sezon Başlangıç',
            'sezon_bitis' => 'Sezon Bitiş',
            'sezonluk_fiyat' => 'Sezonluk Fiyat',
            'evcil_hayvan' => 'Evcil Hayvan',
            'sigara_icin_uygun' => 'Sigara İçin Uygun',
            'parti_etkinlik_yasak' => 'Parti/Etkinlik Yasak',
            'maksimum_misafir' => 'Maksimum Misafir',
            'kimlik_zorunlu' => 'Kimlik Zorunlu',
            'wifi' => 'WiFi',
            'klima' => 'Klima',
            'havuz' => 'Havuz',
            'barbeku' => 'Barbekü',
            'otopark' => 'Otopark',
            'denize_yakin' => 'Denize Yakın',
            default => ucwords(str_replace('_', ' ', $slug)),
        };
    }

    /**
     * Get feature unit
     */
    protected function getFeatureUnit(string $slug): ?string
    {
        return match ($slug) {
            'gunluk_fiyat', 'temizlik_ucreti', 'depozito_tutari', 'sezonluk_fiyat' => 'TL',
            'minimum_konaklama' => 'gece',
            'maksimum_misafir' => 'kişi',
            default => null,
        };
    }

    /**
     * Get feature description
     */
    protected function getFeatureDescription(string $slug): ?string
    {
        return match ($slug) {
            'gunluk_fiyat' => 'Günlük kiralama ücreti',
            'minimum_konaklama' => 'Minimum konaklama süresi (gece)',
            'temizlik_ucreti' => 'Bir kerelik temizlik ücreti',
            'depozito_tutari' => 'Depozito/kapora tutarı',
            'giris_saati' => 'Giriş saati (ör: 14:00)',
            'cikis_saati' => 'Çıkış saati (ör: 11:00)',
            'sezon_baslangic' => 'Sezon başlangıç tarihi',
            'sezon_bitis' => 'Sezon bitiş tarihi',
            'sezonluk_fiyat' => 'Sezonluk kiralama fiyatı',
            'maksimum_misafir' => 'Maksimum misafir kapasitesi',
            default => null,
        };
    }
}

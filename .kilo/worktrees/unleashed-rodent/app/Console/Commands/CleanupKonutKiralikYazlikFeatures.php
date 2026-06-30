<?php

namespace App\Console\Commands;

use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Konut "Kiralık" yayın tipinden yazlık özelliklerini temizle
 *
 * Context7: Konut'un uzun süreli "Kiralık" yayın tipine yazlık özellikleri atanmamalı.
 * Bu özellikler sadece "Yazlık Kiralık" yayın tipine ait olmalı.
 */
class CleanupKonutKiralikYazlikFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'features:cleanup-konut-kiralik
                            {--dry-run : Sadece rapor göster, değişiklik yapma}
                            {--force : Onay sorma, doğrudan uygula}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Konut "Kiralık" yayın tipinden yazlık özelliklerini temizle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Konut "Kiralık" yayın tipinden yazlık özellikleri temizleniyor...');
        $this->newLine();

        // Konut kategorisini bul
        $konutKategori = IlanKategori::where('slug', 'konut')->first();
        if (!$konutKategori) {
            $this->error('❌ Konut kategorisi bulunamadı!');
            return 1;
        }

        // Konut "Kiralık" yayın tipini bul
        $kiralikYayinTipi = YayinTipiSablonu::where('kategori_id', $konutKategori->id)
            ->where('yayin_tipi', 'Kiralık')
            ->first();

        if (!$kiralikYayinTipi) {
            $this->error('❌ Konut "Kiralık" yayın tipi bulunamadı!');
            return 1;
        }

        $this->info("📋 Kategori: {$konutKategori->name} (ID: {$konutKategori->id})");
        $this->info("📋 Yayın Tipi: {$kiralikYayinTipi->yayin_tipi} (ID: {$kiralikYayinTipi->id})");
        $this->newLine();

        // Yazlık özelliklerini bul
        $allAssignments = FeatureAssignment::where('assignable_id', $kiralikYayinTipi->id)
            ->where('assignable_type', YayinTipiSablonu::class)
            ->with(['feature'])
            ->get();

        $yazlikAssignments = $allAssignments->filter(function ($assignment) {
            $slug = strtolower($assignment->feature->slug ?? '');
            $name = strtolower($assignment->feature->name ?? '');

            // Yazlık özelliklerini tespit et
            return str_contains($slug, '-yazlik-')
                || str_contains($slug, 'yazlik')
                || (str_contains($name, 'yazlık') && !str_contains($slug, 'konut'));
        });

        $this->info("📊 Toplam Feature Assignment: {$allAssignments->count()}");
        $this->info("⚠️  Yazlık Özellikleri: {$yazlikAssignments->count()}");
        $this->newLine();

        if ($yazlikAssignments->isEmpty()) {
            $this->info('✅ Yazlık özelliği bulunamadı. Temizlik gerekmiyor.');
            return 0;
        }

        // Yazlık özelliklerini listele
        $this->info('📋 Silinecek Yazlık Özellikleri:');
        $this->newLine();

        $tableData = [];
        foreach ($yazlikAssignments as $assignment) {
            $tableData[] = [
                'ID' => $assignment->id,
                'Feature' => $assignment->feature->name,
                'Slug' => $assignment->feature->slug,
                'Kategori' => $assignment->feature->category->name ?? 'N/A',
            ];
        }

        $this->table(['ID', 'Feature', 'Slug', 'Kategori'], $tableData);
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY-RUN modu: Değişiklik yapılmayacak.');
            $this->info("✅ {$yazlikAssignments->count()} adet feature assignment silinecek.");
            return 0;
        }

        // Onay al (CI / non-interactive için --force kullanılmalı)
        if (!$force && !$this->confirm("⚠️  {$yazlikAssignments->count()} adet feature assignment silinecek. Devam edilsin mi?")) {
            $this->info('❌ İşlem iptal edildi. (--force ile onaysız çalıştırabilirsiniz.)');
            return 0;
        }

        // Sil
        $this->info('🗑️  Feature assignments siliniyor...');

        DB::transaction(function () use ($yazlikAssignments) {
            $ids = $yazlikAssignments->pluck('id')->toArray();
            FeatureAssignment::whereIn('id', $ids)->delete();
        });

        $this->newLine();
        $this->info("✅ {$yazlikAssignments->count()} adet feature assignment başarıyla silindi.");
        $this->newLine();

        // Sonuç kontrolü
        $remainingAssignments = FeatureAssignment::where('assignable_id', $kiralikYayinTipi->id)
            ->where('assignable_type', YayinTipiSablonu::class)
            ->count();

        $this->info("📊 Kalan Feature Assignment: {$remainingAssignments}");
        $this->newLine();

        return 0;
    }
}

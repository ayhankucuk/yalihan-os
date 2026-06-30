<?php

namespace App\Console\Commands;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🌍 TurkiyeAPI'den Mahalle İmport Komutu
 *
 * TurkiyeAPI'den mahalle verilerini çeker ve veritabanına kaydeder.
 * Sonrasında SyncMahalleCoordinates ile koordinatları geocode edilebilir.
 */
class ImportMahallerFromTurkiyeAPI extends Command
{
    protected $signature = 'mahalle:import-turkiyeapi
                            {--il= : İl adı (örn: Muğla)}
                            {--ilce= : İlçe adı (örn: Marmaris)}
                            {--all : Tüm Türkiye}';

    protected $description = '🌍 TurkiyeAPI\'den mahalle verilerini import eder';

    private int $importedCount = 0;
    private int $skippedCount = 0;
    private int $errorCount = 0;

    public function handle()
    {
        $this->info('🌍 TurkiyeAPI Mahalle İmport Başlatılıyor...');
        $this->newLine();

        if ($this->option('ilce')) {
            $this->importByIlce($this->option('ilce'));
        } elseif ($this->option('il')) {
            $this->importByIl($this->option('il'));
        } elseif ($this->option('all')) {
            $this->importAll();
        } else {
            $this->error('❌ Lütfen --il, --ilce veya --all seçeneğini belirtin.');
            return 1;
        }

        $this->displayResults();
        return 0;
    }

    private function importByIlce(string $ilceAdi)
    {
        $ilce = Ilce::where('ilce_adi', $ilceAdi)
            ->with('il')
            ->first();

        if (!$ilce) {
            $this->error("❌ İlçe bulunamadı: {$ilceAdi}");
            return;
        }

        $this->info("📍 İlçe: {$ilce->ilce_adi}, {$ilce->il->il_adi}");
        $this->newLine();

        // TurkiyeAPI'den ilçe ID'sini bul
        $turkiyeApiIlceId = $this->findTurkiyeApiDistrictId($ilce->il->il_adi, $ilce->ilce_adi);

        if (!$turkiyeApiIlceId) {
            $this->error("❌ TurkiyeAPI'de ilçe bulunamadı: {$ilce->ilce_adi}");
            return;
        }

        $this->importNeighborhoodsForDistrict($ilce, $turkiyeApiIlceId);
    }

    private function importByIl(string $ilAdi)
    {
        $il = Il::where('il_adi', $ilAdi)->first();

        if (!$il) {
            $this->error("❌ İl bulunamadı: {$ilAdi}");
            return;
        }

        $this->info("📍 İl: {$il->il_adi}");
        $this->newLine();

        $ilceler = Ilce::where('il_id', $il->id)->get();

        $bar = $this->output->createProgressBar($ilceler->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Başlatılıyor...');
        $bar->start();

        foreach ($ilceler as $ilce) {
            $bar->setMessage("İşleniyor: {$ilce->ilce_adi}");

            $turkiyeApiIlceId = $this->findTurkiyeApiDistrictId($il->il_adi, $ilce->ilce_adi);

            if ($turkiyeApiIlceId) {
                $this->importNeighborhoodsForDistrict($ilce, $turkiyeApiIlceId, false);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function importAll()
    {
        $this->warn('⚠️  Tüm Türkiye import işlemi uzun sürebilir!');

        if (!$this->confirm('Devam etmek istiyor musunuz?')) {
            return;
        }

        $iller = Il::all();

        foreach ($iller as $il) {
            $this->info("📍 İl: {$il->il_adi}");
            $this->importByIl($il->il_adi);
        }
    }

    private function findTurkiyeApiDistrictId(string $ilAdi, string $ilceAdi): ?int
    {
        try {
            // Önce il ID'sini bul
            $response = Http::timeout(10)->get('https://turkiyeapi.dev/api/v1/provinces');

            if (!$response->successful()) {
                return null;
            }

            $provinces = $response->json()['data'] ?? [];
            $province = collect($provinces)->firstWhere('name', $ilAdi);

            if (!$province) {
                return null;
            }

            // İlçe ID'sini bul
            $response = Http::timeout(10)->get("https://turkiyeapi.dev/api/v1/provinces/{$province['id']}");

            if (!$response->successful()) {
                return null;
            }

            $districts = $response->json()['data']['districts'] ?? [];
            $district = collect($districts)->firstWhere('name', $ilceAdi);

            return $district['id'] ?? null;
        } catch (\Exception $e) {
            Log::error("TurkiyeAPI district lookup error", [
                'il' => $ilAdi,
                'ilce' => $ilceAdi,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function importNeighborhoodsForDistrict(Ilce $ilce, int $turkiyeApiIlceId, bool $showProgress = true)
    {
        try {
            $response = Http::timeout(10)->get("https://turkiyeapi.dev/api/v1/districts/{$turkiyeApiIlceId}");

            if (!$response->successful()) {
                $this->errorCount++;
                return;
            }

            $neighborhoods = $response->json()['data']['neighborhoods'] ?? [];

            if ($showProgress) {
                $bar = $this->output->createProgressBar(count($neighborhoods));
                $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
                $bar->setMessage('Mahalleler import ediliyor...');
                $bar->start();
            }

            foreach ($neighborhoods as $neighborhood) {
                $mahalleData = [
                    'ilce_id' => $ilce->id,
                    'mahalle_adi' => $neighborhood['name'],
                    'mahalle_kodu' => (string) $neighborhood['id'],
                    'api_id' => $neighborhood['id'] ?? null,
                    'aktiflik_durumu' => 1, // ✅ SAB: status yasak
                ];

                try {
                    Mahalle::updateOrCreate(
                        [
                            'ilce_id' => $ilce->id,
                            'mahalle_adi' => $neighborhood['name'],
                        ],
                        $mahalleData
                    );

                    $this->importedCount++;
                } catch (\Exception $e) {
                    $this->skippedCount++;
                    Log::warning("Mahalle import skipped", [
                        'mahalle' => $neighborhood['name'],
                        'error' => $e->getMessage(),
                    ]);
                }

                if ($showProgress) {
                    $bar->advance();
                }
            }

            if ($showProgress) {
                $bar->finish();
                $this->newLine();
            }

            Log::info("Mahalleler imported from TurkiyeAPI", [
                'ilce' => $ilce->ilce_adi,
                'count' => count($neighborhoods),
            ]);
        } catch (\Exception $e) {
            $this->errorCount++;
            Log::error("TurkiyeAPI neighborhood import error", [
                'ilce_id' => $turkiyeApiIlceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function displayResults(): void
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 SONUÇLAR');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->line("  ✅ Import Edilen:   <fg=green>{$this->importedCount}</>");
        $this->line("  ⏭️  Atlanan:         <fg=yellow>{$this->skippedCount}</>");
        $this->line("  ❌ Hata:            <fg=red>{$this->errorCount}</>");

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🎯 Sonraki Adım: Koordinatları geocode edin:');
        $this->line('   php artisan mahalle:sync-coordinates --il=Muğla --provider=nominatim');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}

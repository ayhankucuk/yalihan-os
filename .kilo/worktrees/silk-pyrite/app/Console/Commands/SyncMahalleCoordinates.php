<?php

namespace App\Console\Commands;

use App\Models\Mahalle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🗺️ Mahalle Koordinat Senkronizasyon Komutu
 *
 * Digital Twin Strategy: Dış API'lerden (Nominatim/Google) gelen koordinatları
 * bir kez çekip kalıcı olarak veritabanına kaydeder.
 *
 * Avantajlar:
 * - ⚡ Sıfır Gecikme: Veriler local DB'de
 * - 💰 Sıfır Maliyet: Tek seferlik API kullanımı
 * - 🧠 Cortex AI Yakıtı: Kalıcı veri, sürekli analiz
 * - 🔒 Veri Egemenliği: Bağımsız sistem
 */
class SyncMahalleCoordinates extends Command
{
    protected $signature = 'mahalle:sync-coordinates
                            {--il= : İl adı (örn: Muğla)}
                            {--ilce= : İlçe adı (örn: Bodrum)}
                            {--all : Tüm mahalleler}
                            {--provider=nominatim : Geocoding provider (nominatim|google)}
                            {--missing-only : Sadece koordinatı olmayan mahalleler}
                            {--force : Mevcut koordinatları da güncelle}';

    protected $description = '🗺️ Mahalle koordinatlarını otomatik geocode eder (Digital Twin Strategy)';

    private int $successCount = 0;
    private int $failedCount = 0;
    private int $skippedCount = 0;
    private array $failedMahalleler = [];

    public function handle()
    {
        $this->info('🚀 Mahalle Koordinat Senkronizasyonu Başlatılıyor...');
        $this->newLine();

        $provider = $this->option('provider');
        $missingOnly = $this->option('missing-only');
        $force = $this->option('force');

        // Mahalle sorgusunu oluştur
        $query = Mahalle::with(['ilce.il']);

        if ($this->option('il')) {
            $query->whereHas('ilce.il', fn($q) => $q->where('il_adi', $this->option('il')));
        }

        if ($this->option('ilce')) {
            $query->whereHas('ilce', fn($q) => $q->where('ilce_adi', $this->option('ilce')));
        }

        if ($missingOnly) {
            $query->where(function($q) {
                $q->whereNull('enlem')->orWhereNull('boylam');
            });
        }

        $mahalleler = $query->get();

        if ($mahalleler->isEmpty()) {
            $this->warn('⚠️  Hiç mahalle bulunamadı!');
            return 1;
        }

        $this->info("📊 Toplam {$mahalleler->count()} mahalle bulundu.");
        $this->newLine();

        // Onay al
        if (!$this->confirm("Bu işlem {$mahalleler->count()} mahalle için geocoding yapacak. Devam edilsin mi?")) {
            $this->warn('❌ İşlem iptal edildi.');
            return 0;
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($mahalleler->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Başlatılıyor...');
        $bar->start();

        foreach ($mahalleler as $mahalle) {
            $bar->setMessage("İşleniyor: {$mahalle->mahalle_adi}");

            // Koordinat varsa ve force değilse atla
            if (!$force && $mahalle->enlem && $mahalle->boylam) {
                $this->skippedCount++;
                $bar->advance();
                continue;
            }

            $coordinates = $this->geocode($mahalle, $provider);

            if ($coordinates) {
                $mahalle->update([
                    'enlem' => $coordinates['lat'],
                    'boylam' => $coordinates['lng'],
                ]);
                $this->successCount++;

                Log::info("Mahalle geocoded: {$mahalle->mahalle_adi}", [
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng'],
                    'provider' => $provider,
                ]);
            } else {
                $this->failedCount++;
                $this->failedMahalleler[] = [
                    'mahalle' => $mahalle->mahalle_adi,
                    'ilce' => $mahalle->ilce->ilce_adi,
                    'il' => $mahalle->ilce->il->il_adi,
                ];

                Log::warning("Mahalle geocoding failed: {$mahalle->mahalle_adi}");
            }

            $bar->advance();

            // Rate limiting (Nominatim için 1 req/sec)
            if ($provider === 'nominatim') {
                sleep(1);
            } else {
                usleep(100000); // 100ms for Google
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Sonuçları göster
        $this->displayResults();

        return 0;
    }

    private function geocode(Mahalle $mahalle, string $provider): ?array
    {
        $query = "{$mahalle->mahalle_adi}, {$mahalle->ilce->ilce_adi}, {$mahalle->ilce->il->il_adi}, Turkey";

        if ($provider === 'nominatim') {
            return $this->geocodeNominatim($query);
        } elseif ($provider === 'google') {
            return $this->geocodeGoogle($query);
        }

        return null;
    }

    private function geocodeNominatim(string $query): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'YalihanEmlak/1.0 (https://yalihanemlak.com)',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'tr',
                    'addressdetails' => 1,
                ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                ];
            }
        } catch (\Exception $e) {
            Log::error("Nominatim error: {$query}", ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function geocodeGoogle(string $query): ?array
    {
        try {
            $apiKey = config('services.google_maps.api_key');

            if (!$apiKey) {
                $this->error('❌ Google Maps API key bulunamadı! .env dosyasına GOOGLE_MAPS_API_KEY ekleyin.');
                return null;
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $query,
                'key' => $apiKey,
                'language' => 'tr',
                'region' => 'tr',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    return [
                        'lat' => $result['geometry']['location']['lat'],
                        'lng' => $result['geometry']['location']['lng'],
                    ];
                } elseif ($data['status'] === 'ZERO_RESULTS') {
                    Log::warning("Google: No results for {$query}");
                } else {
                    Log::warning("Google API error: {$data['status']} for {$query}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Google error: {$query}", ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function displayResults(): void
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 SONUÇLAR');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $total = $this->successCount + $this->failedCount + $this->skippedCount;

        $this->line("  Toplam İşlenen:     <fg=cyan>{$total}</>");
        $this->line("  ✅ Başarılı:        <fg=green>{$this->successCount}</>");
        $this->line("  ⏭️  Atlanan:         <fg=yellow>{$this->skippedCount}</>");
        $this->line("  ❌ Başarısız:       <fg=red>{$this->failedCount}</>");

        if ($this->successCount > 0) {
            $successRate = round(($this->successCount / ($this->successCount + $this->failedCount)) * 100, 2);
            $this->line("  📈 Başarı Oranı:    <fg=green>{$successRate}%</>");
        }

        $this->newLine();

        if (!empty($this->failedMahalleler)) {
            $this->warn('⚠️  Başarısız Mahalleler:');
            $this->newLine();

            foreach (array_slice($this->failedMahalleler, 0, 10) as $failed) {
                $this->line("   • {$failed['mahalle']}, {$failed['ilce']}, {$failed['il']}");
            }

            if (count($this->failedMahalleler) > 10) {
                $remaining = count($this->failedMahalleler) - 10;
                $this->line("   ... ve {$remaining} mahalle daha");
            }

            $this->newLine();
            $this->info('💡 İpucu: Başarısız mahalleleri Google provider ile tekrar deneyin:');
            $this->line('   php artisan mahalle:sync-coordinates --missing-only --provider=google');
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🎯 Digital Twin Strategy: Veriler kalıcı olarak kaydedildi!');
        $this->info('⚡ Artık harita ve POI sistemleri sıfır gecikme ile çalışacak.');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}

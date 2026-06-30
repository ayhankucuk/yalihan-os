<?php

namespace App\Console\Commands;

use App\Models\Ilce;
use App\Models\Mahalle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeNominatim extends Command
{
    protected $signature = 'geocode:nominatim 
                            {--province-id=48 : İl ID (default: Muğla)}
                            {--delay=1100 : İstekler arası bekleme (ms)}
                            {--limit= : Maksimum işlem sayısı}';

    protected $description = 'Geocode addresses using Nominatim (OpenStreetMap)';

    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private int $successCount = 0;
    private int $failCount = 0;

    public function handle(): int
    {
        $provinceId = $this->option('province-id');
        $delay = $this->option('delay');
        $limit = $this->option('limit');

        $this->info('🗺️  Nominatim Geocoding Başlıyor...');
        $this->newLine();

        // İlçeleri geocode et
        $this->geocodeIlceler($provinceId, $delay, $limit);

        // Mahalleleri geocode et
        $this->geocodeMahalleler($provinceId, $delay, $limit);

        // Özet
        $this->newLine();
        $this->info('📊 SONUÇLAR:');
        $this->table(
            ['Durum', 'Sayı'],
            [
                ['✅ Başarılı', $this->successCount],
                ['❌ Başarısız', $this->failCount],
            ]
        );

        return Command::SUCCESS;
    }

    private function geocodeIlceler(int $provinceId, int $delay, ?int $limit): void
    {
        $query = Ilce::with('il')
            ->where('il_id', $provinceId)
            ->where(function($q) {
                $q->whereNull('lat')->orWhereNull('lng');
            });

        if ($limit) {
            $query->limit($limit);
        }

        $ilceler = $query->get();

        if ($ilceler->isEmpty()) {
            $this->warn('⚠️  Koordinatsız ilçe bulunamadı.');
            return;
        }

        $this->info("📌 İlçeler geocode ediliyor ({$ilceler->count()} adet)...");
        $bar = $this->output->createProgressBar($ilceler->count());
        $bar->start();

        foreach ($ilceler as $ilce) {
            $il = $ilce->il;
            
            if (!$il) {
                $this->failCount++;
                Log::warning("İl bulunamadı: İlçe ID {$ilce->id}");
                $bar->advance();
                continue;
            }

            $query = "{$ilce->ilce_adi}, {$il->il_adi}, Türkiye";

            $coords = $this->geocode($query);

            if ($coords) {
                $ilce->update([
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ]);
                $this->successCount++;
            } else {
                $this->failCount++;
                Log::warning("Geocode FAIL: {$query}");
            }

            $bar->advance();
            usleep($delay * 1000); // Rate limit
        }

        $bar->finish();
        $this->newLine();
    }

    private function geocodeMahalleler(int $provinceId, int $delay, ?int $limit): void
    {
        $query = Mahalle::with(['ilce.il'])
            ->whereHas('ilce', fn($q) => $q->where('il_id', $provinceId))
            ->where(function($q) {
                $q->whereNull('lat')->orWhereNull('lng');
            });

        if ($limit) {
            $query->limit($limit);
        }

        $mahalleler = $query->get();

        if ($mahalleler->isEmpty()) {
            $this->warn('⚠️  Koordinatsız mahalle bulunamadı.');
            return;
        }

        $this->info("🏘️  Mahalleler geocode ediliyor ({$mahalleler->count()} adet)...");
        $bar = $this->output->createProgressBar($mahalleler->count());
        $bar->start();

        foreach ($mahalleler as $mahalle) {
            $ilce = $mahalle->ilce;
            
            if (!$ilce || !$ilce->il) {
                $this->failCount++;
                Log::warning("İlçe/İl bulunamadı: Mahalle ID {$mahalle->id}");
                $bar->advance();
                continue;
            }

            $il = $ilce->il;
            $query = "{$mahalle->mahalle_adi}, {$ilce->ilce_adi}, {$il->il_adi}, Türkiye";

            $coords = $this->geocode($query);

            if ($coords) {
                $mahalle->update([
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ]);
                $this->successCount++;
            } else {
                $this->failCount++;
                Log::warning("Geocode FAIL: {$query}");
            }

            $bar->advance();
            usleep($delay * 1000); // Rate limit
        }

        $bar->finish();
        $this->newLine();
    }

    private function geocode(string $address): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'YalihanEmlak/1.0 (contact@yalihanai.com)',
            ])->get(self::NOMINATIM_URL, [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'tr',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                    return [
                        'lat' => (float) $data[0]['lat'],
                        'lng' => (float) $data[0]['lon'],
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Geocode exception: {$address} - {$e->getMessage()}");
            return null;
        }
    }
}

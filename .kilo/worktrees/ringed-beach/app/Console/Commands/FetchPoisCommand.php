<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchPoisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:fetch-pois {--city= : Belirli bir şehri (ilçe) çek (bodrum, milas, didim, yatagan)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Google Places API (New) kullanarak POI verilerini JSON dosyasına çeker ve veritabanına kaydeder.';

    protected array $targets = [
        'bodrum' => [
            'name' => 'Bodrum',
            'province' => 'Muğla',
            'neighborhoods_sample' => ['Yalıkavak', 'Turgutreis', 'Gümüşlük', 'Bitez', 'Ortakent', 'Konacık', 'Gümbet', 'Türkbükü', 'Gündoğan', 'Gölköy', 'Torba', 'Kızılağaç', 'Yalıçiftlik', 'Mumcular']
        ],
        'milas' => [
            'name' => 'Milas',
            'province' => 'Muğla',
            'neighborhoods_sample' => ['Ören', 'Güllük', 'Boğaziçi', 'Kıyıkışlacık', 'Beçin', 'Selimiye', 'Bafa']
        ],
        'didim' => [
            'name' => 'Didim',
            'province' => 'Aydın',
            'neighborhoods_sample' => ['Altınkum', 'Akbük', 'Mavişehir', 'Fevzipaşa', 'Akyeniköy', 'Balat']
        ],
        'yatagan' => [
            'name' => 'Yatağan',
            'province' => 'Muğla',
            'neighborhoods_sample' => ['Bozarmut', 'Madendağı', 'Turgut', 'Yeşilbağcılar']
        ],
        'kavaklidere' => [
            'name' => 'Kavaklıdere',
            'province' => 'Muğla',
            'neighborhoods_sample' => ['Çayboyu', 'Menteşe', 'Salkım']
        ]
    ];

    protected array $categories = [
        'school' => ['primary_school', 'secondary_school', 'university'],
        'hospital' => ['hospital', 'pharmacy', 'doctor'],
        'shopping_mall' => ['shopping_mall', 'supermarket', 'market'],
        'tourist_attraction' => ['beach', 'marina', 'museum', 'historical_landmark', 'park'],
        'transportation' => ['bus_station', 'airport'],
        'finance' => ['bank', 'atm'], // Added bank
        'food' => ['restaurant', 'cafe'] // Added restaurant
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('services.google_maps.api_key');
        if (empty($apiKey)) {
            $this->error('GOOGLE_MAPS_API_KEY .env dosyasında bulunamadı!');
            return 1;
        }

        $city = $this->option('city');
        $targets = $city ? array_intersect_key($this->targets, [$city => true]) : $this->targets;

        $this->info("Toplam " . count($targets) . " hedef ilçe taranacak...");

        $allPois = [];
        $totalFound = 0;

        foreach ($targets as $key => $target) {
            $this->info("📍 Hedef: {$target['name']}, {$target['province']}");
            
            // Ana ilçe merkezi
            $locations = array_merge([$target['name']], $target['neighborhoods_sample']);

            foreach ($locations as $location) {
                foreach ($this->categories as $catGroup => $types) {
                    foreach ($types as $type) {
                        $query = "{$type} in {$location}, {$target['name']}, {$target['province']}";
                        $this->comment("   🔎 Aranıyor: $query");

                        $results = $this->fetchFromGoogle($query, $apiKey, $type, $catGroup);
                        
                        if (!empty($results)) {
                            $count = count($results);
                            $this->line("      ✅ $count adet bulundu.");
                            $allPois = array_merge($allPois, $results);
                            $totalFound += $count;
                        }

                        // Rate limit koruması (yarım saniye bekle)
                        usleep(500000); 
                    }
                }
            }
        }

        // Veriyi kaydet ve işle
        if ($totalFound > 0) {
            $this->saveToJson($allPois);
            $this->syncToDatabase($allPois);
            $this->info("🎉 İşlem tamamlandı! Toplam $totalFound POI veritabanına eklendi/güncellendi.");
        } else {
            $this->warn("Hiç veri bulunamadı.");
        }
    }

    private function fetchFromGoogle($query, $apiKey, $originalType, $categoryGroup)
    {
        try {
            // Text Search (New) Endpoint
            $url = 'https://places.googleapis.com/v1/places:searchText';

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.location,places.primaryType,places.formattedAddress'
            ])
            ->post($url, [
                'textQuery' => $query,
                'maxResultCount' => 20, // Her sorguda max 20 sonuç (Yeterli)
                'languageCode' => 'tr'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $places = $data['places'] ?? [];
                
                return array_map(function($place) use ($originalType, $categoryGroup) {
                    return [
                        'google_place_id' => $place['id'],
                        'name' => $place['displayName']['text'],
                        'address' => $place['formattedAddress'] ?? '',
                        'lat' => $place['location']['latitude'],
                        'lng' => $place['location']['longitude'],
                        'type' => $originalType,
                        'category' => $categoryGroup
                    ];
                }, $places);
            } else {
                $this->error("API Hatası: " . $response->body());
                return [];
            }
        } catch (\Exception $e) {
            $this->error("İstek Hatası: " . $e->getMessage());
            return [];
        }
    }

    private function saveToJson(array $data)
    {
        $filename = 'pois_backup_' . date('Y-m-d_H-i') . '.json';
        $path = storage_path('app/' . $filename);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("💾 JSON yedeği oluşturuldu: $filename");
    }

    private function syncToDatabase(array $pois)
    {
        $this->info("🗄️ Veritabanına yazılıyor...");
        $bar = $this->output->createProgressBar(count($pois));
        $bar->start();

        foreach ($pois as $poi) {
            \App\Models\PointOfInterest::updateOrCreate(
                ['google_place_id' => $poi['google_place_id']],
                [
                    'poi_adi' => $poi['name'],
                    'poi_turu' => $poi['type'],
                    'poi_kategorisi' => $poi['category'],
                    'lat' => $poi['lat'],
                    'lng' => $poi['lng'],
                    'ek_veri' => json_encode(['address' => $poi['address']]),
                    'aktiflik_durumu' => 1
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}

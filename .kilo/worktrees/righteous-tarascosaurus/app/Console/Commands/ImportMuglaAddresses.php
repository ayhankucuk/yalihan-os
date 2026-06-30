<?php

namespace App\Console\Commands;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Services\TurkiyeAPI\AddressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMuglaAddresses extends Command
{
    protected $signature = 'turkiye:import-mugla 
                            {--force : Force reimport even if data exists}';

    protected $description = 'Import Muğla province, districts and neighborhoods from TurkiyeAPI';

    private AddressService $turkiyeAPI;

    public function __construct(AddressService $turkiyeAPI)
    {
        parent::__construct();
        $this->turkiyeAPI = $turkiyeAPI;
    }

    public function handle(): int
    {
        $this->info('🇹🇷 TürkiyeAPI - Muğla İmport Başlıyor...');
        $this->newLine();

        try {
            DB::beginTransaction();

            // 1. Muğla İlini İmport Et
            $this->info('📍 İl: Muğla');
            $muglaData = $this->turkiyeAPI->getProvinceByName('muğla');

            if (!$muglaData) {
                $this->error('❌ Muğla verisi TurkiyeAPI\'den alınamadı!');
                return Command::FAILURE;
            }

            $mugla = $this->importProvince($muglaData);
            $this->info("✅ İl: {$mugla->il_adi} (ID: {$mugla->id})");
            $this->newLine();

            // 2. İlçeleri İmport Et
            $this->info('📌 İlçeler import ediliyor...');
            $districts = $muglaData['districts'] ?? [];
            $bar = $this->output->createProgressBar(count($districts));
            $bar->start();

            $importedDistricts = 0;
            $importedNeighborhoods = 0;

            foreach ($districts as $districtData) {
                $ilce = $this->importDistrict($mugla->id, $districtData);
                $importedDistricts++;

                // 3. Mahalleler (varsa)
                $neighborhoods = $this->turkiyeAPI->searchNeighborhoods(
                    '', 
                    $districtData['id']
                );

                foreach ($neighborhoods as $neighborhoodData) {
                    $this->importNeighborhood($ilce->id, $neighborhoodData);
                    $importedNeighborhoods++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            DB::commit();

            // Özet
            $this->info('🎉 İmport Tamamlandı!');
            $this->table(
                ['Kategori', 'Sayı'],
                [
                    ['İl', '1 (Muğla)'],
                    ['İlçe', $importedDistricts],
                    ['Mahalle', $importedNeighborhoods],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Hata: ' . $e->getMessage());
            $this->error('Stack: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function importProvince(array $data): Il
    {
        $areaCode = $data['areaCode'] ?? [];
        $telefonKodu = is_array($areaCode) && !empty($areaCode) 
            ? (string) $areaCode[0] 
            : null;

        // Önce api_id ile ara
        $il = Il::where('api_id', $data['id'])->first();
        
        if (!$il) {
            // Yoksa yeni oluştur
            $il = new Il();
            $il->id = $data['id']; // Plaka kodu olarak ID set et
        }

        // Güncelle
        $il->il_adi = $data['name'];
        $il->api_id = $data['id'];
        $il->plaka_kodu = $data['id'];
        $il->telefon_kodu = $telefonKodu;
        $il->lat = $data['coordinates']['latitude'] ?? null;
        $il->lng = $data['coordinates']['longitude'] ?? null;
        $il->save();

        return $il;
    }

    private function importDistrict(int $ilId, array $data): Ilce
    {
        return Ilce::updateOrCreate(
            ['api_id' => $data['id']],
            [
                'il_id' => $ilId,
                'ilce_adi' => $data['name'],
                'ilce_kodu' => $data['id'],
                'lat' => null, // TurkiyeAPI'de ilçe koordinatı yok
                'lng' => null,
            ]
        );
    }

    private function importNeighborhood(int $ilceId, array $data): Mahalle
    {
        return Mahalle::updateOrCreate(
            ['api_id' => $data['id']],
            [
                'ilce_id' => $ilceId,
                'mahalle_adi' => $data['name'],
                'mahalle_kodu' => $data['id'],
                'lat' => null, // Koordinat sonra geocoding ile eklenecek
                'lng' => null,
            ]
        );
    }
}

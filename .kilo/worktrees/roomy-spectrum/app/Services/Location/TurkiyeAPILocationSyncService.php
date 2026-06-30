<?php

namespace App\Services\Location;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Services\TurkiyeAPIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * TurkiyeAPI Location Sync Service
 *
 * Refactored from AdresYonetimiController::syncFromTurkiyeAPI() (440 lines → modular)
 * Handles synchronization of location data from TurkiyeAPI
 */
class TurkiyeAPILocationSyncService
{
    protected TurkiyeAPIService $turkiyeAPI;

    public function __construct(TurkiyeAPIService $turkiyeAPI)
    {
        $this->turkiyeAPI = $turkiyeAPI;
    }

    /**
     * Sync locations from TurkiyeAPI
     *
     * @param string $type 'all', 'provinces', 'districts', 'neighborhoods'
     * @param int|null $provinceId
     * @param int|null $districtId
     * @return array Sync results
     */
    public function sync(string $type = 'all', ?int $provinceId = null, ?int $districtId = null): array
    {
        $syncResults = [
            'provinces' => 0,
            'districts' => 0,
            'neighborhoods' => 0,
            'towns' => 0,
            'villages' => 0,
        ];

        DB::beginTransaction();

        try {
            if ($type === 'all' || $type === 'provinces') {
                $syncResults['provinces'] = $this->syncProvinces();
            }

            if ($type === 'all' || $type === 'districts') {
                $syncResults['districts'] = $this->syncDistricts($provinceId, $type);
            }

            if ($type === 'all' || $type === 'neighborhoods') {
                $result = $this->syncNeighborhoods($districtId, $provinceId);
                $syncResults['neighborhoods'] = $result['neighborhoods'];
                $syncResults['towns'] = $result['towns'];
                $syncResults['villages'] = $result['villages'];
            }

            DB::commit();
            Log::info('TurkiyeAPI: Sync completed successfully', $syncResults);

            return $syncResults;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TurkiyeAPI: Sync failed', [
                'error' => $e->getMessage(),
                'type' => $type, // context7-ignore
                'province_id' => $provinceId,
                'district_id' => $districtId,
            ]);
            throw $e;
        }
    }

    /**
     * Sync provinces from TurkiyeAPI
     *
     * @return int Count of synced provinces
     */
    protected function syncProvinces(): int
    {
        $iller = $this->turkiyeAPI->getProvinces();

        foreach ($iller as $il) {
            $ilData = [
                'il_adi' => $il['name'],
            ];

            // Context7: plaka_kodu kolonu zorunlu ve unique
            if (Schema::hasColumn('iller', 'plaka_kodu')) {
                $plakaKodu = str_pad($il['id'], 2, '0', STR_PAD_LEFT);
                $ilData['plaka_kodu'] = $plakaKodu;
            }

            Il::updateOrCreate(
                ['id' => $il['id']],
                $ilData
            );
        }

        Cache::forget('adres_yonetimi_iller');
        Log::info('TurkiyeAPI: İller sync edildi', ['count' => count($iller)]);

        return count($iller);
    }

    /**
     * Sync districts from TurkiyeAPI
     *
     * @param int|null $provinceId
     * @param string $type
     * @return int Count of synced districts
     */
    protected function syncDistricts(?int $provinceId, string $type): int
    {
        $illerToSync = $provinceId
            ? [['id' => $provinceId]]
            : ($type === 'all' ? $this->turkiyeAPI->getProvinces() : []);

        $totalDistricts = 0;

        foreach ($illerToSync as $il) {
            $ilceler = $this->turkiyeAPI->getDistricts($il['id']);

            foreach ($ilceler as $ilce) {
                $ilceData = [
                    'il_id' => $il['id'],
                    'ilce_adi' => $ilce['name'],
                ];

                // Context7: Duplicate önleme
                try {
                    Ilce::updateOrCreate(
                        [
                            'il_id' => $il['id'],
                            'ilce_adi' => $ilce['name'],
                        ],
                        $ilceData
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() === '23000') {
                        Log::debug("TurkiyeAPI: Duplicate ilçe atlandı - {$ilce['name']} (İl ID: {$il['id']})");
                        continue;
                    }
                    throw $e;
                }
            }

            $totalDistricts += count($ilceler);
            Cache::forget("adres_yonetimi_ilceler_il_{$il['id']}");
        }

        Cache::forget('adres_yonetimi_all_ilceler');
        Log::info('TurkiyeAPI: İlçeler sync edildi', ['count' => $totalDistricts]);

        return $totalDistricts;
    }

    /**
     * Sync neighborhoods from TurkiyeAPI
     *
     * @param int|null $districtId
     * @param int|null $provinceId
     * @return array Results with neighborhoods, towns, villages counts
     */
    protected function syncNeighborhoods(?int $districtId, ?int $provinceId): array
    {
        $results = [
            'neighborhoods' => 0,
            'towns' => 0,
            'villages' => 0,
        ];

        if ($districtId) {
            return $this->syncNeighborhoodsForDistrict($districtId, $provinceId);
        }

        // Sync all districts
        return $this->syncAllNeighborhoods();
    }

    /**
     * Sync neighborhoods for specific district
     *
     * @param int $districtId
     * @param int|null $provinceId
     * @return array Results
     */
    protected function syncNeighborhoodsForDistrict(int $districtId, ?int $provinceId): array
    {
        $dbDistrict = Ilce::find($districtId);
        $turkiyeAPIDistrictId = $this->resolveTurkiyeAPIDistrictId($dbDistrict, $provinceId, $districtId);

        if (!$turkiyeAPIDistrictId) {
            return ['neighborhoods' => 0, 'towns' => 0, 'villages' => 0];
        }

        $neighborhoods = $this->turkiyeAPI->getNeighborhoods($turkiyeAPIDistrictId);
        return $this->processNeighborhoods($neighborhoods, $districtId, $dbDistrict);
    }

    /**
     * Sync all neighborhoods for all districts
     *
     * @return array Results
     */
    protected function syncAllNeighborhoods(): array
    {
        $results = ['neighborhoods' => 0, 'towns' => 0, 'villages' => 0];
        $iller = $this->turkiyeAPI->getProvinces();

        foreach ($iller as $il) {
            $ilceler = $this->turkiyeAPI->getDistricts($il['id']);

            foreach ($ilceler as $ilce) {
                $dbDistrict = Ilce::where('il_id', $il['id'])
                    ->where('ilce_adi', $ilce['name'])
                    ->first();

                if (!$dbDistrict) {
                    Log::warning("TurkiyeAPI: İlçe DB'de bulunamadı - {$ilce['name']} (İl: {$il['name']})");
                    continue;
                }

                $neighborhoods = $this->turkiyeAPI->getNeighborhoods($ilce['id']);
                $result = $this->processNeighborhoods($neighborhoods, $dbDistrict->id, $dbDistrict);

                $results['neighborhoods'] += $result['neighborhoods'];
                $results['towns'] += $result['towns'];
                $results['villages'] += $result['villages'];
            }
        }

        Cache::forget('adres_yonetimi_all_mahalleler');
        Log::info('TurkiyeAPI: Tüm mahalleler sync edildi', $results);

        return $results;
    }

    /**
     * Resolve TurkiyeAPI district ID from database district
     *
     * @param Ilce|null $dbDistrict
     * @param int|null $provinceId
     * @param int $districtId
     * @return int|null
     */
    protected function resolveTurkiyeAPIDistrictId(?Ilce $dbDistrict, ?int $provinceId, int $districtId): ?int
    {
        if (!$dbDistrict || ($provinceId && $dbDistrict->il_id != $provinceId)) {
            return null;
        }

        if ($provinceId) {
            $turkiyeAPIDistricts = $this->turkiyeAPI->getDistricts($provinceId);
            $turkiyeAPIDistrict = collect($turkiyeAPIDistricts)->first(function ($tIlce) use ($dbDistrict) {
                return mb_strtolower(trim($tIlce['name'])) === mb_strtolower(trim($dbDistrict->ilce_adi));
            });

            if ($turkiyeAPIDistrict) {
                Log::info("TurkiyeAPI: İlçe eşleştirildi", [
                    'db_id' => $dbDistrict->id,
                    'api_id' => $turkiyeAPIDistrict['id'],
                    'name' => $dbDistrict->ilce_adi,
                ]);
                return $turkiyeAPIDistrict['id'];
            }

            Log::warning("TurkiyeAPI: İlçe API'de bulunamadı", [
                'db_id' => $dbDistrict->id,
                'name' => $dbDistrict->ilce_adi,
            ]);
        }

        return $districtId;
    }

    /**
     * Process and save neighborhoods
     *
     * @param array $neighborhoods
     * @param int $dbDistrictId
     * @param Ilce $dbDistrict
     * @return array Results
     */
    protected function processNeighborhoods(array $neighborhoods, int $dbDistrictId, Ilce $dbDistrict): array
    {
        $results = ['neighborhoods' => 0, 'towns' => 0, 'villages' => 0];

        foreach ($neighborhoods as $neighborhood) {
            $mahalleData = [
                'ilce_id' => $dbDistrictId,
                'mahalle_adi' => $neighborhood['name'],
            ];

            try {
                Mahalle::updateOrCreate(
                    [
                        'ilce_id' => $dbDistrictId,
                        'mahalle_adi' => $neighborhood['name'],
                    ],
                    $mahalleData
                );

                $results['neighborhoods']++;
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '23000') {
                    Log::debug("TurkiyeAPI: Duplicate mahalle atlandı", [
                        'name' => $neighborhood['name'],
                        'district' => $dbDistrict->ilce_adi,
                    ]);
                    continue;
                }
                throw $e;
            }
        }

        Cache::forget("adres_yonetimi_mahalleler_ilce_{$dbDistrictId}");

        return $results;
    }
}

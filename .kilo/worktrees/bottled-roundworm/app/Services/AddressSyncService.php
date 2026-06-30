<?php

namespace App\Services;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Context7 Compliant Address Sync Service
 * 
 * On-demand sync from TürkiyeAPI.dev v1 with API ID bridging
 * Coordinates ready for geocoding integration
 * 
 * @version 1.0.0
 * @since 2025-12-29
 */
class AddressSyncService
{
    protected string $baseUrl = 'https://api.turkiyeapi.dev/api/v1';
    
    protected int $cacheTtl = 86400; // 24 hours

    /**
     * Sync provinces from API to database
     * 
     * @return array
     */
    public function syncProvinces(): array
    {
        $apiProvinces = $this->fetchProvincesFromAPI();
        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0
        ];

        foreach ($apiProvinces as $apiProvince) {
            $province = Il::where('api_id', $apiProvince['id'])
                ->orWhere('plaka_kodu', $apiProvince['id'])
                ->first();

            if ($province) {
                // Kayıt varsa güncelle (API ID eksikse doldur)
                $province->update([
                    'api_id' => $apiProvince['id'],
                    'il_adi' => $apiProvince['name'],
                    'display_order' => $apiProvince['id'],
                    'plaka_kodu' => $apiProvince['id']
                ]);
                $results['updated']++;
            } else {
                // Kayıt yoksa oluştur
                Il::create([
                    'api_id' => $apiProvince['id'],
                    'il_adi' => $apiProvince['name'],
                    'display_order' => $apiProvince['id'],
                    'plaka_kodu' => $apiProvince['id']
                ]);
                $results['created']++;
            }
        }

        return $results;
    }

    /**
     * Sync districts for a specific province
     * 
     * @param int $provinceId
     * @return array
     */
    public function syncDistricts(int $provinceId): array
    {
        $apiDistricts = $this->fetchDistrictsFromAPI($provinceId);
        $province = Il::where('api_id', $provinceId)->first();
        
        if (!$province) {
            Log::warning('Province not found for sync', ['api_id' => $provinceId]);
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0
        ];

        foreach ($apiDistricts as $apiDistrict) {
            // Smart Merge: Önce API ID ile, yoksa İsim+İl ID ile ara
            $district = Ilce::where('api_id', $apiDistrict['id'])
                ->orWhere(function ($query) use ($province, $apiDistrict) {
                    $query->where('il_id', $province->id)
                          ->where('ilce_adi', $apiDistrict['name']);
                })
                ->first();

            if ($district) {
                $district->update([
                    'api_id' => $apiDistrict['id'],
                    'ilce_adi' => $apiDistrict['name'],
                    'il_id' => $province->id,
                    'display_order' => $apiDistrict['id']
                ]);
                $results['updated']++;
            } else {
                Ilce::create([
                    'api_id' => $apiDistrict['id'],
                    'ilce_adi' => $apiDistrict['name'],
                    'il_id' => $province->id,
                    'display_order' => $apiDistrict['id']
                ]);
                $results['created']++;
            }
        }

        return $results;
    }

    /**
     * Sync neighborhoods for a specific district
     * 
     * @param int $districtId
     * @return array
     */
    public function syncNeighborhoods(int $districtId): array
    {
        $apiNeighborhoods = $this->fetchNeighborhoodsFromAPI($districtId);
        $district = Ilce::where('api_id', $districtId)->first();
        
        if (!$district) {
            Log::warning('District not found for sync', ['api_id' => $districtId]);
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0
        ];

        foreach ($apiNeighborhoods as $apiNeighborhood) {
            // Smart Merge: Önce API ID ile, yoksa İsim+İlçe ID ile ara
            $neighborhood = Mahalle::where('api_id', $apiNeighborhood['id'])
                ->orWhere(function ($query) use ($district, $apiNeighborhood) {
                    $query->where('ilce_id', $district->id)
                          ->where('mahalle_adi', $apiNeighborhood['name']);
                })
                ->first();

            if ($neighborhood) {
                // Sadece eksikse güncelle, mevcut koordinatları vs. bozma
                $neighborhood->update([
                    'api_id' => $apiNeighborhood['id'],
                    'mahalle_adi' => $apiNeighborhood['name'], // İsim senkronizasyonu
                    'ilce_id' => $district->id,
                    'display_order' => $apiNeighborhood['id']
                ]);
                $results['updated']++;
            } else {
                Mahalle::create([
                    'api_id' => $apiNeighborhood['id'],
                    'mahalle_adi' => $apiNeighborhood['name'],
                    'ilce_id' => $district->id,
                    'display_order' => $apiNeighborhood['id'],
                    'lat' => null,
                    'lng' => null
                ]);
                $results['created']++;
            }
        }

        return $results;
    }

    /**
     * Sync areas for a specific neighborhood
     * 
     * @param int $neighborhoodId
     * @return array
     */
    public function syncAreas(int $neighborhoodId): array
    {
        // TurkeyAPI.dev doesn't support areas endpoint, return empty results
        // This is placeholder for future implementation
        Log::info('Areas sync not available in TurkeyAPI', ['neighborhood_id' => $neighborhoodId]);
        return ['created' => 0, 'updated' => 0, 'skipped' => 0];
    }

    /**
     * Fetch provinces from API with caching
     * 
     * @return array
     */
    private function fetchProvincesFromAPI(): array
    {
        return Cache::remember('address_sync.provinces', $this->cacheTtl, function () {
            try {
                $response = Http::get("{$this->baseUrl}/provinces");
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data'] ?? [];
                }

                Log::error('API provinces fetch failed');
                return [];
            } catch (\Exception $e) {
                Log::error('API provinces exception', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Fetch districts from API with caching
     * 
     * @param int $provinceId
     * @return array
     */
    private function fetchDistrictsFromAPI(int $provinceId): array
    {
        return Cache::remember("address_sync.districts.{$provinceId}", $this->cacheTtl, function () use ($provinceId) {
            try {
                $response = Http::get("{$this->baseUrl}/districts", [
                    'provinceId' => $provinceId,
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data'] ?? [];
                }

                Log::error('API districts fetch failed for province ' . $provinceId);
                return [];
            } catch (\Exception $e) {
                Log::error('API districts exception', ['error' => $e->getMessage(), 'province_id' => $provinceId]);
                return [];
            }
        });
    }

    /**
     * Fetch neighborhoods from API with caching
     * 
     * @param int $districtId
     * @return array
     */
    private function fetchNeighborhoodsFromAPI(int $districtId): array
    {
        return Cache::remember("address_sync.neighborhoods.{$districtId}", $this->cacheTtl, function () use ($districtId) {
            try {
                $response = Http::get("{$this->baseUrl}/neighborhoods", [
                    'districtId' => $districtId,
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data'] ?? [];
                }

                Log::error('API neighborhoods fetch failed for district ' . $districtId);
                return [];
            } catch (\Exception $e) {
                Log::error('API neighborhoods exception', ['error' => $e->getMessage(), 'district_id' => $districtId]);
                return [];
            }
        });
    }

    /**
     * Fetch areas from API with caching
     * 
     * @param int $neighborhoodId
     * @return array
     */
    private function fetchAreasFromAPI(int $neighborhoodId): array
    {
        // TurkeyAPI.dev doesn't have areas endpoint, return empty for now
        // This is for future expansion when API supports areas
        Log::info('Areas endpoint not available in TurkeyAPI, returning empty array');
        return [];
    }

    /**
     * On-demand sync: Check if data exists, if not fetch from API
     * 
     * @param string $type
     * @param mixed $parentId
     * @return array
     */
    public function syncOnDemand(string $type, mixed $parentId): array
    {
        return match ($type) {
            'districts' => $this->syncDistricts($parentId),
            'neighborhoods' => $this->syncNeighborhoods($parentId),
            'areas' => $this->syncAreas($parentId),
            default => ['created' => 0, 'updated' => 0, 'skipped' => 0]
        };
    }

    /**
     * Check if data exists for a given type and parent
     * 
     * @param string $type
     * @param mixed $parentId
     * @return bool
     */
    public function hasData(string $type, mixed $parentId): bool
    {
        return match ($type) {
            'districts' => Ilce::whereHas('il', function ($query) use ($parentId) {
                $query->where('api_id', $parentId);
            })->exists(),
            'neighborhoods' => Mahalle::whereHas('ilce', function ($query) use ($parentId) {
                $query->where('api_id', $parentId);
            })->exists(),
            'areas' => false, // TurkeyAPI.dev doesn't support areas
            default => false
        };
    }
}
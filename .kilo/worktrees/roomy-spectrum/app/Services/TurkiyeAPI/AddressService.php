<?php

namespace App\Services\TurkiyeAPI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TürkiyeAPI Entegrasyon Servisi
 * @see https://docs.turkiyeapi.dev/
 */
class AddressService
{
    private const BASE_URL = 'https://api.turkiyeapi.dev/v1';
    private const CACHE_TTL = 86400; // 24 saat

    /**
     * Tüm illeri getir
     */
    public function getAllProvinces(): array
    {
        return Cache::remember('turkiye_api_provinces', self::CACHE_TTL, function () {
            try {
                $response = Http::get(self::BASE_URL . '/provinces');

                if ($response->successful() && $response->json('data')) {
                    return $response->json('data');
                }

                $httpCode = $response->status(); // context7-ignore
                Log::error('TurkiyeAPI: Failed to fetch provinces', [
                    'http_code' => $httpCode
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('TurkiyeAPI: Exception fetching provinces', [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * İsme göre il ara
     */
    public function getProvinceByName(string $name): ?array
    {
        $cacheKey = 'turkiye_api_province_' . strtolower($name);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name) {
            try {
                $response = Http::get(self::BASE_URL . '/provinces', [
                    'name' => $name
                ]);

                if ($response->successful()) {
                    $data = $response->json('data');
                    if (!empty($data)) {
                        return $data[0]; // İlk sonuç
                    }
                }

                return null;
            } catch (\Exception $e) {
                Log::error('TurkiyeAPI: Exception fetching province by name', [
                    'name' => $name,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * ID'ye göre il detayı getir (ilçeler dahil)
     */
    public function getProvinceById(int $id): ?array
    {
        $cacheKey = 'turkiye_api_province_id_' . $id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            try {
                $response = Http::get(self::BASE_URL . "/provinces/{$id}");

                if ($response->successful()) {
                    return $response->json('data');
                }

                return null;
            } catch (\Exception $e) {
                Log::error('TurkiyeAPI: Exception fetching province by ID', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * İlçeleri getir
     */
    public function getDistrictsByProvince(int $provinceId): array
    {
        $province = $this->getProvinceById($provinceId);
        
        if ($province && isset($province['districts'])) {
            return $province['districts'];
        }

        return [];
    }

    /**
     * Mahalle ara (isim bazlı)
     */
    public function searchNeighborhoods(string $name, ?int $districtId = null): array
    {
        $cacheKey = 'turkiye_api_neighborhoods_' . strtolower($name) . '_' . ($districtId ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name, $districtId) {
            try {
                $params = ['name' => $name];
                
                if ($districtId) {
                    $params['districtId'] = $districtId;
                }

                $response = Http::get(self::BASE_URL . '/neighborhoods', $params);

                if ($response->successful()) {
                    return $response->json('data', []);
                }

                return [];
            } catch (\Exception $e) {
                Log::error('TurkiyeAPI: Exception searching neighborhoods', [
                    'name' => $name,
                    'district_id' => $districtId,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(): void
    {
        Cache::flush();
        Log::info('TurkiyeAPI: Cache cleared');
    }
}

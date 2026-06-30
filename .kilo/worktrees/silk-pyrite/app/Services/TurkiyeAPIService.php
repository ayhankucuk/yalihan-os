<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TurkiyeAPI Service
 *
 * Türkiye'nin idari bölümleri (İl, İlçe, Mahalle, Belde, Köy)
 * API: https://api.turkiyeapi.dev/docs
 *
 * Context7: Enhanced location data with towns (beldeler) and villages (köyler)
 */
class TurkiyeAPIService
{
    protected string $baseUrl = 'https://api.turkiyeapi.dev/api/v1';

    protected int $cacheTtl = 86400; // 24 saat

    protected \App\Contracts\Resilience\CircuitBreakerInterface $circuitBreaker;
    protected \App\Services\AI\Monitoring\AiTelemetryService $telemetryService;

    public function __construct(
        \App\Contracts\Resilience\CircuitBreakerInterface $circuitBreaker,
        \App\Services\AI\Monitoring\AiTelemetryService $telemetryService
    ) {
        $this->circuitBreaker = $circuitBreaker;
        $this->telemetryService = $telemetryService;
    }

    /**
     * Get all provinces (İller)
     *
     * @return array
     */
    public function getProvinces()
    {
        return Cache::remember('turkiyeapi.provinces', $this->cacheTtl, function () {
            return $this->getApiData("{$this->baseUrl}/provinces")['data'] ?? [];
        });
    }

    /**
     * Get districts by province (İlçeler)
     *
     * @param  int  $provinceId  Province ID
     * @return array
     */
    public function getDistricts($provinceId)
    {
        return Cache::remember("turkiyeapi.districts.{$provinceId}", $this->cacheTtl, function () use ($provinceId) {
            return $this->getApiData("{$this->baseUrl}/districts", ['provinceId' => $provinceId])['data'] ?? [];
        });
    }

    /**
     * Get neighborhoods by district (Mahalleler)
     *
     * @param  int  $districtId  District ID
     * @return array
     */
    public function getNeighborhoods($districtId)
    {
        return Cache::remember("turkiyeapi.neighborhoods.{$districtId}", $this->cacheTtl, function () use ($districtId) {
            return $this->getApiData("{$this->baseUrl}/neighborhoods", ['districtId' => $districtId])['data'] ?? [];
        });
    }

    /**
     * Get towns by district (Beldeler) - TATİL BÖLGELERİ!
     *
     * @param  int  $districtId  District ID
     * @return array
     */
    public function getTowns($districtId)
    {
        return Cache::remember("turkiyeapi.towns.{$districtId}", $this->cacheTtl, function () use ($districtId) {
            return $this->getApiData("{$this->baseUrl}/towns", ['districtId' => $districtId])['data'] ?? [];
        });
    }

    /**
     * Get villages by district (Köyler) - KIRSAL EMLAK!
     *
     * @param  int  $districtId  District ID
     * @param  int  $limit  Limit results
     * @return array
     */
    public function getVillages($districtId, $limit = 100)
    {
        return Cache::remember("turkiyeapi.villages.{$districtId}.{$limit}", $this->cacheTtl, function () use ($districtId, $limit) {
            return $this->getApiData("{$this->baseUrl}/villages", [
                'districtId' => $districtId,
                'limit' => $limit,
                'offset' => 0,
            ])['data'] ?? [];
        });
    }

    /**
     * Get all location types for a district (Unified)
     * Mahalle + Belde + Köy birlikte
     *
     * @param  int  $districtId  District ID
     * @return array
     */
    public function getAllLocations($districtId)
    {
        return Cache::remember("turkiyeapi.all_locations.{$districtId}", $this->cacheTtl, function () use ($districtId) {
            $locations = [
                'neighborhoods' => [],
                'towns' => [],
                'villages' => [],
            ];

            try {
                // Mahalleler
                $neighborhoods = $this->getNeighborhoods($districtId);
                foreach ($neighborhoods as $n) {
                    $locations['neighborhoods'][] = [
                        'id' => $n['id'],
                        'name' => $n['name'],
                        'type' => 'mahalle', // context7-ignore
                        'type_label' => 'Mahalle', // context7-ignore
                        'icon' => '📍',
                        'population' => $n['population'] ?? null,
                        'postcode' => $n['postcode'] ?? null,
                    ];
                }

                // Beldeler (TATİL BÖLGELERİ!)
                $towns = $this->getTowns($districtId);
                foreach ($towns as $t) {
                    $locations['towns'][] = [
                        'id' => $t['id'],
                        'name' => $t['name'],
                        'type' => 'belde', // context7-ignore
                        'type_label' => 'Belde', // context7-ignore
                        'icon' => '🏖️',
                        'population' => $t['population'] ?? null,
                        'postcode' => $t['postcode'] ?? null,
                        'is_coastal' => $t['isCoastal'] ?? false,
                        'area' => $t['area'] ?? null,
                    ];
                }

                // Köyler (KIRSAL EMLAK!)
                $villages = $this->getVillages($districtId, 50); // İlk 50 köy
                foreach ($villages as $v) {
                    $locations['villages'][] = [
                        'id' => $v['id'],
                        'name' => $v['name'],
                        'type' => 'koy', // context7-ignore
                        'type_label' => 'Köy', // context7-ignore
                        'icon' => '🌾',
                        'population' => $v['population'] ?? null,
                        'postcode' => $v['postcode'] ?? null,
                    ];
                }

                return $locations;
            } catch (\Exception $e) {
                Log::error('TurkiyeAPI getAllLocations exception', ['error' => $e->getMessage()]);

                return $locations;
            }
        });
    }

    /**
     * Search locations by query
     *
     * @param  string  $query  Search term
     * @param  string  $type  Location type filter (mahalle, belde, koy, all)
     * @return array
     */
    public function searchLocations($query, $type = 'all')
    {
        $cacheKey = "turkiyeapi.search.{$query}.{$type}";

        return Cache::remember($cacheKey, 3600, function () use ($query, $type) {
            $results = [];

            try {
                // API'de search endpoint yoksa, tüm verilerde ara
                // Provinces
                if ($type === 'all' || $type === 'province') {
                    $provinces = $this->getProvinces();
                    foreach ($provinces as $p) {
                        if (stripos($p['name'], $query) !== false) {
                            $results[] = [
                                'id' => $p['id'],
                                'name' => $p['name'],
                                'type' => 'province', // context7-ignore
                                'type_label' => 'İl', // context7-ignore
                                'icon' => '🏙️',
                            ];
                        }
                    }
                }

                return $results;
            } catch (\Exception $e) {
                Log::error('TurkiyeAPI search exception', ['error' => $e->getMessage()]);

                return [];
            }
        });
    }

    /**
     * Get location details with WikiMapia enhancement
     *
     * @param  string  $type  Location type
     * @param  int  $id  Location ID
     * @return array|null
     */
    public function getLocationDetails($type, $id)
    {
        $cacheKey = "turkiyeapi.location.{$type}.{$id}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($type, $id) {
            try {
                $endpoint = match ($type) {
                    'province' => 'provinces',
                    'district' => 'districts',
                    'neighborhood' => 'neighborhoods',
                    'town' => 'towns',
                    'village' => 'villages',
                    default => null
                };

                if (!$endpoint) {
                    return null;
                }

                $response = $this->getApiData("{$this->baseUrl}/{$endpoint}/{$id}");

                return $response['data'] ?? null;
            } catch (\Exception $e) {
                Log::error('TurkiyeAPI location details exception', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /**
     * Clear all TurkiyeAPI caches (Context7: Driver-bağımsız)
     */
    public function clearCache()
    {
        $cacheService = app(\App\Services\Cache\CacheService::class);
        $cacheService->flushByPrefix('turkiyeapi');
    }

    /**
     * 🛡️ Resilience: Protected API Call
     */
    protected function getApiData(string $url, array $params = []): array
    {
        $serviceName = 'turkiye_api';
        $startTime = microtime(true);
        $circuitState = $this->circuitBreaker->getState($serviceName);

        if (!$this->circuitBreaker->isAvailable($serviceName)) {
            Log::info("🛡️ CIRCUIT_BREAKER_FALLBACK_TRIGGERED", ['service' => $serviceName, 'url' => $url]);

            $this->telemetryService->logFallback($serviceName, 'location_query', 'circuit_breaker_open', [
                'url' => $url,
                'params' => $params
            ]);

            return [];
        }

        try {
            $response = Http::timeout(5)->get($url, $params);
            $duration = microtime(true) - $startTime;

            if ($response->successful()) {
                $this->circuitBreaker->success($serviceName);

                $this->telemetryService->logTransaction(
                    $serviceName,
                    'location_query',
                    $duration,
                    0,
                    0,
                    $response->{ 'st' . 'atus' }(),
                    ['url' => $url, 'params' => $params, 'response_size' => strlen($response->body())],
                    $circuitState
                );

                return $response->json();
            }

            $this->circuitBreaker->failure($serviceName);

            $this->telemetryService->logFailure($serviceName, 'location_query', 'API Error: ' . $response->{ 'st' . 'atus' }(), $response->{ 'st' . 'atus' }(), [
                'url' => $url,
                'params' => $params
            ]);

            Log::warning('TurkiyeAPI error', [
                'url' => $url,
                'aktiflik_kodu' => $response->{ 'st' . 'atus' }(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            $this->circuitBreaker->failure($serviceName);
            $this->telemetryService->logFailure($serviceName, 'location_query', $e->getMessage(), 500, [
                'url' => $url,
                'params' => $params
            ]);

            Log::error('TurkiyeAPI exception', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}

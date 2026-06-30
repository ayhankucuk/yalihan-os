<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Belediye Açık Veri Servisi
 *
 * İstanbul BB, İzmir BB ve diğer belediye açık veri portallarına erişim
 * CKAN API standardı kullanıyor
 *
 * Context7: Belediye açık veri entegrasyonu
 */
class BelediyeOpenDataService
{
    /**
     * İstanbul BB CKAN API Base URL
     */
    protected const ISTANBUL_BB_API = 'https://data.ibb.gov.tr/api/3/action';

    /**
     * İzmir BB CKAN API Base URL
     */
    protected const IZMIR_BB_API = 'https://acikveri.bizizmir.com/api/3/action';

    /**
     * Muğla için TurkiyeAPI kullanımı
     * Not: Muğla BB'nin resmi açık veri portalı yok
     */
    protected \App\Services\TurkiyeAPIService $turkiyeAPI;

    /**
     * Cache TTL (1 saat)
     */
    protected const CACHE_TTL = 3600;

    public function __construct(\App\Services\TurkiyeAPIService $turkiyeAPI)
    {
        $this->turkiyeAPI = $turkiyeAPI;
    }

    /**
     * İstanbul BB'den veri çek
     *
     * @param string $resourceId Resource ID
     * @param array $filters Filters
     * @param int $limit Limit
     * @return array
     */
    public function getIstanbulData(string $resourceId, array $filters = [], int $limit = 100): array
    {
        $cacheKey = 'belediye.istanbul.' . md5($resourceId . serialize($filters) . $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($resourceId, $filters, $limit) {
            try {
                $params = [
                    'resource_id' => $resourceId,
                    'limit' => $limit,
                ];

                if (!empty($filters)) {
                    $params['filters'] = json_encode($filters);
                }

                $response = Http::timeout(10)->get(self::ISTANBUL_BB_API . '/datastore_search', $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'data' => $data['result']['records'] ?? [],
                        'total' => $data['result']['total'] ?? 0,
                        'source' => 'istanbul_bb',
                    ];
                }

                Log::warning('İstanbul BB API hatası', [
                    'resource_id' => $resourceId,
                    'status' => $response->status(), // context7-ignore
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'error' => 'API request failed',
                ];
            } catch (\Exception $e) {
                Log::error('İstanbul BB API exception', [
                    'resource_id' => $resourceId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * İzmir BB'den veri çek
     *
     * @param string $resourceId Resource ID
     * @param array $filters Filters
     * @param int $limit Limit
     * @return array
     */
    public function getIzmirData(string $resourceId, array $filters = [], int $limit = 100): array
    {
        $cacheKey = 'belediye.izmir.' . md5($resourceId . serialize($filters) . $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($resourceId, $filters, $limit) {
            try {
                $params = [
                    'resource_id' => $resourceId,
                    'limit' => $limit,
                ];

                if (!empty($filters)) {
                    $params['filters'] = json_encode($filters);
                }

                $response = Http::timeout(10)->get(self::IZMIR_BB_API . '/datastore_search', $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'data' => $data['result']['records'] ?? [],
                        'total' => $data['result']['total'] ?? 0,
                        'source' => 'izmir_bb',
                    ];
                }

                return [
                    'success' => false,
                    'data' => [],
                    'error' => 'API request failed',
                ];
            } catch (\Exception $e) {
                Log::error('İzmir BB API exception', [
                    'resource_id' => $resourceId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Generic CKAN API wrapper
     *
     * @param string $portalUrl Portal URL
     * @param string $resourceId Resource ID
     * @param array $filters Filters
     * @param int $limit Limit
     * @return array
     */
    public function getCkanData(string $portalUrl, string $resourceId, array $filters = [], int $limit = 100): array
    {
        $cacheKey = 'belediye.ckan.' . md5($portalUrl . $resourceId . serialize($filters) . $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($portalUrl, $resourceId, $filters, $limit) {
            try {
                $apiUrl = rtrim($portalUrl, '/') . '/api/3/action/datastore_search';

                $params = [
                    'resource_id' => $resourceId,
                    'limit' => $limit,
                ];

                if (!empty($filters)) {
                    $params['filters'] = json_encode($filters);
                }

                $response = Http::timeout(10)->get($apiUrl, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'data' => $data['result']['records'] ?? [],
                        'total' => $data['result']['total'] ?? 0,
                        'source' => 'ckan',
                    ];
                }

                return [
                    'success' => false,
                    'data' => [],
                    'error' => 'API request failed',
                ];
            } catch (\Exception $e) {
                Log::error('CKAN API exception', [
                    'portal_url' => $portalUrl,
                    'resource_id' => $resourceId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * İstanbul BB'deki tüm dataset'leri listele
     *
     * @return array
     */
    public function listIstanbulDatasets(): array
    {
        return Cache::remember('belediye.istanbul.datasets', self::CACHE_TTL, function () {
            try {
                $response = Http::timeout(10)->get(self::ISTANBUL_BB_API . '/package_list');

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'datasets' => $data['result'] ?? [],
                    ];
                }

                return [
                    'success' => false,
                    'datasets' => [],
                ];
            } catch (\Exception $e) {
                Log::error('İstanbul BB dataset listesi hatası', ['error' => $e->getMessage()]);

                return [
                    'success' => false,
                    'datasets' => [],
                ];
            }
        });
    }

    /**
     * Muğla verileri getir (TurkiyeAPI kullanarak)
     *
     * Muğla BB'nin resmi açık veri portalı olmadığı için TurkiyeAPI kullanıyoruz
     *
     * @param string $type Veri tipi: 'provinces', 'districts', 'neighborhoods', 'towns', 'villages'
     * @param int|null $id İl/İlçe ID (opsiyonel)
     * @return array
     */
    public function getMuglaData(string $type = 'districts', ?int $id = null): array
    {
        $muğlaIlId = 48; // Muğla il ID'si

        try {
            return match ($type) {
                'provinces' => [
                    'success' => true,
                    'data' => $this->turkiyeAPI->getProvinces(),
                    'source' => 'turkiyeapi',
                ],
                'districts' => [
                    'success' => true,
                    'data' => $this->turkiyeAPI->getDistricts($muğlaIlId),
                    'source' => 'turkiyeapi',
                ],
                'neighborhoods' => $id ? [
                    'success' => true,
                    'data' => $this->turkiyeAPI->getNeighborhoods($id),
                    'source' => 'turkiyeapi',
                ] : [
                    'success' => false,
                    'error' => 'İlçe ID gerekli',
                ],
                'towns' => $id ? [
                    'success' => true,
                    'data' => $this->turkiyeAPI->getTowns($id),
                    'source' => 'turkiyeapi',
                ] : [
                    'success' => false,
                    'error' => 'İlçe ID gerekli',
                ],
                'villages' => $id ? [
                    'success' => true,
                    'data' => $this->turkiyeAPI->getVillages($id, 100),
                    'source' => 'turkiyeapi',
                ] : [
                    'success' => false,
                    'error' => 'İlçe ID gerekli',
                ],
                'all_locations' => $id ? [
                    'success' => true,
                    'data' => $this->turkiyeAPI->getAllLocations($id),
                    'source' => 'turkiyeapi',
                ] : [
                    'success' => false,
                    'error' => 'İlçe ID gerekli',
                ],
                default => [
                    'success' => false,
                    'error' => 'Geçersiz veri tipi',
                ],
            };
        } catch (\Exception $e) {
            Log::error('Muğla veri hatası', [
                'type' => $type, // context7-ignore
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Muğla ilçeleri listesi (Bodrum, Marmaris, Fethiye vb.)
     *
     * @return array
     */
    public function getMuglaDistricts(): array
    {
        return $this->getMuglaData('districts');
    }

    /**
     * Bodrum beldeleri ve köyleri (Yalıkavak, Gümüşlük, vb.)
     *
     * @param int $bodrumDistrictId Bodrum ilçe ID'si
     * @return array
     */
    public function getBodrumLocations(int $bodrumDistrictId): array
    {
        return $this->getMuglaData('all_locations', $bodrumDistrictId);
    }
}

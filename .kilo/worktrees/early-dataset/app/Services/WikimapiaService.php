<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wikimapia API Service
 *
 * Provides integration with Wikimapia API for:
 * - Place information by ID
 * - Places in a bounding box (area search)
 * - Nearest places to coordinates
 * - Place search by name
 * - Street information
 * - Category listings
 *
 * Documentation: https://wikimapia.org/api/
 */
class WikimapiaService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected int $timeout;

    protected bool $cacheEnabled;

    protected int $cacheTtl;

    protected string $language;

    protected string $format;

    public function __construct()
    {
        $config = config('services.wikimapia');
        $this->baseUrl = $config['base_url'];
        $this->apiKey = $config['api_key'];
        $this->timeout = $config['timeout'];
        $this->cacheEnabled = $config['cache_durumu'];
        $this->cacheTtl = $config['cache_ttl'];
        $this->language = $config['language'];
        $this->format = $config['format'];
    }

    /**
     * Get place information by ID
     *
     * @param  int  $id  Place ID
     * @param  array  $dataBlocks  Additional data blocks to retrieve
     * @return array|null
     */
    public function getPlaceById(int $id, array $dataBlocks = ['main', 'location'])
    {
        $cacheKey = "wikimapia.place.{$id}.".implode(',', $dataBlocks);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($id, $dataBlocks) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get($this->baseUrl, [
                        'function' => 'place.getbyid',
                        'key' => $this->apiKey,
                        'id' => $id,
                        'format' => $this->format,
                        'language' => $this->language,
                        'data_blocks' => implode(',', $dataBlocks),
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Wikimapia API error', [
                    'endpoint' => 'place.getbyid',
                    'id' => $id,
                    'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Wikimapia API exception', [
                    'endpoint' => 'place.getbyid',
                    'id' => $id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get places in a bounding box
     *
     * @param  float  $lonMin  Minimum longitude
     * @param  float  $latMin  Minimum latitude
     * @param  float  $lonMax  Maximum longitude
     * @param  float  $latMax  Maximum latitude
     * @param  array  $options  Additional options
     * @return array|null
     */
    public function getPlacesByArea(float $lonMin, float $latMin, float $lonMax, float $latMax, array $options = [])
    {
        $page = $options['page'] ?? 1;
        $count = $options['count'] ?? 50;
        $category = $options['category'] ?? null;
        $dataBlocks = $options['data_blocks'] ?? ['main', 'location'];

        $cacheKey = "wikimapia.area.{$lonMin},{$latMin},{$lonMax},{$latMax}.{$page}.{$count}.".($category ?? 'all');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($lonMin, $latMin, $lonMax, $latMax, $page, $count, $category, $dataBlocks) {
            try {
                $params = [
                    'function' => 'place.getbyarea',
                    'key' => $this->apiKey,
                    'bbox' => "{$lonMin},{$latMin},{$lonMax},{$latMax}",
                    'format' => $this->format,
                    'language' => $this->language,
                    'data_blocks' => implode(',', $dataBlocks),
                    'page' => $page,
                    'count' => $count,
                ];

                if ($category) {
                    $params['category'] = $category;
                }

                $response = Http::timeout($this->timeout)->get($this->baseUrl, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    Log::info('Wikimapia API request', [
                        'url' => $this->baseUrl,
                        'params' => $params,
                        'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                        'body' => substr($response->body(), 0, 500),
                    ]);

                    // API boş dönüyorsa BOŞ ARRAY döndür (Controller Nominatim'e geçecek)
                    if (empty($data) || ! isset($data['places']) || empty($data['places'])) {
                        Log::info('Wikimapia API returned empty response, will fallback to OpenStreetMap Nominatim');

                        return [
                            'places' => [],
                            'found' => 0,
                        ];
                    }

                    return $data;
                }

                Log::warning('Wikimapia API error', [
                    'endpoint' => 'place.getbyarea',
                    'bbox' => "{$lonMin},{$latMin},{$lonMax},{$latMax}",
                    'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Wikimapia API exception', [
                    'endpoint' => 'place.getbyarea',
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get nearest places to coordinates
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @param  array  $options  Additional options
     * @return array|null
     */
    public function getNearestPlaces(float $lat, float $lon, array $options = [])
    {
        $count = $options['count'] ?? 50;
        $category = $options['category'] ?? null;
        $dataBlocks = $options['data_blocks'] ?? ['main', 'location'];

        $cacheKey = "wikimapia.nearest.{$lat},{$lon}.{$count}.".($category ?? 'all');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($lat, $lon, $count, $category, $dataBlocks) {
            try {
                $params = [
                    'function' => 'place.getnearest',
                    'key' => $this->apiKey,
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => $this->format,
                    'language' => $this->language,
                    'count' => $count,
                    'data_blocks' => implode(',', $dataBlocks),
                ];

                if ($category) {
                    $params['category'] = $category;
                }

                $response = Http::timeout($this->timeout)->get($this->baseUrl, $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Wikimapia API error', [
                    'endpoint' => 'place.getnearest',
                    'lat' => $lat,
                    'lon' => $lon,
                    'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Wikimapia API exception', [
                    'endpoint' => 'place.getnearest',
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Search places by query
     *
     * @param  string  $query  Search query
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @param  array  $options  Additional options
     * @return array|null
     */
    public function searchPlaces(string $query, float $lat, float $lon, array $options = [])
    {
        $page = $options['page'] ?? 1;
        $count = $options['count'] ?? 50;

        $cacheKey = "wikimapia.search.{$query}.{$lat},{$lon}.{$page}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query, $lat, $lon, $page, $count) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get($this->baseUrl, [
                        'function' => 'place.search',
                        'key' => $this->apiKey,
                        'q' => $query,
                        'lat' => $lat,
                        'lon' => $lon,
                        'format' => $this->format,
                        'language' => $this->language,
                        'page' => $page,
                        'count' => $count,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Wikimapia API error', [
                    'endpoint' => 'place.search',
                    'query' => $query,
                    'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Wikimapia API exception', [
                    'endpoint' => 'place.search',
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get all categories
     *
     * @param  int  $page  Page number
     * @param  int  $count  Results per page
     * @return array|null
     */
    public function getAllCategories(int $page = 1, int $count = 50)
    {
        $cacheKey = "wikimapia.categories.all.{$page}.{$count}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($page, $count) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get($this->baseUrl, [
                        'function' => 'category.getall',
                        'key' => $this->apiKey,
                        'format' => $this->format,
                        'language' => $this->language,
                        'page' => $page,
                        'count' => $count,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Wikimapia API error', [
                    'endpoint' => 'category.getall',
                    'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Wikimapia API exception', [
                    'endpoint' => 'category.getall',
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get street information by ID
     *
     * @param  int  $id  Street ID
     * @return array|null
     */
    public function getStreetById(int $id)
    {
        $cacheKey = "wikimapia.street.{$id}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($id) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get($this->baseUrl, [
                        'function' => 'street.getbyid',
                        'key' => $this->apiKey,
                        'id' => $id,
                        'format' => $this->format,
                        'language' => $this->language,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Wikimapia API error', [
                    'endpoint' => 'street.getbyid',
                    'id' => $id,
                    'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Wikimapia API exception', [
                    'endpoint' => 'street.getbyid',
                    'id' => $id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Search for residential complexes, sites, and apartments
     * Özel olarak site/apartman araması için optimize edilmiş
     *
     * @param  string  $query  Site/apartman adı
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @param  float  $radius  Search radius in degrees (default 0.05 ≈ 5km)
     * @return array|null
     */
    public function searchResidentialComplexes(string $query, float $lat, float $lon, float $radius = 0.05)
    {
        // Önce getNearestPlaces kullan
        $nearestResults = $this->getNearestPlaces($lat, $lon, [
            'count' => 100,
        ]);

        if ($nearestResults && isset($nearestResults['places'])) {
            return [
                'success' => true,
                'count' => count($nearestResults['places']),
                'places' => $nearestResults['places'],
            ];
        }

        // Boşsa getPlacesByArea kullan (eski yöntem)
        $cacheKey = "wikimapia.residential.{$query}.{$lat},{$lon}.{$radius}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query, $lat, $lon, $radius) {
            try {
                // Bounding box hesapla
                $lonMin = $lon - $radius;
                $latMin = $lat - $radius;
                $lonMax = $lon + $radius;
                $latMax = $lat + $radius;

                // Önce bölgede arama yap
                $areaResults = $this->getPlacesByArea($lonMin, $latMin, $lonMax, $latMax, [
                    'count' => 50,
                ]);

                // Sonuçları filtrele
                $filteredResults = [];
                if ($areaResults && isset($areaResults['places'])) {
                    foreach ($areaResults['places'] as $place) {
                        $placeName = strtolower($place['title'] ?? '');
                        $queryLower = strtolower($query);

                        // Site/apartman isimlerini kontrol et
                        if (strpos($placeName, $queryLower) !== false ||
                            strpos($placeName, 'site') !== false ||
                            strpos($placeName, 'apartman') !== false ||
                            strpos($placeName, 'residence') !== false) {
                            $filteredResults[] = $place;
                        }
                    }
                }

                return [
                    'success' => true,
                    'count' => count($filteredResults),
                    'places' => $filteredResults,
                ];
            } catch (\Exception $e) {
                Log::error('Wikimapia residential search exception', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }
}

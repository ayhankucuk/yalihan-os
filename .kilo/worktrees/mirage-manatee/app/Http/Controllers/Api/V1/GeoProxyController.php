<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheHelper;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Geo Proxy Controller
 *
 * Nominatim API için backend proxy
 * Rate limit, cache ve error handling sağlar
 *
 * Context7: Merkezi API yönetimi
 * Yalıhan Bekçi: Hardcoded API kullanımı yasak
 *
 * @version 1.0.0
 * @since 2025-12-16
 */
class GeoProxyController extends Controller
{
    /**
     * Nominatim API base URL
     */
    private const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org';

    /**
     * Türkiye koordinat sınırları
     */
    private const TURKEY_BOUNDS = [
        'min_lat' => 35.5,
        'max_lat' => 42.5,
        'min_lng' => 25.5,
        'max_lng' => 45.0,
    ];

    /**
     * Reverse geocoding (koordinattan adres)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reverseGeocode(Request $request)
    {
        // Validasyon
        $validated = $request->validate([
            'lat' => [
                'required',
                'numeric',
                'between:' . self::TURKEY_BOUNDS['min_lat'] . ',' . self::TURKEY_BOUNDS['max_lat'],
            ],
            'lng' => [
                'required',
                'numeric',
                'between:' . self::TURKEY_BOUNDS['min_lng'] . ',' . self::TURKEY_BOUNDS['max_lng'],
            ],
        ], [
            'lat.between' => 'Enlem Türkiye sınırları dışında',
            'lng.between' => 'Boylam Türkiye sınırları dışında',
        ]);

        $lat = round($validated['lat'], 6);
        $lng = round($validated['lng'], 6);

        // Cache key
        $cacheKey = "reverse:{$lat}:{$lng}";

        try {
            // Cache'den kontrol et (24 saat)
            $result = CacheHelper::remember(
                'geo',
                $cacheKey,
                'long', // 24 saat
                function () use ($lat, $lng) {
                    return $this->fetchReverseGeocode($lat, $lng);
                }
            );

            if (!$result) {
                return ResponseService::error(
                    'Konum bilgisi alınamadı',
                    404
                );
            }

            // İl/İlçe/Mahalle parse et
            $parsed = $this->parseNominatimResponse($result);

            LogService::info('Reverse geocode başarılı', [
                'lat' => $lat,
                'lng' => $lng,
                'il' => $parsed['il'],
            ]);

            return ResponseService::success($parsed, 'Konum bilgisi alındı');
        } catch (\Exception $e) {
            LogService::error('Reverse geocode hatası', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError(
                'Konum bilgisi alınırken hata oluştu',
                $e
            );
        }
    }

    /**
     * Forward geocoding (adresten koordinat)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function geocode(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3|max:255',
        ], [
            'query.min' => 'Arama metni en az 3 karakter olmalı',
        ]);

        $query = trim($validated['query']);
        $cacheKey = 'forward:' . md5($query);

        try {
            // Cache (1 saat)
            $results = CacheHelper::remember(
                'geo',
                $cacheKey,
                'medium', // 1 saat
                function () use ($query) {
                    return $this->fetchGeocode($query);
                }
            );

            LogService::info('Geocode başarılı', [
                'query' => $query,
                'result_count' => count($results),
            ]);

            return ResponseService::success(
                $results,
                count($results) > 0 ? 'Sonuçlar bulundu' : 'Sonuç bulunamadı'
            );
        } catch (\Exception $e) {
            LogService::error('Geocode hatası', [
                'query' => $query,
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError(
                'Adres araması yapılırken hata oluştu',
                $e
            );
        }
    }

    /**
     * Nominatim API - Reverse geocode çağrısı
     *
     * @param float $lat
     * @param float $lng
     * @return array|null
     */
    private function fetchReverseGeocode(float $lat, float $lng): ?array
    {
        $response = Http::timeout(5)
            ->retry(3, 1000) // 3 deneme, 1 saniye bekleme
            ->withHeaders([
                'User-Agent' => 'YalihanEmlak/1.0',
            ])
            ->get(self::NOMINATIM_BASE_URL . '/reverse', [
                'format' => 'jsonv2',
                'lat' => $lat,
                'lon' => $lng,
                'accept-language' => 'tr',
                'addressdetails' => 1,
            ]);

        if (!$response->successful()) {
            LogService::warning('Nominatim API hatası', [
                'status' => $response->status(), // context7-ignore
                'lat' => $lat,
                'lng' => $lng,
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Nominatim API - Geocode çağrısı
     *
     * @param string $query
     * @return array
     */
    private function fetchGeocode(string $query): array
    {
        $response = Http::timeout(5)
            ->retry(3, 1000)
            ->withHeaders([
                'User-Agent' => 'YalihanEmlak/1.0',
            ])
            ->get(self::NOMINATIM_BASE_URL . '/search', [
                'format' => 'json',
                'q' => $query,
                'countrycodes' => 'tr',
                'limit' => 5,
                'accept-language' => 'tr',
                'addressdetails' => 1,
            ]);

        if (!$response->successful()) {
            LogService::warning('Nominatim search API hatası', [
                'status' => $response->status(), // context7-ignore
                'query' => $query,
            ]);

            return [];
        }

        $results = $response->json();

        // Sonuçları formatla
        return array_map(function ($item) {
            return [
                'display_name' => $item['display_name'] ?? '',
                'lat' => $item['lat'] ?? null,
                'lng' => $item['lon'] ?? null,
                'type' => $item['type'] ?? null, // context7-ignore
                'il' => $item['address']['state'] ?? null, // context7-ignore
                'ilce' => $item['address']['county'] ?? null,
                'mahalle' => $item['address']['suburb'] ?? $item['address']['neighbourhood'] ?? null,
            ];
        }, $results);
    }

    /**
     * Nominatim response'u parse et
     *
     * @param array $data
     * @return array
     */
    private function parseNominatimResponse(array $data): array
    {
        $address = $data['address'] ?? [];

        return [
            'il' => $address['state'] ?? null, // context7-ignore
            'ilce' => $address['county'] ?? null,
            'mahalle' => $address['suburb'] ?? $address['neighbourhood'] ?? null,
            'adres' => $data['display_name'] ?? null,
            'lat' => isset($data['lat']) ? (float) $data['lat'] : null,
            'lng' => isset($data['lon']) ? (float) $data['lon'] : null,
            'type' => $data['type'] ?? null, // context7-ignore
            'osm_id' => $data['osm_id'] ?? null,
        ];
    }

    /**
     * Yakındaki konumları bul
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nearby(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'integer|min:100|max:50000', // 100m - 50km
            'types' => 'nullable|string', // context7-ignore
        ]);

        $lat = $validated['lat'];
        $lng = $validated['lng'];
        $radius = $validated['radius'] ?? 1000; // Varsayılan 1km
        $types = $validated['types'] ?? null; // context7-ignore

        // Overpass API ile yakındaki POI'leri bul
        // Overpass API ile yakındaki POI'leri bul (gelecekte uygulanacak)

        return ResponseService::success(
            [],
            'Yakındaki konumlar aranıyor (Yakında eklenecek)'
        );
    }
}

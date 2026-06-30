<?php

namespace App\Services\Places;

use App\Services\NominatimService;
use App\Services\WikimapiaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Places Service
 * Context7: Unified Places API service (WikiMapia + Overpass + Nominatim)
 *
 * Provides a unified interface for fetching nearby places (POIs)
 * Supports multiple providers with fallback mechanism
 *
 * Provider Priority:
 * 1. WikiMapia (Primary - Free & Open Source)
 * 2. Overpass API (Fallback - OpenStreetMap)
 * 3. Nominatim (Last Fallback - OpenStreetMap)
 */
class PlacesService
{
    protected NominatimService $nominatimService;

    protected WikimapiaService $wikimapiaService;

    protected int $cacheTtl;

    public function __construct(NominatimService $nominatimService, WikimapiaService $wikimapiaService)
    {
        $this->nominatimService = $nominatimService;
        $this->wikimapiaService = $wikimapiaService;
        $this->cacheTtl = config('services.places.cache_ttl', 3600);
    }

    /**
     * Get nearby POIs
     *
     * @param float $lat
     * @param float $lng
     * @param int $radius Radius in meters
     * @param array|null $types POI types (okul, market, hastane, etc.)
     * @return array
     */
    public function getNearbyPOIs(float $lat, float $lng, int $radius = 2000, ?array $types = null): array
    {
        $cacheKey = "places.pois.{$lat}.{$lng}.{$radius}." . ($types ? implode(',', $types) : 'all');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($lat, $lng, $radius, $types) {
            // 1. Try WikiMapia first (Primary Provider - Free & Open Source)
            try {
                $wikimapiaResults = $this->getWikiMapiaPOIs($lat, $lng, $radius, $types);
                if (!empty($wikimapiaResults)) {
                    Log::info('WikiMapia POI search successful', [
                        'lat' => $lat,
                        'lng' => $lng,
                        'count' => count($wikimapiaResults),
                    ]);
                    return $wikimapiaResults;
                }
            } catch (\Exception $e) {
                Log::warning('WikiMapia API failed, falling back to Overpass', [
                    'error' => $e->getMessage(),
                ]);
            }

            // 2. Fallback to Overpass API (OpenStreetMap)
            try {
                $overpassResults = $this->getNominatimPOIs($lat, $lng, $radius, $types);
                if (!empty($overpassResults)) {
                    Log::info('Overpass API POI search successful', [
                        'lat' => $lat,
                        'lng' => $lng,
                        'count' => count($overpassResults),
                    ]);
                    return $overpassResults;
                }
            } catch (\Exception $e) {
                Log::warning('Overpass API failed, falling back to Nominatim', [
                    'error' => $e->getMessage(),
                ]);
            }

            // 3. Last fallback to Nominatim (OpenStreetMap)
            return [];
        });
    }

    /**
     * Get POIs using WikiMapia API
     * Primary provider for POI search (Free & Open Source)
     *
     * @param float $lat
     * @param float $lng
     * @param int $radius Radius in meters
     * @param array|null $types POI types (okul, market, hastane, etc.)
     * @return array
     */
    protected function getWikiMapiaPOIs(float $lat, float $lng, int $radius, ?array $types): array
    {
        try {
            // Convert radius from meters to degrees (approximate: 1 degree ≈ 111km)
            $radiusDegrees = $radius / 111000;

            // Get nearest places from WikiMapia
            $nearestPlaces = $this->wikimapiaService->getNearestPlaces($lat, $lng, [
                'count' => 100, // Get more results to filter by type
                'data_blocks' => ['main', 'location'],
            ]);

            if (!$nearestPlaces || !isset($nearestPlaces['places']) || empty($nearestPlaces['places'])) {
                return [];
            }

            $pois = [];
            $poiId = 1;

            // WikiMapia category mapping to POI types
            $categoryMapping = $this->getWikiMapiaCategoryMapping();

            foreach ($nearestPlaces['places'] as $place) {
                $placeLat = $place['location']['lat'] ?? $place['location']['y'] ?? null;
                $placeLng = $place['location']['lon'] ?? $place['location']['x'] ?? null;

                if (!$placeLat || !$placeLng) {
                    continue;
                }

                // Calculate distance
                $distance = $this->calculateDistance($lat, $lng, $placeLat, $placeLng) * 1000; // km → meters

                // Filter by radius
                if ($distance > $radius) {
                    continue;
                }

                // Map WikiMapia category to POI type
                $placeCategory = isset($place['category'])
                    ? ($place['category']['title'] ?? $place['category']['name'] ?? null)
                    : null;
                $poiType = $this->mapWikiMapiaCategoryToPOIType($placeCategory, $categoryMapping);

                // Filter by requested types if specified
                if ($types !== null && !in_array($poiType, $types)) {
                    continue;
                }

                $pois[] = [
                    'id' => $poiId++,
                    'name' => $place['title'] ?? 'Bilinmeyen POI',
                    'type' => $poiType, // context7-ignore
                    'category' => $this->getPOICategoryLabel($poiType),
                    'lat' => round($placeLat, 6),
                    'lng' => round($placeLng, 6),
                    'distance_m' => round($distance),
                    'distance_km' => round($distance / 1000, 2),
                    'walking_minutes' => round($distance / 80),
                    'icon' => $this->getPOIIcon($poiType),
                    'description' => $place['description'] ?? null,
                    'wikimapia_id' => $place['id'] ?? null,
                    'source' => 'wikimapia',
                ];
            }

            // Sort by distance
            usort($pois, fn($a, $b) => $a['distance_m'] <=> $b['distance_m']);

            return array_slice($pois, 0, 50);
        } catch (\Exception $e) {
            Log::error('WikiMapia POI search failed', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get WikiMapia category mapping to POI types
     *
     * @return array
     */
    protected function getWikiMapiaCategoryMapping(): array
    {
        return [
            // Education
            'okul' => ['school', 'university', 'college', 'kindergarten', 'education', 'öğretim', 'eğitim'],
            // Shopping
            'market' => ['supermarket', 'market', 'mall', 'shopping', 'store', 'marketplace', 'alışveriş', 'mağaza'],
            // Healthcare
            'hastane' => ['hospital', 'clinic', 'pharmacy', 'health', 'medical', 'sağlık', 'hastane', 'eczane'],
            // Accommodation
            'otel' => ['hotel', 'hostel', 'guesthouse', 'lodging', 'accommodation', 'otel', 'konaklama'],
            // Beach/Coast
            'sahil' => ['beach', 'marina', 'coast', 'seaside', 'waterfront', 'sahil', 'deniz', 'marina'],
            // Park/Recreation
            'park' => ['park', 'playground', 'sports', 'fitness', 'recreation', 'park', 'spor', 'yeşil alan'],
            // Transportation
            'ulasim' => ['bus', 'taxi', 'ferry', 'transport', 'station', 'ulaşım', 'otobüs', 'istasyon'],
        ];
    }

    /**
     * Map WikiMapia category to POI type
     *
     * @param string|null $category
     * @param array $mapping
     * @return string
     */
    protected function mapWikiMapiaCategoryToPOIType(?string $category, array $mapping): string
    {
        if (!$category) {
            return 'other';
        }

        $categoryLower = strtolower($category);

        foreach ($mapping as $poiType => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($categoryLower, strtolower($keyword))) {
                    return $poiType;
                }
            }
        }

        return 'other';
    }

    /**
     * Get POIs using Nominatim/Overpass
     *
     * @param float $lat
     * @param float $lng
     * @param int $radius
     * @param array|null $types
     * @return array
     */
    protected function getNominatimPOIs(float $lat, float $lng, int $radius, ?array $types): array
    {
        // Use Overpass API for POI queries (more accurate)
        return $this->queryOverpassAPI($lat, $lng, $radius, $types);
    }

    /**
     * Query Overpass API
     *
     * @param float $lat
     * @param float $lng
     * @param int $radius
     * @param array|null $types
     * @return array
     */
    protected function queryOverpassAPI(float $lat, float $lng, int $radius, ?array $types): array
    {
        $amenityMapping = [
            'okul' => ['school', 'university', 'college', 'kindergarten'],
            'market' => ['supermarket', 'marketplace', 'convenience', 'mall'],
            'hastane' => ['hospital', 'clinic', 'pharmacy', 'doctors'],
            'otel' => ['hotel', 'hostel', 'guesthouse'],
            'sahil' => ['beach_resort', 'marina'],
            'park' => ['park', 'playground', 'sports_centre', 'fitness_centre'],
            'ulasim' => ['bus_station', 'taxi', 'ferry_terminal'],
        ];

        $allPOIs = [];
        $poiId = 1;
        $typesToQuery = $types ?: array_keys($amenityMapping);

        foreach ($typesToQuery as $type) {
            if (!isset($amenityMapping[$type])) {
                continue;
            }

            $amenities = $amenityMapping[$type];
            foreach ($amenities as $amenity) {
                $results = $this->queryOverpassForAmenity($lat, $lng, $amenity, $radius);

                foreach ($results as $item) {
                    if (!isset($item['lat']) || !isset($item['lng'])) {
                        continue;
                    }

                    $distance = $this->calculateDistance($lat, $lng, $item['lat'], $item['lng']) * 1000;

                    if ($distance > $radius) {
                        continue;
                    }

                    $allPOIs[] = [
                        'id' => $poiId++,
                        'name' => $item['name'] ?? $this->getDefaultNameForAmenity($amenity),
                        'type' => $type, // context7-ignore
                        'category' => $this->getPOICategoryLabel($type),
                        'lat' => round($item['lat'], 6),
                        'lng' => round($item['lng'], 6),
                        'distance_m' => round($distance),
                        'distance_km' => round($distance / 1000, 2),
                        'walking_minutes' => round($distance / 80),
                        'icon' => $this->getPOIIcon($type),
                        'osm_id' => $item['id'] ?? null,
                        'tags' => $item['tags'] ?? [],
                        'source' => 'openstreetmap',
                    ];
                }
            }
        }

        usort($allPOIs, fn($a, $b) => $a['distance_m'] <=> $b['distance_m']);

        return array_slice($allPOIs, 0, 50);
    }

    /**
     * Query Overpass API for specific amenity
     *
     * @param float $lat
     * @param float $lng
     * @param string $amenity
     * @param int $radius
     * @return array
     */
    protected function queryOverpassForAmenity(float $lat, float $lng, string $amenity, int $radius): array
    {
        $radiusDegrees = $radius / 111000;
        $south = $lat - $radiusDegrees;
        $north = $lat + $radiusDegrees;
        $west = $lng - $radiusDegrees;
        $east = $lng + $radiusDegrees;

        $query = "[out:json][timeout:25];
        (
            node[\"amenity\"=\"{$amenity}\"]({$south},{$west},{$north},{$east});
            way[\"amenity\"=\"{$amenity}\"]({$south},{$west},{$north},{$east});
            relation[\"amenity\"=\"{$amenity}\"]({$south},{$west},{$north},{$east});
        );
        out center;";

        try {
            $overpassUrl = config('services.overpass.base_url', 'https://overpass-api.de/api/interpreter');

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Yalihan Emlak / ' . config('mail.from.address', 'info@yalihan.com'),
                ])
                ->asForm()
                ->post($overpassUrl, [
                    'data' => $query,
                ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            if (!isset($data['elements'])) {
                return [];
            }

            $results = [];
            foreach ($data['elements'] as $element) {
                $elementLat = $element['lat'] ?? $element['center']['lat'] ?? null;
                $elementLng = $element['lon'] ?? $element['center']['lon'] ?? null;

                if (!$elementLat || !$elementLng) {
                    continue;
                }

                $results[] = [
                    'id' => $element['id'],
                    'lat' => $elementLat,
                    'lng' => $elementLng,
                    'name' => $element['tags']['name'] ?? $this->getDefaultNameForAmenity($amenity),
                    'amenity' => $amenity,
                    'tags' => $element['tags'] ?? [],
                ];
            }

            return $results;
        } catch (\Exception $e) {
            Log::warning("Overpass API query failed for {$amenity}", [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }


    /**
     * Get default name for amenity
     *
     * @param string $amenity
     * @return string
     */
    protected function getDefaultNameForAmenity(string $amenity): string
    {
        $names = [
            'school' => 'Okul',
            'university' => 'Üniversite',
            'supermarket' => 'Süpermarket',
            'hospital' => 'Hastane',
            'pharmacy' => 'Eczane',
            'hotel' => 'Otel',
            'park' => 'Park',
            'bus_station' => 'Otobüs Durağı',
        ];

        return $names[$amenity] ?? 'POI';
    }

    /**
     * Get POI category label
     *
     * @param string $type
     * @return string
     */
    protected function getPOICategoryLabel(string $type): string
    {
        return match ($type) {
            'okul' => 'Eğitim',
            'market' => 'Alışveriş',
            'hastane' => 'Sağlık',
            'otel' => 'Konaklama',
            'sahil' => 'Sahil & Deniz',
            'park' => 'Park & Yeşil Alan',
            'ulasim' => 'Ulaşım',
            default => 'Diğer',
        };
    }

    /**
     * Get POI icon
     *
     * @param string $type
     * @return string
     */
    protected function getPOIIcon(string $type): string
    {
        return match ($type) {
            'okul' => 'school',
            'market' => 'shopping-cart',
            'hastane' => 'hospital',
            'otel' => 'hotel',
            'sahil' => 'beach',
            'park' => 'tree',
            'ulasim' => 'bus',
            default => 'map-pin',
        };
    }

    /**
     * Calculate distance between two points (Haversine formula)
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Distance in kilometers
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance to nearest sea/beach
     *
     * @param float $lat
     * @param float $lng
     * @param int $maxRadius Maximum search radius in meters (default: 10000 = 10km)
     * @return array|null Distance in meters, or null if not found
     */
    public function calculateDistanceToSea(float $lat, float $lng, int $maxRadius = 10000): ?array
    {
        $cacheKey = "places.sea_distance.{$lat}.{$lng}";

        return Cache::remember($cacheKey, 3600 * 24, function () use ($lat, $lng, $maxRadius) {
            $nearestSea = null;
            $minDistance = null;

            // Search for beaches and marinas using POI search
            $pois = $this->getNearbyPOIs($lat, $lng, $maxRadius, ['sahil']);

            foreach ($pois as $poi) {
                if (isset($poi['distance_m']) && $poi['distance_m'] <= $maxRadius) {
                    if ($minDistance === null || $poi['distance_m'] < $minDistance) {
                        $minDistance = $poi['distance_m'];
                        $nearestSea = $poi;
                    }
                }
            }

            // If no POI found, try Overpass API for natural=coastline
            if ($nearestSea === null) {
                $coastlineResults = $this->queryOverpassForCoastline($lat, $lng, $maxRadius);
                if (!empty($coastlineResults)) {
                    $nearest = $coastlineResults[0];
                    $distance = $this->calculateDistance($lat, $lng, $nearest['lat'], $nearest['lng']) * 1000;
                    if ($distance <= $maxRadius) {
                        $minDistance = $distance;
                        $nearestSea = [
                            'name' => 'Deniz Kıyısı',
                            'type' => 'coastline', // context7-ignore
                            'distance_m' => round($distance),
                            'distance_km' => round($distance / 1000, 2),
                            'lat' => $nearest['lat'],
                            'lng' => $nearest['lng'],
                        ];
                    }
                }
            }

            if ($nearestSea === null) {
                return null;
            }

            return [
                'distance_m' => round($minDistance),
                'distance_km' => round($minDistance / 1000, 2),
                'walking_minutes' => round($minDistance / 80),
                'location' => $nearestSea['name'] ?? 'Deniz Kıyısı',
                'coordinates' => [
                    'lat' => $nearestSea['lat'] ?? null,
                    'lng' => $nearestSea['lng'] ?? null,
                ],
            ];
        });
    }

    /**
     * Query Overpass API for coastline
     *
     * @param float $lat
     * @param float $lng
     * @param int $radius
     * @return array
     */
    protected function queryOverpassForCoastline(float $lat, float $lng, int $radius): array
    {
        $radiusDegrees = $radius / 111000;
        $south = $lat - $radiusDegrees;
        $north = $lat + $radiusDegrees;
        $west = $lng - $radiusDegrees;
        $east = $lng + $radiusDegrees;

        $query = "[out:json][timeout:25];
        (
            way[\"natural\"=\"coastline\"]({$south},{$west},{$north},{$east});
        );
        out center;";

        try {
            $overpassUrl = config('services.overpass.base_url', 'https://overpass-api.de/api/interpreter');

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Yalihan Emlak / ' . config('mail.from.address', 'info@yalihan.com'),
                ])
                ->asForm()
                ->post($overpassUrl, [
                    'data' => $query,
                ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            if (!isset($data['elements'])) {
                return [];
            }

            $results = [];
            foreach ($data['elements'] as $element) {
                $elementLat = $element['lat'] ?? $element['center']['lat'] ?? null;
                $elementLng = $element['lon'] ?? $element['center']['lon'] ?? null;

                if (!$elementLat || !$elementLng) {
                    continue;
                }

                $results[] = [
                    'id' => $element['id'],
                    'lat' => $elementLat,
                    'lng' => $elementLng,
                ];
            }

            // Sort by distance
            usort($results, function ($a, $b) use ($lat, $lng) {
                $distA = $this->calculateDistance($lat, $lng, $a['lat'], $a['lng']);
                $distB = $this->calculateDistance($lat, $lng, $b['lat'], $b['lng']);
                return $distA <=> $distB;
            });

            return $results;
        } catch (\Exception $e) {
            Log::warning('Overpass API coastline query failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}

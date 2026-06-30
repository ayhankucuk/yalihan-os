<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenStreetMap Nominatim Service
 *
 * FREE alternative to WikiMapia and Google Places
 * Rate limit: 1 request/second
 * Coverage: Worldwide, good Turkey support
 */
class NominatimService
{
    protected string $baseUrl;

    protected string $email;

    protected int $timeout;

    protected bool $cacheEnabled;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('services.nominatim.base_url', 'https://nominatim.openstreetmap.org');
        $this->email = config('services.nominatim.email', config('mail.from.address'));
        $this->timeout = config('services.nominatim.timeout', 10);
        $this->cacheEnabled = config('services.nominatim.cache_durumu', true);
        $this->cacheTtl = config('services.nominatim.cache_ttl', 3600);
    }

    /**
     * Search places by query and location
     */
    public function searchPlaces(string $query, float $lat, float $lon, int $limit = 10)
    {
        $cacheKey = "nominatim.search.{$query}.{$lat}.{$lon}.{$limit}";

        return $this->cacheEnabled
            ? Cache::remember($cacheKey, $this->cacheTtl, fn () => $this->performSearch($query, $lat, $lon, $limit))
            : $this->performSearch($query, $lat, $lon, $limit);
    }

    /**
     * Search nearby residential complexes
     */
    public function searchNearby(float $lat, float $lon, float $radius = 0.5)
    {
        $cacheKey = "nominatim.nearby.{$lat}.{$lon}.{$radius}";

        return $this->cacheEnabled
            ? Cache::remember($cacheKey, $this->cacheTtl, fn () => $this->performNearbySearch($lat, $lon, $radius))
            : $this->performNearbySearch($lat, $lon, $radius);
    }

    /**
     * Perform actual search
     */
    protected function performSearch(string $query, float $lat, float $lon, int $limit)
    {
        try {
            // Rate limiting: Sleep 1 second between requests
            sleep(1);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Yalihan Emlak / '.$this->email,
                ])
                ->get($this->baseUrl.'/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => $limit,
                    'viewbox' => $this->getViewbox($lat, $lon, $radius ?? 0.5),
                    'bounded' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $this->formatResults($data);
            }

            Log::warning('Nominatim API error', [
                'http_durumu' => $response->status(), // context7-ignore
                'body' => $response->body(),
            ]);

            return ['places' => []];

        } catch (\Exception $e) {
            Log::error('Nominatim API exception', [
                'error' => $e->getMessage(),
            ]);

            return ['places' => []];
        }
    }

    /**
     * Perform nearby search (REVERSE GEOCODE - get places in area)
     */
    protected function performNearbySearch(float $lat, float $lon, float $radius)
    {
        try {
            sleep(1); // Rate limiting

            // Strategy: Use reverse geocode at neighbourhood level (avoid streets)
            // zoom=14: neighbourhood/suburb level (better than zoom=16 street level)
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Yalihan Emlak / '.$this->email,
                ])
                ->get($this->baseUrl.'/reverse', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'zoom' => 14, // Neighbourhood/suburb level (NOT street!)
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Nominatim reverse geocode result', [
                    'display_name' => $data['display_name'] ?? 'N/A',
                    'type' => $data['type'] ?? 'N/A', // context7-ignore
                ]);

                // Format single result as array
                $formatted = $this->formatResults([$data]);

                // If reverse geocode found nothing useful, try area search
                if (empty($formatted['places'])) {
                    Log::info('Reverse geocode empty, trying area search');

                    return $this->searchAreaPlaces($lat, $lon, $radius);
                }

                return $formatted;
            }

            // Fallback: Area search
            return $this->searchAreaPlaces($lat, $lon, $radius);

        } catch (\Exception $e) {
            Log::error('Nominatim nearby exception', [
                'error' => $e->getMessage(),
            ]);

            return ['places' => []];
        }
    }

    /**
     * Search for places in area (fallback method)
     */
    protected function searchAreaPlaces(float $lat, float $lon, float $radius)
    {
        try {
            // Get neighbourhood/suburb name from reverse geocode
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Yalihan Emlak / '.$this->email,
                ])
                ->get($this->baseUrl.'/search', [
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 50,
                    'viewbox' => $this->getViewbox($lat, $lon, $radius),
                    'bounded' => 1,
                    // Search for ANY place (not just buildings)
                    'featuretype' => 'settlement',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Nominatim area search returned '.count($data).' raw results');

                return $this->formatResults($data);
            }

            return ['places' => []];

        } catch (\Exception $e) {
            Log::error('Nominatim area search exception', [
                'error' => $e->getMessage(),
            ]);

            return ['places' => []];
        }
    }

    /**
     * Format Nominatim results to WikiMapia-compatible format
     */
    protected function formatResults(array $data): array
    {
        $places = [];

        foreach ($data as $item) {
            // Filter: Reject roads but accept places/neighbourhoods/buildings
            if (! $this->isResidentialPlace($item)) {
                Log::info('Nominatim filter REJECTED', [
                    'type' => $item['type'] ?? 'N/A', // context7-ignore
                    'class' => $item['class'] ?? 'N/A',
                    'name' => $item['display_name'] ?? 'N/A',
                ]);

                continue;
            }

            $places[] = [
                'id' => $item['place_id'] ?? rand(1000, 9999),
                'title' => $this->extractTitle($item),
                'description' => $this->extractDescription($item),
                'location' => [
                    'latitude' => (float) ($item['lat'] ?? 0),
                    'longitude' => (float) ($item['lon'] ?? 0),
                ],
                'address' => $item['display_name'] ?? '',
                'type' => $item['type'] ?? 'building', // context7-ignore
                'category' => $item['class'] ?? 'place',
                'url' => 'https://www.openstreetmap.org/'.($item['osm_type'] ?? 'node').'/'.($item['osm_id'] ?? ''),
                'source' => 'openstreetmap',
            ];
        }

        return [
            'places' => $places,
            'found' => count($places),
        ];
    }

    /**
     * Check if place is residential (STRICT FILTER - NO ROADS!)
     */
    protected function isResidentialPlace(array $item): bool
    {
        $type = strtolower($item['type'] ?? ''); // context7-ignore
        $class = strtolower($item['class'] ?? '');

        // ❌ BLACKLIST: Roads, highways, streets (BU TİPLERİ ASLA ALMA!)
        $blacklist = [
            'highway', 'road', 'street', 'footway', 'path', 'track',
            'motorway', 'trunk', 'primary', 'secondary', 'tertiary',
            'residential_road', 'service', 'cycleway', 'pedestrian',
        ];

        // Eğer blacklist'te varsa REJECT!
        if (in_array($type, $blacklist) || in_array($class, $blacklist)) {
            return false;
        }

        // ✅ WHITELIST: Buildings, apartments, sites, amenities, PLACES
        $residentialTypes = [
            'apartments', 'residential', 'house', 'building',
            'dormitory', 'detached', 'terrace', 'flats',
            'apartment_building', 'residential_complex',
            // Places & Settlements
            'neighbourhood', 'suburb', 'quarter', 'city', 'town',
            'village', 'hamlet', 'locality', 'district', 'municipality',
        ];

        $residentialClasses = [
            'building', 'place', 'tourism', 'amenity', 'boundary',
        ];

        // Building, place, settlement varsa ACCEPT!
        return in_array($type, $residentialTypes) ||
               in_array($class, $residentialClasses) ||
               str_contains($type, 'apartment') ||
               str_contains($type, 'site') ||
               str_contains($type, 'neighbourhood') ||
               str_contains($type, 'village') ||
               str_contains($class, 'building') ||
               str_contains($class, 'place');
    }

    /**
     * Extract title from result (BUILDING FOCUSED)
     */
    protected function extractTitle(array $item): string
    {
        // Priority 1: Named building
        if (! empty($item['namedetails']['name'])) {
            return $item['namedetails']['name'];
        }

        if (! empty($item['name'])) {
            return $item['name'];
        }

        // Priority 2: Building name from address
        if (! empty($item['address']['building'])) {
            return $item['address']['building'];
        }

        if (! empty($item['address']['apartments'])) {
            return $item['address']['apartments'];
        }

        // Priority 3: Neighbourhood/suburb (site location)
        if (! empty($item['address']['neighbourhood'])) {
            return $item['address']['neighbourhood'].' Bölgesi';
        }

        if (! empty($item['address']['suburb'])) {
            return $item['address']['suburb'].' Bölgesi';
        }

        // Priority 4: City/town
        if (! empty($item['address']['city'])) {
            return $item['address']['city'].' - '.($item['type'] ?? 'Bölge'); // context7-ignore
        }

        // Fallback: Display name (shorten it)
        $displayName = $item['display_name'] ?? 'Bilinmeyen Yer';
        $parts = explode(',', $displayName);

        return $parts[0] ?? $displayName;
    }

    /**
     * Extract description from result (MORE DETAILED)
     */
    protected function extractDescription(array $item): string
    {
        $parts = [];

        // Building type indicator
        $type = ucfirst($item['type'] ?? ''); // context7-ignore
        if ($type && $type !== 'Yes') {
            $parts[] = $type;
        }

        // Street address
        if (! empty($item['address']['road'])) {
            $parts[] = $item['address']['road'];
        }

        // Neighbourhood/suburb
        if (! empty($item['address']['neighbourhood'])) {
            $parts[] = $item['address']['neighbourhood'];
        } elseif (! empty($item['address']['suburb'])) {
            $parts[] = $item['address']['suburb'];
        }

        // District
        if (! empty($item['address']['city_district'])) {
            $parts[] = $item['address']['city_district'];
        }

        // City
        if (! empty($item['address']['city'])) {
            $parts[] = $item['address']['city'];
        } elseif (! empty($item['address']['town'])) {
            $parts[] = $item['address']['town'];
        }

        // Province
        if (! empty($item['address']['state'])) { // context7-ignore
            $parts[] = $item['address']['state']; // context7-ignore
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Get viewbox for bounded search
     */
    protected function getViewbox(float $lat, float $lon, float $radius): string
    {
        $lonMin = $lon - $radius;
        $latMin = $lat - $radius;
        $lonMax = $lon + $radius;
        $latMax = $lat + $radius;

        return "{$lonMin},{$latMax},{$lonMax},{$latMin}";
    }
}

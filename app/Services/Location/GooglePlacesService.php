<?php

declare(strict_types=1);

namespace App\Services\Location;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GooglePlacesService
 *
 * Sprint 6.2: Queries Google Places API for POIs with a mock fallback.
 */
class GooglePlacesService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('location.google_maps.api_key') ?? config('services.google_maps.api_key');
    }

    /**
     * Get surrounding points of interest.
     */
    public function getNearbyPOIs(float $lat, float $lng, int $radius = 5000): array
    {
        if (empty($this->apiKey)) {
            Log::info('GooglePlacesService: API Key is missing. Returning mock POIs.');
            return $this->getMockPOIs($lat, $lng);
        }

        $pois = [];
        $types = ['hospital', 'school', 'marina', 'tourist_attraction'];

        foreach ($types as $type) { // context7-ignore
            try {
                $response = Http::timeout(5)
                    ->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                        'location' => sprintf('%f,%f', $lat, $lng),
                        'radius'   => $radius,
                        'type'     => $type, // context7-ignore
                        'key'      => $this->apiKey,
                    ]);

                if ($response->successful()) {
                    $results = $response->json()['results'] ?? [];
                    foreach ($results as $item) {
                        $pois[] = [
                            'name'            => $item['name'] ?? 'N/A',
                            'type'            => $this->mapGoogleTypeToCanonical($type), // context7-ignore
                            'distance_meters' => $this->calculateDistance($lat, $lng, (float) $item['geometry']['location']['lat'], (float) $item['geometry']['location']['lng']),
                            'rating'          => isset($item['rating']) ? (float) $item['rating'] : null,
                            'lat'             => (float) $item['geometry']['location']['lat'],
                            'lng'             => (float) $item['geometry']['location']['lng'],
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('GooglePlacesService: Places search failed', ['type' => $type, 'error' => $e->getMessage()]); // context7-ignore
            }
        }

        // Sort by distance ascending
        usort($pois, fn($a, $b) => $a['distance_meters'] <=> $b['distance_meters']);

        return array_slice($pois, 0, 15);
    }

    private function mapGoogleTypeToCanonical(string $type): string // context7-ignore
    {
        return match ($type) { // context7-ignore
            'hospital'           => 'hospital',
            'school'             => 'school',
            'marina'             => 'marina',
            'tourist_attraction' => 'beach',
            default              => 'other',
        };
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000; // in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) ($earthRadius * $c);
    }

    private function getMockPOIs(float $lat, float $lng): array
    {
        return [
            [
                'name'            => 'Bodrum Marina',
                'type'            => 'marina', // context7-ignore
                'distance_meters' => 1200,
                'rating'          => 4.7,
                'lat'             => 37.0344,
                'lng'             => 27.4305,
            ],
            [
                'name'            => 'Yalıkavak Beach',
                'type'            => 'beach', // context7-ignore
                'distance_meters' => 850,
                'rating'          => 4.5,
                'lat'             => 37.1042,
                'lng'             => 27.2889,
            ],
            [
                'name'            => 'Bodrum State Hospital',
                'type'            => 'hospital', // context7-ignore
                'distance_meters' => 2400,
                'rating'          => 4.1,
                'lat'             => 37.0422,
                'lng'             => 27.4385,
            ],
            [
                'name'            => 'Bodrum High School',
                'type'            => 'school', // context7-ignore
                'distance_meters' => 1500,
                'rating'          => 4.2,
                'lat'             => 37.0360,
                'lng'             => 27.4250,
            ],
        ];
    }
}

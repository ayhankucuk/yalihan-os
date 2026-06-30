<?php

namespace App\Services\Integrations;

use App\Services\WikimapiaService as BaseWikimapiaService;
use Illuminate\Support\Facades\Log;

class WikiMapiaService
{
    public function __construct(
        protected BaseWikimapiaService $wikimapiaService
    ) {
    }

    /**
     * Search nearby points of interest using Wikimapia API.
     *
     * @return array<int, array{
     *     name: string,
     *     type: string|null,
     *     distance_m: float|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     description: string|null
     * }>
     */
    public function searchNearbyPlaces(float $lat, float $lng, int $limit = 15): array
    {
        try {
            $nearest = $this->wikimapiaService->getNearestPlaces($lat, $lng, [
                'count' => $limit,
                'data_blocks' => ['main', 'location'],
            ]);

            if ($nearest && isset($nearest['places'])) {
                return $this->transformPlaces($nearest['places'], $lat, $lng, $limit);
            }

            return $this->searchByArea($lat, $lng, $limit);
        } catch (\Throwable $e) {
            Log::warning('Wikimapia nearby search failed', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function searchByArea(float $lat, float $lng, int $limit): array
    {
        $radius = 0.02; // ≈ 2km

        $area = $this->wikimapiaService->getPlacesByArea(
            $lng - $radius,
            $lat - $radius,
            $lng + $radius,
            $lat + $radius,
            ['count' => $limit]
        );

        if ($area && isset($area['places'])) {
            return $this->transformPlaces($area['places'], $lat, $lng, $limit);
        }

        return [];
    }

    protected function transformPlaces(array $places, float $originLat, float $originLng, int $limit): array
    {
        $transformed = [];

        foreach ($places as $place) {
            $placeLat = $place['location']['lat'] ?? $place['location']['y'] ?? null;
            $placeLng = $place['location']['lon'] ?? $place['location']['x'] ?? null;

            $transformed[] = [
                'name' => $place['title'] ?? 'Bilinmeyen POI',
                'type' => $place['category']['title'] ?? null, // context7-ignore
                'distance_m' => ($placeLat && $placeLng)
                    ? round($this->calculateDistanceMeters($originLat, $originLng, $placeLat, $placeLng), 2)
                    : null,
                'latitude' => $placeLat,
                'longitude' => $placeLng,
                'description' => $place['description'] ?? null,
            ];

            if (count($transformed) >= $limit) {
                break;
            }
        }

        return $transformed;
    }

    /**
     * Haversine formula to calculate distance in meters.
     */
    protected function calculateDistanceMeters(float $latFrom, float $lngFrom, float $latTo, float $lngTo): float
    {
        $earthRadius = 6371000; // meters

        $latFromRad = deg2rad($latFrom);
        $latToRad = deg2rad($latTo);
        $deltaLat = deg2rad($latTo - $latFrom);
        $deltaLng = deg2rad($lngTo - $lngFrom);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($latFromRad) * cos($latToRad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}



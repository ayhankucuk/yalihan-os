<?php

namespace App\Services;

use App\Models\Ilan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Yalıhan Cortex AI: Heatmap Service
 *
 * Context7 Standard: C7-HEATMAP-SERVICE-2025-12-23
 * Version: 1.0.0
 *
 * ROI Heatmap generation:
 * - Bölgesel yatırım skoru haritası
 * - GeoJSON format (mapping libraries ile uyumlu)
 * - Grid-based density calculation
 * - Real-time data aggregation
 */
class CortexHeatmapService
{
    protected $repository;

    public function __construct(\App\Repositories\CortexHeatmapRepository $repository)
    {
        $this->repository = $repository;
    }
    /**
     * Grid size (km) for heatmap density
     */
    private const GRID_SIZE_KM = 2;

    /**
     * Minimum properties per grid cell
     */
    private const MIN_PROPERTIES_PER_CELL = 3;

    /**
     * Cache duration (minutes)
     */
    private const CACHE_DURATION = 15;

    /**
     * Generate ROI heatmap data
     *
     * @param array $filters
     * @param bool $useCache
     * @return array
     */
    public function generateROIHeatmap(array $filters = [], bool $useCache = true): array
    {
        $cacheKey = 'cortex_heatmap_' . md5(json_encode($filters));

        if ($useCache && Cache::has($cacheKey)) {
            Log::info('Heatmap data from cache');
            return Cache::get($cacheKey);
        }

        $startTime = microtime(true);

        // Get properties with location and ROI data
        $properties = $this->getPropertiesWithROI($filters);

        if ($properties->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No properties with location data found',
                'data' => [],
            ];
        }

        // Generate heatmap grid
        $heatmapData = $this->generateHeatmapGrid($properties);

        // Generate GeoJSON
        $geoJson = $this->generateGeoJSON($heatmapData);

        // Calculate statistics
        $stats = $this->calculateHeatmapStats($heatmapData);

        $result = [
            'success' => true,
            'data' => [
                'geojson' => $geoJson,
                'grid_cells' => $heatmapData,
                'statistics' => $stats,
                'filters_applied' => $filters,
            ],
            'meta' => [
                'total_properties' => $properties->count(),
                'grid_size_km' => self::GRID_SIZE_KM,
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'cached' => false,
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        // Cache result
        if ($useCache) {
            Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_DURATION));
        }

        return $result;
    }

    /**
     * Get properties with ROI and location data
     *
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    private function getPropertiesWithROI(array $filters): \Illuminate\Support\Collection
    {
        return $this->repository->getPropertiesWithROI($filters)
            ->map(function ($ilan) {
                $locationData = is_string($ilan->location_data)
                    ? json_decode($ilan->location_data, true)
                    : $ilan->location_data;

                $metadata = is_string($ilan->additional_metadata)
                    ? json_decode($ilan->additional_metadata, true)
                    : $ilan->additional_metadata;

                return [
                    'id' => $ilan->id,
                    'title' => $ilan->baslik,
                    'price' => $ilan->fiyat,
                    'lat' => $locationData['latitude'] ?? null,
                    'lng' => $locationData['longitude'] ?? null,
                    'cortex_score' => $metadata['cortex_ai']['cortex_score'] ?? 0,
                    'roi_percentage' => $metadata['cortex_ai']['roi_data']['roi_percentage'] ?? 0,
                ];
            })
            ->filter(fn($p) => $p['lat'] && $p['lng']);
    }

    /**
     * Generate heatmap grid
     *
     * @param \Illuminate\Support\Collection $properties
     * @return array
     */
    private function generateHeatmapGrid(\Illuminate\Support\Collection $properties): array
    {
        $grid = [];

        foreach ($properties as $property) {
            $gridKey = $this->getGridKey($property['lat'], $property['lng']);

            if (!isset($grid[$gridKey])) {
                $grid[$gridKey] = [
                    'cell_id' => $gridKey,
                    'center_lat' => $this->getGridCenterLat($gridKey),
                    'center_lng' => $this->getGridCenterLng($gridKey),
                    'properties' => [],
                    'count' => 0,
                    'avg_cortex_score' => 0,
                    'avg_roi_percentage' => 0,
                    'total_value' => 0,
                ];
            }

            $grid[$gridKey]['properties'][] = $property['id'];
            $grid[$gridKey]['count']++;
            $grid[$gridKey]['avg_cortex_score'] += $property['cortex_score'];
            $grid[$gridKey]['avg_roi_percentage'] += $property['roi_percentage'];
            $grid[$gridKey]['total_value'] += $property['price'];
        }

        // Calculate averages
        foreach ($grid as $key => &$cell) {
            if ($cell['count'] > 0) {
                $cell['avg_cortex_score'] = round($cell['avg_cortex_score'] / $cell['count'], 2);
                $cell['avg_roi_percentage'] = round($cell['avg_roi_percentage'] / $cell['count'], 2);
            }

            // Determine intensity (0-100)
            $cell['intensity'] = $this->calculateCellIntensity($cell);

            // Determine color category
            $cell['color_category'] = $this->getColorCategory($cell['intensity']);
        }

        // Filter out cells with too few properties
        $grid = array_filter($grid, function($cell) {
            return $cell['count'] >= self::MIN_PROPERTIES_PER_CELL;
        });

        return array_values($grid);
    }

    /**
     * Get grid key for coordinates
     *
     * @param float $lat
     * @param float $lng
     * @return string
     */
    private function getGridKey(float $lat, float $lng): string
    {
        $latGrid = floor($lat / (self::GRID_SIZE_KM / 111));
        $lngGrid = floor($lng / (self::GRID_SIZE_KM / 111));

        return "{$latGrid}_{$lngGrid}";
    }

    /**
     * Get grid center latitude
     *
     * @param string $gridKey
     * @return float
     */
    private function getGridCenterLat(string $gridKey): float
    {
        [$latGrid, ] = explode('_', $gridKey);
        return ($latGrid + 0.5) * (self::GRID_SIZE_KM / 111);
    }

    /**
     * Get grid center longitude
     *
     * @param string $gridKey
     * @return float
     */
    private function getGridCenterLng(string $gridKey): float
    {
        [, $lngGrid] = explode('_', $gridKey);
        return ($lngGrid + 0.5) * (self::GRID_SIZE_KM / 111);
    }

    /**
     * Calculate cell intensity (0-100)
     *
     * @param array $cell
     * @return int
     */
    private function calculateCellIntensity(array $cell): int
    {
        // Weighted scoring
        $cortexWeight = 0.5;
        $roiWeight = 0.3;
        $densityWeight = 0.2;

        $cortexScore = min($cell['avg_cortex_score'] * 10, 100);
        $roiScore = min($cell['avg_roi_percentage'] * 5, 100);
        $densityScore = min($cell['count'] * 10, 100);

        $intensity = ($cortexScore * $cortexWeight) +
                     ($roiScore * $roiWeight) +
                     ($densityScore * $densityWeight);

        return (int) round($intensity);
    }

    /**
     * Get color category for intensity
     *
     * @param int $intensity
     * @return string
     */
    private function getColorCategory(int $intensity): string
    {
        return match(true) {
            $intensity >= 80 => 'hot',        // Red
            $intensity >= 60 => 'warm',       // Orange
            $intensity >= 40 => 'moderate',   // Yellow
            $intensity >= 20 => 'cool',       // Green
            default => 'cold',                // Blue
        };
    }

    /**
     * Generate GeoJSON from heatmap data
     *
     * @param array $heatmapData
     * @return array
     */
    private function generateGeoJSON(array $heatmapData): array
    {
        $features = [];

        foreach ($heatmapData as $cell) {
            $features[] = [
                'type' => 'Feature', // context7-ignore
                'geometry' => [
                    'type' => 'Point', // context7-ignore
                    'coordinates' => [$cell['center_lng'], $cell['center_lat']],
                ],
                'properties' => [
                    'cell_id' => $cell['cell_id'],
                    'intensity' => $cell['intensity'],
                    'color_category' => $cell['color_category'],
                    'property_count' => $cell['count'],
                    'avg_cortex_score' => $cell['avg_cortex_score'],
                    'avg_roi_percentage' => $cell['avg_roi_percentage'],
                    'total_value' => $cell['total_value'],
                    'property_ids' => $cell['properties'],
                ],
            ];
        }

        return [
            'type' => 'FeatureCollection', // context7-ignore
            'features' => $features,
        ];
    }

    /**
     * Calculate heatmap statistics
     *
     * @param array $heatmapData
     * @return array
     */
    private function calculateHeatmapStats(array $heatmapData): array
    {
        $hotCells = array_filter($heatmapData, fn($c) => $c['color_category'] === 'hot');
        $warmCells = array_filter($heatmapData, fn($c) => $c['color_category'] === 'warm');

        $totalProperties = array_sum(array_column($heatmapData, 'count'));
        $avgIntensity = array_sum(array_column($heatmapData, 'intensity')) / count($heatmapData);

        return [
            'total_cells' => count($heatmapData),
            'hot_zones' => count($hotCells),
            'warm_zones' => count($warmCells),
            'total_properties_mapped' => $totalProperties,
            'average_intensity' => round($avgIntensity, 1),
            'top_investment_zones' => $this->getTopInvestmentZones($heatmapData),
        ];
    }

    /**
     * Get top investment zones
     *
     * @param array $heatmapData
     * @return array
     */
    private function getTopInvestmentZones(array $heatmapData): array
    {
        usort($heatmapData, function($a, $b) {
            return $b['intensity'] <=> $a['intensity'];
        });

        return array_slice(array_map(function($cell) {
            return [
                'cell_id' => $cell['cell_id'],
                'coordinates' => [
                    'lat' => $cell['center_lat'],
                    'lng' => $cell['center_lng'],
                ],
                'intensity' => $cell['intensity'],
                'property_count' => $cell['count'],
                'avg_roi' => $cell['avg_roi_percentage'],
            ];
        }, $heatmapData), 0, 10);
    }

    /**
     * Get properties in specific grid cell
     *
     * @param string $cellId
     * @return array
     */
    public function getPropertiesInCell(string $cellId): array
    {
        [$latGrid, $lngGrid] = explode('_', $cellId);

        $minLat = $latGrid * (self::GRID_SIZE_KM / 111);
        $maxLat = ($latGrid + 1) * (self::GRID_SIZE_KM / 111);
        $minLng = $lngGrid * (self::GRID_SIZE_KM / 111);
        $maxLng = ($lngGrid + 1) * (self::GRID_SIZE_KM / 111);

        $properties = $this->repository->getPropertiesInCellBounds($minLat, $maxLat, $minLng, $maxLng);

        return [
            'cell_id' => $cellId,
            'bounds' => [
                'min_lat' => $minLat,
                'max_lat' => $maxLat,
                'min_lng' => $minLng,
                'max_lng' => $maxLng,
            ],
            'properties' => $properties->map(function($ilan) {
                return [
                    'id' => $ilan->id,
                    'title' => $ilan->baslik,
                    'price' => $ilan->fiyat,
                    'location' => $ilan->il?->name . ' / ' . $ilan->ilce?->name,
                    'category' => $ilan->anaKategori?->name,
                ];
            }),
            'count' => $properties->count(),
        ];
    }

    /**
     * Get heatmap metadata statistics
     */
    public function getHeatmapMetadata(): array
    {
        $totalWithCoordinates = $this->repository->countWithCoordinates();

        $totalWithROI = $this->repository->countWithROI();

        $readyForHeatmap = $this->repository->countReadyForHeatmap();

        return [
            'total_properties_with_coordinates' => $totalWithCoordinates,
            'total_properties_with_roi' => $totalWithROI,
            'ready_for_heatmap' => $readyForHeatmap,
            'grid_size_km' => self::GRID_SIZE_KM,
            'min_properties_per_cell' => self::MIN_PROPERTIES_PER_CELL,
        ];
    }
}


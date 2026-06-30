<?php

namespace App\Services\MarketIntelligence;

use App\DTOs\MarketIntelligence\LocationInsightDTO;
use App\Services\Location\PoiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\Logging\LogService;

/**
 * Location Intelligence Service — MIE V4
 *
 * POI verilerini deterministik olarak analiz eder.
 * AI çağırmaz, yan etkisi yoktur, sadece hesap yapar.
 *
 * 3 alt skor:
 *   access_score   (0–40): Kritik ihtiyaç erişimi (eğitim, sağlık, ulaşım, günlük)
 *   density_score  (0–30): POI yoğunluğu (dedup dahil)
 *   coverage_score (0–30): Farklı grup çeşitliliği
 *
 * Total: location_signal_score = access + density + coverage (0–100)
 */
class LocationIntelligenceService
{
    private array $config;

    public function __construct(
        private readonly PoiService $poiService,
        ?array $config = null,
    ) {
        $this->config = $config ?? config('location_intelligence', []);
    }

    /**
     * Konum sinyali analizi.
     *
     * @param float|null $lat  Enlem (null = no_coordinates)
     * @param float|null $lng  Boylam (null = no_coordinates)
     * @return LocationInsightDTO
     */
    public function analyze(?float $lat, ?float $lng): LocationInsightDTO
    {
        if ($lat === null || $lng === null) {
            return LocationInsightDTO::insufficient('no_coordinates');
        }

        try {
            $radiusKm = $this->config['default_radius_km'] ?? 3.0;
            $pois = $this->poiService->findNearby($lat, $lng, $radiusKm);

            if ($pois->isEmpty()) {
                return LocationInsightDTO::insufficient('insufficient_location_data');
            }

            $classified = $this->classifyPois($pois);
            $totalPoi = $pois->count();
            $groupCount = count($classified);

            // Minimum veri kontrolü
            $minPoi = $this->config['confidence']['min_poi_count'] ?? 3;
            $minGroups = $this->config['confidence']['min_group_count'] ?? 2;

            if ($totalPoi < $minPoi && $groupCount < $minGroups) {
                return LocationInsightDTO::insufficient('insufficient_location_data');
            }

            // 3 alt skor
            $accessScore = $this->calculateAccessScore($classified);
            $densityScore = $this->calculateDensityScore($classified);
            $coverageScore = $this->calculateCoverageScore($classified);

            $signalScore = $accessScore + $densityScore + $coverageScore;

            // Confidence
            [$confidenceScore, $confidenceLabel] = $this->calculateConfidence($totalPoi, $groupCount);

            // Top nearby groups (UI için)
            $topGroups = $this->buildTopNearbyGroups($classified);

            // Reason codes
            $reasonCodes = $this->generateReasonCodes($classified, $coverageScore, $densityScore, $totalPoi);

            // Demand modifier
            $demandModifier = $this->calculateDemandModifier($signalScore);

            // Human summary
            $humanSummary = $this->buildHumanSummary($topGroups, $signalScore);

            return new LocationInsightDTO(
                location_signal_score: $signalScore,
                confidence_score: $confidenceScore,
                confidence_label: $confidenceLabel,
                data_status: 'ok',
                poi_access_score: $accessScore,
                poi_density_score: $densityScore,
                poi_coverage_score: $coverageScore,
                top_nearby_groups: $topGroups,
                reason_codes: $reasonCodes,
                human_summary: $humanSummary,
                demand_modifier: $demandModifier,
            );
        } catch (\Throwable $e) {
            try {
                LogService::error('LocationIntelligenceService: analyze error', [
                    'lat' => $lat,
                    'lng' => $lng,
                ], $e);
            } catch (\Throwable $nested) {
            \Illuminate\Support\Facades\Log::error("Silent catch: " . $nested->getMessage());
                // Facade not available (pure unit tests)
                error_log("P3_SILENT_CATCH_FALLBACK: LocationIntelligenceService analyze error for lat={$lat}, lng={$lng}. " . $e->getMessage());
            }

            return LocationInsightDTO::insufficient('insufficient_location_data');
        }
    }

    /**
     * POI'leri config gruplarına sınıflandır.
     *
     * @return array<string, array{label: string, pois: array}>
     */
    private function classifyPois(Collection $pois): array
    {
        $groups = $this->config['poi_groups'] ?? [];
        $typeToGroup = $this->buildTypeToGroupMap($groups);
        $classified = [];

        foreach ($pois as $poi) {
            $poiTuru = $poi['poi_turu'] ?? $poi['type'] ?? null;
            if ($poiTuru === null) {
                continue;
            }

            $groupKey = $typeToGroup[strtolower($poiTuru)] ?? null;
            if ($groupKey === null) {
                continue;
            }

            if (!isset($classified[$groupKey])) {
                $classified[$groupKey] = [
                    'label' => $groups[$groupKey]['label'] ?? $groupKey,
                    'pois' => [],
                ];
            }

            $classified[$groupKey]['pois'][] = [
                'poi_adi' => $poi['poi_adi'] ?? $poi['name'] ?? '',
                'poi_turu' => $poiTuru,
                'distance_m' => (int) ($poi['distance'] ?? ($poi['distance_km'] ?? 0) * 1000),
                'distance_km' => (float) ($poi['distance_km'] ?? 0),
                'rating' => $poi['rating'] ?? null,
            ];
        }

        return $classified;
    }

    /**
     * poi_turu → group_key reverse map oluştur (cache-friendly).
     */
    private function buildTypeToGroupMap(array $groups): array
    {
        $map = [];
        foreach ($groups as $groupKey => $groupConfig) {
            foreach ($groupConfig['types'] ?? [] as $type) {
                $map[strtolower($type)] = $groupKey;
            }
        }
        return $map;
    }

    /**
     * Erişim skoru hesapla (0–40).
     *
     * Sadece access_weights'te tanımlı 4 kritik grup:
     * education (10), health (10), transport (10), daily_need (10)
     *
     * Her grup için en yakın POI'nin mesafe kovasından ağırlık alınır.
     * group_weight * bucket_weight → toplam max 40
     */
    private function calculateAccessScore(array $classified): int
    {
        $accessWeights = $this->config['access_weights'] ?? [];
        $buckets = $this->config['distance_buckets'] ?? [];
        $total = 0.0;

        foreach ($accessWeights as $groupKey => $maxWeight) {
            if (!isset($classified[$groupKey]) || empty($classified[$groupKey]['pois'])) {
                continue;
            }

            // En yakın POI'yi bul (mesafeye göre zaten sıralı gelebilir ama emin olalım)
            $closestDistance = PHP_INT_MAX;
            foreach ($classified[$groupKey]['pois'] as $poi) {
                if ($poi['distance_m'] < $closestDistance) {
                    $closestDistance = $poi['distance_m'];
                }
            }

            $bucketWeight = $this->getBucketWeight($closestDistance, $buckets);
            $total += $maxWeight * $bucketWeight;
        }

        return (int) min(40, round($total));
    }

    /**
     * Mesafe kovasından ağırlık değerini bul.
     */
    private function getBucketWeight(int $distanceM, array $buckets): float
    {
        foreach ($buckets as $bucket) {
            if ($distanceM < ($bucket['max_m'] ?? PHP_INT_MAX)) {
                return (float) ($bucket['weight'] ?? 0);
            }
        }
        return 0.0; // Hiçbir kovaya düşmedi = çok uzak
    }

    /**
     * Yoğunluk skoru hesapla (0–30).
     *
     * Grup başına cap_per_group'a kadar POI sayılır.
     * Aynı gruptaki POI'ler dedup_min_distance_m içindeyse tek sayılır.
     * Sonuç normalize edilir: count / (cap_per_group * total_groups) * max_score
     */
    private function calculateDensityScore(array $classified): int
    {
        $maxScore = $this->config['density']['max_score'] ?? 30;
        $capPerGroup = $this->config['density']['cap_per_group'] ?? 5;
        $dedupMinM = $this->config['density']['dedup_min_distance_m'] ?? 50;
        $totalGroups = $this->config['coverage']['total_groups'] ?? 7;

        $totalCounted = 0;

        foreach ($classified as $groupPois) {
            $uniquePois = $this->deduplicatePois($groupPois['pois'], $dedupMinM);
            $totalCounted += min($capPerGroup, count($uniquePois));
        }

        $expectedMax = $capPerGroup * $totalGroups; // 5 * 7 = 35
        $score = ($expectedMax > 0) ? ($totalCounted / $expectedMax) * $maxScore : 0;

        return (int) min($maxScore, round($score));
    }

    /**
     * Aynı gruptaki yakın POI'leri deduplicate et.
     */
    private function deduplicatePois(array $pois, int $minDistanceM): array
    {
        if (empty($pois)) {
            return [];
        }

        // Mesafeye göre sırala (en yakından uzağa)
        usort($pois, fn($a, $b) => $a['distance_m'] <=> $b['distance_m']);

        $unique = [$pois[0]];

        for ($i = 1; $i < count($pois); $i++) {
            $isDuplicate = false;
            foreach ($unique as $existing) {
                // Aynı gruptaki iki POI birbirine çok yakınsa → duplicate
                if (abs($pois[$i]['distance_m'] - $existing['distance_m']) < $minDistanceM) {
                    $isDuplicate = true;
                    break;
                }
            }
            if (!$isDuplicate) {
                $unique[] = $pois[$i];
            }
        }

        return $unique;
    }

    /**
     * Çeşitlilik skoru hesapla (0–30).
     *
     * Kaç farklı grup mevcut → orantılı skor.
     * min_groups_for_full'a ulaşırsa full score.
     */
    private function calculateCoverageScore(array $classified): int
    {
        $maxScore = $this->config['coverage']['max_score'] ?? 30;
        $minGroupsForFull = $this->config['coverage']['min_groups_for_full'] ?? 5;

        $presentGroups = count($classified);

        if ($presentGroups >= $minGroupsForFull) {
            return $maxScore;
        }

        return (int) round(($presentGroups / $minGroupsForFull) * $maxScore);
    }

    /**
     * Güven skoru ve etiketi hesapla.
     *
     * @return array{0: int, 1: string} [score, label]
     */
    private function calculateConfidence(int $totalPoi, int $groupCount): array
    {
        $c = $this->config['confidence'] ?? [];

        if ($totalPoi >= ($c['high_min_poi'] ?? 10) && $groupCount >= ($c['high_min_groups'] ?? 4)) {
            return [90, 'HIGH'];
        }

        if ($totalPoi >= ($c['medium_min_poi'] ?? 5) && $groupCount >= ($c['medium_min_groups'] ?? 3)) {
            return [65, 'MEDIUM'];
        }

        if ($totalPoi >= ($c['min_poi_count'] ?? 3) && $groupCount >= ($c['min_group_count'] ?? 2)) {
            return [40, 'LOW'];
        }

        return [15, 'VERY_LOW'];
    }

    /**
     * Top nearby grup listesi oluştur (UI top-3 card).
     *
     * @return array<int, array{group: string, label: string, closest_m: int, count: int}>
     */
    private function buildTopNearbyGroups(array $classified): array
    {
        $result = [];

        foreach ($classified as $groupKey => $groupData) {
            $pois = $groupData['pois'];
            $closestM = PHP_INT_MAX;
            foreach ($pois as $poi) {
                if ($poi['distance_m'] < $closestM) {
                    $closestM = $poi['distance_m'];
                }
            }

            $result[] = [
                'group' => $groupKey,
                'label' => $groupData['label'],
                'closest_m' => $closestM,
                'count' => count($pois),
            ];
        }

        // En yakın mesafeye göre sırala
        usort($result, fn($a, $b) => $a['closest_m'] <=> $b['closest_m']);

        return $result;
    }

    /**
     * Reason code listesi oluştur.
     */
    private function generateReasonCodes(
        array $classified,
        int $coverageScore,
        int $densityScore,
        int $totalPoi,
    ): array {
        $codes = [];
        $coverageMax = $this->config['coverage']['max_score'] ?? 30;
        $densityMax = $this->config['density']['max_score'] ?? 30;

        // Grup bazlı erişim reason'ları (en yakın POI < 750m ise)
        $groupReasonMap = [
            'education'    => 'near_education_access',
            'health'       => 'near_health_access',
            'transport'    => 'strong_transport_access',
            'daily_need'   => 'near_daily_need_access',
            'shopping'     => 'near_shopping_access',
            'food_social'  => 'near_food_social_access',
            'green_leisure' => 'near_green_leisure_access',
        ];

        foreach ($groupReasonMap as $groupKey => $reasonCode) {
            if (!isset($classified[$groupKey])) {
                continue;
            }
            $closestM = PHP_INT_MAX;
            foreach ($classified[$groupKey]['pois'] as $poi) {
                if ($poi['distance_m'] < $closestM) {
                    $closestM = $poi['distance_m'];
                }
            }
            if ($closestM <= 750) {
                $codes[] = $reasonCode;
            }
        }

        // Coverage reason
        $coverageRatio = ($coverageMax > 0) ? $coverageScore / $coverageMax : 0;
        if ($coverageRatio >= 0.7) {
            $codes[] = 'strong_poi_coverage';
        } elseif ($coverageRatio >= 0.4) {
            $codes[] = 'moderate_poi_coverage';
        } else {
            $codes[] = 'weak_poi_coverage';
        }

        // Density reason
        if ($densityMax > 0 && ($densityScore / $densityMax) >= 0.66) {
            $codes[] = 'high_poi_density';
        }

        // Düşük veri uyarısı
        $minPoi = $this->config['confidence']['min_poi_count'] ?? 3;
        if ($totalPoi < ($minPoi * 2)) {
            $codes[] = 'limited_neighborhood_signal';
        }

        return $codes;
    }

    /**
     * Demand modifier hesapla (capped ±10).
     */
    private function calculateDemandModifier(int $signalScore): int
    {
        $dm = $this->config['demand_modifier'] ?? [];
        $maxPositive = $dm['max_positive'] ?? 10;
        $threshold = $dm['threshold'] ?? 50;

        if ($signalScore <= $threshold) {
            return 0;
        }

        // signalScore 51–100 → modifier 0–10 orantılı
        $range = 100 - $threshold; // 50
        $excess = $signalScore - $threshold;
        $modifier = ($range > 0) ? round(($excess / $range) * $maxPositive) : 0;

        return (int) min($maxPositive, $modifier);
    }

    /**
     * Deterministik Türkçe özet oluştur.
     */
    private function buildHumanSummary(array $topGroups, int $signalScore): string
    {
        $templates = $this->config['summary_templates'] ?? [];

        if ($signalScore >= 65) {
            $templateKey = 'strong';
        } elseif ($signalScore >= 35) {
            $templateKey = 'moderate';
        } else {
            $templateKey = 'weak';
        }

        $template = $templates[$templateKey] ?? 'Konum sinyali hesaplandı.';

        // Top 3 grubun label'larını al
        $groupLabels = array_slice(
            array_map(fn($g) => $g['label'], $topGroups),
            0,
            3,
        );

        $groupsStr = !empty($groupLabels)
            ? implode(', ', $groupLabels)
            : 'çevresel hizmet';

        return str_replace('{:groups}', $groupsStr, $template);
    }
}

<?php

namespace App\Services;

use App\Models\Ilan;
use App\Models\POI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Yalıhan Cortex AI: Spatial Intelligence Service
 *
 * Context7 Standard: C7-SPATIAL-INTELLIGENCE-2025-12-24
 * Version: 2.1.0
 *
 * Konum bazlı akıllı analiz:
 * - Mekansal veritabanı (POI) entegrasyonu
 * - Gerçek yürünebilirlik skoru hesaplama
 * - AI için semantik bağlam (Context) üretimi
 */
class CortexSpatialIntelligenceService
{
    /**
     * Spatial + ROI combined data
     */
    public function getSpatialWithROI(int $ilanId, bool $useCache = true): ?array
    {
        $cacheKey = "cortex_spatial_v2_{$ilanId}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori'])->find($ilanId);
        if (!$ilan) return null;

        $spatialData = $this->extractSpatialData($ilan);

        // ROI data (Placeholder for actual ROI engine)
        $roiData = [
            'score' => 85,
            'investment_potential' => 'high',
            'roi_percentage' => 12.5
        ];

        $result = [
            'ilan_id' => $ilan->id,
            'spatial' => $spatialData,
            'cortex_ai' => $roiData,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => '2.1.0'
            ]
        ];

        if ($useCache) {
            Cache::put($cacheKey, $result, now()->addMinutes(15));
        }

        return $result;
    }

    /**
     * Extract spatial context and calculate scores
     */
    private function extractSpatialData(Ilan $ilan): array
    {
        $locationData = is_string($ilan->location_data) ? json_decode($ilan->location_data, true) : $ilan->location_data;
        $lat = $locationData['latitude'] ?? $locationData['lat'] ?? null;
        $lng = $locationData['longitude'] ?? $locationData['lng'] ?? null;

        if (!$lat || !$lng) {
            return ['error_code' => 'no_coordinates'];
        }

        return $this->getContextFromCoordinates((float)$lat, (float)$lng, $ilan);
    }

    /**
     * Get spatial context directly from coordinates (for Wizard Step 3)
     */
    public function getContextFromCoordinates(float $lat, float $lng, ?Ilan $ilan = null): array
    {
        // 1. Get Nearby POIs from Local Spatial Cache
        $nearbyPois = POI::active()
            ->nearby($lat, $lng, 2000) // 2km radius
            ->get();

        // 2. Calculate Walkability Score (0-100)
        $scores = $this->calculateSmartScores($nearbyPois);

        // 3. Build Semantic Context for AI
        $context = $this->buildSemanticContext($nearbyPois);

        return [
            'coordinates' => ['lat' => $lat, 'lng' => $lng],
            'address' => [
                'full' => $ilan ? ($ilan->mahalle?->name . ', ' . $ilan->ilce?->name . ', ' . $ilan->il?->name) : 'Bilinmeyen Konum'
            ],
            'scores' => $scores,
            'nearby_summary' => $nearbyPois->groupBy('category')->map->count(),
            'semantic_context' => $context,
            'pois' => $nearbyPois->take(10) // Top 10 for UI
        ];
    }

    /**
     * Calculate accessibility score based on POI density and distance
     * Metro: +40, Market: +20, School: +20, Hospital: +20
     */
    private function calculateSmartScores($pois): array
    {
        $score = 0;
        $breakdown = [];

        // Metro/Ulaşım (P0: +40)
        $metro = $pois->where('type', 'ulasim')->first(); // context7-ignore
        if ($metro) {
            $mDist = $metro->distance_km ?? 1.0;
            $mScore = $mDist < 0.5 ? 40 : (1.0 - $mDist) * 40;
            $score += max(0, $mScore);
            $breakdown['transport'] = round($mScore);
        }

        // Alışveriş (P1: +20)
        $market = $pois->where('category', 'Alışveriş')->first();
        if ($market) {
            $score += 20;
            $breakdown['shopping'] = 20;
        }

        // Eğitim (P1: +20)
        $school = $pois->where('category', 'Eğitim')->first();
        if ($school) {
            $score += 20;
            $breakdown['education'] = 20;
        }

        // Sağlık (P1: +20)
        $hosp = $pois->where('category', 'Sağlık')->first();
        if ($hosp) {
            $score += 20;
            $breakdown['health'] = 20;
        }

        return [
            'walkability_score' => min(100, round($score)),
            'breakdown' => $breakdown
        ];
    }

    /**
     * Build human-readable description for AI Prompting
     */
    private function buildSemanticContext($pois): string
    {
        $sentences = [];

        // 1. Transportation
        $closestUlasim = $pois->where('type', 'ulasim')->first(); // context7-ignore
        if ($closestUlasim) {
            $mDist = $closestUlasim->distance_km ?? 1.0;
            $mins = round(($mDist * 1000) / 80);
            $mins = max(1, $mins);

            if ($mins <= 5) {
                $sentences[] = "Ulaşım avantajı: {$closestUlasim->name} durağına sadece {$mins} dakikalık çok kısa bir yürüme mesafesinde, şehir içi ulaşım için mükemmel bir konumda.";
            } else {
                $sentences[] = "Konum avantajı: {$closestUlasim->name} noktasına {$mins} dakikalık yürüme mesafesiyle kolay ulaşım imkanı sunuyor.";
            }
        }

        // 2. Shopping & Daily Needs
        $shoppingCount = $pois->where('category', 'Alışveriş')->count();
        if ($shoppingCount > 5) {
            $sentences[] = "Zengin sosyal imkanlar: Çevresinde {$shoppingCount} farklı market ve alışveriş noktası bulunuyor, tüm günlük ihtiyaçlarınız elinizin altında.";
        } elseif ($shoppingCount > 0) {
            $sentences[] = "Günlük ihtiyaçlar kolaylığı: Yakın çevresindeki market ve alışveriş seçenekleriyle pratik bir yaşam sunuyor.";
        }

        // 3. Green Areas & Parks
        $greenAreas = $pois->where('category', 'Park & Yeşil Alan')->count();
        if ($greenAreas > 0) {
            $sentences[] = "Huzurlu çevre: Bölgedeki park ve yeşil alanlara olan yakınlığıyla doğayla iç içe, ferah bir yaşam atmosferine sahip.";
        }

        // 4. Education & Health (Added)
        $eduCount = $pois->where('category', 'Eğitim')->count();
        if ($eduCount > 0) {
            $sentences[] = "Eğitim kurumlarına yakınlığıyla aileler için stratejik bir noktada yer alıyor.";
        }

        $healthCount = $pois->where('category', 'Sağlık')->count();
        if ($healthCount > 0) {
            $sentences[] = "Sağlık kuruluşlarına kolay erişim mesafesinde, güvenli bir konumda.";
        }

        return implode(' ', $sentences);
    }

    /**
     * External Entry Point for Cortex AI
     */
    public function getEnvironmentContext(int $ilanId): array
    {
        $data = $this->getSpatialWithROI($ilanId);
        return [
            'walkability_score' => $data['spatial']['scores']['walkability_score'] ?? 0,
            'summary' => $data['spatial']['semantic_context'] ?? '',
            'pois' => $data['spatial']['pois'] ?? []
        ];
    }
    /**
     * Batch get spatial data for multiple listings
     */
    public function getBatchSpatialData(array $ilanIds): array
    {
        $results = [];
        
        // Eager load relationships for efficiency
        $ilanlar = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori'])
            ->whereIn('id', $ilanIds)
            ->get();

        foreach ($ilanlar as $ilan) {
            $spatial = $this->getSpatialWithROI($ilan->id);
            if ($spatial) {
                $results[$ilan->id] = $spatial;
            }
        }

        return $results;
    }
}

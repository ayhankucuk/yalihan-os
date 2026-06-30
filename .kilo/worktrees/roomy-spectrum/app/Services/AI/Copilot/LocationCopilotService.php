<?php

namespace App\Services\AI\Copilot;

use App\Services\Location\PoiService;
use App\Services\MarketIntelligence\LocationIntelligenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * §10 Location / POI / Polygon Copilot
 *
 * POI scoring, polygon validation, reverse geocode integration,
 * coordinate consistency checking, location value scoring.
 */
class LocationCopilotService
{
    public function __construct(
        protected PoiService $poiService,
        protected LocationIntelligenceService $locationIntelligence,
    ) {}

    /**
     * Analyze location data for a listing and return intelligence.
     */
    public function analyzeListing(int $ilanId): array
    {
        try {
            $ilan = DB::table('ilanlar')->find($ilanId);
            if (!$ilan) {
                return ['error' => 'İlan bulunamadı'];
            }

            $result = [
                'coordinate_quality' => $this->assessCoordinateQuality($ilan),
                'location_consistency' => $this->checkLocationConsistency($ilan),
                'polygon_durumu' => $this->assessPolygonDurumu($ilan),
            ];

            // POI analysis (only if valid coordinates exist)
            if (!empty($ilan->lat) && !empty($ilan->lng) && $ilan->lat != 0 && $ilan->lng != 0) {
                $result['poi_score'] = $this->getPoiScore($ilan->lat, $ilan->lng);
                $result['location_value'] = $this->getLocationValue($ilan->lat, $ilan->lng);
            }

            return $result;
        } catch (\Exception $e) {
            Log::warning('LocationCopilotService analysis failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * §10.1 Coordinate quality assessment
     */
    protected function assessCoordinateQuality(object $ilan): array
    {
        $lat = $ilan->lat ?? null;
        $lng = $ilan->lng ?? null;

        if (empty($lat) || empty($lng)) {
            return [
                'quality' => 'missing',
                'label' => 'Koordinat yok',
                'score' => 0,
                'issues' => ['Harita koordinatı girilmemiş'],
            ];
        }

        $issues = [];
        $score = 100;

        // Check for zero coordinates
        if ($lat == 0 || $lng == 0) {
            $issues[] = 'Koordinat sıfır — geçersiz konum';
            $score -= 100;
        }

        // Turkey bounding box validation
        if ($lat < 35.8 || $lat > 42.1 || $lng < 25.6 || $lng > 44.8) {
            $issues[] = 'Koordinat Türkiye sınırları dışında';
            $score -= 50;
        }

        // Precision check (too few decimal places = low accuracy)
        $latPrecision = strlen((string) ($lat - floor($lat))) - 2;
        $lngPrecision = strlen((string) ($lng - floor($lng))) - 2;

        if ($latPrecision < 4 || $lngPrecision < 4) {
            $issues[] = 'Düşük hassasiyet koordinat (bina seviyesi değil)';
            $score -= 15;
        }

        $quality = match (true) {
            $score >= 85 => 'excellent',
            $score >= 60 => 'good',
            $score >= 30 => 'poor',
            default => 'invalid',
        };

        return [
            'quality' => $quality,
            'label' => match ($quality) {
                'excellent' => 'Mükemmel',
                'good' => 'İyi',
                'poor' => 'Düşük',
                'invalid' => 'Geçersiz',
            },
            'score' => max(0, $score),
            'lat' => $lat,
            'lng' => $lng,
            'issues' => $issues,
        ];
    }

    /**
     * §10.2 Location consistency check
     * Verifies il/ilce matches coordinate location.
     */
    protected function checkLocationConsistency(object $ilan): array
    {
        $result = [
            'consistent' => true,
            'issues' => [],
        ];

        // Check: Has coordinates but no il/ilce
        if (!empty($ilan->lat) && !empty($ilan->lng) && $ilan->lat != 0 && $ilan->lng != 0) {
            if (empty($ilan->il_id)) {
                $result['consistent'] = false;
                $result['issues'][] = 'Koordinat var ama il bilgisi yok — reverse geocode önerilir';
            }
            if (empty($ilan->ilce_id)) {
                $result['issues'][] = 'İlçe bilgisi eksik';
            }
        }

        // Check: Has il/ilce but no coordinates
        if (!empty($ilan->il_id) && (empty($ilan->lat) || empty($ilan->lng) || $ilan->lat == 0)) {
            $result['issues'][] = 'İl/ilçe seçili ama harita koordinatı yok';
        }

        if (!empty($result['issues'])) {
            $result['consistent'] = false;
        }

        return $result;
    }

    /**
     * §10.3 Polygon/geometry durum assessment
     */
    protected function assessPolygonDurumu(object $ilan): array
    {
        $result = [
            'has_polygon' => false,
            'geometry_type' => $ilan->geometry_type ?? null,
            'recommendation' => null,
        ];

        if (!empty($ilan->geometry)) {
            $result['has_polygon'] = true;

            // Validate GeoJSON structure
            $geometry = json_decode($ilan->geometry, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['valid'] = false;
                $result['recommendation'] = 'Polygon verisi geçersiz JSON formatında. Yeniden çizilmeli.';
            } else {
                $result['valid'] = true;
                $result['point_count'] = count($geometry['coordinates'][0] ?? []);
            }
        } else {
            // Check if this category type should have polygon
            $kategoriAdi = '';
            if (!empty($ilan->ana_kategori_id)) {
                $kategoriAdi = mb_strtolower(DB::table('ilan_kategorileri')
                    ->where('id', $ilan->ana_kategori_id)
                    ->value('name') ?? '');
            }

            $arsaKeywords = ['arsa', 'arazi', 'tarla', 'zeytinlik'];
            foreach ($arsaKeywords as $keyword) {
                if (str_contains($kategoriAdi, $keyword)) {
                    $result['recommendation'] = 'Bu ' . $keyword . ' ilanı için parsel çizimi önerilir.';
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * §10.4 POI scoring — wraps existing PoiService
     */
    protected function getPoiScore(float $lat, float $lng): array
    {
        try {
            $nearby = $this->poiService->findNearby($lat, $lng, 2);

            $highlights = $this->poiService->getHighlights($lat, $lng, 2);

            return [
                'poi_count' => $nearby->count(),
                'highlights' => $highlights,
                'score_label' => match (true) {
                    $nearby->count() >= 15 => 'Çok iyi konumda',
                    $nearby->count() >= 8 => 'İyi konumda',
                    $nearby->count() >= 3 => 'Orta konumda',
                    default => 'İzole konumda',
                },
            ];
        } catch (\Exception $e) {
            Log::warning('LocationCopilotService POI scoring failed', ['error' => $e->getMessage()]);
            return ['poi_count' => 0, 'score_label' => 'Hesaplanamadı'];
        }
    }

    /**
     * §10.5 Location value — wraps existing LocationIntelligenceService
     */
    protected function getLocationValue(float $lat, float $lng): array
    {
        try {
            $insight = $this->locationIntelligence->analyze($lat, $lng);

            return [
                'location_signal_score' => $insight->location_signal_score ?? 0,
                'confidence' => $insight->confidence_label ?? 'LOW',
                'poi_access_score' => $insight->poi_access_score ?? 0,
                'poi_density_score' => $insight->poi_density_score ?? 0,
                'poi_coverage_score' => $insight->poi_coverage_score ?? 0,
                'top_groups' => $insight->top_nearby_groups ?? [],
                'human_summary' => $insight->human_summary ?? '',
                'demand_modifier' => $insight->demand_modifier ?? 0,
            ];
        } catch (\Exception $e) {
            Log::warning('LocationCopilotService location value failed', ['error' => $e->getMessage()]);
            return ['location_signal_score' => 0, 'confidence' => 'ERROR'];
        }
    }

    /**
     * Aggregate location quality stats across all active listings.
     */
    public function aggregateLocationStats(): array
    {
        try {
            $total = DB::table('ilanlar')->where('yayin_durumu', 1)->count();
            $withCoords = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->where('lat', '!=', 0)
                ->where('lng', '!=', 0)
                ->count();

            $withIl = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereNotNull('il_id')
                ->count();

            $withGeometry = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereNotNull('geometry')
                ->count();

            return [
                'total_active' => $total,
                'with_coordinates' => $withCoords,
                'with_il' => $withIl,
                'with_geometry' => $withGeometry,
                'coordinate_coverage' => $total > 0 ? round(($withCoords / $total) * 100) : 0,
                'il_coverage' => $total > 0 ? round(($withIl / $total) * 100) : 0,
            ];
        } catch (\Exception $e) {
            Log::warning('LocationCopilotService aggregate failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}

<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * 🛡️ CortexAnalyticsService
 *
 * Dedicated service for architectural compliance.
 * Handles all DB aggregations for analytics dashboards.
 */
class CortexAnalyticsService
{
    /**
     * Get core listing statistics
     */
    protected $analyticsRepository;

    public function __construct(\App\Repositories\Analytics\CortexAnalyticsRepository $analyticsRepository)
    {
        $this->analyticsRepository = $analyticsRepository;
    }

    public function getCoreStats(): array
    {
        return $this->analyticsRepository->getCoreStats();
    }

    /**
     * Get ROI metrics for analyzed listings
     */
    public function getROIMetrics(): array
    {
        $roiScores = DB::table('ilanlar')
            ->select(
                'additional_metadata->cortex_ai->cortex_score as cortex_score',
                'additional_metadata->cortex_ai->roi_data->roi_percentage as roi_percentage'
            )
            ->whereNotNull('additional_metadata->cortex_ai->cortex_score')
            ->get();

        if ($roiScores->isEmpty()) {
            return [
                'average_cortex_score' => 0,
                'average_roi_percentage' => 0,
                'excellent_opportunities' => 0,
                'good_opportunities' => 0,
                'moderate_opportunities' => 0,
                'total_analyzed' => 0,
            ];
        }

        $cortexScores = $roiScores->pluck('cortex_score')->map(fn ($s) => (float) $s);
        $roiPercentages = $roiScores->pluck('roi_percentage')->map(fn ($r) => (float) $r);

        return [
            'average_cortex_score' => round($cortexScores->avg(), 2),
            'average_roi_percentage' => round($roiPercentages->avg(), 2),
            'excellent_opportunities' => $cortexScores->filter(fn ($s) => $s >= 8)->count(),
            'good_opportunities' => $cortexScores->filter(fn ($s) => $s >= 6 && $s < 8)->count(),
            'moderate_opportunities' => $cortexScores->filter(fn ($s) => $s >= 4 && $s < 6)->count(),
            'total_analyzed' => $roiScores->count(),
        ];
    }

    /**
     * Get investment opportunities (Golden Visa)
     */
    public function getGoldenVisaOpportunities(float $minInvestmentTRY): Collection
    {
        return DB::table('ilanlar')
            ->where('yayin_durumu', 'active') // context7-ignore
            ->where('fiyat', '>=', $minInvestmentTRY)
            ->whereIn('ana_kategori_id', [1, 2, 3, 7]) // Konut, Yazlık, Villa, Golden Visa
            ->get();
    }

    /**
     * Get location-based distribution
     */
    public function getCityDistribution(int $limit = 10): Collection
    {
        return DB::table('ilanlar')
            ->selectRaw('il_id, COUNT(*) as count')
            ->where('yayin_durumu', 'active') // context7-ignore
            ->groupBy('il_id')
            ->orderByDesc('count') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Get count for premium city listings
     */
    public function getPremiumCityCount(array $premiumCities): int
    {
        return DB::table('ilanlar')
            ->whereIn('il_id', $premiumCities)
            ->where('yayin_durumu', 'active') // context7-ignore
            ->count();
    }

    /**
     * Get coordinate coverage stats
     */
    public function getCoordinateCoverage(): array
    {
        $total = DB::table('ilanlar')->count();
        $withCoordinates = DB::table('ilanlar')
            ->whereNotNull('location_data->latitude')
            ->whereNotNull('location_data->longitude')
            ->count();

        return [
            'total' => $total,
            'with_coordinates' => $withCoordinates,
            'percentage' => $total > 0 ? round(($withCoordinates / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get latest listings
     */
    public function getRecentListings(int $limit = 5): Collection
    {
        return DB::table('ilanlar')
            ->where('yayin_durumu', 'active') // context7-ignore
            ->orderByDesc('created_at') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Get top viewed listings for a period
     */
    public function getTopViewed(string $startDate, int $limit = 5): Collection
    {
        return DB::table('ilan_goruntulenme_gunluk')
            ->join('ilanlar', 'ilan_goruntulenme_gunluk.ilan_id', '=', 'ilanlar.id')
            ->where('ilan_goruntulenme_gunluk.tarih', '>=', $startDate)
            ->selectRaw('ilan_goruntulenme_gunluk.ilan_id, SUM(ilan_goruntulenme_gunluk.adet) as views, ilanlar.baslik, ilanlar.fiyat, ilanlar.para_birimi')
            ->groupBy('ilan_goruntulenme_gunluk.ilan_id', 'ilanlar.baslik', 'ilanlar.fiyat', 'ilanlar.para_birimi')
            ->orderByDesc('views') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Get category performance by views
     */
    public function getCategoryPerformance(string $startDate, int $limit = 5): Collection
    {
        return $this->analyticsRepository->getCategoryPerformance($startDate, $limit);
    }

    /**
     * Get location performance by views
     */
    public function getLocationPerformance(string $startDate, int $limit = 5): Collection
    {
        return $this->analyticsRepository->getLocationPerformance($startDate, $limit);
    }

    /**
     * Get advisor performance by views
     */
    public function getAdvisorPerformance(string $startDate, int $limit = 5): Collection
    {
        return DB::table('ilan_goruntulenme_gunluk as v')
            ->join('ilanlar as i', 'v.ilan_id', '=', 'i.id')
            ->leftJoin('users as u', 'i.danisman_id', '=', 'u.id')
            ->where('v.tarih', '>=', $startDate)
            ->selectRaw('COALESCE(u.name, "Danışman") as name, SUM(v.adet) as views, COUNT(DISTINCT i.id) as listings')
            ->groupBy('name')
            ->orderByDesc('views') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Get daily views for a period
     */
    public function getDailyViews(string $startDate): Collection
    {
        return DB::table('ilan_goruntulenme_gunluk')
            ->where('tarih', '>=', $startDate)
            ->selectRaw('tarih, SUM(adet) as total')
            ->groupBy('tarih')
            ->orderBy('tarih') // context7-ignore
            ->get();
    }

    /**
     * Get device distribution for a period
     */
    public function getDeviceDistribution(string $startDate): Collection
    {
        return DB::table('ilan_goruntulenme_gunluk')
            ->where('tarih', '>=', $startDate)
            ->selectRaw('cihaz, SUM(adet) as total')
            ->groupBy('cihaz')
            ->get();
    }

    /**
     * Get danisman leaderboard metrics
     */
    public function getDanismanLeaderboard(int $limit = 50, string $period = 'all'): Collection
    {
        return $this->analyticsRepository->getDanismanLeaderboard($limit, $period);
    }

    /**
     * Get danisman individual rank and score
     */
    public function getDanismanRankData(int $danismanId): array
    {
        $score = DB::table('danismanlar_performance_metrics')
            ->where('danisman_id', $danismanId)
            ->where('aktiflik_durumu', true)
            ->avg('overall_score');

        if ($score === null) {
            return ['score' => null, 'rank' => null];
        }

        $rank = DB::table('danismanlar_performance_metrics as dpm')
            ->join('users as u', 'dpm.danisman_id', '=', 'u.id')
            ->where('dpm.aktiflik_durumu', true)
            ->where('u.aktiflik_durumu', true)
            ->groupBy('dpm.danisman_id')
            ->havingRaw('AVG(dpm.overall_score) > ?', [$score])
            ->get()
            ->count() + 1;

        return ['score' => (float) $score, 'rank' => $rank];
    }

    /**
     * Get visual analysis automation stats
     */
    public function getVisualAutomationStats(): array
    {
        $stats = $this->analyticsRepository->getVisualAutomationScores();

        if ($stats->isEmpty()) {
            return [
                'total_analyzed' => 0,
                'average_automation_score' => 0,
                'distribution' => [
                    'high_quality' => 0,
                    'medium_quality' => 0,
                    'low_quality' => 0,
                ],
            ];
        }

        $avgScore = $stats->avg();
        $highQuality = $stats->filter(fn($s) => $s >= 80)->count();
        $mediumQuality = $stats->filter(fn($s) => $s >= 60 && $s < 80)->count();
        $lowQuality = $stats->filter(fn($s) => $s < 60)->count();

        return [
            'total_analyzed' => $stats->count(),
            'average_automation_score' => round($avgScore, 1),
            'distribution' => [
                'high_quality' => $highQuality,
                'medium_quality' => $mediumQuality,
                'low_quality' => $lowQuality,
            ],
        ];
    }

    /**
     * Get benchmark metrics for real estate
     */
    public function getBenchmarkMetrics(array $params): ?array
    {
        $metric = (string) ($params['metric'] ?? '');
        $ilId = $params['il_id'] ?? null;
        $ilceId = $params['ilce_id'] ?? null;
        $kategoriSlug = $params['kategori_slug'] ?? null;

        $since = now()->subDays(90);

        if ($metric === 'price_m2') {
            $query = DB::table('ilanlar')
                ->selectRaw('AVG(fiyat / NULLIF(alan_m2, 0)) as avg')
                ->whereNotNull('fiyat')
                ->whereNotNull('alan_m2')
                ->where('created_at', '>=', $since);

            if ($ilId) $query->where('il_id', $ilId);
            if ($ilceId) $query->where('ilce_id', $ilceId);
            if ($kategoriSlug) $query->where('kategori_slug', $kategoriSlug);

            $row = $query->first();
            return ['avg' => $row ? (float) $row->avg : null];
        }

        if ($metric === 'amortization') {
            $priceCol = Schema::hasColumn('ilanlar', 'fiyat') ? 'fiyat' : 'satis_fiyati';
            $rentCol = Schema::hasColumn('ilanlar', 'aylik_kira') ? 'aylik_kira' : 'kira_bedeli';

            $query = DB::table('ilanlar')
                ->selectRaw("AVG( CASE WHEN {$rentCol} > 0 THEN ({$priceCol} / {$rentCol}) END ) as avg_months")
                ->whereNotNull($priceCol)
                ->whereNotNull($rentCol)
                ->where($rentCol, '>', 0)
                ->where('created_at', '>=', $since);

            if ($ilId) $query->where('il_id', $ilId);
            if ($ilceId) $query->where('ilce_id', $ilceId);
            if ($kategoriSlug) $query->where('kategori_slug', $kategoriSlug);

            $row = $query->first();
            return ['avg_months' => $row ? (float) $row->avg_months : null];
        }

        return null;
    }
}





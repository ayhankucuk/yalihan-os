<?php

namespace App\Services\AI\Analytics;

use App\Models\AiFeatureUsage;
use Illuminate\Support\Facades\DB;

/**
 * ��️ SAB SEALED
 * Domain: Monitoring / AI / Analytics / Context
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - aktiflik_durumu ✅ (system health)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class CortexFeatureCoverageService
{
    /**
     * Get feature coverage report for specific category/yayin_tipi
     *
     * @param string|null $kategoriSlug
     * @param string|null $yayinTipiSlug
     * @param int $days Analysis window in days
     * @param int $limit Top features limit
     * @return array Coverage report
     */
    public function getCoverageReport(
        ?string $kategoriSlug = null,
        ?string $yayinTipiSlug = null,
        int $days = 30,
        int $limit = 20
    ): array {
        $since = now()->subDays($days);

        $query = AiFeatureUsage::query()
            ->where('created_at', '>=', $since);

        if ($kategoriSlug) {
            $query->where('kategori_slug', $kategoriSlug);
        }

        if ($yayinTipiSlug) {
            $query->where('yayin_tipi_slug', $yayinTipiSlug);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return $this->emptyReport($kategoriSlug, $yayinTipiSlug, $days);
        }

        // Aggregate metrics
        $totalCalls = $records->count();
        $avgUsedRatio = $records->avg('used_ratio');

        // Flatten all features
        $allProvidedFeatures = [];
        $allUsedFeatures = [];

        foreach ($records as $record) {
            $provided = $record->features_in_context ?? [];
            $used = $record->features_used ?? [];

            foreach ($provided as $slug) {
                if (!isset($allProvidedFeatures[$slug])) {
                    $allProvidedFeatures[$slug] = 0;
                }
                $allProvidedFeatures[$slug]++;
            }

            foreach ($used as $slug) {
                if (!isset($allUsedFeatures[$slug])) {
                    $allUsedFeatures[$slug] = 0;
                }
                $allUsedFeatures[$slug]++;
            }
        }

        // Sort and limit
        arsort($allUsedFeatures);
        $topUsed = array_slice($allUsedFeatures, 0, $limit, true);

        // Never used = provided but not in used list
        $neverUsed = array_keys(array_diff_key($allProvidedFeatures, $allUsedFeatures));

        return [
            'scope' => [
                'kategori_slug' => $kategoriSlug ?? 'all',
                'yayin_tipi_slug' => $yayinTipiSlug ?? 'all',
            ],
            'window_days' => $days,
            'total_calls' => $totalCalls,
            'avg_used_ratio' => round($avgUsedRatio, 4),
            'provided_features_avg' => count($allProvidedFeatures) > 0
                ? round(array_sum($allProvidedFeatures) / $totalCalls, 1)
                : 0,
            'used_features_avg' => count($allUsedFeatures) > 0
                ? round(array_sum($allUsedFeatures) / $totalCalls, 1)
                : 0,
            'top_used_features' => array_map(
                fn($slug, $count) => ['slug' => $slug, 'count' => $count],
                array_keys($topUsed),
                array_values($topUsed)
            ),
            'never_used_features' => array_values($neverUsed),
            'unique_features_provided' => count($allProvidedFeatures),
            'unique_features_used' => count($allUsedFeatures),
        ];
    }

    /**
     * Get global coverage summary (all categories)
     */
    public function getGlobalSummary(int $days = 30): array
    {
        $since = now()->subDays($days);

        $byCategory = AiFeatureUsage::query()
            ->where('created_at', '>=', $since)
            ->select('kategori_slug', 'yayin_tipi_slug')
            ->selectRaw('COUNT(*) as calls')
            ->selectRaw('AVG(used_ratio) as avg_ratio')
            ->groupBy('kategori_slug', 'yayin_tipi_slug')
            ->orderByDesc('calls') // context7-ignore
            ->get();

        $totalRecords = AiFeatureUsage::where('created_at', '>=', $since)->count();
        $avgRatioGlobal = AiFeatureUsage::where('created_at', '>=', $since)->avg('used_ratio');

        return [
            'window_days' => $days,
            'total_calls' => $totalRecords,
            'avg_used_ratio_global' => round($avgRatioGlobal ?? 0, 4),
            'by_category' => $byCategory->map(fn($item) => [
                'kategori_slug' => $item->kategori_slug,
                'yayin_tipi_slug' => $item->yayin_tipi_slug,
                'calls' => $item->calls,
                'avg_used_ratio' => round($item->avg_ratio, 4),
            ])->toArray(),
        ];
    }

    /**
     * Get top used features across all categories (for Smart Context Filter)
     *
     * @param string $kategoriSlug
     * @param string|null $yayinTipiSlug
     * @param int $days
     * @param int $limit
     * @return array Array of feature slugs sorted by usage frequency
     */
    public function getTopUsedFeatures(
        string $kategoriSlug,
        ?string $yayinTipiSlug = null,
        int $days = 30,
        int $limit = 15
    ): array {
        $since = now()->subDays($days);

        $query = AiFeatureUsage::query()
            ->where('kategori_slug', $kategoriSlug)
            ->where('created_at', '>=', $since);

        if ($yayinTipiSlug) {
            $query->where('yayin_tipi_slug', $yayinTipiSlug);
        }

        $records = $query->get();

        $usageCounts = [];
        foreach ($records as $record) {
            foreach ($record->features_used ?? [] as $slug) {
                if (!isset($usageCounts[$slug])) {
                    $usageCounts[$slug] = 0;
                }
                $usageCounts[$slug]++;
            }
        }

        arsort($usageCounts);

        return array_slice(array_keys($usageCounts), 0, $limit);
    }

    /**
     * Empty report template
     */
    private function emptyReport(?string $kategoriSlug, ?string $yayinTipiSlug, int $days): array
    {
        return [
            'scope' => [
                'kategori_slug' => $kategoriSlug ?? 'all',
                'yayin_tipi_slug' => $yayinTipiSlug ?? 'all',
            ],
            'window_days' => $days,
            'total_calls' => 0,
            'avg_used_ratio' => 0,
            'provided_features_avg' => 0,
            'used_features_avg' => 0,
            'top_used_features' => [],
            'never_used_features' => [],
            'unique_features_provided' => 0,
            'unique_features_used' => 0,
            'notes' => ['No data available for this scope and time window'],
        ];
    }
}

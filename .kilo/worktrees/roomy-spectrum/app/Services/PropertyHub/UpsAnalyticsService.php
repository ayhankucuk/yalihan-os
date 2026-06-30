<?php

namespace App\Services\PropertyHub;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\TemplateChangeLog;
use App\Models\YayinTipiSablonu;

class UpsAnalyticsService
{
    /**
     * Build the complete analytics dashboard data.
     *
     * @param array $filters
     * @return array
     */
    public function buildDashboard(array $filters = []): array
    {
        // 1. Categories (Main Level)
        $categories = IlanKategori::where('seviye', 0)
            ->where('aktiflik_durumu', true)
            ->take(10)
            ->get();

        // 2. Top Features
        $topFeatures = Feature::where('aktiflik_durumu', true)
            ->withCount('assignments')
            ->orderByDesc('assignments_count') // context7-ignore
            ->take(15)
            ->get();

        // 3. Heatmap Data
        $heatmapData = $this->generateHeatmap($categories, $topFeatures);

        // 4. Coverage Stats
        $coverageStats = $this->calculateCoverageStats();

        // 5. Orphaned Features
        $orphanedFeatures = Feature::where('aktiflik_durumu', true)
            ->whereDoesntHave('assignments')
            ->get();

        // 6. Metrics
        $metrics = [
            'avg_query_time' => 12.5, // Placeholder
            'cache_hit_rate' => 85,   // Placeholder
            'templates_created_today' => TemplateChangeLog::whereDate('created_at', today())->count(),
            'ai_suggestions_accepted' => 72, // Placeholder
        ];

        return compact(
            'categories',
            'topFeatures',
            'heatmapData',
            'coverageStats',
            'orphanedFeatures',
            'metrics'
        );
    }

    /**
     * Generate Hitmap Data
     */
    /**
     * Generate Hitmap Data (Optimized for Performance)
     * Replaced nested loops with in-memory aggregation.
     */
    private function generateHeatmap($categories, $topFeatures): array
    {
        $heatmapData = [];
        $featureIds = $topFeatures->pluck('id')->toArray();
        $categoryIds = $categories->pluck('id')->toArray();

        // 1. Initialize Matrix
        foreach ($featureIds as $fid) {
            foreach ($categoryIds as $cid) {
                $heatmapData[$fid][$cid] = 0;
            }
        }

        // 2. Fetch All Relevant Assignments (1 Query)
        $assignments = FeatureAssignment::whereIn('feature_id', $featureIds)
            ->whereIn('assignable_type', [YayinTipiSablonu::class, \App\Models\AltKategoriYayinTipi::class])
            ->get();

        // 3. Build Maps (Minimal Queries)

        // Map: PivotID -> MainCategoryID
        // We load all pivots to map them to their Main Category (via AltKategori parent)
        $pivotMap = \App\Models\AltKategoriYayinTipi::with('altKategori')
            ->get()
            ->mapWithKeys(function ($pivot) {
                $mainCatId = $pivot->altKategori->parent_id ?? null;
                return [$pivot->id => $mainCatId];
            });

        // Map: GlobalID -> [MainCategoryIDs]
        // Which main categories utilize this Global Template?
        $globalLinkMap = [];
        $yayinTipleri = YayinTipiSablonu::with('altKategoriler')->get();
        foreach ($yayinTipleri as $yt) {
            // Collect unique Main Category IDs where this YayinTipi is active via subcategories
            $mainCatIds = $yt->altKategoriler->pluck('parent_id')->unique()->filter()->values()->toArray();
            $globalLinkMap[$yt->id] = $mainCatIds;
        }

        // 4. Aggregate In-Memory
        foreach ($assignments as $assignment) {
            $fid = $assignment->feature_id;

            if ($assignment->assignable_type === YayinTipiSablonu::class) {
                // Global: Add to ALL Main Categories where this YayinTipi is active
                $targetMainCats = $globalLinkMap[$assignment->assignable_id] ?? [];
                foreach ($targetMainCats as $mck) {
                    if (isset($heatmapData[$fid][$mck])) {
                        $heatmapData[$fid][$mck]++;
                    }
                }
            } elseif ($assignment->assignable_type === \App\Models\AltKategoriYayinTipi::class) {
                // Pivot: Add to Specific Main Category
                $targetMainCat = $pivotMap[$assignment->assignable_id] ?? null;
                if ($targetMainCat && isset($heatmapData[$fid][$targetMainCat])) {
                    $heatmapData[$fid][$targetMainCat]++;
                }
            }
        }

        return $heatmapData;
    }

    // applyMainCategoryFilter removed as it's no longer needed for N+1 fix

    /**
     * Calculate Coverage Stats
     */
    private function calculateCoverageStats(): array
    {
        $totalYayinTipleri = YayinTipiSablonu::count();
        $withAssignments = YayinTipiSablonu::has('featureAssignments')->count();

        return [
            [
                'name' => 'Template Coverage',
                'percentage' => $totalYayinTipleri > 0
                    ? round(($withAssignments / $totalYayinTipleri) * 100)
                    : 0,
            ],
            [
                'name' => 'Feature Utilization',
                'percentage' => Feature::count() > 0
                    ? round((Feature::has('assignments')->count() / Feature::count()) * 100)
                    : 0,
            ],
        ];
    }
}

<?php

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * UPS Smart Suggestion Service
 *
 * AI-powered feature suggestions based on:
 * - Sibling category patterns
 * - Parent category inheritance
 * - Global popularity
 * - Semantic matching
 *
 * Context7 Compliance:
 * - aktiflik_durumu: Yayin durumu/aktiflik canonical field
 * - display_order: Siralama canonical field
 */
class UpsSmartSuggestionService
{
    /**
     * Suggest features for a category/yayin tipi combination
     */
    public function suggestFeatures(
        IlanKategori $kategori,
        YayinTipiSablonu $yayinTipi,
        int $limit = 20
    ): Collection {
        $suggestions = collect();

        // 1. Sibling categories' popular features (Legacy logic disabled for V2 Global Template)
        // $siblingFeatures = $this->getSiblingCategoriesFeatures($kategori, $yayinTipi);
        // $suggestions = $suggestions->merge($siblingFeatures);

        // 2. Parent category features (inheritance) (Legacy logic disabled for V2 Global Template)
        // $parentFeatures = $this->getParentCategoryFeatures($kategori, $yayinTipi);
        // $suggestions = $suggestions->merge($parentFeatures);

        // 3. Most popular features globally
        $popularFeatures = $this->getMostPopularFeatures();
        $suggestions = $suggestions->merge($popularFeatures);

        // 4. Category name semantic matching
        $semanticFeatures = $this->getSemanticMatches($kategori);
        $suggestions = $suggestions->merge($semanticFeatures);

        // Remove already assigned features
        $existingIds = $this->getExistingFeatureIds($yayinTipi);
        $suggestions = $suggestions->filter(fn($s) => !in_array($s['feature_id'], $existingIds));

        // Score and rank
        $suggestions = $this->scoreAndRank($suggestions);

        return $suggestions->take($limit)->values();
    }

    /**
     * Get quick suggestions for bulk assignment
     */
    public function getQuickSuggestions(YayinTipiSablonu $yayinTipi, int $limit = 10): Collection
    {
        // Get existing feature IDs
        $existingIds = $this->getExistingFeatureIds($yayinTipi);

        // Get most commonly used features not yet assigned
        $popularFeatures = FeatureAssignment::selectRaw('feature_id, COUNT(*) as usage_count', [])
            ->whereNotIn('feature_id', $existingIds)
            ->groupBy('feature_id')
            ->orderByDesc('usage_count') // context7-ignore
            ->take($limit * 2)
            ->get();

        return $popularFeatures
            ->map(function ($item) {
                $feature = Feature::with('category')->find($item->feature_id);
                if (!$feature || !$feature->aktiflik_durumu) {
                    return null;
                }

                return [
                    'feature_id' => $item->feature_id,
                    'feature_name' => $feature->name,
                    'feature_slug' => $feature->slug,
                    'category_name' => $feature->category?->name ?? 'Genel',
                    'usage_count' => $item->usage_count,
                    'confidence' => min(95, 50 + ($item->usage_count * 5)),
                ];
            })
            ->filter()
            ->take($limit)
            ->values();
    }

    /*
     * Legacy Sibling/Parent Logic Disabled
     */
    private function getSiblingCategoriesFeatures(IlanKategori $kategori, YayinTipiSablonu $yayinTipi): Collection
    {
        return collect();
    }

    private function getParentCategoryFeatures(IlanKategori $kategori, YayinTipiSablonu $yayinTipi): Collection
    {
        return collect();
    }

    /**
     * Get most popular features globally
     */
    private function getMostPopularFeatures(): Collection
    {
        return FeatureAssignment::selectRaw('feature_id, COUNT(*) as usage_count', [])
            ->groupBy('feature_id')
            ->orderByDesc('usage_count') // context7-ignore
            ->take(15)
            ->get()
            ->map(fn($item) => [
                'feature_id' => $item->feature_id,
                'source' => 'popular_global',
                'confidence' => min(80, 30 + ($item->usage_count * 2)),
                'reason' => "Sistemde {$item->usage_count} kez kullanılmış",
            ]);
    }

    /**
     * Get semantic matches based on category name
     */
    private function getSemanticMatches(IlanKategori $kategori): Collection
    {
        $categoryName = strtolower($kategori->name);

        // Keyword to feature mapping
        $keywords = [
            'villa' => ['havuz', 'bahce', 'manzara', 'guvenlik', 'otopark', 'teras', 'jakuzi', 'sauna'],
            'daire' => ['asansor', 'balkon', 'otopark', 'site', 'guvenlik', 'kapici'],
            'arsa' => ['imar', 'tapu', 'yol', 'elektrik', 'su', 'kaks', 'taks'],
            'isyeri' => ['wc', 'depo', 'vitrin', 'otopark', 'klima', 'alarm'],
            'konut' => ['esyali', 'balkon', 'isitma', 'klima', 'internet', 'dogalgaz'],
            'yazlik' => ['denize-yakin', 'havuz', 'manzara', 'bahce', 'teras'],
            'mustakil' => ['bahce', 'otopark', 'depo', 'teras', 'manzara'],
            'bina' => ['asansor', 'otopark', 'guvenlik', 'kapici', 'jenerator'],
            'tarla' => ['sulama', 'yol', 'elektrik', 'su', 'verimli-toprak'],
        ];

        $matchedKeywords = [];
        foreach ($keywords as $type => $features) {
            if (str_contains($categoryName, $type)) {
                $matchedKeywords = array_merge($matchedKeywords, $features);
            }
        }

        if (empty($matchedKeywords)) {
            return collect();
        }

        // Find features matching these keywords
        return Feature::where('aktiflik_durumu', true)
            ->where(function ($q) use ($matchedKeywords) {
                foreach ($matchedKeywords as $keyword) {
                    $q->orWhere('slug', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                }
            })
            ->pluck('id')
            ->unique()
            ->map(fn($id) => [
                'feature_id' => $id,
                'source' => 'semantic_match',
                'confidence' => 70,
                'reason' => 'Kategori adıyla anlamsal eşleşme',
            ]);
    }

    /**
     * Get existing feature IDs for yayin tipi
     */
    private function getExistingFeatureIds(YayinTipiSablonu $yayinTipi): array
    {
        return FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $yayinTipi->id)
            ->pluck('feature_id')
            ->toArray();
    }

    /**
     * Score and rank suggestions
     */
    private function scoreAndRank(Collection $suggestions): Collection
    {
        // Group by feature_id and take highest confidence
        return $suggestions
            ->groupBy('feature_id')
            ->map(function ($group) {
                $best = $group->sortByDesc('confidence')->first();
                $feature = Feature::with('category')->find($best['feature_id']);

                if (!$feature || !$feature->aktiflik_durumu) {
                    return null;
                }

                // Combine sources for better reasoning
                $sources = $group->pluck('source')->unique()->values()->toArray();
                $combinedConfidence = min(98, $best['confidence'] + (count($sources) - 1) * 5);

                return [
                    'feature_id' => $best['feature_id'],
                    'feature_name' => $feature->name,
                    'feature_slug' => $feature->slug,
                    'category_name' => $feature->category?->name ?? 'Genel',
                    'type' => $feature->type, // context7-ignore
                    'source' => $best['source'],
                    'sources' => $sources,
                    'confidence' => $combinedConfidence,
                    'reason' => $best['reason'],
                ];
            })
            ->filter()
            ->sortByDesc('confidence');
    }

    /**
     * Get suggestion statistics
     */
    public function getStats(): array
    {
        $totalSuggestions = DB::table('template_change_logs')
            ->where('aksiyon_tipi', 'assign')
            ->where('aciklama', 'like', '%AI%')
            ->count();

        $acceptedSuggestions = DB::table('template_change_logs')
            ->where('aksiyon_tipi', 'assign')
            ->where('aciklama', 'like', '%AI%')
            ->where('aciklama', 'like', '%kabul%')
            ->count();

        return [
            'total_suggestions' => $totalSuggestions,
            'accepted_suggestions' => $acceptedSuggestions,
            'acceptance_rate' => $totalSuggestions > 0
                ? round(($acceptedSuggestions / $totalSuggestions) * 100, 1)
                : 0,
        ];
    }
}

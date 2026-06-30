<?php

namespace App\Services\AI;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Feature Recommender Service
 *
 * Context7 Standardı: C7-AI-FEATURE-RECOMMENDER-2025-12-23
 *
 * Phase 3: AI Foundation
 * - Usage frequency analysis
 * - Semantic similarity detection
 * - Feature recommendation engine
 *
 * Analyzes feature_assignments table to recommend features based on:
 * 1. Usage frequency in similar categories
 * 2. Semantic similarity (parent_id, naming patterns)
 *
 * @package App\Services\AI
 */
class FeatureRecommenderService
{
    /**
     * Cache key prefix for recommendations
     * Pattern: ai_recommendations:recommend:{kategoriId}:{yayinTipiId}
     */
    private const CACHE_PREFIX = 'ai_recommendations';

    /**
     * Cache TTL (24 hours)
     */
    private const CACHE_TTL = 86400;


    /**
     * Minimum frequency threshold (0-100)
     * Features below this threshold won't be recommended
     */
    private const MIN_FREQUENCY_THRESHOLD = 10;

    /**
     * Recommend features for a category and publication type
     *
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication type ID
     * @return Collection<array{feature_id: int, feature: Feature, score: float, reason: string}>
     */
    public function recommend(int $kategoriId, int $yayinTipiId): Collection
    {
        $cacheKey = self::CACHE_PREFIX . ':recommend:' . $kategoriId . ':' . $yayinTipiId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kategoriId, $yayinTipiId) {
            return $this->calculateRecommendations($kategoriId, $yayinTipiId);
        });
    }

    /**
     * Calculate recommendations (without cache)
     *
     * @param int $kategoriId
     * @param int $yayinTipiId
     * @return Collection
     */
    private function calculateRecommendations(int $kategoriId, int $yayinTipiId): Collection
    {
        // Get similar categories
        $similarCategories = $this->getSimilarCategories($kategoriId);

        // Get all features that are NOT already assigned to this category/publication type
        $assignedFeatureIds = $this->getAssignedFeatureIds($kategoriId, $yayinTipiId);

        // ✅ SAB: aktiflik_durumu (canonical field)
        $allFeatures = Feature::where('aktiflik_durumu', true)
            ->whereNotIn('id', $assignedFeatureIds)
            ->with('category')
            ->get();

        // Calculate frequency and score for each feature
        $recommendations = $allFeatures->map(function (Feature $feature) use ($similarCategories, $kategoriId) {
            $frequency = $this->calculateFrequency($feature->id, $similarCategories);
            $score = $this->calculateScore($frequency, $feature, $similarCategories);
            $reason = $this->generateReason($frequency, $feature, $similarCategories);

            return [
                'feature_id' => $feature->id,
                'feature' => $feature,
                'score' => round($score, 2),
                'frequency' => round($frequency, 2),
                'reason' => $reason,
            ];
        })
            ->filter(function ($recommendation) {
                // Filter out recommendations below threshold
                return $recommendation['score'] >= self::MIN_FREQUENCY_THRESHOLD;
            })
            ->sortByDesc('score')
            ->values();

        return $recommendations;
    }

    /**
     * Get similar categories based on parent_id and naming patterns
     *
     * @param int $kategoriId
     * @return Collection<IlanKategori>
     */
    private function getSimilarCategories(int $kategoriId): Collection
    {
        $kategori = IlanKategori::findOrFail($kategoriId);

        $similarCategories = collect();

        // ✅ SAB Madde 7.1: 1. Same parent category (siblings)
        if ($kategori->parent_id) {
            $siblings = IlanKategori::where('parent_id', $kategori->parent_id)
                ->where('id', '!=', $kategoriId)
                ->where('aktiflik_durumu', true) // ✅ SAB compliant
                ->get();
            $similarCategories = $similarCategories->merge($siblings);
        }

        // ✅ SAB Madde 7.1: 2. Same level categories (seviye)
        $sameLevel = IlanKategori::where('seviye', $kategori->seviye)
            ->where('id', '!=', $kategoriId)
            ->where('aktiflik_durumu', true) // ✅ SAB compliant
            ->get();
        $similarCategories = $similarCategories->merge($sameLevel);

        // 3. Parent category (if exists)
        if ($kategori->parent_id) {
            $parent = IlanKategori::find($kategori->parent_id);
            if ($parent && $parent->aktiflik_durumu) { // ✅ SAB compliant
                $similarCategories->push($parent);
            }
        }

        // ✅ SAB Madde 7.1: 4. Children categories (if exists)
        $children = IlanKategori::where('parent_id', $kategoriId)
            ->where('aktiflik_durumu', true) // ✅ SAB compliant
            ->get();
        $similarCategories = $similarCategories->merge($children);

        // 5. Semantic similarity (naming patterns)
        // Example: "Konut" matches "Villa", "Daire", "Müstakil"
        $semanticMatches = $this->findSemanticMatches($kategori);
        $similarCategories = $similarCategories->merge($semanticMatches);

        // Remove duplicates and return
        return $similarCategories->unique('id');
    }

    /**
     * Find semantic matches based on naming patterns
     *
     * @param IlanKategori $kategori
     * @return Collection<IlanKategori>
     */
    private function findSemanticMatches(IlanKategori $kategori): Collection
    {
        $matches = collect();

        // Define semantic groups
        $semanticGroups = [
            'konut' => ['villa', 'daire', 'müstakil', 'rezidans', 'apartman', 'site'],
            'arsa' => ['tarla', 'bahçe', 'zeytinlik', 'bağ'],
            'isyeri' => ['ofis', 'mağaza', 'dükkan', 'büro', 'plaza'],
            'yazlik' => ['villa', 'ev', 'daire'],
        ];

        $kategoriSlug = strtolower($kategori->slug ?? '');
        $kategoriName = strtolower($kategori->name ?? '');

        // Find matching semantic group
        foreach ($semanticGroups as $groupKey => $groupTerms) {
            if ($kategoriSlug === $groupKey || in_array($kategoriSlug, $groupTerms) || 
                $kategoriName === $groupKey || in_array($kategoriName, $groupTerms)) {
                
                // ✅ SAB Madde 7.1: Find categories in the same semantic group
                $groupCategories = IlanKategori::where('aktiflik_durumu', true) // ✅ SAB compliant
                    ->where(function ($query) use ($groupKey, $groupTerms) {
                        $query->where('slug', $groupKey)
                            ->orWhereIn('slug', $groupTerms)
                            ->orWhere('name', 'like', '%' . ucfirst($groupKey) . '%');
                    })
                    ->where('id', '!=', $kategori->id)
                    ->get();

                $matches = $matches->merge($groupCategories);
                break;
            }
        }

        return $matches;
    }

    /**
     * Calculate frequency of feature usage in similar categories
     *
     * Formula: (Feature Usage Count / Total Assignments in Category) * 100
     *
     * @param int $featureId
     * @param Collection<IlanKategori> $similarCategories
     * @return float Frequency percentage (0-100)
     */
    private function calculateFrequency(int $featureId, Collection $similarCategories): float
    {
        if ($similarCategories->isEmpty()) {
            return 0.0;
        }

        $similarCategoryIds = $similarCategories->pluck('id')->toArray();

        // Get publication type IDs for similar categories
        $publicationTypeIds = YayinTipiSablonu::whereIn('kategori_id', $similarCategoryIds)
            ->pluck('id')
            ->toArray();

        // Count feature assignments in similar categories
        $featureUsageCount = FeatureAssignment::where('feature_id', $featureId)
            ->where('is_visible', true)
            ->where(function ($query) use ($similarCategoryIds, $publicationTypeIds) {
                // Check for IlanKategori assignments
                $query->where(function ($q) use ($similarCategoryIds) {
                    $q->where('assignable_type', IlanKategori::class)
                        ->whereIn('assignable_id', $similarCategoryIds);
                });
                
                // Check for YayinTipiSablonu assignments
                if (!empty($publicationTypeIds)) {
                    $query->orWhere(function ($q) use ($publicationTypeIds) {
                        $q->where('assignable_type', YayinTipiSablonu::class)
                            ->whereIn('assignable_id', $publicationTypeIds);
                    });
                }
            })
            ->count();

        // Count total assignments in similar categories
        $totalAssignments = FeatureAssignment::where('is_visible', true)
            ->where(function ($query) use ($similarCategoryIds, $publicationTypeIds) {
                // Check for IlanKategori assignments
                $query->where(function ($q) use ($similarCategoryIds) {
                    $q->where('assignable_type', IlanKategori::class)
                        ->whereIn('assignable_id', $similarCategoryIds);
                });
                
                // Check for YayinTipiSablonu assignments
                if (!empty($publicationTypeIds)) {
                    $query->orWhere(function ($q) use ($publicationTypeIds) {
                        $q->where('assignable_type', YayinTipiSablonu::class)
                            ->whereIn('assignable_id', $publicationTypeIds);
                    });
                }
            })
            ->count();

        if ($totalAssignments === 0) {
            return 0.0;
        }

        // Calculate frequency percentage
        return ($featureUsageCount / $totalAssignments) * 100;
    }

    /**
     * Calculate recommendation score
     *
     * Score combines frequency with feature metadata (is_filterable, is_searchable, etc.)
     *
     * @param float $frequency
     * @param Feature $feature
     * @param Collection $similarCategories
     * @return float Score (0-100)
     */
    private function calculateScore(float $frequency, Feature $feature, Collection $similarCategories): float
    {
        $baseScore = $frequency;

        // Boost score for filterable features (more valuable)
        if ($feature->is_filterable) {
            $baseScore *= 1.1;
        }

        // Boost score for searchable features
        if ($feature->is_searchable) {
            $baseScore *= 1.05;
        }

        // Boost score for required features
        if ($feature->is_required) {
            $baseScore *= 1.15;
        }

        // Boost score if feature category matches similar categories' common categories
        $commonCategoryIds = $this->getCommonCategoryIds($similarCategories);
        if ($feature->feature_category_id && in_array($feature->feature_category_id, $commonCategoryIds)) {
            $baseScore *= 1.1;
        }

        // Cap score at 100
        return min($baseScore, 100.0);
    }

    /**
     * Get common feature category IDs from similar categories
     *
     * @param Collection<IlanKategori> $similarCategories
     * @return array
     */
    private function getCommonCategoryIds(Collection $similarCategories): array
    {
        if ($similarCategories->isEmpty()) {
            return [];
        }

        // Get feature assignments for similar categories
        $categoryIds = $similarCategories->pluck('id')->toArray();

        $commonCategoryIds = FeatureAssignment::where('is_visible', true)
            ->where(function ($query) use ($categoryIds) {
                $query->where(function ($q) use ($categoryIds) {
                    $q->where('assignable_type', IlanKategori::class)
                        ->whereIn('assignable_id', $categoryIds);
                });
            })
            ->with('feature')
            ->get()
            ->pluck('feature.feature_category_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $commonCategoryIds;
    }

    /**
     * Generate human-readable reason for recommendation
     *
     * @param float $frequency
     * @param Feature $feature
     * @param Collection $similarCategories
     * @return string
     */
    private function generateReason(float $frequency, Feature $feature, Collection $similarCategories): string
    {
        $similarCount = $similarCategories->count();

        if ($frequency >= 80) {
            return sprintf(
                'Used in %.1f%% of similar listings (%d similar categories)',
                $frequency,
                $similarCount
            );
        } elseif ($frequency >= 50) {
            return sprintf(
                'Commonly used in similar listings (%.1f%% frequency, %d similar categories)',
                $frequency,
                $similarCount
            );
        } elseif ($frequency >= 25) {
            return sprintf(
                'Occasionally used in similar listings (%.1f%% frequency)',
                $frequency
            );
        } else {
            return sprintf(
                'Rarely used but may be relevant (%.1f%% frequency)',
                $frequency
            );
        }
    }

    /**
     * Get feature IDs already assigned to category/publication type
     *
     * @param int $kategoriId
     * @param int $yayinTipiId
     * @return array
     */
    private function getAssignedFeatureIds(int $kategoriId, int $yayinTipiId): array
    {
        // Get features assigned to category
        $categoryFeatureIds = FeatureAssignment::where('assignable_type', IlanKategori::class)
            ->where('assignable_id', $kategoriId)
            ->where('is_visible', true)
            ->pluck('feature_id')
            ->toArray();

        // Get features assigned to publication type
        $publicationTypeFeatureIds = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $yayinTipiId)
            ->where('is_visible', true)
            ->pluck('feature_id')
            ->toArray();

        // Merge and return unique IDs
        return array_unique(array_merge($categoryFeatureIds, $publicationTypeFeatureIds));
    }

    /**
     * Invalidate recommendation cache for a category
     *
     * @param int $kategoriId
     * @return void
     */
    public function invalidateCache(int $kategoriId): void
    {
        // Get all publication types for this category
        $yayinTipiIds = YayinTipiSablonu::where('kategori_id', $kategoriId)
            ->pluck('id')
            ->toArray();

        // Invalidate cache for each publication type
        foreach ($yayinTipiIds as $yayinTipiId) {
            $cacheKey = self::CACHE_PREFIX . ':recommend:' . $kategoriId . ':' . $yayinTipiId;
            Cache::forget($cacheKey);
        }
    }

    /**
     * Invalidate all recommendation caches
     *
     * Note: This will clear all caches matching the prefix pattern
     * For Redis, consider using pattern-based deletion: Cache::flush() clears all
     *
     * @return void
     */
    public function invalidateAllCache(): void
    {
        // Note: Laravel Cache doesn't support pattern-based deletion by default
        // For production, consider using Redis SCAN or maintaining a list of cache keys
        // For now, this method is a placeholder - individual cache keys should be invalidated
    }
}


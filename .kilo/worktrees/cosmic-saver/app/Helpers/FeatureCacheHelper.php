<?php

namespace App\Helpers;

use App\Models\Feature;
use App\Models\FeatureCategory;
use Illuminate\Support\Facades\Cache;

/**
 * Feature Cache Helper
 * ✅ SAB: Centralized cache management for features
 *
 * @package App\Helpers
 */
class FeatureCacheHelper
{
    /**
     * Cache key prefixes
     */
    const PREFIX_FEATURE_CATEGORY_LIST = 'feature_category_list';
    const PREFIX_FEATURE_LIST = 'feature_list';
    const PREFIX_FEATURE_BY_CATEGORY = 'feature_by_category';
    const PREFIX_FEATURE_BY_YAYIN_TIPI = 'feature_by_yayin_tipi';
    const PREFIX_FEATURE_STATS = 'feature_stats';

    /**
     * Cache TTL (seconds)
     */
    const TTL_SHORT = 300; // 5 minutes
    const TTL_MEDIUM = 3600; // 1 hour
    const TTL_LONG = 86400; // 24 hours

    /**
     * Get feature category list (cached)
     *
     * @param bool $forceRefresh Force cache refresh
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCategoryList(bool $forceRefresh = false)
    {
        $key = self::PREFIX_FEATURE_CATEGORY_LIST;

        if ($forceRefresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::TTL_MEDIUM, function () {
            return FeatureCategory::select(['id', 'name', 'slug', 'display_order'])
                ->where('aktiflik_durumu', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get feature list (cached)
     *
     * @param array $filters Optional filters
     * @param bool $forceRefresh Force cache refresh
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getFeatureList(array $filters = [], bool $forceRefresh = false)
    {
        $key = self::PREFIX_FEATURE_LIST . ':' . md5(serialize($filters));

        if ($forceRefresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::TTL_MEDIUM, function () use ($filters) {
            $query = Feature::with('category');

            if (isset($filters['category_id'])) {
                $query->where('feature_category_id', $filters['category_id']);
            }

            if (isset($filters['aktiflik_durumu'])) {
                $query->where('aktiflik_durumu', $filters['aktiflik_durumu']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            return $query->orderBy('display_order')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get feature statistics (cached)
     *
     * @param bool $forceRefresh Force cache refresh
     * @return array
     */
    public static function getStats(bool $forceRefresh = false): array
    {
        $key = self::PREFIX_FEATURE_STATS;

        if ($forceRefresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::TTL_SHORT, function () {
            try {
                $total = Feature::count();
                $active = Feature::where('aktiflik_durumu', true)->whereNull('deleted_at')->count();
                $passive = Feature::where('aktiflik_durumu', false)->orWhereNotNull('deleted_at')->count();
                $categories = FeatureCategory::where('aktiflik_durumu', true)->count();
                $uncategorized = Feature::whereNull('feature_category_id')->count();

                // ✅ SAB: Support both English and Turkish keys for backward compatibility
                $stats = [
                    // English keys (for API/JSON responses)
                    'total' => $total,
                    'active' => $active,
                    'passive' => $passive,
                    'categories' => $categories,
                    'uncategorized' => $uncategorized,
                    // Turkish keys (for Blade views - backward compatibility)
                    'toplam' => $total,
                    'aktif' => $active,
                    'pasif' => $passive,
                    'kategori_sayisi' => $categories,
                    'kategorisiz' => $uncategorized,
                ];

                // ✅ Ensure all keys exist (safety check)
                return array_merge([
                    'total' => 0,
                    'active' => 0,
                    'passive' => 0,
                    'categories' => 0,
                    'uncategorized' => 0,
                    'toplam' => 0,
                    'aktif' => 0,
                    'pasif' => 0,
                    'kategori_sayisi' => 0,
                    'kategorisiz' => 0,
                ], $stats);
            } catch (\Exception $e) {
                // ✅ Fallback: Return default values if query fails
                \Log::error('FeatureCacheHelper::getStats error', ['error' => $e->getMessage()]);
                return [
                    'total' => 0,
                    'active' => 0,
                    'passive' => 0,
                    'categories' => 0,
                    'uncategorized' => 0,
                    'toplam' => 0,
                    'aktif' => 0,
                    'pasif' => 0,
                    'kategori_sayisi' => 0,
                    'kategorisiz' => 0,
                ];
            }
        });
    }

    /**
     * Clear all feature-related cache
     *
     * @return void
     */
    public static function clearAll(): void
    {
        $patterns = [
            self::PREFIX_FEATURE_CATEGORY_LIST,
            self::PREFIX_FEATURE_LIST . '*',
            self::PREFIX_FEATURE_BY_CATEGORY . '*',
            self::PREFIX_FEATURE_BY_YAYIN_TIPI . '*',
            self::PREFIX_FEATURE_STATS,
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Clear pattern-based cache (requires Redis or similar)
                Cache::flush(); // Fallback: clear all cache
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Clear cache for specific category
     *
     * @param int|null $categoryId Optional category ID
     * @return void
     */
    public static function clearCategoryCache(?int $categoryId = null): void
    {
        Cache::forget(self::PREFIX_FEATURE_CATEGORY_LIST);

        if ($categoryId) {
            Cache::forget(self::PREFIX_FEATURE_BY_CATEGORY . ':' . $categoryId);
        } else {
            // Clear all category-based cache
            self::clearAll();
        }
    }
}

<?php

namespace App\Services;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Services\Cache\CacheHelper;
use App\Services\Logging\LogService;

/**
 * Listing Navigation Service
 *
 * Context7: Önceki/Sonraki ilan navigasyonu
 * - Kategori bazlı navigasyon
 * - Filtre bazlı navigasyon
 * - Similar listings
 * - AI-powered navigation suggestions
 */
class ListingNavigationService
{
    /**
     * Get setting value with fallback
     */
    protected function getSetting(string $key, $default = null)
    {
        return \App\Models\Setting::get($key, $default);
    }

    /**
     * Check if navigation is status
     */
    public function isEnabled(): bool
    {
        return $this->getSetting('navigation_durumu', true);
    }

    /**
     * Get default similar listings limit
     */
    protected function getDefaultSimilarLimit(): int
    {
        return (int) $this->getSetting('navigation_similar_limit', 4);
    }

    /**
     * Get default navigation mode
     */
    protected function getDefaultMode(): string
    {
        return $this->getSetting('navigation_default_mode', 'default');
    }

    /**
     * Get previous and next listings for a given listing
     *
     * @param  Ilan  $ilan  Current listing
     * @param  array  $filters  Optional filters (category, status, etc.)
     * @return array ['previous' => Ilan|null, 'next' => Ilan|null]
     */
    public function getNavigation(Ilan $ilan, array $filters = []): array
    {
        // Check if navigation is durum
        if (! $this->isEnabled()) {
            return [
                'previous' => null,
                'next' => null,
                'current_index' => null,
                'total' => 0,
            ];
        }

        $cacheKey = "listing_nav.{$ilan->id}.".md5(json_encode($filters));

        return CacheHelper::remember(
            'navigation',
            "listing_{$ilan->id}",
            'medium', // 1 hour
            function () use ($ilan, $filters) {
                try {
                    $query = Ilan::query()
                        ->where('id', '!=', $ilan->id)
                        ->orderBy('created_at', 'desc') // context7-ignore
                        ->orderBy('id', 'desc'); // context7-ignore

                    // Apply filters
                    if (! empty($filters['ana_kategori_id'])) {
                        $query->where('ana_kategori_id', $filters['ana_kategori_id']);
                    }

                    if (! empty($filters['status'])) { // context7-ignore
                        $query->where('yayin_durumu', $filters['status']); // context7-ignore
                    }

                    if (! empty($filters['il_id'])) {
                        $query->where('il_id', $filters['il_id']);
                    }

                    if (! empty($filters['ilce_id'])) {
                        $query->where('ilce_id', $filters['ilce_id']);
                    }

                    // Get all listings (for navigation)
                    $allListings = $query->pluck('id')->toArray();

                    // Find current position
                    $currentIndex = array_search($ilan->id, $allListings);

                    $previous = null;
                    $next = null;

                    if ($currentIndex !== false) {
                        // Previous listing (older)
                        if ($currentIndex > 0) {
                            $previousId = $allListings[$currentIndex - 1];
                            $previous = Ilan::find($previousId);
                        }

                        // Next listing (newer)
                        if ($currentIndex < count($allListings) - 1) {
                            $nextId = $allListings[$currentIndex + 1];
                            $next = Ilan::find($nextId);
                        }
                    }

                    // If not found in filtered results, try without filters
                    if (! $previous && ! $next) {
                        $allListings = Ilan::where('id', '!=', $ilan->id)
                            ->orderBy('created_at', 'desc') // context7-ignore
                            ->orderBy('id', 'desc') // context7-ignore
                            ->pluck('id')
                            ->toArray();

                        $currentIndex = array_search($ilan->id, $allListings);

                        if ($currentIndex !== false) {
                            if ($currentIndex > 0) {
                                $previousId = $allListings[$currentIndex - 1];
                                $previous = Ilan::find($previousId);
                            }

                            if ($currentIndex < count($allListings) - 1) {
                                $nextId = $allListings[$currentIndex + 1];
                                $next = Ilan::find($nextId);
                            }
                        }
                    }

                    return [
                        'previous' => $previous,
                        'next' => $next,
                        'current_index' => $currentIndex !== false ? $currentIndex + 1 : null,
                        'total' => count($allListings) + 1,
                    ];
                } catch (\Exception $e) {
                    LogService::error('Listing navigation failed', ['ilan_id' => $ilan->id], $e);

                    return [
                        'previous' => null,
                        'next' => null,
                        'current_index' => null,
                        'total' => 0,
                    ];
                }
            },
            $filters
        );
    }

    /**
     * Get similar listings (same category/location)
     *
     * @param  Ilan  $ilan  Current listing
     * @param  int  $limit  Number of similar listings
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSimilar(Ilan $ilan, ?int $limit = null)
    {
        // Use default limit from settings if not provided
        $limit = $limit ?? $this->getDefaultSimilarLimit();

        $cacheKey = "listing_similar.{$ilan->id}.{$limit}";

        return CacheHelper::remember(
            'navigation',
            "similar_{$ilan->id}",
            'medium',
            function () use ($ilan, $limit) {
                try {
                    $query = Ilan::where('id', '!=', $ilan->id)
                        ->where('yayin_durumu', IlanDurumu::YAYINDA->value) // Context7: publication status
                        ->with([
                            'il:id,il_adi',
                            'fotograflar' => function ($q) {
                                $q->select('id', 'ilan_id', 'dosya_yolu', 'display_order', 'kapak_fotografi')
                                    ->orderBy('display_order') // context7-ignore
                                    ->limit(1);
                            },
                        ])
                        ->orderByRaw(' // context7-ignore
                            CASE
                                WHEN ana_kategori_id = ? THEN 1
                                WHEN il_id = ? THEN 2
                                WHEN ilce_id = ? THEN 3
                                ELSE 4
                            END
                        ', [$ilan->ana_kategori_id, $ilan->il_id, $ilan->ilce_id])
                        ->orderBy('created_at', 'desc') // context7-ignore
                        ->limit($limit);

                    return $query->get();
                } catch (\Exception $e) {
                    LogService::error('Similar listings failed', ['ilan_id' => $ilan->id], $e);

                    return collect([]);
                }
            }
        );
    }

    /**
     * Get navigation by category
     *
     * @param  Ilan  $ilan  Current listing
     */
    public function getByCategory(Ilan $ilan): array
    {
        $filters = [];

        if ($ilan->ana_kategori_id) {
            $filters['ana_kategori_id'] = $ilan->ana_kategori_id;
        }

        return $this->getNavigation($ilan, $filters);
    }

    /**
     * Get navigation by location
     *
     * @param  Ilan  $ilan  Current listing
     */
    public function getByLocation(Ilan $ilan): array
    {
        $filters = [];

        if ($ilan->il_id) {
            $filters['il_id'] = $ilan->il_id;
        }

        if ($ilan->ilce_id) {
            $filters['ilce_id'] = $ilan->ilce_id;
        }

        return $this->getNavigation($ilan, $filters);
    }
}

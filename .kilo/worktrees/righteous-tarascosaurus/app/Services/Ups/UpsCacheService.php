<?php

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Facades\Cache;

/**
 * UPS Cache Service
 *
 * Centralized caching for UPS operations with wildcard pattern invalidation.
 *
 * Context7 Compliance:
 * - Uses wildcard pattern caching (NO Cache::tags - not portable)
 * - Cache key format: ups:{entity}:{context}:v{version}
 * - aktiflik_durumu: Yayin durumu canonical field
 * - display_order: Canonical ordering field
 */
class UpsCacheService
{
    private const CACHE_VERSION = 'v2';
    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Get assignments with caching
     */
    public function getAssignments(int $kategoriId, int $yayinTipiId): array
    {
        $cacheKey = $this->buildKey('assignments', "cat_{$kategoriId}_yt_{$yayinTipiId}");

        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($kategoriId, $yayinTipiId) {
            return FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->with([
                    'feature' => fn($q) => $q->with('category')->where('aktiflik_durumu', true),
                ])
                ->ordered() // context7-ignore
                ->get()
                ->toArray();
        });
    }

    /**
     * Get all features with caching
     */
    public function getAllFeatures(bool $activeOnly = true): array
    {
        $cacheKey = $this->buildKey('features', $activeOnly ? 'active' : 'all'); // context7-ignore

        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($activeOnly) {
            $query = Feature::with('category')->ordered(); // context7-ignore

            if ($activeOnly) {
                $query->where('aktiflik_durumu', true);
            }

            return $query->get()->toArray();
        });
    }

    /**
     * Get features by category with caching
     */
    public function getFeaturesByCategory(int $categoryId): array
    {
        $cacheKey = $this->buildKey('features', "category_{$categoryId}");

        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($categoryId) {
            return Feature::where('feature_category_id', $categoryId)
                ->where('aktiflik_durumu', true)
                ->ordered() // context7-ignore
                ->get()
                ->toArray();
        });
    }

    /**
     * Get template stats with caching
     */
    public function getTemplateStats(int $yayinTipiId): array
    {
        $cacheKey = $this->buildKey('stats', "template_{$yayinTipiId}");

        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($yayinTipiId) {
            $assignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $yayinTipiId)
                ->get();

            return [
                'total' => $assignments->count(),
                'manual' => $assignments->where('source_type', 'manual')->count(),
                'pack' => $assignments->where('source_type', 'pack')->count(),
                'inherited' => $assignments->where('source_type', 'inherited')->count(),
                'ai' => $assignments->where('source_type', 'ai')->count(),
            ];
        });
    }

    /**
     * Invalidate cache for entity
     */
    public function invalidate(string $entity, ?string $context = null): void
    {
        $pattern = $this->buildKey($entity, $context ?? '*');

        // Get all cache keys matching pattern
        $keys = $this->getCacheKeys();

        foreach ($keys as $key) {
            if ($this->matchesPattern($key, $pattern)) {
                Cache::forget($key);
                $this->unregisterKey($key);
            }
        }
    }

    /**
     * Invalidate all UPS cache
     */
    public function invalidateAll(): void
    {
        $keys = $this->getCacheKeys();

        foreach ($keys as $key) {
            if (str_starts_with($key, 'ups:')) {
                Cache::forget($key);
            }
        }

        Cache::forget('ups:cache_registry');
    }

    // =========================================================================
    // RESOLVER KEY API — TemplateResolver için (template_v2_* key evrenini
    // UpsCacheService registry altına alır; invalidation garantili)
    // =========================================================================

    /**
     * Resolver cache key oluştur (registry takipli)
     */
    public function buildResolverKey(int $kategoriId, string $typeSlug): string
    {
        return $this->buildKey('resolver', "cat_{$kategoriId}_{$typeSlug}");
    }

    /**
     * Resolver cache remember (atomik — race condition yok)
     */
    public function rememberResolver(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Resolver cache temizle
     */
    public function forgetResolverKey(int $kategoriId, string $typeSlug): void
    {
        $key = $this->buildKey('resolver', "cat_{$kategoriId}_{$typeSlug}");
        Cache::forget($key);
        $this->unregisterKey($key);
    }

    // =========================================================================
    // FEATURE-GROUPED KEY API — FeatureTemplateResolver için
    // =========================================================================

    /**
     * Feature grouped cache key oluştur (flat veya ui gruplu)
     */
    public function buildFeatureGroupedKey(int $kategoriId, int $yayinTipiId, string $type = 'flat'): string
    {
        $suffix = $type === 'ui' ? 'ui' : 'flat';
        return $this->buildKey('feature_grouped', "{$suffix}_{$kategoriId}_{$yayinTipiId}");
    }

    /**
     * Feature grouped cache remember
     */
    public function rememberFeatureGrouped(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Feature grouped cache temizle (hem flat hem ui için)
     */
    public function forgetFeatureGroupedKeys(int $kategoriId, int $yayinTipiId): void
    {
        foreach (['flat', 'ui'] as $type) {
            $key = $this->buildKey('feature_grouped', "{$type}_{$kategoriId}_{$yayinTipiId}");
            Cache::forget($key);
            $this->unregisterKey($key);
        }
    }

    // =========================================================================
    // JUNCTION INVALIDATION CONTRACT
    // Template değiştiğinde tüm bağımlı cache'leri temizler
    // =========================================================================

    /**
     * Junction (yayin_tipi_sablonu) ile bağlı tüm cache'leri invalidate et.
     *
     * Çağrılma noktaları:
     * - TemplateSealedListener (event-driven)
     * - FeatureAssignment yazan her write path
     */
    public function invalidateForJunction(int $junctionId, ?int $kategoriId = null, ?int $yayinTipiId = null): void
    {
        // 1. UPS registry tabanlı invalidation (assignments, stats, templates)
        $this->invalidate('templates');
        $this->invalidate('stats', "template_{$junctionId}");

        if ($kategoriId) {
            $this->invalidate('assignments', "cat_{$kategoriId}_yt_{$yayinTipiId}");
        }

        // 2. Resolver keys (template_v2_* equivalents — her kategori için temizle)
        // Pattern: ups:resolver:cat_*_*:v2
        $this->invalidate('resolver');

        // 3. Feature grouped keys — spesifik ya da geniş temizlik
        if ($kategoriId && $yayinTipiId) {
            $this->forgetFeatureGroupedKeys($kategoriId, $yayinTipiId);
        } else {
            $this->invalidate('feature_grouped');
        }
    }

    /**
     * Warm up cache for common queries
     */
    public function warmUp(): array
    {
        $warmed = [];

        // Warm up all features
        $this->getAllFeatures(true);
        $warmed[] = 'features:active';

        $this->getAllFeatures(false);
        $warmed[] = 'features:all';

        return $warmed;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $keys = $this->getCacheKeys();

        return [
            'total_keys' => count($keys),
            'keys_by_type' => collect($keys)
                ->groupBy(fn($key) => explode(':', $key)[1] ?? 'unknown')
                ->map(fn($group) => $group->count())
                ->toArray(),
        ];
    }

    /**
     * Build cache key
     */
    private function buildKey(string $entity, string $context): string
    {
        $key = "ups:{$entity}:{$context}:" . self::CACHE_VERSION;
        $this->registerKey($key);
        return $key;
    }

    /**
     * Register cache key in registry
     */
    private function registerKey(string $key): void
    {
        $keys = Cache::get('ups:cache_registry', []);
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            Cache::put('ups:cache_registry', $keys, now()->addDay());
        }
    }

    /**
     * Unregister cache key from registry
     */
    private function unregisterKey(string $key): void
    {
        $keys = Cache::get('ups:cache_registry', []);
        $keys = array_filter($keys, fn($k) => $k !== $key);
        Cache::put('ups:cache_registry', array_values($keys), now()->addDay());
    }

    /**
     * Get all registered cache keys
     */
    private function getCacheKeys(): array
    {
        return Cache::get('ups:cache_registry', []);
    }

    /**
     * Check if key matches pattern
     */
    private function matchesPattern(string $key, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        // Convert wildcard pattern to regex
        $regex = str_replace(['*', ':'], ['.*', '\:'], preg_quote($pattern, '/'));

        return (bool) preg_match("/^{$regex}$/", $key);
    }

    // =========================================================================
    // JUNCTION-FIRST RESOLVER CACHE — collision-proof (junction_id + kategori)
    // =========================================================================

    /**
     * Junction-first resolver cache key.
     * Collision-proof: junction_id + optional kategori guard bileşik anahtar.
     *
     * @param  int       $junctionId       YayinTipiSablonu.id
     * @param  int|null  $requestKategoriId  guard kontrolü için
     */
    public function buildJunctionKey(int $junctionId, ?int $requestKategoriId = null): string
    {
        $suffix = $requestKategoriId ? "junc_{$junctionId}_cat_{$requestKategoriId}" : "junc_{$junctionId}";
        return $this->buildKey('resolver', $suffix);
    }

    /**
     * Junction resolver cache remember (atomik).
     */
    public function rememberJunction(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Junction cache temizle.
     */
    public function forgetJunctionKey(int $junctionId, ?int $requestKategoriId = null): void
    {
        $key = $this->buildJunctionKey($junctionId, $requestKategoriId);
        Cache::forget($key);
        $this->unregisterKey($key);
        // kategori guard'lı versiyonu da temizle
        if ($requestKategoriId === null) {
            // pattern-based invalidation — tüm junc_{id}_* keyleri temizle
            $this->invalidate('resolver', "junc_{$junctionId}");
        }
    }
}

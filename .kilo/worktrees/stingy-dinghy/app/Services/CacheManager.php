<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheManager
 *
 * Centralized cache management service for Yalıhan Emlak.
 * Implements tag-based invalidation and TTL management.
 *
 * @package App\Services
 */
class CacheManager
{
    /**
     * Remember data with automatic TTL from config
     */
    public function remember(string $key, string $tag, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? config("yalihan.cache.{$tag}_ttl", 3600);
        $cacheKey = $this->buildKey($tag, $key);

        return Cache::tags([$tag])->remember(
            $cacheKey,
            $ttl,
            function() use ($callback, $cacheKey) {
                Log::debug("Cache MISS: {$cacheKey}");
                return $callback();
            }
        );
    }

    /**
     * Get cached data
     */
    public function get(string $key, string $tag): mixed
    {
        $cacheKey = $this->buildKey($tag, $key);
        $value = Cache::tags([$tag])->get($cacheKey);

        if ($value !== null) {
            Log::debug("Cache HIT: {$cacheKey}");
        } else {
            Log::debug("Cache MISS: {$cacheKey}");
        }

        return $value;
    }

    /**
     * Put data in cache
     */
    public function put(string $key, string $tag, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? config("yalihan.cache.{$tag}_ttl", 3600);
        $cacheKey = $this->buildKey($tag, $key);

        Log::debug("Cache PUT: {$cacheKey} (TTL: {$ttl}s)");

        return Cache::tags([$tag])->put($cacheKey, $value, $ttl);
    }

    /**
     * Forget specific cache entry
     */
    public function forget(string $key, string $tag): bool
    {
        $cacheKey = $this->buildKey($tag, $key);
        Log::debug("Cache FORGET: {$cacheKey}");

        return Cache::tags([$tag])->forget($cacheKey);
    }

    /**
     * Flush entire tag
     */
    public function flushTag(string $tag): bool
    {
        Log::info("Cache FLUSH TAG: {$tag}");
        return Cache::tags([$tag])->flush();
    }

    /**
     * Flush multiple tags
     */
    public function flushTags(array $tags): bool
    {
        Log::info("Cache FLUSH TAGS: " . implode(', ', $tags));
        return Cache::tags($tags)->flush();
    }

    /**
     * Build cache key with tag prefix
     */
    private function buildKey(string $tag, string $key): string
    {
        return "{$tag}:{$key}";
    }

    /**
     * Get cache statistics (Redis specific)
     */
    public function getStats(): array
    {
        try {
            $redis = Cache::getStore()->connection();
            $info = $redis->info();

            return [
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate(
                    $info['keyspace_hits'] ?? 0,
                    $info['keyspace_misses'] ?? 0
                ),
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate cache hit rate percentage
     */
    private function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }
}

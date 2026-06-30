<?php

namespace App\Services\Cache;

/**
 * @sab-ignore-catch
 */

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Standardized Cache Service
 *
 * Context7 Cache Standardization
 * Provides consistent cache key formatting, TTL management, and invalidation strategies
 */
class CacheService
{
    /**
     * Cache key prefix
     */
    protected string $prefix = 'emlak_pro';

    /**
     * Default TTL values (in seconds)
     */
    protected array $ttl = [
        'very_short' => 60,      // 1 minute
        'short' => 300,          // 5 minutes
        'medium' => 3600,        // 1 hour
        'long' => 86400,         // 1 day
        'very_long' => 604800,   // 1 week
    ];

    /**
     * Cache tags for grouped invalidation
     */
    protected array $tags = [
        'features' => 'feature_cache',
        'categories' => 'category_cache',
        'listings' => 'ilan_cache',
        'demands' => 'talep_cache',
        'statistics' => 'stats_cache',
        'search' => 'search_cache',
        'ai' => 'ai_cache',
        'prices' => 'price_cache',
        'dashboard' => 'dashboard_cache',
    ];

    public function __construct()
    {
        $this->prefix = config('redis-cache.cache_prefix', 'emlak_pro');
        $this->ttl = array_merge($this->ttl, config('redis-cache.ttl', []));
    }

    /**
     * Generate standardized cache key
     *
     * Format: {prefix}:{namespace}:{key}:{params?}
     * Example: emlak_pro:ilan:stats:active
     *
     * @param  string  $namespace  Cache namespace (e.g., 'ilan', 'category', 'ai')
     * @param  string  $key  Cache key
     * @param  array  $params  Optional parameters for key uniqueness
     * @return string Standardized cache key
     */
    public function key(string $namespace, string $key, array $params = []): string
    {
        $parts = [$this->prefix, $namespace, $key];

        if (! empty($params)) {
            // Sort params for consistent key generation
            ksort($params);
            $paramString = implode(':', array_map(function ($k, $v) {
                return $k.'='.(is_array($v) ? md5(json_encode($v)) : $v);
            }, array_keys($params), $params));
            $parts[] = $paramString;
        }

        return implode(':', $parts);
    }

    /**
     * Get value from cache
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if not found
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $startTime = microtime(true);
        $value = Cache::get($key, $default);
        $duration = (microtime(true) - $startTime) * 1000;

        if (config('redis-cache.monitoring.log_hits', false) && $value !== null) {
            Log::debug('Cache Hit', [
                'key' => $key,
                'duration_ms' => round($duration, 2),
            ]);
        }

        if (config('redis-cache.monitoring.log_misses', true) && $value === null) {
            Log::debug('Cache Miss', [
                'key' => $key,
                'duration_ms' => round($duration, 2),
            ]);
        }

        return $value;
    }

    /**
     * Store value in cache
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|string|null  $ttl  TTL in seconds or preset name (e.g., 'short', 'medium')
     */
    public function put(string $key, $value, $ttl = null): bool
    {
        $ttl = $this->resolveTtl($ttl ?? 'medium');

        return Cache::put($key, $value, $ttl);
    }

    /**
     * Remember value in cache (get or compute)
     *
     * @param  string  $key  Cache key
     * @param  int|string|null  $ttl  TTL in seconds or preset name
     * @param  callable  $callback  Callback to compute value if not cached
     * @return mixed
     */
    public function remember(string $key, $ttl, callable $callback)
    {
        $ttl = $this->resolveTtl($ttl ?? 'medium');

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Check if key exists in cache
     *
     * @param  string  $key  Cache key
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Remove value from cache
     *
     * @param  string  $key  Cache key
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Invalidate cache by namespace (Context7: Driver-bağımsız)
     *
     * @param  string  $namespace  Namespace to invalidate
     * @param  array  $params  Optional parameters to match specific keys
     * @return int Number of keys invalidated
     */
    public function invalidateNamespace(string $namespace, array $params = []): int
    {
        $tag = $this->getTagForNamespace($namespace);
        return $this->flushByPrefix($tag);
    }

    /**
     * Invalidate cache by tag (Context7: Driver-bağımsız)
     *
     * @param  string  $tag  Tag name
     * @return int Number of keys invalidated
     */
    public function invalidateByTag(string $tag): int
    {
        return $this->flushByPrefix($tag);
    }

    /**
     * Flush cache by prefix (Context7: Driver-bağımsız wildcard pattern)
     *
     * @param  string  $prefix  Prefix pattern (e.g., 'frontend-properties')
     * @return int Number of keys invalidated
     */
    public function flushByPrefix(string $prefix): int
    {
        $count = 0;

        if ($this->supportsTags()) {
            try {
                Cache::tags([$prefix])->flush();
                return 1;
            } catch (\Exception $e) {
                Log::warning('Tag-based cache flush failed, using wildcard fallback', [
                    'prefix' => $prefix,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->supportsWildcard()) {
            $pattern = $this->prefix . ':' . $prefix . '*';
            $keys = $this->getKeysByPattern($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
                $count++;
            }
        } else {
            Log::warning('Cache driver does not support wildcard pattern matching', [
                'prefix' => $prefix,
                'driver' => get_class(Cache::getStore()),
            ]);
        }

        return $count;
    }

    /**
     * Remember with prefix (Context7: Driver-bağımsız)
     *
     * @param  string  $prefix  Prefix for grouping
     * @param  string  $key  Cache key
     * @param  int|string|null  $ttl  TTL
     * @param  callable  $callback  Callback
     * @return mixed
     */
    public function rememberWithPrefix(string $prefix, string $key, $ttl, callable $callback)
    {
        $fullKey = $this->prefix . ':' . $prefix . ':' . $key;
        $ttlSeconds = $this->resolveTtl($ttl ?? 'medium');

        if ($this->supportsTags()) {
            try {
                return Cache::tags([$prefix])->remember($fullKey, $ttlSeconds, $callback);
            } catch (\Exception $e) {
                Log::warning('Tag-based cache remember failed, using standard cache', [
                    'prefix' => $prefix,
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Cache::remember($fullKey, $ttlSeconds, $callback);
    }

    /**
     * Check if cache driver supports tags
     *
     * @return bool
     */
    private function supportsTags(): bool
    {
        static $supported = null;

        if ($supported !== null) {
            return $supported;
        }

        try {
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                $testTag = 'cache_test_' . uniqid();
                Cache::tags([$testTag])->put('test', 'value', 1);
                Cache::tags([$testTag])->flush();
                $supported = true;
                return true;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Cache tag support detection failed: " . $e->getMessage());
        }

        $supported = false;
        return false;
    }

    /**
     * Check if cache driver supports wildcard pattern matching
     *
     * @return bool
     */
    private function supportsWildcard(): bool
    {
        $store = Cache::getStore();
        return method_exists($store, 'getRedis') && $store->getRedis() instanceof \Redis;
    }

    /**
     * Get cache keys by pattern (Redis only)
     *
     * @param  string  $pattern  Pattern (e.g., 'prefix:*')
     * @return array
     */
    private function getKeysByPattern(string $pattern): array
    {
        if (!$this->supportsWildcard()) {
            return [];
        }

        try {
            $redis = Cache::getStore()->getRedis();
            return $redis->keys($pattern) ?: [];
        } catch (\Exception $e) {
            Log::warning('Wildcard cache key pattern matching failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Invalidate related caches when model changes
     *
     * @param  string  $modelName  Model name (e.g., 'Ilan', 'Category')
     * @param  int|null  $modelId  Optional model ID for specific invalidation
     */
    public function invalidateModel(string $modelName, ?int $modelId = null): void
    {
        $namespaceMap = [
            'Ilan' => 'ilan',
            'IlanKategori' => 'category',
            'Feature' => 'feature',
            'Talep' => 'talep',
            'Kisi' => 'kisi',
            'Price' => 'price',
        ];

        $namespace = $namespaceMap[$modelName] ?? strtolower($modelName);

        // Invalidate specific model cache
        if ($modelId) {
            $this->forget($this->key($namespace, "model:{$modelId}"));
        }

        // Invalidate related caches
        $this->invalidateNamespace($namespace);
        $this->invalidateByTag($this->getTagForNamespace($namespace));

        // Invalidate statistics
        $this->forget($this->key('stats', "{$namespace}:stats"));
    }

    /**
     * Get TTL value (resolve preset names to seconds)
     *
     * @param  int|string  $ttl  TTL in seconds or preset name
     * @return int TTL in seconds
     */
    protected function resolveTtl($ttl): int
    {
        if (is_string($ttl) && isset($this->ttl[$ttl])) {
            return $this->ttl[$ttl];
        }

        if (is_numeric($ttl)) {
            return (int) $ttl;
        }

        return $this->ttl['medium']; // Default to medium
    }

    /**
     * Get tag for namespace
     *
     * @param  string  $namespace  Namespace
     * @return string Tag name
     */
    protected function getTagForNamespace(string $namespace): string
    {
        return $this->tags[$namespace] ?? "{$namespace}_cache";
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        // This would require Redis-specific implementation
        // For now, return basic info
        return [
            'prefix' => $this->prefix,
            'ttl_presets' => $this->ttl,
            'tags' => array_keys($this->tags),
        ];
    }
}

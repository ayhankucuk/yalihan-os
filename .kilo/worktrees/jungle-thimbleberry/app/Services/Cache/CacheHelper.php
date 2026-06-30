<?php

namespace App\Services\Cache;

/**
 * Cache Helper Functions
 *
 * Provides convenient helper functions for common cache operations
 */
class CacheHelper
{
    /**
     * Get cache service instance
     */
    public static function cache(): CacheService
    {
        return app(CacheService::class);
    }

    /**
     * Quick cache key generation
     */
    public static function key(string $namespace, string $key, array $params = []): string
    {
        return static::cache()->key($namespace, $key, $params);
    }

    /**
     * Quick cache remember
     *
     * @param  int|string  $ttl
     * @return mixed
     */
    public static function remember(string $namespace, string $key, $ttl, callable $callback, array $params = [])
    {
        $cacheKey = static::key($namespace, $key, $params);

        return static::cache()->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Quick cache get
     *
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $namespace, string $key, $default = null, array $params = [])
    {
        $cacheKey = static::key($namespace, $key, $params);

        return static::cache()->get($cacheKey, $default);
    }

    /**
     * Quick cache put
     *
     * @param  mixed  $value
     * @param  int|string  $ttl
     */
    public static function put(string $namespace, string $key, $value, $ttl = 'medium', array $params = []): bool
    {
        $cacheKey = static::key($namespace, $key, $params);

        return static::cache()->put($cacheKey, $value, $ttl);
    }

    /**
     * Quick cache forget
     */
    public static function forget(string $namespace, string $key, array $params = []): bool
    {
        $cacheKey = static::key($namespace, $key, $params);

        return static::cache()->forget($cacheKey);
    }

    /**
     * Invalidate namespace
     */
    public static function invalidate(string $namespace, array $params = []): int
    {
        return static::cache()->invalidateNamespace($namespace, $params);
    }

    /**
     * Invalidate model cache
     */
    public static function invalidateModel(string $modelName, ?int $modelId = null): void
    {
        static::cache()->invalidateModel($modelName, $modelId);
    }
}

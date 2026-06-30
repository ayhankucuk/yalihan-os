<?php

/**
 * Cache Key Helper - Merkezi Cache Key Yönetimi
 *
 * Context7 Standard: C7-CACHE-KEY-HELPER-2025-12-06
 *
 * Merkezi cache keys config'den key'leri alır ve CacheService ile entegre çalışır.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

namespace App\Helpers;

use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Cache;

class CacheKeyHelper
{
    /**
     * Cache key oluştur
     *
     * @param string $path Dot notation path (örn: 'ilan.list')
     * @param array $params Key parametreleri (örn: ['il_id' => 48])
     * @return string Cache key
     */
    public static function get(string $path, array $params = []): string
    {
        $config = config("cache-keys.{$path}", null);

        if (!$config) {
            // Fallback: Basit key oluştur
            return self::generateFallbackKey($path, $params);
        }

        $namespace = $config['namespace'] ?? '';
        $key = $config['key'] ?? '';
        $expectedParams = $config['params'] ?? [];

        // Parametreleri filtrele (sadece beklenen parametreleri kullan)
        $filteredParams = [];
        foreach ($expectedParams as $paramName) {
            if (isset($params[$paramName])) {
                $filteredParams[$paramName] = $params[$paramName];
            }
        }

        // CacheService kullanarak key oluştur
        $cacheService = app(CacheService::class);
        return $cacheService->key($namespace, $key, $filteredParams);
    }

    /**
     * TTL değerini al
     *
     * @param string $path Dot notation path
     * @return int TTL (saniye cinsinden)
     */
    public static function getTtl(string $path): int
    {
        $config = config("cache-keys.{$path}", null);

        if (!$config) {
            // Varsayılan TTL
            return config('cache-keys.ttl.medium', 3600);
        }

        $ttl = $config['ttl'] ?? 'medium';

        // TTL preset ise çöz
        if (is_string($ttl)) {
            return config("cache-keys.ttl.{$ttl}", 3600);
        }

        return (int) $ttl;
    }

    /**
     * Cache tag'lerini al
     *
     * @param string $path Dot notation path
     * @return array Tag'ler
     */
    public static function getTags(string $path): array
    {
        $config = config("cache-keys.{$path}", null);

        if (!$config) {
            return [];
        }

        return $config['tags'] ?? [];
    }

    /**
     * Tag'li cache store al
     */
    protected static function getTaggedCache(array $tags)
    {
        return (!empty($tags) && Cache::getStore() instanceof \Illuminate\Cache\TaggedCache)
            ? Cache::tags($tags)
            : Cache::getStore();
    }

    /**
     * Cache key ile remember
     */
    public static function remember(string $path, array $params, callable $callback)
    {
        $key = self::get($path, $params);
        $ttl = self::getTtl($path);
        $tags = self::getTags($path);
        $store = self::getTaggedCache($tags);
        return $store->remember($key, $ttl, $callback);
    }

    /**
     * Cache key ile get
     */
    public static function getValue(string $path, array $params = [], $default = null)
    {
        $key = self::get($path, $params);
        $tags = self::getTags($path);
        $store = self::getTaggedCache($tags);
        return $store->get($key, $default);
    }

    /**
     * Cache key ile put
     */
    public static function put(string $path, array $params, $value): bool
    {
        $key = self::get($path, $params);
        $ttl = self::getTtl($path);
        $tags = self::getTags($path);
        $store = self::getTaggedCache($tags);
        return $store->put($key, $value, $ttl);
    }

    /**
     * Cache key ile forget
     */
    public static function forget(string $path, array $params = []): bool
    {
        $key = self::get($path, $params);
        $tags = self::getTags($path);
        $store = self::getTaggedCache($tags);
        return $store->forget($key);
    }

    /**
     * Namespace'e göre cache invalidate et
     *
     * @param string $namespace Namespace (örn: 'ilan')
     * @param array $params Optional parameters
     * @return int Invalidated key count
     */
    public static function invalidateNamespace(string $namespace, array $params = []): int
    {
        $cacheService = app(CacheService::class);
        return $cacheService->invalidateNamespace($namespace, $params);
    }

    /**
     * Tag'e göre cache invalidate et
     */
    public static function invalidateByTag($tags): int
    {
        $cacheService = app(CacheService::class);
        $tags = is_array($tags) ? $tags : [$tags];
        return array_sum(array_map(fn($tag) => $cacheService->invalidateByTag($tag), $tags));
    }

    /**
     * Model değiştiğinde ilgili cache'leri invalidate et
     *
     * @param string $modelName Model name (örn: 'Ilan')
     * @param int|null $modelId Model ID
     * @return void
     */
    public static function invalidateModel(string $modelName, ?int $modelId = null): void
    {
        $cacheService = app(CacheService::class);
        $cacheService->invalidateModel($modelName, $modelId);
    }

    /**
     * Fallback key oluştur (config'de yoksa)
     *
     * @param string $path Dot notation path
     * @param array $params Key parametreleri
     * @return string Cache key
     */
    protected static function generateFallbackKey(string $path, array $params): string
    {
        $parts = explode('.', $path);
        $namespace = $parts[0] ?? 'default';
        $key = $parts[1] ?? 'key';

        $cacheService = app(CacheService::class);
        return $cacheService->key($namespace, $key, $params);
    }
}

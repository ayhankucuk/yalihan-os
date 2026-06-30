<?php

namespace App\Services\Cache;

/**
 * @sab-ignore-catch
 */

use Illuminate\Support\Facades\Cache;

/**
 * Merkezi Cache Yönetim Servisi
 *
 * Context7 Standardı: C7-CACHE-MANAGER-2025-12-06
 *
 * Tüm cache işlemlerini merkezi olarak yönetir.
 * Cache key'lerini standardize eder ve tutarlılık sağlar.
 *
 * @package App\Services\Cache
 */
class CacheManager
{
    /**
     * Cache key prefix'leri
     * Context7: Tüm prefix'ler burada tanımlı
     */
    private const PREFIXES = [
        'feature_categories' => 'feature_categories',
        'ilan_kategorileri' => 'ilan_kategorileri',
        'adres_yonetimi' => 'adres_yonetimi',
        'ozellikler' => 'features',
        'admin' => 'admin',
    ];

    /**
     * Cache TTL değerleri (saniye)
     */
    private const TTL = [
        'short' => 300,      // 5 dakika
        'medium' => 1800,    // 30 dakika
        'long' => 3600,      // 1 saat
        'very_long' => 7200, // 2 saat
    ];

    /**
     * Cache tag desteği kontrolü (lazy loaded)
     * ✅ Bug Fix: Tag desteğini bir kere kontrol edip sakla
     *
     * @var bool|null
     */
    private static ?bool $tagsSupported = null;

    /**
     * Cache key oluştur
     *
     * @param string $prefix Prefix adı (PREFIXES'den)
     * @param string $suffix Suffix (opsiyonel)
     * @return string
     */
    public function key(string $prefix, string $suffix = ''): string
    {
        $baseKey = self::PREFIXES[$prefix] ?? $prefix;
        return $suffix ? "{$baseKey}_{$suffix}" : $baseKey;
    }

    /**
     * Cache'e kaydet
     *
     * @param string $prefix Prefix adı
     * @param string $suffix Suffix
     * @param callable $callback Cache'lenecek veriyi üreten callback
     * @param string|int $ttl TTL (string: 'short'|'medium'|'long'|'very_long' veya int: saniye)
     * @return mixed
     */
    public function remember(string $prefix, string $suffix, callable $callback, $ttl = 'medium')
    {
        $cacheKey = $this->key($prefix, $suffix);
        $ttlSeconds = is_string($ttl) ? (self::TTL[$ttl] ?? self::TTL['medium']) : $ttl;

        return Cache::remember($cacheKey, $ttlSeconds, $callback);
    }

    /**
     * Cache'i temizle
     *
     * @param string $prefix Prefix adı
     * @param string|null $suffix Suffix (null ise tüm prefix cache'leri temizlenir)
     * @return void
     */
    public function forget(string $prefix, ?string $suffix = null): void
    {
        if ($suffix !== null) {
            Cache::forget($this->key($prefix, $suffix));
        } else {
            $this->forgetAll($prefix);
        }
    }

    /**
     * Tüm prefix cache'lerini temizle (Context7: Driver-bağımsız)
     *
     * @param string $prefix Prefix adı
     * @return void
     */
    public function forgetAll(string $prefix): void
    {
        $cacheService = app(\App\Services\Cache\CacheService::class);
        $cacheService->flushByPrefix($prefix);
    }

    /**
     * Query string'e göre cache key oluştur
     *
     * @param string $prefix Prefix adı
     * @param string $baseSuffix Base suffix
     * @param string|null $queryString Query string (null ise request'ten alınır)
     * @return string
     */
    public function keyWithQuery(string $prefix, string $baseSuffix, ?string $queryString = null): string
    {
        $queryString = $queryString ?? request()->getQueryString() ?? '';
        $queryHash = md5($queryString);
        return $this->key($prefix, "{$baseSuffix}_{$queryHash}");
    }

    /**
     * Cache tag desteğini kontrol et
     * ✅ Bug Fix: Tag desteğini tutarlı şekilde kontrol et
     *
     * @return bool
     */
    private function supportsTags(): bool
    {
        if (self::$tagsSupported !== null) {
            return self::$tagsSupported;
        }

        // Tag desteğini kontrol et
        if (!method_exists(Cache::getStore(), 'tags')) {
            self::$tagsSupported = false;
            return false;
        }

        // Tag desteğini test et
        try {
            $testTag = 'cache_manager_test_' . uniqid();
            Cache::tags([$testTag])->put('test_key', 'test_value', 1);
            Cache::tags([$testTag])->flush();
            self::$tagsSupported = true;
            return true;
        } catch (\Exception $e) {
            self::$tagsSupported = false;
            return false;
        }
    }

    /**
     * Cache'i temizle (query string ile)
     * ✅ Bug Fix: rememberWithQuery ile aynı stratejiyi kullan
     *
     * @param string $prefix Prefix adı
     * @param string $baseSuffix Base suffix
     * @return void
     */
    public function forgetWithQuery(string $prefix, string $baseSuffix): void
    {
        $tag = "{$prefix}_{$baseSuffix}_query";

        $cacheService = app(\App\Services\Cache\CacheService::class);
        $cacheService->flushByPrefix($tag);

        $this->forgetCommonQueries($prefix, $baseSuffix);
    }

    /**
     * Yaygın query kombinasyonlarını temizle
     * ✅ Bug Fix: Fallback mekanizması için ayrı metod
     *
     * @param string $prefix Prefix adı
     * @param string $baseSuffix Base suffix
     * @return void
     */
    private function forgetCommonQueries(string $prefix, string $baseSuffix): void
    {
        // Yaygın query string kombinasyonlarını temizle
        $commonQueries = [
            '',
            'q=',
            'status=1', // context7-ignore
            'status=0', // context7-ignore
            'q=&status=1',
            'q=&status=0',
        ];

        foreach ($commonQueries as $query) {
            $cacheKey = $this->keyWithQuery($prefix, $baseSuffix, $query);
            Cache::forget($cacheKey);
        }
    }

    /**
     * Query string ile cache'e kaydet (tag desteği ile)
     * ✅ Bug Fix: forgetWithQuery ile aynı stratejiyi kullan
     *
     * @param string $prefix Prefix adı
     * @param string $baseSuffix Base suffix
     * @param callable $callback Cache'lenecek veriyi üreten callback
     * @param string|int $ttl TTL
     * @return mixed
     */
    public function rememberWithQuery(string $prefix, string $baseSuffix, callable $callback, $ttl = 'medium')
    {
        $cacheKey = $this->keyWithQuery($prefix, $baseSuffix);
        $ttlSeconds = is_string($ttl) ? (self::TTL[$ttl] ?? self::TTL['medium']) : $ttl;
        $tag = "{$prefix}_{$baseSuffix}_query";

        $cacheService = app(\App\Services\Cache\CacheService::class);
        return $cacheService->rememberWithPrefix($tag, $cacheKey, $ttlSeconds, $callback);
    }
}

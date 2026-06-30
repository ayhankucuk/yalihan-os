<?php

namespace App\Http\Controllers\Admin\Traits;

/**
 * @sab-ignore-thin
 */

use App\Services\Cache\CacheManager;
use App\Services\Slug\SlugGenerator;

/**
 * Kategori İşlemleri Trait
 *
 * Context7 Standardı: C7-CATEGORY-TRAIT-2025-12-06
 *
 * Ortak kategori işlemleri için merkezi metodlar sağlar.
 * Cache yönetimi, slug generation ve diğer ortak işlemler.
 *
 * @package App\Http\Controllers\Admin\Traits
 */
trait HandlesCategoryOperations
{
    /**
     * Cache Manager
     *
     * @var CacheManager
     */
    protected CacheManager $cacheManager;

    /**
     * Slug Generator
     *
     * @var SlugGenerator
     */
    protected SlugGenerator $slugGenerator;

    /**
     * Trait initialization
     *
     * @return void
     */
    public function initializeCategoryOperations(): void
    {
        // ✅ Bug Fix: Null kontrolü ekle - isset() yeterli değil, null olabilir
        if (!isset($this->cacheManager) || $this->cacheManager === null) {
            $this->cacheManager = app(CacheManager::class);
        }
        if (!isset($this->slugGenerator) || $this->slugGenerator === null) {
            $this->slugGenerator = app(SlugGenerator::class);
        }
    }

    /**
     * Cache'i temizle
     *
     * @param string $prefix Prefix adı
     * @param string|null $suffix Suffix (null ise tüm prefix cache'leri temizlenir)
     * @return void
     */
    protected function clearCache(string $prefix, ?string $suffix = null): void
    {
        $this->initializeCategoryOperations();
        $this->cacheManager->forget($prefix, $suffix);
    }

    /**
     * Tüm prefix cache'lerini temizle
     *
     * @param string $prefix Prefix adı
     * @return void
     */
    protected function clearAllCache(string $prefix): void
    {
        $this->initializeCategoryOperations();
        $this->cacheManager->forgetAll($prefix);
    }

    /**
     * Slug oluştur
     *
     * @param string $base Base string
     * @param string $modelClass Model class name
     * @param int|null $excludeId Hariç tutulacak ID
     * @return string
     */
    protected function generateSlug(string $base, string $modelClass, ?int $excludeId = null): string
    {
        $this->initializeCategoryOperations();
        return $this->slugGenerator->generateUnique($base, $modelClass, $excludeId);
    }

    /**
     * Cache'e kaydet
     *
     * @param string $prefix Prefix adı
     * @param string $suffix Suffix
     * @param callable $callback Cache'lenecek veriyi üreten callback
     * @param string|int $ttl TTL
     * @return mixed
     */
    protected function rememberCache(string $prefix, string $suffix, callable $callback, $ttl = 'medium')
    {
        $this->initializeCategoryOperations();
        return $this->cacheManager->remember($prefix, $suffix, $callback, $ttl);
    }

    /**
     * Cache'e query string ile kaydet (cache tag desteği ile)
     *
     * @param string $prefix Prefix adı
     * @param string $baseSuffix Base suffix
     * @param callable $callback Cache'lenecek veriyi üreten callback
     * @param string|int $ttl TTL
     * @return mixed
     */
    protected function rememberWithQuery(string $prefix, string $baseSuffix, callable $callback, $ttl = 'medium')
    {
        $this->initializeCategoryOperations();
        return $this->cacheManager->rememberWithQuery($prefix, $baseSuffix, $callback, $ttl);
    }

    /**
     * Query string ile cache'i temizle
     *
     * @param string $prefix Prefix adı
     * @param string $baseSuffix Base suffix
     * @return void
     */
    protected function forgetWithQuery(string $prefix, string $baseSuffix): void
    {
        $this->initializeCategoryOperations();
        $this->cacheManager->forgetWithQuery($prefix, $baseSuffix);
    }
}

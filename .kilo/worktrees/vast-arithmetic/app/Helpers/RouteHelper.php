<?php

/**
 * Route Helper - Merkezi Route Yönetim Helper'ı
 *
 * Context7 Standard: C7-ROUTE-HELPER-2025-12-06
 *
 * Route'ları merkezi config'den almak için helper fonksiyonlar.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class RouteHelper
{
    /**
     * Route ismini merkezi config'den al
     *
     * @param string $path Dot notation path (örn: 'admin.kullanicilar.index')
     * @param mixed ...$params Route parametreleri
     * @return string|null Route ismi veya null
     */
    public static function get(string $path, ...$params): ?string
    {
        $routeName = config("routes.{$path}");

        if (!$routeName) {
            // Fallback: Direkt route ismini kullan
            return $path;
        }

        return $routeName;
    }

    /**
     * Route URL'ini oluştur
     *
     * @param string $path Dot notation path
     * @param mixed ...$params Route parametreleri
     * @return string Route URL'i
     */
    public static function url(string $path, ...$params): string
    {
        $routeName = self::get($path);

        if (!$routeName || !Route::has($routeName)) {
            // Fallback: Path'i direkt URL olarak kullan
            return '/' . str_replace('.', '/', $path);
        }

        return route($routeName, $params);
    }

    /**
     * Route'un var olup olmadığını kontrol et
     *
     * @param string $path Dot notation path
     * @return bool
     */
    public static function has(string $path): bool
    {
        $routeName = self::get($path);

        if (!$routeName) {
            return false;
        }

        return Route::has($routeName);
    }

    /**
     * Blade template'inde kullanım için route helper
     *
     * @param string $path Dot notation path
     * @param mixed ...$params Route parametreleri
     * @return string
     */
    public static function route(string $path, ...$params): string
    {
        $routeName = self::get($path);

        if (!$routeName || !Route::has($routeName)) {
            // Fallback: Path'i direkt route olarak kullan
            return route($path, $params);
        }

        return route($routeName, $params);
    }
}


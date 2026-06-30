<?php

/**
 * Permission Route Helper - Permission-Based Route Yönetimi
 *
 * Context7 Standard: C7-PERMISSION-ROUTE-HELPER-2025-12-06
 *
 * Permission kontrolü ile route erişimi sağlar.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class PermissionRouteHelper
{
    /**
     * Route'a erişim izni var mı kontrol et
     *
     * @param string $routeName Route ismi (örn: 'admin.kullanicilar.index')
     * @param mixed $user Kullanıcı (null ise Auth::user() kullanılır)
     * @return bool
     */
    public static function canAccess(string $routeName, $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Public route kontrolü
        $publicRoutes = config('permission-routes.public', []);
        if (in_array($routeName, $publicRoutes)) {
            return true;
        }

        // Permission mapping'den permission'ı al
        $permission = self::getPermissionForRoute($routeName);

        if (!$permission) {
            // Varsayılan permission
            $permission = config('permission-routes.default', 'view-admin-panel');
        }

        // Permission türüne göre kontrol et
        return self::checkPermission($permission, $user);
    }

    /**
     * Route için gerekli permission'ı al
     *
     * @param string $routeName Route ismi
     * @return string|null Permission ismi
     */
    public static function getPermissionForRoute(string $routeName): ?string
    {
        // Route ismini config path'ine çevir
        // 'admin.kullanicilar.index' -> 'admin.kullanicilar.index'
        $configPath = str_replace('.', '.', $routeName);
        $permission = config("permission-routes.{$configPath}");

        // Nested path kontrolü
        if (!$permission) {
            $parts = explode('.', $routeName);
            $path = '';
            foreach ($parts as $part) {
                $path .= ($path ? '.' : '') . $part;
                $permission = config("permission-routes.{$path}");
                if ($permission && !is_array($permission)) {
                    return $permission;
                }
            }
        }

        return is_string($permission) ? $permission : null;
    }

    /**
     * Permission kontrolü yap
     *
     * @param string $permission Permission ismi
     * @param mixed $user Kullanıcı
     * @return bool
     */
    public static function checkPermission(string $permission, $user): bool
    {
        // Gate kontrolü
        $gatePermissions = config('permission-routes.types.gate', []);
        if (in_array($permission, $gatePermissions)) {
            return Gate::forUser($user)->allows($permission);
        }

        // Spatie Permission kontrolü
        $spatiePermissions = config('permission-routes.types.permission', []);
        if (in_array($permission, $spatiePermissions)) {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo($permission);
            }
        }

        // Role kontrolü
        $roles = config('permission-routes.types.role', []);
        if (in_array($permission, $roles)) {
            if (method_exists($user, 'hasRole')) {
                return $user->hasRole($permission);
            }
        }

        // Varsayılan: Gate kontrolü
        return Gate::forUser($user)->allows($permission);
    }

    /**
     * Route URL'ini permission kontrolü ile oluştur
     *
     * @param string $routeName Route ismi
     * @param mixed ...$params Route parametreleri
     * @return string|null Route URL'i veya null (permission yoksa)
     */
    public static function url(string $routeName, ...$params): ?string
    {
        if (!self::canAccess($routeName)) {
            return null;
        }

        if (!Route::has($routeName)) {
            return null;
        }

        return route($routeName, $params);
    }

    /**
     * Blade template'inde kullanım için helper
     *
     * @param string $routeName Route ismi
     * @param mixed ...$params Route parametreleri
     * @return string
     */
    public static function route(string $routeName, ...$params): string
    {
        $url = self::url($routeName, ...$params);

        return $url ?? '#';
    }

    /**
     * Tüm erişilebilir route'ları listele
     *
     * @param mixed $user Kullanıcı
     * @return array Route isimleri
     */
    public static function getAccessibleRoutes($user = null): array
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return [];
        }

        $allRoutes = config('permission-routes', []);
        $accessibleRoutes = [];

        self::collectAccessibleRoutes($allRoutes, $accessibleRoutes, $user, '');

        return $accessibleRoutes;
    }

    /**
     * Recursive olarak erişilebilir route'ları topla
     *
     * @param array $routes Route config
     * @param array &$accessibleRoutes Erişilebilir route'lar (referans)
     * @param mixed $user Kullanıcı
     * @param string $prefix Route prefix
     */
    protected static function collectAccessibleRoutes(array $routes, array &$accessibleRoutes, $user, string $prefix): void
    {
        foreach ($routes as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                self::collectAccessibleRoutes($value, $accessibleRoutes, $user, $currentPath);
            } else {
                // Permission kontrolü
                if (self::checkPermission($value, $user)) {
                    $accessibleRoutes[] = $currentPath;
                }
            }
        }
    }
}

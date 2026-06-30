<?php

/**
 * Menu Helper - Merkezi Menu Yönetimi
 *
 * Context7 Standard: C7-MENU-HELPER-2025-12-06
 *
 * Merkezi menu config'den menu item'ları alır ve permission kontrolü yapar.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class MenuHelper
{
    /**
     * Menu item'larını al (permission kontrolü ile)
     *
     * @param string $menuName Menu ismi (örn: 'admin.sidebar')
     * @param mixed $user Kullanıcı (null ise Auth::user() kullanılır)
     * @return array Menu item'ları
     */
    public static function get(string $menuName, $user = null): array
    {
        $user = $user ?? Auth::user();
        $menuItems = config("menus.{$menuName}", []);

        if (empty($menuItems)) {
            return [];
        }

        // Permission kontrolü ile filtrele
        return self::filterByPermission($menuItems, $user);
    }

    /**
     * Permission kontrolü ile menu item'larını filtrele
     *
     * @param array $items Menu item'ları
     * @param mixed $user Kullanıcı
     * @return array Filtrelenmiş menu item'ları
     */
    protected static function filterByPermission(array $items, $user): array
    {
        $filtered = [];

        foreach ($items as $item) {
            // Permission kontrolü
            if (isset($item['permission'])) {
                if (!PermissionRouteHelper::checkPermission($item['permission'], $user)) {
                    continue;
                }
            }

            // Route kontrolü
            if (isset($item['route']) && !Route::has($item['route'])) {
                continue;
            }

            // Children varsa recursive filtrele
            if (isset($item['children']) && is_array($item['children'])) {
                $filteredChildren = self::filterByPermission($item['children'], $user);

                // Eğer children yoksa ve group ise, item'ı ekleme
                if (empty($filteredChildren) && ($item['type'] ?? '') === 'group') {
                    continue;
                }

                $item['children'] = $filteredChildren;
            }

            $filtered[] = $item;
        }

        // display_order'a göre sırala (Context7: 'order' yasak)
        usort($filtered, function ($a, $b) {
            $orderA = $a['display_order'] ?? 999;
            $orderB = $b['display_order'] ?? 999;
            return $orderA <=> $orderB;
        });

        return $filtered;
    }

    /**
     * Menu item'ı render et (Blade için)
     *
     * @param array $item Menu item
     * @return string HTML
     */
    public static function renderItem(array $item): string
    {
        if ($item['type'] === 'group') {
            return self::renderGroup($item);
        }

        return self::renderLink($item);
    }

    /**
     * Link item render et
     *
     * @param array $item Menu item
     * @return string HTML
     */
    protected static function renderLink(array $item): string
    {
        $route = $item['route'] ?? '#';
        $name = $item['name'] ?? '';
        $icon = self::getIcon($item['icon'] ?? '');
        $badge = $item['badge'] ?? null;

        $url = PermissionRouteHelper::route($route);
        $isActive = request()->routeIs($route . '*');

        $html = '<a href="' . $url . '" class="flex items-center gap-3 h-11 px-3 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-all duration-200' . ($isActive ? ' bg-blue-600 text-white' : '') . '">';

        if ($icon) {
            $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">' . $icon . '</svg>';
        }

        $html .= '<span>' . $name . '</span>';

        if ($badge) {
            $html .= '<span class="ml-auto text-xs bg-green-500/20 text-green-600 px-1.5 py-0.5 rounded">' . $badge . '</span>';
        }

        $html .= '</a>';

        return $html;
    }

    /**
     * Group item render et
     *
     * @param array $item Menu item
     * @return string HTML
     */
    protected static function renderGroup(array $item): string
    {
        $name = $item['name'] ?? '';
        $icon = self::getIcon($item['icon'] ?? '');
        $children = $item['children'] ?? [];
        $isOpen = self::isGroupOpen($item);

        $html = '<div x-data="{ open: ' . ($isOpen ? 'true' : 'false') . ' }">';
        $html .= '<button @click="open = !open" class="flex items-center gap-3 h-11 px-3 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 w-full">';

        if ($icon) {
            $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">' . $icon . '</svg>';
        }

        $html .= '<span>' . $name . '</span>';
        $html .= '<svg class="w-4 h-4 ml-auto transition-transform" :class="{ \'rotate-180\': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />';
        $html .= '</svg>';
        $html .= '</button>';

        $html .= '<div x-show="open" class="ml-6 mt-1 space-y-1">';
        foreach ($children as $child) {
            $html .= self::renderLink($child);
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Group'un açık olup olmadığını kontrol et
     *
     * @param array $item Menu item
     * @return bool
     */
    protected static function isGroupOpen(array $item): bool
    {
        $children = $item['children'] ?? [];

        foreach ($children as $child) {
            if (isset($child['route']) && request()->routeIs($child['route'] . '*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Icon SVG path'ini al
     *
     * @param string $iconName Icon ismi
     * @return string SVG path
     */
    protected static function getIcon(string $iconName): string
    {
        $icons = config('menus.icons', []);
        return $icons[$iconName] ?? '';
    }

    /**
     * Menu item'ı JSON formatında al (API için)
     *
     * @param string $menuName Menu ismi
     * @param mixed $user Kullanıcı
     * @return array JSON formatında menu item'ları
     */
    public static function toJson(string $menuName, $user = null): array
    {
        $items = self::get($menuName, $user);

        return array_map(function ($item) {
            $json = [
                'id' => $item['id'] ?? null,
                'type' => $item['type'] ?? 'link',
                'name' => $item['name'] ?? '',
                'route' => $item['route'] ?? null,
                'icon' => $item['icon'] ?? null,
                'display_order' => $item['display_order'] ?? 999,
            ];

            if (isset($item['badge'])) {
                $json['badge'] = $item['badge'];
            }

            if (isset($item['children'])) {
                $json['children'] = array_map(function ($child) {
                    return [
                        'id' => $child['id'] ?? null,
                        'type' => $child['type'] ?? 'link',
                        'name' => $child['name'] ?? '',
                        'route' => $child['route'] ?? null,
                        'icon' => $child['icon'] ?? null,
                        'display_order' => $child['display_order'] ?? 999,
                        'badge' => $child['badge'] ?? null,
                    ];
                }, $item['children']);
            }

            return $json;
        }, $items);
    }
}

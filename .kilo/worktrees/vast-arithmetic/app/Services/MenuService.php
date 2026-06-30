<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class MenuService
{
    /**
     * Basic MenuService implementation
     */
    public function getMenuItems()
    {
        return [];
    }

    public function generateMenu($userRole = null)
    {
        return [
            'dashboard' => ['name' => 'Dashboard', 'route' => 'admin.dashboard.index'],
            'users' => ['name' => 'Kullanıcılar', 'route' => 'admin.kullanicilar.index'],
        ];
    }

    /**
     * Get menu items for specific role
     * Context7 compliant implementation - safe routes only
     */
    public function getMenuForRole($role, $userId = null)
    {
        $baseMenu = [
            [
                'type' => 'link', // context7-ignore
                'name' => 'Dashboard',
                'route' => 'admin.dashboard.index',
                'icon' => 'dashboard',
            ],
            [
                'type' => 'group', // context7-ignore
                'name' => 'İlan Yönetimi',
                'icon' => 'listing',
                'children' => [
                    ['type' => 'link', 'name' => 'Tüm İlanlar', 'route' => 'admin.ilanlar.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Yeni İlan', 'route' => 'admin.ilanlar.create'], // context7-ignore
                    ['type' => 'link', 'name' => 'İlan Kategorileri', 'route' => 'admin.ilan-kategorileri.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Yayın Tipi Yöneticisi', 'route' => 'admin.property_types.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Özellik Grupları', 'route' => 'admin.ozellikler.kategoriler.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Özellikler', 'route' => 'admin.ozellikler.index'], // context7-ignore
                ],
            ],
            [
                'type' => 'link', // context7-ignore
                'name' => 'Kullanıcılar',
                'route' => 'admin.kullanicilar.index',
                'icon' => 'users',
            ],
            [
                'type' => 'group', // context7-ignore
                'name' => 'CRM Yönetimi',
                'icon' => 'crm',
                'children' => [
                    ['type' => 'link', 'name' => 'CRM Dashboard', 'route' => 'admin.crm.dashboard'], // context7-ignore
                    ['type' => 'link', 'name' => 'Kişiler', 'route' => 'admin.kisiler.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Talepler', 'route' => 'admin.talepler.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Eşleştirmeler', 'route' => 'admin.eslesmeler.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Talep-Portföy', 'route' => 'admin.talep-portfolyo.index'], // context7-ignore
                ],
            ],
            [
                'type' => 'group', // context7-ignore
                'name' => 'Finans Yönetimi',
                'icon' => 'finance',
                'children' => [
                    ['type' => 'link', 'name' => 'Finansal İşlemler', 'route' => 'admin.finans.islemler.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Yeni İşlem', 'route' => 'admin.finans.islemler.create'], // context7-ignore
                    ['type' => 'link', 'name' => 'Komisyonlar', 'route' => 'admin.finans.komisyonlar.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Yeni Komisyon', 'route' => 'admin.finans.komisyonlar.create'], // context7-ignore
                ],
            ],
            [
                'type' => 'group', // context7-ignore
                'name' => 'AI Sistemi',
                'icon' => 'ai',
                'children' => [
                    ['type' => 'link', 'name' => 'AI Ayarları', 'route' => 'admin.ai-settings.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'AI Analytics', 'route' => 'admin.ai-settings.analytics'], // context7-ignore
                    ['type' => 'link', 'name' => 'AI Monitoring', 'route' => 'admin.ai-monitor.index'], // context7-ignore
                ],
            ],
            [
                'type' => 'group', // context7-ignore
                'name' => 'Blog Yönetimi',
                'icon' => 'blog',
                'children' => [
                    ['type' => 'link', 'name' => 'Yazılar', 'route' => 'admin.blog.posts.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Kategoriler', 'route' => 'admin.blog.categories.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Yorumlar', 'route' => 'admin.blog.comments.index'], // context7-ignore
                ],
            ],
            [
                'type' => 'group', // context7-ignore
                'name' => 'Adres Yönetimi',
                'icon' => 'location',
                'children' => [
                    ['type' => 'link', 'name' => 'Adres Yönetimi', 'route' => 'admin.adres-yonetimi.index'], // context7-ignore
                    ['type' => 'link', 'name' => 'Wikimapia Arama', 'route' => 'admin.wikimapia-search.index'], // context7-ignore
                ],
            ],
            [
                'type' => 'link', // context7-ignore
                'name' => 'Raporlar',
                'route' => 'admin.reports.index',
                'icon' => 'reports',
            ],
            [
                'type' => 'link', // context7-ignore
                'name' => 'Bildirimler',
                'route' => 'admin.notifications.index',
                'icon' => 'notifications',
            ],
            [
                'type' => 'link', // context7-ignore
                'name' => 'Ayarlar',
                'route' => 'admin.ayarlar.index',
                'icon' => 'settings',
            ],
        ];

        $cacheKey = 'admin_menu:' . ($role ?? 'guest') . ':' . ($userId ?? '0');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($baseMenu, $role) {
            $allowed = Gate::allows('view-admin-panel');
            $filtered = [];
            foreach ($baseMenu as $item) {
                if (isset($item['route']) && ! Route::has($item['route'])) {
                    continue;
                }
                if (! $allowed && ($item['route'] ?? null) !== 'admin.dashboard.index') {
                    continue;
                }
                if (isset($item['children'])) {
                    $children = [];
                    foreach ($item['children'] as $child) {
                        if (isset($child['route']) && ! Route::has($child['route'])) {
                            continue;
                        }
                        if (! $allowed) {
                            continue;
                        }
                        $children[] = $child;
                    }
                    if (! empty($children)) {
                        $item['children'] = $children;
                        $filtered[] = $item;
                    }

                    continue;
                }
                $filtered[] = $item;
            }
            Log::channel('module_changes')->info('admin-menu-generated', [
                'role' => $role,
                'count' => count($filtered),
            ]);

            return $filtered;
        });
    }
}

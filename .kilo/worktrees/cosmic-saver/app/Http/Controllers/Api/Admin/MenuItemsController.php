<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Cache\ControllerCacheMutationService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Menu Items API Controller
 *
 * Sidebar menü öğelerini API üzerinden sağlar (Lazy Loading için)
 * Context7 Standardı: C7-MENU-API-2025-12-01
 */
class MenuItemsController extends Controller
{
    public function __construct(private readonly ControllerCacheMutationService $cacheMutationService) {}

    /**
     * Menü öğelerini döndür
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Cache'den menü öğelerini al (5 dakika TTL)
        $menuItems = Cache::remember('admin.menu.items', 300, function () {
            return $this->buildMenuItems();
        });

        // ResponseService::success() formatı: { success: true, message: "...", data: [...] }
        return ResponseService::success($menuItems, 'Menü öğeleri başarıyla yüklendi');
    }

    /**
     * Menü öğelerini oluştur
     * Sidebar.blade.php'den tüm menü öğelerini çıkarır
     *
     * @return array
     */
    protected function buildMenuItems(): array
    {
        $items = [];

        // 1. Dashboard
        if (Route::has('admin.dashboard.index')) {
            $items[] = [
                'id' => 1,
                'text' => 'Dashboard',
                'route' => 'admin.dashboard.index',
                'icon' => 'dashboard',
                'type' => 'single', // context7-ignore
            ];
        }

        // 2. Kullanıcılar
        if (Route::has('admin.kullanicilar.index')) {
            $items[] = [
                'id' => 2,
                'text' => 'Kullanıcılar',
                'route' => 'admin.kullanicilar.index',
                'icon' => 'users',
                'type' => 'single', // context7-ignore
            ];
        }

        // 3. İlan Yönetimi (Dropdown)
        $ilanChildren = [];
        if (Route::has('admin.ilanlar.index')) {
            $ilanChildren[] = [
                'id' => 31,
                'text' => 'Tüm İlanlar',
                'route' => 'admin.ilanlar.index',
                'icon' => 'list',
            ];
        }
        if (Route::has('admin.ilanlar.create')) {
            $ilanChildren[] = [
                'id' => 32,
                'text' => 'Yeni İlan',
                'route' => 'admin.ilanlar.create',
                'icon' => 'plus',
                'badge' => 'AI',
            ];
        }
        if (Route::has('admin.ilan-kategorileri.index')) {
            $ilanChildren[] = [
                'id' => 33,
                'text' => 'İlan Kategorileri',
                'route' => 'admin.ilan-kategorileri.index',
                'icon' => 'tag',
            ];
        }
        if (Route::has('admin.property_types.index')) {
            $ilanChildren[] = [
                'id' => 34,
                'text' => 'Yayın Tipi Yöneticisi',
                'route' => 'admin.property_types.index',
                'icon' => 'tag',
                'badge' => 'Yeni',
            ];
        }
        if (Route::has('admin.ozellikler.kategoriler.index')) {
            $ilanChildren[] = [
                'id' => 35,
                'text' => 'Özellik Grupları',
                'route' => 'admin.ozellikler.kategoriler.index',
                'icon' => 'settings',
            ];
        }
        if (Route::has('admin.ozellikler.index')) {
            $ilanChildren[] = [
                'id' => 36,
                'text' => 'Özellikler',
                'route' => 'admin.ozellikler.index',
                'icon' => 'settings',
            ];
        }
        if (Route::has('admin.ozellikler.kategoriler.show')) {
            $ilanChildren[] = [
                'id' => 37,
                'text' => 'Site Özellikleri',
                'route' => 'admin.ozellikler.kategoriler.show',
                'route_params' => ['id' => 5],
                'icon' => 'building',
            ];
        }

        if (!empty($ilanChildren)) {
            $items[] = [
                'id' => 3,
                'text' => 'İlan Yönetimi',
                'icon' => 'document',
                'type' => 'dropdown', // context7-ignore
                'children' => $ilanChildren,
            ];
        }

        // 4. Danışmanlar
        if (Route::has('admin.danisman.index')) {
            $items[] = [
                'id' => 4,
                'text' => 'Danışmanlar',
                'route' => 'admin.danisman.index',
                'icon' => 'user-check',
                'type' => 'single', // context7-ignore
            ];
        }

        // 5. CRM Yönetimi (Dropdown)
        $crmChildren = [];
        if (Route::has('admin.crm.dashboard')) {
            $crmChildren[] = [
                'id' => 51,
                'text' => 'CRM Dashboard',
                'route' => 'admin.crm.dashboard',
                'icon' => 'dashboard',
            ];
        }
        if (Route::has('admin.kisiler.index')) {
            $crmChildren[] = [
                'id' => 52,
                'text' => 'Kişiler',
                'route' => 'admin.kisiler.index',
                'icon' => 'users',
            ];
        }
        if (Route::has('admin.talepler.index')) {
            $crmChildren[] = [
                'id' => 53,
                'text' => 'Talepler',
                'route' => 'admin.talepler.index',
                'icon' => 'file',
            ];
        }
        if (Route::has('admin.eslesmeler.index')) {
            $crmChildren[] = [
                'id' => 54,
                'text' => 'Eşleştirmeler',
                'route' => 'admin.eslesmeler.index',
                'icon' => 'link',
            ];
        }
        if (Route::has('admin.talep-portfolyo.index')) {
            $crmChildren[] = [
                'id' => 55,
                'text' => 'Talep-Portföy',
                'route' => 'admin.talep-portfolyo.index',
                'icon' => 'briefcase',
            ];
        }

        if (!empty($crmChildren)) {
            $items[] = [
                'id' => 5,
                'text' => 'CRM Yönetimi',
                'icon' => 'message',
                'type' => 'dropdown', // context7-ignore
                'children' => $crmChildren,
            ];
        }

        // 6. Finans Yönetimi (Dropdown)
        $finansChildren = [];
        if (Route::has('admin.finans.islemler.index')) {
            $finansChildren[] = [
                'id' => 61,
                'text' => 'Finansal İşlemler',
                'route' => 'admin.finans.islemler.index',
                'icon' => 'dollar-sign',
            ];
        }
        if (Route::has('admin.finans.islemler.create')) {
            $finansChildren[] = [
                'id' => 62,
                'text' => 'Yeni İşlem',
                'route' => 'admin.finans.islemler.create',
                'icon' => 'plus',
            ];
        }
        if (Route::has('admin.finans.komisyonlar.index')) {
            $finansChildren[] = [
                'id' => 63,
                'text' => 'Komisyonlar',
                'route' => 'admin.finans.komisyonlar.index',
                'icon' => 'percent',
            ];
        }
        if (Route::has('admin.finans.komisyonlar.create')) {
            $finansChildren[] = [
                'id' => 64,
                'text' => 'Yeni Komisyon',
                'route' => 'admin.finans.komisyonlar.create',
                'icon' => 'plus',
            ];
        }

        if (!empty($finansChildren)) {
            $items[] = [
                'id' => 6,
                'text' => 'Finans Yönetimi',
                'icon' => 'dollar-sign',
                'type' => 'dropdown', // context7-ignore
                'children' => $finansChildren,
            ];
        }

        // 7. Yazlık Kiralama (Dropdown)
        $yazlikChildren = [];
        if (Route::has('admin.yazlik-kiralama.index')) {
            $yazlikChildren[] = [
                'id' => 71,
                'text' => 'Yazlık İlanları',
                'route' => 'admin.yazlik-kiralama.index',
                'icon' => 'home',
            ];
        }
        if (Route::has('admin.yazlik-kiralama.takvim.index')) {
            $yazlikChildren[] = [
                'id' => 72,
                'text' => 'Takvim & Sezonlar',
                'route' => 'admin.yazlik-kiralama.takvim.index',
                'icon' => 'calendar',
            ];
        }
        if (Route::has('admin.yazlik-kiralama.bookings')) {
            $yazlikChildren[] = [
                'id' => 73,
                'text' => 'Rezervasyonlar',
                'route' => 'admin.yazlik-kiralama.bookings',
                'icon' => 'check-circle',
            ];
        }

        if (!empty($yazlikChildren)) {
            $items[] = [
                'id' => 7,
                'text' => 'Yazlık Kiralama',
                'icon' => 'home',
                'type' => 'dropdown', // context7-ignore
                'children' => $yazlikChildren,
            ];
        }

        // 8. Raporlar
        if (Route::has('admin.reports.index')) {
            $items[] = [
                'id' => 8,
                'text' => 'Raporlar',
                'route' => 'admin.reports.index',
                'icon' => 'bar-chart',
                'type' => 'single', // context7-ignore
            ];
        }

        // 9. Bildirimler
        if (Route::has('admin.notifications.index')) {
            $items[] = [
                'id' => 9,
                'text' => 'Bildirimler',
                'route' => 'admin.notifications.index',
                'icon' => 'bell',
                'type' => 'single', // context7-ignore
            ];
        }

        // 10. AI Sistemi (Dropdown)
        $aiChildren = [];
        if (Route::has('admin.ai.dashboard')) {
            $aiChildren[] = [
                'id' => 101,
                'text' => 'AI Command Center',
                'route' => 'admin.ai.dashboard',
                'icon' => 'brain',
                'badge' => 'Yeni',
            ];
        }
        if (Route::has('admin.ai-settings.index')) {
            $aiChildren[] = [
                'id' => 102,
                'text' => 'AI Ayarları',
                'route' => 'admin.ai-settings.index',
                'icon' => 'settings',
            ];
        }
        if (Route::has('admin.ai-settings.analytics')) {
            $aiChildren[] = [
                'id' => 103,
                'text' => 'AI Analytics',
                'route' => 'admin.ai-settings.analytics',
                'icon' => 'bar-chart',
            ];
        }
        if (Route::has('admin.ai-monitor.index')) {
            $aiChildren[] = [
                'id' => 104,
                'text' => 'AI Monitoring',
                'route' => 'admin.ai-monitor.index',
                'icon' => 'activity',
            ];
        }

        if (!empty($aiChildren)) {
            $items[] = [
                'id' => 10,
                'text' => 'AI Sistemi',
                'icon' => 'zap',
                'type' => 'dropdown', // context7-ignore
                'children' => $aiChildren,
            ];
        }

        // 11. Takım Yönetimi (Dropdown)
        $takimChildren = [];
        if (Route::has('admin.takim-yonetimi.index')) {
            $takimChildren[] = [
                'id' => 111,
                'text' => 'Takım Üyeleri',
                'route' => 'admin.takim-yonetimi.index',
                'icon' => 'users',
            ];
        }
        if (Route::has('admin.takim.gorevler.index')) {
            $takimChildren[] = [
                'id' => 112,
                'text' => 'Görevler',
                'route' => 'admin.takim.gorevler.index',
                'icon' => 'check-square',
            ];
        }
        if (Route::has('admin.takim-yonetimi.takim.performans')) {
            $takimChildren[] = [
                'id' => 113,
                'text' => 'Performans',
                'route' => 'admin.takim-yonetimi.takim.performans',
                'icon' => 'trending-up',
            ];
        }

        if (!empty($takimChildren)) {
            $items[] = [
                'id' => 11,
                'text' => 'Takım Yönetimi',
                'icon' => 'users',
                'type' => 'dropdown', // context7-ignore
                'children' => $takimChildren,
            ];
        }

        // 12. Analytics (Dropdown)
        $analyticsChildren = [];
        if (Route::has('admin.analytics.index')) {
            $analyticsChildren[] = [
                'id' => 121,
                'text' => 'Genel Analytics',
                'route' => 'admin.analytics.index',
                'icon' => 'bar-chart',
            ];
        }
        if (Route::has('admin.analytics.dashboard')) {
            $analyticsChildren[] = [
                'id' => 122,
                'text' => 'Analytics Dashboard',
                'route' => 'admin.analytics.dashboard',
                'icon' => 'dashboard',
            ];
        }
        if (Route::has('admin.reports.index')) {
            $analyticsChildren[] = [
                'id' => 123,
                'text' => 'Raporlar',
                'route' => 'admin.reports.index',
                'icon' => 'file-text',
            ];
        }

        if (!empty($analyticsChildren)) {
            $items[] = [
                'id' => 12,
                'text' => 'Analytics',
                'icon' => 'bar-chart',
                'type' => 'dropdown', // context7-ignore
                'children' => $analyticsChildren,
            ];
        }

        // 13. Telegram Bot (Dropdown)
        $telegramChildren = [];
        if (Route::has('admin.telegram-bot.index')) {
            $telegramChildren[] = [
                'id' => 131,
                'text' => 'Genel',
                'route' => 'admin.telegram-bot.index',
                'icon' => 'message-circle',
            ];
        }
        if (Route::has('admin.telegram-bot.durum')) {
            $telegramChildren[] = [
                'id' => 132,
                'text' => 'Durum',
                'route' => 'admin.telegram-bot.durum',
                'icon' => 'activity',
            ];
        }
        if (Route::has('admin.telegram-bot.webhook-info')) {
            $telegramChildren[] = [
                'id' => 133,
                'text' => 'Webhook Bilgisi',
                'route' => 'admin.telegram-bot.webhook-info',
                'icon' => 'webhook',
            ];
        }

        if (!empty($telegramChildren)) {
            $items[] = [
                'id' => 13,
                'text' => 'Telegram Bot',
                'icon' => 'send',
                'type' => 'dropdown', // context7-ignore
                'children' => $telegramChildren,
            ];
        }

        // 14. Blog Yönetimi (Dropdown)
        $blogChildren = [];
        if (Route::has('admin.blog.posts.index')) {
            $blogChildren[] = [
                'id' => 141,
                'text' => 'Yazılar',
                'route' => 'admin.blog.posts.index',
                'icon' => 'file-text',
            ];
        }
        if (Route::has('admin.blog.categories.index')) {
            $blogChildren[] = [
                'id' => 142,
                'text' => 'Kategoriler',
                'route' => 'admin.blog.categories.index',
                'icon' => 'folder',
            ];
        }
        if (Route::has('admin.blog.comments.index')) {
            $blogChildren[] = [
                'id' => 143,
                'text' => 'Yorumlar',
                'route' => 'admin.blog.comments.index',
                'icon' => 'message-square',
            ];
        }

        if (!empty($blogChildren)) {
            $items[] = [
                'id' => 14,
                'text' => 'Blog Yönetimi',
                'icon' => 'file-text',
                'type' => 'dropdown', // context7-ignore
                'children' => $blogChildren,
            ];
        }

        // 15. Adres Yönetimi (Dropdown)
        $adresChildren = [];
        if (Route::has('admin.adres-yonetimi.index')) {
            $adresChildren[] = [
                'id' => 151,
                'text' => 'Adres Yönetimi',
                'route' => 'admin.adres-yonetimi.index',
                'icon' => 'map-pin',
            ];
        }
        if (Route::has('admin.wikimapia-search.index')) {
            $adresChildren[] = [
                'id' => 152,
                'text' => 'Wikimapia Arama',
                'route' => 'admin.wikimapia-search.index',
                'icon' => 'search',
                'badge' => 'Yeni',
            ];
        }

        if (!empty($adresChildren)) {
            $items[] = [
                'id' => 15,
                'text' => 'Adres Yönetimi',
                'icon' => 'map-pin',
                'type' => 'dropdown', // context7-ignore
                'children' => $adresChildren,
            ];
        }

        // 16. İlanlarım
        if (Route::has('admin.ilanlarim.index')) {
            $items[] = [
                'id' => 16,
                'text' => 'İlanlarım',
                'route' => 'admin.ilanlarim.index',
                'icon' => 'bookmark',
                'type' => 'single', // context7-ignore
            ];
        }

        // 17. Harita
        if (Route::has('admin.map.index')) {
            $items[] = [
                'id' => 17,
                'text' => 'Harita',
                'route' => 'admin.map.index',
                'icon' => 'map',
                'type' => 'single', // context7-ignore
            ];
        }

        // 18. Pazar İstihbaratı (Dropdown)
        $marketChildren = [];
        if (Route::has('admin.market-intelligence.dashboard')) {
            $marketChildren[] = [
                'id' => 181,
                'text' => 'Dashboard',
                'route' => 'admin.market-intelligence.dashboard',
                'icon' => 'dashboard',
            ];
        }
        if (Route::has('admin.market-intelligence.settings')) {
            $marketChildren[] = [
                'id' => 182,
                'text' => 'Bölge Ayarları',
                'route' => 'admin.market-intelligence.settings',
                'icon' => 'settings',
            ];
        }
        if (Route::has('admin.market-intelligence.compare')) {
            $marketChildren[] = [
                'id' => 183,
                'text' => 'Fiyat Karşılaştırma',
                'route' => 'admin.market-intelligence.compare',
                'icon' => 'trending-up',
            ];
        }
        if (Route::has('admin.market-intelligence.trends')) {
            $marketChildren[] = [
                'id' => 184,
                'text' => 'Piyasa Trendleri',
                'route' => 'admin.market-intelligence.trends',
                'icon' => 'line-chart',
            ];
        }

        if (!empty($marketChildren)) {
            $items[] = [
                'id' => 18,
                'text' => 'Pazar İstihbaratı',
                'icon' => 'trending-up',
                'type' => 'dropdown', // context7-ignore
                'children' => $marketChildren,
            ];
        }

        // 19. Akıllı Hesaplayıcı
        if (Route::has('admin.smart-calculator')) {
            $items[] = [
                'id' => 19,
                'text' => 'Akıllı Hesaplayıcı',
                'route' => 'admin.smart-calculator',
                'icon' => 'calculator',
                'type' => 'single', // context7-ignore
            ];
        }

        // 20. System Tools (Dropdown) - Static, route kontrolü yok
        $items[] = [
            'id' => 20,
            'text' => 'System Tools',
            'icon' => 'settings',
            'type' => 'dropdown', // context7-ignore
            'children' => [
                [
                    'id' => 201,
                    'text' => 'Horizon (Queue)',
                    'url' => '/horizon',
                    'icon' => 'zap',
                    'badge' => 'FREE',
                    'external' => true,
                ],
                [
                    'id' => 202,
                    'text' => 'Telescope (Debug)',
                    'url' => '/telescope',
                    'icon' => 'search',
                    'badge' => 'DEV',
                    'external' => true,
                    'condition' => 'app.debug',
                ],
                [
                    'id' => 203,
                    'text' => 'Sentry (Errors)',
                    'url' => 'https://sentry.io',
                    'icon' => 'alert-triangle',
                    'badge' => 'FREE',
                    'external' => true,
                    'condition' => 'sentry.dsn',
                ],
            ],
        ];

        // 21. Ayarlar
        if (Route::has('admin.ayarlar.index')) {
            $items[] = [
                'id' => 21,
                'text' => 'Ayarlar',
                'route' => 'admin.ayarlar.index',
                'icon' => 'settings',
                'type' => 'single', // context7-ignore
            ];
        }

        return $items;
    }

    /**
     * Menü cache'ini temizle
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        $this->cacheMutationService->forget('admin.menu.items');

        return ResponseService::success(null, 'Menü cache\'i temizlendi');
    }
}

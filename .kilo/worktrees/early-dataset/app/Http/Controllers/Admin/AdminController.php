<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminSettingsCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

/**
 * Base Admin Controller
 * Context7: Tüm admin controller'lar için ortak davranışlar
 *
 * Yalıhan Bekçi Fix: Undefined variable sorunlarını çözmek için
 * tüm admin sayfalarında ortak kullanılan değişkenler burada tanımlanır
 */
class AdminController extends Controller
{
    use \App\Traits\AdminMenu;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Clear shared data cache
     * Context7: Cache'i temizle (ayarlar değiştiğinde)
     */
    protected function clearSharedDataCache(): void
    {
        app(AdminSettingsCacheService::class)->invalidateAdminSharedData();
    }
}

<?php

namespace App\Providers;

use App\Traits\AdminMenu;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AdminMenuServiceProvider extends ServiceProvider
{
    use AdminMenu;

    public function boot(): void
    {
        View::composer('admin.*', function ($view) {
            // 🛡️ GOVERNANCE: Standardize Admin View Data
            // This replaces the direct DB calls in AdminController::__construct
            // and ensures data is only loaded when an admin view is actually rendered.
            
            // 1. Admin Menu
            $view->with('adminMenu', $this->adminMenu());

            // 2. Common Data (Lazy Loaded)
            $view->with('yayin_durumlari', [
                'taslak' => 'Taslak',
                'onay_bekliyor' => 'Onay Bekliyor',
                'yayinda' => 'Yayında',
                'satildi' => 'Satıldı',
                'kiralandı' => 'Kiralandı',
                'pasif' => 'Pasif',
                'arsivlendi' => 'Arşivlendi',
            ]);

            $view->with('taslak', [
                '0' => 'Hayır',
                '1' => 'Evet',
            ]);

            $view->with('para_birimleri', [
                'TRY' => '₺ TL',
                'USD' => '$ USD',
                'EUR' => '€ EUR',
                'GBP' => '£ GBP',
            ]);

            // 3. Database-backed data (Cache Optimized)
            // First Category ID
            $view->with('firstCategoryId', \Illuminate\Support\Facades\Cache::remember('admin.first_category_id', 3600, function () {
                return \App\Models\IlanKategori::where('seviye', 0)
                    ->where('aktiflik_durumu', true)
                    ->orderBy('name', 'ASC')
                    ->value('id') ?? 1;
            }));

            // Etiketler
            $view->with('etiketler', \Illuminate\Support\Facades\Cache::remember('admin.etiketler', 3600, function () {
                if (class_exists(\App\Models\Etiket::class)) {
                    return \App\Models\Etiket::where('aktiflik_durumu', 1)->orderBy('id')->get();
                }
                return collect([]);
            }));

            // Ülkeler
            $view->with('ulkeler', \Illuminate\Support\Facades\Cache::remember('admin.ulkeler', 3600, function () {
                if (class_exists(\App\Models\Ulke::class)) {
                    return \App\Models\Ulke::orderBy('ulke_adi')->get();
                }
                return collect([]);
            }));

            // Yayın Tipleri
            $view->with('yayin_tipleri', \Illuminate\Support\Facades\Cache::remember('admin.yayin_tipleri', 3600, function () {
                if (class_exists(\App\Models\YayinTipiSablonu::class)) {
                    return \App\Models\YayinTipiSablonu::where('aktiflik_durumu', true)->orderBy('display_order')->get();
                }
                return collect([]);
            }));
        });
    }
}

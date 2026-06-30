<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * View Namespace Service Provider
 *
 * #60 Fix: App\Providers\ModuleServiceProvider isim çakışmasını çözmek için
 * ViewNamespaceServiceProvider olarak rename edildi.
 * (App\Modules\ModuleServiceProvider → sub-module kayıt, bu → view namespace yükleme)
 */
class ViewNamespaceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Address module removed - using unified location selector instead
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Emlak modülü için view namespace'i kaydet - eğer dizin varsa
        if (is_dir(resource_path('views/emlak'))) {
            $this->loadViewsFrom(resource_path('views/emlak'), 'emlak-views');
        }
    }
}

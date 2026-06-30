<?php

namespace App\Modules\Finans;

use Illuminate\Support\ServiceProvider;

/**
 * Finans Module Service Provider
 *
 * Context7 Standardı: C7-FINANS-PROVIDER-2025-11-25
 */
class FinansServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $moduleName = 'Finans';
        $basePath = __DIR__;

        // Rotaları yükle
        if (file_exists($basePath.'/routes/web.php')) {
            $this->loadRoutesFrom($basePath.'/routes/web.php');
        }
        if (file_exists($basePath.'/routes/api.php')) {
            $this->loadRoutesFrom($basePath.'/routes/api.php');
        }

        // Migration'ları yükle
        if (is_dir($basePath.'/database/migrations')) {
            $this->loadMigrationsFrom($basePath.'/database/migrations');
        }

        // View'ları yükle
        if (is_dir($basePath.'/Resources/views')) {
            $this->loadViewsFrom($basePath.'/Resources/views', $moduleName);
        } elseif (is_dir($basePath.'/Views')) {
            $this->loadViewsFrom($basePath.'/Views', $moduleName);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Service binding'leri buraya eklenebilir
    }
}

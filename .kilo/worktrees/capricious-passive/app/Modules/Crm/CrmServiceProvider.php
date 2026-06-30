<?php

namespace App\Modules\Crm;

use App\Modules\Crm\Services\AktiviteService;
use App\Modules\Crm\Services\EtiketService;
use App\Modules\Crm\Services\KisiService;
use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $moduleName = 'Crm';
        $basePath = __DIR__;

        // Rotaları yükle
        if (file_exists($basePath.'/routes/web.php')) {
            $this->loadRoutesFrom($basePath.'/routes/web.php');
        }
        if (file_exists($basePath.'/routes/api.php')) {
            $this->loadRoutesFrom($basePath.'/routes/api.php');
        }

        // Migration'ları yükle
        if (is_dir($basePath.'/Database/Migrations')) {
            $this->loadMigrationsFrom($basePath.'/Database/Migrations');
        }

        // View'ları yükle
        if (is_dir($basePath.'/Resources/views')) {
            $this->loadViewsFrom($basePath.'/Resources/views', $moduleName);
        } elseif (is_dir($basePath.'/Views')) { // Eski yapı için kontrol
            $this->loadViewsFrom($basePath.'/Views', $moduleName);
        }

        // Dil dosyalarını yükle
        if (is_dir($basePath.'/Resources/lang')) {
            $this->loadTranslationsFrom($basePath.'/Resources/lang', $moduleName);
            $this->loadJsonTranslationsFrom($basePath.'/Resources/lang');
        }

        // Config dosyalarını yükle
        if (is_dir($basePath.'/Config')) {
            $configPath = $basePath.'/Config';
            $this->publishes([
                $configPath => config_path($moduleName.'.php'),
            ], 'config');
            // Modül config dosyasının varlığını kontrol et
            if (file_exists($configPath.'/'.strtolower($moduleName).'.php')) {
                $this->mergeConfigFrom($configPath.'/'.strtolower($moduleName).'.php', strtolower($moduleName));
            }
        }

        // Helper dosyalarını yükle
        if (file_exists($basePath.'/Helpers/helpers.php')) {
            include_once $basePath.'/Helpers/helpers.php';
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(KisiService::class, function ($app) {
            return new KisiService;
        });

        $this->app->singleton(EtiketService::class, function ($app) {
            return new EtiketService;
        });

        $this->app->singleton(AktiviteService::class, function ($app) {
            return new AktiviteService;
        });

        // Diğer servisler veya repository'ler burada kaydedilebilir.
    }
}

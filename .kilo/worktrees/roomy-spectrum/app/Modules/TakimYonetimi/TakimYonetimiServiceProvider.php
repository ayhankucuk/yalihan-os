<?php

namespace App\Modules\TakimYonetimi;

use Illuminate\Support\ServiceProvider;

class TakimYonetimiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $moduleName = 'TakimYonetimi';
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
        if (is_dir($basePath.'/Views')) {
            $this->loadViewsFrom($basePath.'/Views', strtolower($moduleName));
        }

        // Dil dosyalarını yükle
        if (is_dir($basePath.'/Lang')) {
            $this->loadTranslationsFrom($basePath.'/Lang', strtolower($moduleName));
        }

        // Config dosyalarını yükle
        if (is_dir($basePath.'/Config')) {
            $configPath = $basePath.'/Config';
            $this->publishes([
                $configPath => config_path(strtolower($moduleName)),
            ], 'config');

            if (file_exists($configPath.'/'.strtolower($moduleName).'.php')) {
                $this->mergeConfigFrom($configPath.'/'.strtolower($moduleName).'.php', strtolower($moduleName));
            }
        }

        // Policy'leri kaydet
        $this->registerPolicies();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Servisleri kaydet
        $this->app->bind('takim.gorev', function ($app) {
            return new \App\Modules\TakimYonetimi\Services\GorevYonetimService;
        });

        $this->app->bind('takim.telegram', function ($app) {
            return new \App\Modules\TakimYonetimi\Services\TelegramBotService;
        });

        $this->app->bind('takim.context7', function ($app) {
            return new \App\Modules\TakimYonetimi\Services\Context7AIService;
        });
    }

    /**
     * Policy'leri kaydet
     */
    protected function registerPolicies()
    {
        // Policy'ler otomatik olarak Laravel tarafından keşfedilir
        // Burada özel policy kayıtları yapılabilir
    }
}

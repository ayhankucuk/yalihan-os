<?php

namespace App\Modules\Talep;

use Illuminate\Support\ServiceProvider;

class TalepServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ✅ NOTE: Talep modülü route'ları TalepAnaliz modülüne taşındı
        // TalepController artık TalepAnaliz modülünde kullanılıyor
        // Bu route'lar gelecekte TalepAnaliz modülüne entegre edilebilir
        /*
        // Route tanımları (TalepAnaliz modülünde kullanılıyor)
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // View tanımları
        $this->loadViewsFrom(__DIR__ . '/Views', 'talep');

        // Migration tanımları
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        */

        // Çeviri tanımları
        $this->loadTranslationsFrom(__DIR__.'/Lang', 'talep');

        // Config dosyalarının yayınlanması
        $this->publishes([
            __DIR__.'/Config' => config_path('talep'),
        ], 'talep-config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Talep modülü için servis sınıflarını bağla
        $this->app->bind('talep.analiz', function ($app) {
            return new \App\Modules\TalepAnaliz\Services\AIAnalizService;
        });

        // Config already migrated to config/talep.php (Standard Laravel location)
        /*
        $this->mergeConfigFrom(
            __DIR__.'/Config/talep.php',
            'talep'
        );
        */
    }
}

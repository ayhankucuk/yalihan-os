<?php

namespace App\Modules\Auth;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        // ✅ FIXED: Views directory doesn't exist, skip loadViewsFrom (2025-12-27)
        // if (is_dir(__DIR__.'/Views')) {
        //     $this->loadViewsFrom(__DIR__.'/Views', 'auth');
        // }
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Bağımlılıkları ve servisleri kaydet
    }
}

<?php

namespace App\Modules\Admin;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/Views', 'admin');
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Bağımlılıkları ve servisleri kaydet
    }
}

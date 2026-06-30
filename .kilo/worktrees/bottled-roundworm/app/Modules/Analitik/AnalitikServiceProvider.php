<?php

namespace App\Modules\Analitik;

use Illuminate\Support\ServiceProvider;

class AnalitikServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        // Views
        $this->loadViewsFrom(__DIR__.'/Views', 'analitik');

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}

<?php

namespace App\Modules\Emlak;

use Illuminate\Support\ServiceProvider;

class EmlakServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/Views', 'emlak');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register any bindings or services
    }
}

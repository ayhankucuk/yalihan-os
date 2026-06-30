<?php

namespace App\Modules\TalepAnaliz;

use Illuminate\Support\ServiceProvider;

class TalepAnalizServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'TalepAnaliz');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        }
    }
}

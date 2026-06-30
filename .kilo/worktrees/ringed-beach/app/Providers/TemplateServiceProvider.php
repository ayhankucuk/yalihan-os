<?php

namespace App\Providers;

use App\Contracts\TemplateResolverInterface;
use App\Services\TemplateResolver;
use Illuminate\Support\ServiceProvider;

class TemplateServiceProvider extends ServiceProvider
{
    /**
     * Register Template System services
     */
    public function register(): void
    {
        $this->app->singleton(TemplateResolverInterface::class, TemplateResolver::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}

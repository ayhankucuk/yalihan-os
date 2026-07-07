<?php

namespace App\Providers;

use App\Contracts\TemplateResolverInterface;
use App\Domain\PropertyWorkspace\Templates\TemplateRegistry;
use App\Services\PropertyWorkspace\TemplateEngineService;
use App\Services\TemplateResolver;
use App\Services\Workspace\IntentService;
use Illuminate\Support\ServiceProvider;

class TemplateServiceProvider extends ServiceProvider
{
    /**
     * Register Template System services
     */
    public function register(): void
    {
        // Existing bindings
        $this->app->singleton(TemplateResolverInterface::class, TemplateResolver::class);

        // Sprint 6.1: Property Workspace Template Engine
        $this->app->singleton(TemplateRegistry::class, function () {
            return new TemplateRegistry();
        });

        $this->app->singleton(IntentService::class, function () {
            return new IntentService();
        });

        $this->app->singleton(TemplateEngineService::class, function ($app) {
            return new TemplateEngineService(
                registry: $app->make(TemplateRegistry::class),
                intentService: $app->make(IntentService::class),
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}

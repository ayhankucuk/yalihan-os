<?php

namespace App\Providers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Services\Hermes\Handlers\NotificationAgentHandler;
use App\Services\Hermes\HermesDispatcher;
use App\Services\Hermes\HermesRegistry;
use App\Services\Hermes\HermesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * HermesServiceProvider
 *
 * Wires Hermes event bus components.
 * Registers event handlers and provides singleton services.
 */
class HermesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register HermesRegistry as singleton
        $this->app->singleton(HermesRegistry::class, function () {
            return new HermesRegistry();
        });

        // Register HermesDispatcher
        $this->app->singleton(HermesDispatcher::class, function ($app) {
            return new HermesDispatcher(
                $app->make(HermesRegistry::class)
            );
        });

        // Register HermesService
        $this->app->singleton(HermesService::class, function ($app) {
            return new HermesService(
                $app->make(HermesDispatcher::class)
            );
        });

        // Register NotificationAgentHandler
        $this->app->singleton(NotificationAgentHandler::class, function () {
            return new NotificationAgentHandler();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register all handlers with the registry
        $registry = $this->app->make(HermesRegistry::class);

        $handlers = [
            $this->app->make(NotificationAgentHandler::class),
        ];

        foreach ($handlers as $handler) {
            $registry->register($handler);
        }

        Log::info('[HermesServiceProvider] Hermes event bus initialized', [
            'handlers_count' => count($handlers),
            'registered_events' => $registry->getRegisteredEvents(),
        ]);
    }
}

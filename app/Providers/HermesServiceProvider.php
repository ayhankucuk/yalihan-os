<?php

namespace App\Providers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Services\Hermes\Handlers\AnalyticsHandler;
use App\Services\Hermes\Handlers\GovernanceNotificationHandler;
use App\Services\Hermes\Handlers\NotificationAgentHandler;
use App\Services\Hermes\Handlers\Workforce\DescriptionAgent;
use App\Services\Hermes\Handlers\Workforce\NotificationAgent;
use App\Services\Hermes\Handlers\Workforce\PhotoAgent;
use App\Services\Hermes\Handlers\Workforce\PortfolioAgent;
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
 *
 * Sprint 4.3: Adds AI Workforce agents (PortfolioAgent, PhotoAgent,
 * DescriptionAgent, NotificationAgent).
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

        // ─── Handler singletons ─────────────────────────────────────────

        $this->app->singleton(NotificationAgentHandler::class, fn () => new NotificationAgentHandler());
        $this->app->singleton(AnalyticsHandler::class, fn () => new AnalyticsHandler());
        $this->app->singleton(GovernanceNotificationHandler::class, fn () => new GovernanceNotificationHandler());

        // AI Workforce agents
        $this->app->singleton(PortfolioAgent::class, fn ($app) => new PortfolioAgent(
            $app->make(HermesService::class)
        ));
        $this->app->singleton(PhotoAgent::class, fn () => new PhotoAgent());
        $this->app->singleton(DescriptionAgent::class, fn () => new DescriptionAgent());
        $this->app->singleton(NotificationAgent::class, fn () => new NotificationAgent());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register all handlers with the registry
        $registry = $this->app->make(HermesRegistry::class);

        $handlers = [
            // Existing handlers
            $this->app->make(NotificationAgentHandler::class),
            $this->app->make(AnalyticsHandler::class),
            $this->app->make(GovernanceNotificationHandler::class),

            // AI Workforce agents — Sprint 4.3
            $this->app->make(PortfolioAgent::class),
            $this->app->make(PhotoAgent::class),
            $this->app->make(DescriptionAgent::class),
            $this->app->make(NotificationAgent::class),
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

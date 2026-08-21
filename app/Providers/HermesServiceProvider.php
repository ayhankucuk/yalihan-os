<?php

namespace App\Providers;

use App\Services\Drive\DriveWorkspaceService;
use App\Domain\Hermes\Handlers\CommunicationEmailHandler;
use App\Services\Hermes\Handlers\AnalyticsHandler;
use App\Services\Hermes\Handlers\GovernanceNotificationHandler;
use App\Services\Hermes\Handlers\NotificationAgentHandler;
use App\Services\Hermes\Handlers\Workflow\PropertyScoreAgent;
use App\Services\Hermes\Handlers\Workflow\PublishDecisionAgent;
use App\Services\Hermes\Handlers\Workforce\DescriptionAgent;
use App\Services\Hermes\Handlers\Workforce\DriveAgent;
use App\Services\Hermes\Handlers\Workforce\NotificationAgent;
use App\Services\Hermes\Handlers\Workforce\PhotoAgent;
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
 * Sprint 4.5: Workspace-First chain
 *   portfolio.created → DriveAgent → workspace.created → PhotoAgent
 *   → description.completed → PropertyScoreAgent
 *   → property_score.calculated → PublishDecisionAgent
 *   → publishing.decision_ready → NotificationAgent
 */
class HermesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register HermesRegistry as singleton
        $this->app->singleton(HermesRegistry::class, fn () => new HermesRegistry());

        // Register HermesDispatcher
        $this->app->singleton(HermesDispatcher::class, fn ($app) => new HermesDispatcher(
            $app->make(HermesRegistry::class)
        ));

        // Register HermesService
        $this->app->singleton(HermesService::class, fn ($app) => new HermesService(
            $app->make(HermesDispatcher::class)
        ));

        // ─── Handler singletons ─────────────────────────────────────────

        $this->app->singleton(NotificationAgentHandler::class, fn () => new NotificationAgentHandler());
        $this->app->singleton(AnalyticsHandler::class, fn () => new AnalyticsHandler());
        $this->app->singleton(GovernanceNotificationHandler::class, fn () => new GovernanceNotificationHandler());

        // DriveWorkspace service + agent — Sprint 4.4
        $this->app->singleton(DriveWorkspaceService::class, fn () => new DriveWorkspaceService());
        $this->app->singleton(DriveAgent::class, fn ($app) => new DriveAgent(
            $app->make(DriveWorkspaceService::class),
            $app->make(HermesService::class),
        ));

        // AI Workforce agents — Sprint 4.5 Workspace-First chain
        $this->app->singleton(PhotoAgent::class, fn ($app) => new PhotoAgent(
            $app->make(HermesService::class)
        ));
        $this->app->singleton(DescriptionAgent::class, fn ($app) => new DescriptionAgent(
            $app->make(HermesService::class)
        ));
        $this->app->singleton(PropertyScoreAgent::class, fn ($app) => new PropertyScoreAgent(
            $app->make(HermesService::class)
        ));
        $this->app->singleton(PublishDecisionAgent::class, fn ($app) => new PublishDecisionAgent(
            $app->make(HermesService::class)
        ));
        $this->app->singleton(NotificationAgent::class, fn ($app) => new NotificationAgent(
            $app->make(HermesService::class)
        ));

        // Gmail Communications Intelligence — Wave 1
        $this->app->singleton(CommunicationEmailHandler::class, fn () => new CommunicationEmailHandler());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $registry = $this->app->make(HermesRegistry::class);

        $handlers = [
            // Existing handlers
            $this->app->make(NotificationAgentHandler::class),
            $this->app->make(AnalyticsHandler::class),
            $this->app->make(GovernanceNotificationHandler::class),

            // Workspace-First chain — Sprint 4.5
            $this->app->make(DriveAgent::class),
            $this->app->make(PhotoAgent::class),
            $this->app->make(DescriptionAgent::class),
            $this->app->make(PropertyScoreAgent::class),
            $this->app->make(PublishDecisionAgent::class),
            $this->app->make(NotificationAgent::class),

            // Gmail Communications Intelligence — Wave 1
            $this->app->make(CommunicationEmailHandler::class),
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

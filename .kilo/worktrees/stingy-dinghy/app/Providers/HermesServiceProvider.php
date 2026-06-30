<?php

namespace App\Providers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Services\Hermes\Handlers\NotificationAgentHandler;
use App\Services\Hermes\HermesDispatcher;
use App\Services\Hermes\HermesRegistry;
use App\Services\Hermes\HermesService;
use App\Services\Hermes\Registry\AgentRegistry;
use App\Services\Hermes\Registry\CapabilityRegistry;
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
        // Register CapabilityRegistry as singleton (ontology layer)
        $this->app->singleton(CapabilityRegistry::class, function () {
            return new CapabilityRegistry();
        });

        // Register AgentRegistry as singleton (agent layer)
        $this->app->singleton(AgentRegistry::class, function () {
            return new AgentRegistry();
        });

        // Register HermesRegistry as singleton (handler layer)
        $this->app->singleton(HermesRegistry::class, function ($app) {
            $registry = new HermesRegistry();

            // Auto-register agents from AgentRegistry
            $agentRegistry = $app->make(AgentRegistry::class);
            foreach ($agentRegistry->all() as $entry) {
                if ($entry->enabled && class_exists($entry->agentClass)) {
                    $handler = $app->make($entry->agentClass);
                    if ($handler instanceof HermesHandlerContract) {
                        $registry->register($handler);
                    }
                }
            }

            return $registry;
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
        $agentRegistry = $this->app->make(AgentRegistry::class);
        $capabilityRegistry = $this->app->make(CapabilityRegistry::class);
        $hermesRegistry = $this->app->make(HermesRegistry::class);

        Log::info('[HermesServiceProvider] Hermes event bus initialized', [
            'agents_registered' => count($agentRegistry->all()),
            'ontology_events' => count($capabilityRegistry->getRegisteredEvents()),
            'ontology_capabilities' => count($capabilityRegistry->getRegisteredCapabilities()),
            'hermes_handlers' => count($hermesRegistry->getRegisteredEvents()),
        ]);
    }
}

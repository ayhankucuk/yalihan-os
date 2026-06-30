<?php

namespace App\Providers;

use App\Domain\AI\Contracts\CortexServiceInterface;
use Illuminate\Support\ServiceProvider;

class CortexServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 1. Register Dedicated Adapters
        $this->app->singleton(\App\Infrastructure\AI\Providers\OllamaCortexAdapter::class);
        $this->app->singleton(\App\Infrastructure\AI\Providers\OpenAICortexAdapter::class);
        $this->app->singleton(\App\Infrastructure\AI\Providers\GeminiCortexAdapter::class);
        $this->app->singleton(\App\Infrastructure\AI\Providers\DeepSeekCortexAdapter::class);

        // 2. Register Scoring Components
        $this->app->singleton(\App\Application\AI\Support\RequestComplexityEstimator::class);
        $this->app->singleton(\App\Application\AI\Support\CostEstimator::class);
        $this->app->singleton(\App\Application\AI\Support\ProviderLatencyRepository::class);
        $this->app->singleton(\App\Application\AI\Support\ProviderReliabilityRepository::class);
        $this->app->singleton(\App\Infrastructure\AI\Routing\ProviderScorer::class);

        // 3. Register Routing Engine
        $this->app->singleton(\App\Infrastructure\AI\Routing\ProviderRegistry::class);
        $this->app->singleton(\App\Domain\AI\Contracts\AIProviderRouterInterface::class, \App\Infrastructure\AI\Routing\AIProviderRouter::class);
        $this->app->singleton(\App\Application\AI\Support\RoutedCortexExecutor::class);

        // 4. Bind Service Interface to Orchestrator
        $this->app->singleton(CortexServiceInterface::class, function ($app) {
            return $app->make(\App\Infrastructure\AI\CortexOrchestrator::class);
        });

        // 5. Bind YalihanCortex for direct resolution (IlanService etc.)
        $this->app->singleton(\App\Services\AI\YalihanCortex::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

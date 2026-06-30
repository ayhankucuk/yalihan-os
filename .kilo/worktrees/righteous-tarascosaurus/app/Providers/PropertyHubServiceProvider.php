<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\PropertyHub\Resolution\Engine\TemplateResolutionEngine;
use App\Domain\PropertyHub\Rules\Registry\GovernedRuleRegistry;
use App\Domain\PropertyHub\Resiliency\CircuitBreaker;
use App\Modules\GovernanceCore\Core\EngineOrchestrator;
use App\Services\Template\TemplateService;
use Illuminate\Support\ServiceProvider;

class PropertyHubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 1. Registry
        $this->app->singleton(\App\Domain\PropertyHub\Rules\Registry\GovernedRuleRegistry::class);
        $this->app->bind(\App\Domain\PropertyHub\Rules\Contracts\RuleRegistryInterface::class, \App\Domain\PropertyHub\Rules\Registry\GovernedRuleRegistry::class);

        // 2. V3 Engine
        $this->app->singleton('propertyhub.engine.v3', function ($app) {
            return new TemplateResolutionEngine(
                $app->make(\App\Domain\PropertyHub\Rules\Contracts\RuleRegistryInterface::class),
                $app->make(\App\Domain\PropertyHub\Rules\Evaluators\ConditionEvaluator::class)
            );
        });

        $this->app->singleton(CircuitBreaker::class, function ($app) {
            $config = $app['config']['propertyhub.circuit_breaker'] ?? [];
            return new CircuitBreaker(
                errorThreshold: (float) ($config['error_threshold'] ?? 0.05),
                windowSeconds: (int) ($config['window_seconds'] ?? 300),
                bucketSize: (int) ($config['bucket_size'] ?? 60)
            );
        });

        $this->app->singleton(\App\Domain\PropertyHub\Chaos\ChaosSimulationService::class);
        $this->app->singleton(\App\Domain\PropertyHub\Chaos\ChaosModeService::class);

        $this->app->singleton(\App\Domain\PropertyHub\Resiliency\RegistryBypassDetector::class);

        // 4. Orchestrator
        $this->app->singleton(EngineOrchestrator::class, function ($app) {
            return new EngineOrchestrator(
                v3: $app->make('propertyhub.engine.v3'),
                circuitBreaker: $app->make(CircuitBreaker::class)
            );
        });
    }

    public function boot(): void
    {
        // 🚨 ZERO-TRUST: Start Registry Bypass Monitoring
        if (config('propertyhub.strict_governance')) {
            app(\App\Domain\PropertyHub\Resiliency\RegistryBypassDetector::class);
        }
    }
}

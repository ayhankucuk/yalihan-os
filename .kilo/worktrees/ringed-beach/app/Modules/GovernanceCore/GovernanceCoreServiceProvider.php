<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore;

use App\Modules\GovernanceCore\Contracts\GovernanceEngineInterface;
use App\Modules\GovernanceCore\Core\ActivationLockService;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use App\Modules\GovernanceCore\Core\DriftDetectionService;
use App\Modules\GovernanceCore\Core\GovernanceRiskScorer;
use App\Modules\GovernanceCore\Core\VersionActivationService;
use App\Modules\GovernanceCore\Core\VersionRollbackService;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Modules\GovernanceCore\Intelligence\AdaptiveRiskThresholdManager;
use App\Modules\GovernanceCore\Intelligence\DraftImpactSimulator;
use App\Modules\GovernanceCore\Intelligence\GovernanceIntelligenceService;
use App\Modules\GovernanceCore\Intelligence\PredictiveDriftAnalyzer;
use App\Modules\GovernanceCore\Services\AutoContainmentPolicy;
use App\Modules\GovernanceCore\Services\AutonomousDriftResponder;
use App\Modules\GovernanceCore\Services\RuleSetDiffService;
use Illuminate\Support\ServiceProvider;

/**
 * GovernanceCoreServiceProvider
 *
 * Registers all GovernanceCore module classes into the Laravel DI container.
 *
 * Fix #57: GovernanceCore was previously unregistered — 16 classes were
 * invisible to Laravel's container. This provider resolves that.
 *
 * Registration: config/app.php → providers array
 * (added 2026-05-15)
 */
class GovernanceCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ─── Core Layer ─────────────────────────────────────────────────
        $this->app->singleton(ConfigSnapshotService::class);
        $this->app->singleton(ActivationLockService::class);
        $this->app->singleton(VersionStateMachine::class);
        $this->app->singleton(GovernanceRiskScorer::class);
        $this->app->singleton(DriftDetectionService::class);
        $this->app->singleton(VersionActivationService::class);
        $this->app->singleton(VersionRollbackService::class);

        // ─── Intelligence Layer ──────────────────────────────────────────
        $this->app->singleton(AdaptiveRiskThresholdManager::class);
        $this->app->singleton(DraftImpactSimulator::class);
        $this->app->singleton(GovernanceIntelligenceService::class);
        $this->app->singleton(PredictiveDriftAnalyzer::class);

        // ─── Services Layer ──────────────────────────────────────────────
        $this->app->singleton(AutoContainmentPolicy::class);
        $this->app->singleton(AutonomousDriftResponder::class);
        $this->app->singleton(RuleSetDiffService::class);

        // ─── Primary Contract Binding ────────────────────────────────────
        // GovernanceEngineInterface → GovernanceEngine (concrete implementation)
        // GovernanceApiController ve diğer tüketiciler bu binding ile çözülür.
        $this->app->bind(
            GovernanceEngineInterface::class,
            GovernanceEngine::class
        );
    }

    public function boot(): void
    {
        // No boot-time side effects — governance is event-driven
    }
}

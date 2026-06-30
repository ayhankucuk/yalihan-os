<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Chaos;

use App\Domain\PropertyHub\Observability\GovernanceIncidentService;
use Illuminate\Support\Facades\Log;

/**
 * Chaos Simulation Service
 *
 * Provides a framework for failure injection into the Governance Engine.
 * Used for Zero-Trust stress testing and resilience verification.
 */
class ChaosSimulationService
{
    public const TYPE_REDIS_FAILURE = 'redis_failure';
    public const TYPE_CACHE_CORRUPTION = 'cache_corruption';
    public const TYPE_CONCURRENT_ACTIVATION = 'concurrent_activation';
    public const TYPE_SIGNATURE_TAMPER = 'signature_tamper';
    public const TYPE_DB_DEADLOCK = 'db_deadlock';

    private array $activeScenarios = [];

    public function __construct(
        private readonly GovernanceIncidentService $incidentService
    ) {}

    /**
     * Inject a chaos scenario.
     */
    public function inject(string $type): void
    {
        $this->activeScenarios[$type] = true;

        $this->incidentService->record(
            type: 'chaos_injection',
            source: 'ChaosSimulationService',
            risk: 'MEDIUM',
            details: ['scenario' => $type]
        );

        Log::channel('governance_security')->warning("CHAOS INJECTED: [{$type}]. Prepare for failure.");
    }

    /**
     * Alias for inject() - compatibility with legacy tests.
     */
    public function simulate(string $type): void
    {
        $this->inject($type);
    }

    /**
     * Alias for inject() - compatibility with legacy tests.
     */
    public function set(string $type): void
    {
        $this->inject($type);
    }

    /**
     * Check if a chaos scenario is active.
     */
    public function isActive(string $type): bool
    {
        return isset($this->activeScenarios[$type]) && $this->activeScenarios[$type] === true;
    }

    /**
     * Clear all active chaos scenarios.
     */
    public function clear(): void
    {
        $this->activeScenarios = [];
    }

    /**
     * Trigger a deterministic failure for the active scenario.
     *
     * @throws \RuntimeException
     */
    public function trigger(string $type, string $message = "Chaos Induced Failure"): void
    {
        if ($this->isActive($type)) {
            Log::channel('governance_security')->emergency("CHAOS TRIGGERED: [{$type}] - {$message}");
            throw new \RuntimeException($message);
        }
    }
}

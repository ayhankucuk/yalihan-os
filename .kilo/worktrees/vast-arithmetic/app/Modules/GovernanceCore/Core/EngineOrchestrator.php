<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;

use App\Domain\PropertyHub\Resolution\Contracts\TemplateResolutionEngineInterface;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionResult;
use App\Domain\PropertyHub\Resiliency\CircuitBreaker;

class EngineOrchestrator
{
    public function __construct(
        private readonly TemplateResolutionEngineInterface $v3,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    public function resolve(ResolutionContext $context): ResolutionResult
    {
        // 0. Tenant Context
        // In Sprint 18, we enforce tenant isolation at the Orchestrator level.
        $tenantId = config('app.tenant_id', 'SYSTEM');

        // 1. Hard Lock Check (Auto-Containment)
        // If the tenant is compromised (e.g. via GovTenantFreeze or IncidentService), we reject all processing.
        if (\Illuminate\Support\Facades\Cache::has("governance.compromised.{$tenantId}")) {
            throw new \App\Exceptions\CriticalGovernanceException("TENANT LOCKED: Isolation Breach or Security Incident for [{$tenantId}].");
        }

        // 2. Circuit Breaker Check
        // In the new V3-only world, if the circuit is open, we fail fast or return a safe default.
        // For now, we respect the breaker's availability check.
        if (!$this->circuitBreaker->isAvailable($tenantId)) {
             // Fallback logic or Exception.
             // Since we deleted V2, we cannot fall back to it.
             // We throw an exception to be handled by the global handler or upper layers,
             // or returns a "Safe Mode" result if implemented.
             // For strict governance, failing fast is safer than unknown states.
             throw new \RuntimeException("PropertyHub Engine is unavailable (Circuit Open) for [{$tenantId}].");
        }

        // 3. Direct V3 Resolution
        // No more Shadow, No more Rescue, No more V2.
        try {
            $result = $this->v3->resolve($context);
            $this->circuitBreaker->report(true, $tenantId);
            return $result;
        } catch (\Throwable $e) {
            $this->circuitBreaker->report(false, $tenantId);
            $this->circuitBreaker->check($tenantId);
            throw $e;
        }
    }
}

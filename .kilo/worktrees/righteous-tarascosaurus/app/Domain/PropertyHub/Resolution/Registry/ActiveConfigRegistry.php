<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\Registry;

use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use App\Modules\GovernanceCore\Core\ConfigSnapshotService;

/**
 * Active Configuration Registry
 *
 * Single Source of Truth for the currently ACTIVE configuration version.
 * Provides memory-mapped access to the versioned snapshot (Rules, Templates, Assignments).
 */
class ActiveConfigRegistry
{
    private ?PropertyConfigVersion $activeVersion = null;
    private ?array $snapshot = null;

    public function __construct(
        private readonly \App\Domain\PropertyHub\Observability\GovernanceIncidentService $incidentService,
        private readonly \App\Domain\PropertyHub\Chaos\ChaosSimulationService $chaosSimulation
    ) {}

    /**
     * Get the current active version with Hard Lock protection.
     * ✅ O(1) Local Cache
     * ✅ O(N) Signature Verification (Background/Periodic)
     */
    /**
     * Get the current active version with Hard Lock protection.
     * ✅ O(1) Local Cache
     * ✅ O(N) Signature Verification (Background/Periodic)
     * ✅ Tenant Scoped
     */
    public function getActiveVersion(): PropertyConfigVersion
    {
        // 🚨 SPRINT 13: Chaos Injection - Redis Outage Simulation
        if ($this->chaosSimulation->isActive($this->chaosSimulation::TYPE_REDIS_FAILURE)) {
            Log::channel('governance_security')->warning("CHAOS: Redis Outage Simulated. Falling back to SAFE_LOCK mode.");
            return $this->resolveFromSafeLock();
        }

        $tenantId = $this->getTenantId();
        $cacheKey = "gov_v2:{$tenantId}:active_version";

        $versionToRecord = null;
        try {
            $this->activeVersion = Cache::remember($cacheKey, 3600, function () use ($tenantId, &$versionToRecord) {
                // Scope query by tenant
                $version = PropertyConfigVersion::activeForTenant($tenantId)->first();

                if (!$version) {
                    throw new \App\Exceptions\CriticalGovernanceException("Yalıhan Governance Error: No active configuration found for tenant [{$tenantId}].");
                }

                $versionToRecord = $version;
                return $version;
            });

            // 🚨 ZERO-TRUST: Re-verify integrity on EVERY access
            $this->verifyIntegrity($this->activeVersion);

            $this->snapshot = $this->activeVersion->snapshot_json;
            return $this->activeVersion;
        } catch (\App\Exceptions\CriticalGovernanceException $e) {
            // ✅ D3: Signature Alerts - Record Incident with actual version context
            $this->incidentService->recordTamper($versionToRecord ?? new PropertyConfigVersion(), $e->getMessage());

            $this->tripLockdown($e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            // 🚨 SPRINT 13: D5 - Graceful Degradation on Infrastructure Failure
            Log::error("Governance Infrastructure Failure: {$e->getMessage()}. Triggering SAFE_LOCK.");
            return $this->resolveFromSafeLock();
        }
    }

    /**
     * Get features from the active snapshot.
     */
    public function getGovernedFeatures(): array
    {
        $snapshot = $this->getSnapshot();
        return $snapshot['features'] ?? [];
    }

    private function getTenantId(): string
    {
        // Priority: Config (App) > Config (AI Default) > Fallback SYSTEM
        return config('app.tenant_id', config('ai.defaults.tenant_id', 'SYSTEM'));
    }

    /**
     * SPRINT 13: D5 - Safe Lock Fallback
     * Directly from DB, bypassing cache, with strict signature verification.
     */
    private function resolveFromSafeLock(): PropertyConfigVersion
    {
        $tenantId = $this->getTenantId();
        $version = PropertyConfigVersion::activeForTenant($tenantId)->first();

        if (!$version) {
            throw new \App\Exceptions\CriticalGovernanceException("CRITICAL: SAFE_LOCK failure. No active configuration available for tenant [{$tenantId}].");
        }

        $this->verifyIntegrity($version);
        $this->activeVersion = $version;
        $this->snapshot = $this->activeVersion->snapshot_json;
        return $version;
    }

    private ?bool $memoizedCompromised = null;

    /**
     * Check if the system is in HARD LOCKDOWN mode due to integrity breach.
     */
    public function isSystemCompromised(): bool
    {
        if ($this->memoizedCompromised === null) {
            $this->memoizedCompromised = Cache::has('governance.system_compromised');
        }
        return $this->memoizedCompromised;
    }

    public function reset(): void
    {
        $this->activeVersion = null;
        $this->snapshot = null;
        $this->memoizedCompromised = null;

        try {
            if (app()->bound('cache')) {
                Cache::forget('governance.active_version');
                Cache::forget('governance.system_compromised');
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }

    /**
     * Trip the system-wide lockdown.
     */
    public function tripLockdown(string $reason = 'Manual Lockdown'): void
    {
        Log::channel('governance_security')->critical("TRIP LOCKDOWN: {$reason}");
        $this->incidentService->triggerHardLock($this->activeVersion?->tenant_id ?? 'SYSTEM', $reason);
        $this->memoizedCompromised = true;
    }

    /**
     * Verify the integrity of a configuration version.
     *
     * @throws \App\Exceptions\CriticalGovernanceException
     */
    public function verifyIntegrity(PropertyConfigVersion $version): void
    {
        if ($this->isSystemCompromised()) {
            throw new \App\Exceptions\CriticalGovernanceException("CONTEXT7 HARD LOCK: System is compromised. All configuration operations are disabled.");
        }

        // ✅ SAB: Strict Integrity Check (@rules 5)
        $expectedSignature = ConfigSnapshotService::computeSignature($version->snapshot_json);

        // 🚨 SPRINT 13: Chaos Injection - Signature Tampering
        if ($this->chaosSimulation->isActive($this->chaosSimulation::TYPE_SIGNATURE_TAMPER)) {
            $expectedSignature = "TAMPERED_HASH_" . bin2hex(random_bytes(8));
        }

        if (!hash_equals($version->signature ?? '', $expectedSignature)) {
            Log::channel('governance_security')->error("SIGNATURE MISMATCH: Received: {$version->signature}, Expected: {$expectedSignature}");
            // CRITICAL: Tamper detection
            throw new \App\Exceptions\CriticalGovernanceException(
                "CONTEXT7 SECURITY ALERT: Signature mismatch! Possible data tampering detected. Received: {$version->signature}, Expected: {$expectedSignature}"
            );
        }
    }

    /**
     * Get the snapshot data for the active version.
     */
    public function getSnapshot(): ?array
    {
        $this->getActiveVersion();
        return $this->snapshot;
    }

    /**
     * Get templates from the active snapshot.
     */
    public function getGovernedTemplates(): array
    {
        $snapshot = $this->getSnapshot();
        return $snapshot['templates'] ?? [];
    }

    /**
     * Get assignments from the active snapshot.
     */
    public function getGovernedAssignments(): array
    {
        $snapshot = $this->getSnapshot();
        return $snapshot['assignments'] ?? [];
    }

    /**
     * Get rules from the active snapshot.
     */
    public function getGovernedRules(): array
    {
        $snapshot = $this->getSnapshot();
        return $snapshot['rules'] ?? [];
    }

    public function clear(): void
    {
        $this->reset();
    }

    public static function clearStaticState(): void
    {
        try {
            $app = \Illuminate\Container\Container::getInstance();
            if ($app && $app->bound('cache')) {
                $app->make('cache')->forget('governance.active_version');
                $app->make('cache')->forget('governance.system_compromised');
                $app->make('cache')->forget('governance.last_audit');
            }
        } catch (\Throwable $e) {
            // Ignore invalid container state
        }
    }
}

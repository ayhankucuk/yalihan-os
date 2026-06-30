<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\Registry;

use App\Models\PropertyConfigVersion;
use App\Services\PropertyHub\ConfigSnapshotService;
use App\Exceptions\CriticalGovernanceException;
use App\Domain\PropertyHub\Chaos\ChaosSimulationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class TenantConfigRegistry
 *
 * Manages configuration resolution per tenant.
 * ✅ SAB: Multi-tenant isolated.
 */
class TenantConfigRegistry
{
    private array $activeVersions = []; // Local cache for Model instances
    private array $snapshotCache = []; // Memory-mapped cache for raw snapshots

    public function __construct(
        private readonly ChaosSimulationService $chaosSimulation,
        private readonly GovernanceCacheAdapter $cacheAdapter
    ) {}

    /**
     * Resolve the active version for a specific tenant.
     * ✅ Phase 16.1: Optimized resolution pathway.
     */
    public function resolve(string $tenantId): PropertyConfigVersion
    {
        $start = hrtime(true);

        // 1. Request-level Memory Mapping (L1)
        if (isset($this->activeVersions[$tenantId])) {
            $this->cacheAdapter->recordL1Hit($tenantId);
            return $this->activeVersions[$tenantId];
        }

        $this->cacheAdapter->recordL1Miss($tenantId);

        // 2. Redis Persistent Cache (L2)
        // We need the version_hash and signature to verify the cache.
        // For performance, we fetch only the metadata from DB first or assume latest.
        // Actually, to avoid a DB call just to check cache, we can store the 'current_active_hash' in a separate lean cache.
        $version = $this->resolveFromPersistentCache($tenantId);

        if (!$version) {
            // 3. Database Fallback (L3)
            $version = PropertyConfigVersion::activeForTenant($tenantId)
                ->latest('applied_at')
                ->first();

            if (!$version) {
                throw new CriticalGovernanceException("GOVERNANCE ERROR: No active configuration found for tenant [{$tenantId}].");
            }

            // Immediately verify and cache
            $this->verifyIntegrity($version);
            $this->cacheAdapter->put($tenantId, $version->version_hash, $version->snapshot_json, $version->signature);
        }

        // Finalize Request Cache
        $this->snapshotCache[$tenantId] = $version->snapshot_json;
        $this->activeVersions[$tenantId] = $version;

        $end = hrtime(true);
        $latencyMs = ($end - $start) / 1e6;

        if ($latencyMs > 50) {
            Log::channel('governance_performance')->warning("SLOW RESOLUTION [Tenant: {$tenantId}]: {$latencyMs}ms");
        }

        return $version;
    }

    /**
     * Attempt to resolve the active version from the Redis cache.
     */
    private function resolveFromPersistentCache(string $tenantId): ?PropertyConfigVersion
    {
        $activeMetadata = Cache::get("gov_v2:active_meta:{$tenantId}");

        if (!$activeMetadata) {
            return null;
        }

        $snapshot = $this->cacheAdapter->get(
            $tenantId,
            $activeMetadata['hash'],
            $activeMetadata['signature']
        );

        if (!$snapshot) {
            return null;
        }

        // Reconstruct model without DB hit
        $version = new PropertyConfigVersion();
        $version->setRawAttributes([
            'tenant_id' => $tenantId,
            'version_hash' => $activeMetadata['hash'],
            'signature' => $activeMetadata['signature'],
            'snapshot_json' => $snapshot,
            'yonetim_durumu' => 'AKTIF',
        ]);
        $version->exists = true;

        return $version;
    }

    /**
     * Get the memory-mapped snapshot directly.
     * ZERO overhead.
     */
    public function getActiveSnapshot(string $tenantId): array
    {
        if (!isset($this->snapshotCache[$tenantId])) {
            $this->resolve($tenantId);
        }

        return $this->snapshotCache[$tenantId];
    }

    /**
     * Verify the integrity of a tenant-specific version.
     */
    public function verifyIntegrity(PropertyConfigVersion $version): void
    {
        if ($this->isSystemCompromised($version->tenant_id)) {
            throw new CriticalGovernanceException("CONTEXT7 HARD LOCK: System is compromised for tenant [{$version->tenant_id}].");
        }

        // Performance: Use static computation to avoid object instantiation overhead
        $expectedSignature = ConfigSnapshotService::computeSignature($version->snapshot_json);

        // Chaos Injection for testing
        if ($this->chaosSimulation->isActive($this->chaosSimulation::TYPE_SIGNATURE_TAMPER)) {
            $expectedSignature = "TAMPERED_HASH_" . bin2hex(random_bytes(8));
        }

        if (!hash_equals($version->signature ?? '', $expectedSignature)) {
            Log::channel('governance_security')->error("SIGNATURE MISMATCH [Tenant: {$version->tenant_id}]: Received: {$version->signature}, Expected: {$expectedSignature}");
            throw new CriticalGovernanceException(
                "CONTEXT7 SECURITY ALERT: Signature mismatch! Possible data tampering detected for tenant [{$version->tenant_id}]."
            );
        }
    }

    public function isSystemCompromised(string $tenantId): bool
    {
        return Cache::get("governance.compromised.{$tenantId}", false);
    }

    public function triggerHardLock(string $tenantId, string $reason): void
    {
        Cache::forever("governance.compromised.{$tenantId}", true);
        Log::channel('governance_security')->emergency("TENANT COMPROMISED: Hard Lock Initiated for [{$tenantId}]. Reason: {$reason}");
    }
}

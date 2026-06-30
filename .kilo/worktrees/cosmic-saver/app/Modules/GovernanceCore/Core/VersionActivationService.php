<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;

use App\Domain\PropertyHub\Resiliency\CircuitBreaker;
use App\Domain\PropertyHub\Chaos\ChaosModeService;
use App\Models\PropertyConfigVersion;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service to handle atomic version activation with safety guards.
 */
class VersionActivationService
{
    public function __construct(
        private readonly \App\Domain\PropertyHub\Resolution\Registry\TenantConfigRegistry $tenantRegistry,
        private readonly ActiveConfigRegistry $registry,
        private readonly CircuitBreaker $circuitBreaker,
        private readonly \App\Domain\PropertyHub\Chaos\ChaosSimulationService $chaosSimulation,
        private readonly \App\Modules\GovernanceCore\Core\ActivationLockService $lockService,
        private readonly VersionStateMachine $stateMachine,
    ) {}

    /**
     * Activate a new configuration version atomically.
     */
    public function activate(PropertyConfigVersion $version, int $actorId): void
    {
        // 🚨 SPRINT 15: Cross-Tenant Isolation Firewall
        // (Handled by assertTransition + TenantRegistry)

        // 1. Validate State Transition & Predictive Policy
        $this->stateMachine->assertTransition($version, VersionStateMachine::DURUM_AKTIF);

        // ✅ ZERO-TRUST: Strict Sequentual Activation
        $this->lockService->executeLocked(function () use ($version, $actorId) {
            // 🚨 PHASE 16.3: Force primary connection for atomic state change
            DB::connection('mysql')->transaction(function () use ($version, $actorId) {
                // ✅ MODEL BRIDGE: Delegate column resolution to Model
                $stateColumn = PropertyConfigVersion::resolveYonetimDurumuKolonu();

                // 2. Deactivate current ACTIVE
                PropertyConfigVersion::where('tenant_id', $version->tenant_id)
                    ->where($stateColumn, VersionStateMachine::DURUM_AKTIF)
                    ->update([
                        $stateColumn => VersionStateMachine::DURUM_ARSIVLENDI,
                        'updated_at' => now(),
                    ]);

                // 3. Activate new version (✅ uses canonical field via mutator)
                $version->update([
                    'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
                    'applied_at' => now(),
                ]);

                // 🚨 PHASE 16.2: Cache Warming (Bust old, seed new)
                Cache::put("gov_v2:active_meta:{$version->tenant_id}", [
                    'hash' => $version->version_hash,
                    'signature' => $version->signature,
                    'activated_at' => now()->toIso8601String(),
                ], now()->addDay());

                // Seed the persistent snapshot cache
                resolve(\App\Domain\PropertyHub\Resolution\Registry\GovernanceCacheAdapter::class)
                    ->put($version->tenant_id, $version->version_hash, $version->snapshot_json, $version->signature);
            });

            // 🚨 SPRINT 13: Chaos Injection
            $this->chaosSimulation->trigger($this->chaosSimulation::TYPE_CONCURRENT_ACTIVATION, "Concurrent activation simulated failure.");

            // 4. Audit Trail (@rules 3)
            $this->logAudit($version->id, 'activated', $actorId);

            // 5. Reset Circuit Breaker (@rules 3)
            $this->circuitBreaker->reset();

            Log::info("PropertyHub version {$version->version_hash} activated by user {$actorId}");
        });
    }

    /**
     * Log audit trail.
     */
    public function logAudit(int $versionId, string $action, int $actorId, array $metadata = []): void
    {
        DB::table('property_config_audit_logs')->insert([
            'version_id' => $versionId,
            'islem_tipi' => $action,
            'islem_yapan_id' => $actorId,
            'ek_bilgiler' => json_encode($metadata),
            'olusturma_tarihi' => now(),
        ]);
    }
}

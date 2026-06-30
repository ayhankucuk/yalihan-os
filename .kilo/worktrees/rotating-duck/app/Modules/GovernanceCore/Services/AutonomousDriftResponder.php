<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Services;

use App\Models\PropertyConfigVersion;
use App\Domain\PropertyHub\Observability\GovernanceIncidentService;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Modules\GovernanceCore\Core\GovernanceRiskScorer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Class AutonomousDriftResponder
 *
 * Automatically responds to drift based on risk-adaptive policies.
 * ✅ SAB: Deterministic, no direct ACTIVE mutation.
 */
class AutonomousDriftResponder
{
    public function __construct(
        private readonly GovernanceIncidentService $incidentService,
        private readonly GovernanceRiskScorer $riskScorer
    ) {}

    /**
     * Respond to detected drift incidents.
     */
    public function respond(PropertyConfigVersion $activeVersion, array $driftResults): void
    {
        if (empty($driftResults['drifts']) && empty($driftResults['shadow_missing']) && empty($driftResults['ungoverned'])) {
            return;
        }

        $tenantId = $activeVersion->tenant_id;

        // 1. Calculate Risk of the Drift
        // Drift is essentially a mutation of what *should* be.
        // We evaluate what the risk would be if we "accepted" this drift as the new reality.
        $driftRisk = $this->evaluateDriftRisk($driftResults);

        Log::channel('governance_events')->info("AUTONOMOUS RESPONDER [Tenant: {$tenantId}]: Drift detected. Risk Level: {$driftRisk}");

        // 2. Execute Policy Based on Risk
        match ($driftRisk) {
            'CRITICAL' => $this->handleCriticalDrift($tenantId, $driftResults),
            'HIGH', 'MEDIUM' => $this->handleModerateDrift($tenantId, $driftResults),
            'LOW' => $this->handleLowRiskDrift($activeVersion, $driftResults),
            default => $this->handleUnknownDrift($tenantId),
        };
    }

    /**
     * Evaluate the risk level of detected drifts.
     */
    private function evaluateDriftRisk(array $driftResults): string
    {
        // Simple logic: Any ungoverned items are HIGH risk.
        // Shadow missing is MEDIUM.
        // Value drifts are evaluated by the RiskScorer.
        if (!empty($driftResults['ungoverned'])) {
            return 'CRITICAL';
        }

        if (!empty($driftResults['shadow_missing'])) {
            return 'HIGH';
        }

        // For value drifts, check if any affect critical attributes
        foreach ($driftResults['drifts'] as $drift) {
            // If ad or display_order changes, it's moderate.
            // If aktiflik_durumu changes, it's high.
            if (($drift['actual']['aktiflik_durumu'] ?? null) !== ($drift['expected']['aktiflik_durumu'] ?? null)) {
                return 'HIGH';
            }
        }

        return 'LOW';
    }

    /**
     * CRITICAL: Hard lock the system.
     */
    private function handleCriticalDrift(string $tenantId, array $details): void
    {
        Cache::put("governance.compromised.{$tenantId}", true, now()->addHours(6));

        $this->incidentService->record(
            'AUTONOMOUS_LOCK',
            'DriftDetection',
            'CRITICAL',
            $tenantId,
            null,
            null,
            ['reason' => 'Ungoverned database mutation detected.', 'drift' => $details]
        );
    }

    /**
     * MEDIUM/HIGH: Escalation to review.
     */
    private function handleModerateDrift(string $tenantId, array $details): void
    {
        $this->incidentService->record(
            'AUTONOMOUS_ESCALATION',
            'DriftDetection',
            'HIGH',
            $tenantId,
            null,
            null,
            ['reason' => 'Significant drift detected. Manual review required.', 'drift' => $details]
        );
    }

    /**
     * LOW: Create an auto-correction draft.
     */
    private function handleLowRiskDrift(PropertyConfigVersion $activeVersion, array $driftResults): void
    {
        // Self-Healing: Create a draft that would "fix" the DB to match the snapshot
        // OR a draft that "accepts" the minor change if it's considered safe.
        // Rule: Never mutate ACTIVE. Create DRAFT for review.

        $correctedSnapshot = $activeVersion->snapshot_json;

        // Note: For now, we "suggest" a fix by creating a draft version
        // that is identical to the active one but tagged as an auto-correction.
        $draftSignature = \App\Services\PropertyHub\ConfigSnapshotService::computeSignature($correctedSnapshot);
        $driftParmakIzi = hash('sha256', json_encode([
            'tenant_id' => $activeVersion->tenant_id,
            'aktif_hash' => $activeVersion->version_hash,
            'aktif_imza' => $activeVersion->signature,
            'drifts' => $driftResults['drifts'] ?? [],
            'shadow_missing' => $driftResults['shadow_missing'] ?? [],
            'ungoverned' => $driftResults['ungoverned'] ?? [],
            'draft_signature' => $draftSignature,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        $deterministikDraftHash = 'AUTO_FIX_' . strtoupper(substr($driftParmakIzi, 0, 16));

        $durumKolonu = Schema::hasColumn('property_config_versions', 'yonetim_durumu')
            ? 'yonetim_durumu'
            : 'governance_state';

        $zatenVar = PropertyConfigVersion::query()
            ->where('tenant_id', $activeVersion->tenant_id)
            ->where('version_hash', $deterministikDraftHash)
            ->where($durumKolonu, VersionStateMachine::DURUM_TASLAK)
            ->exists();

        if (!$zatenVar) {
            PropertyConfigVersion::create([
                'tenant_id' => $activeVersion->tenant_id,
                'version_hash' => $deterministikDraftHash,
                'yonetim_durumu' => VersionStateMachine::DURUM_TASLAK,
                'snapshot_json' => $correctedSnapshot,
                'description' => 'AUTONOMOUS FIX: Draft generated to resolve low-risk value drift.',
                'signature' => $draftSignature,
            ]);
        }

        $this->incidentService->record(
            'AUTONOMOUS_FIX_PROPOSED',
            'DriftDetection',
            'LOW',
            $activeVersion->tenant_id,
            $activeVersion->id,
            null,
            ['reason' => 'Minor drift detected. Auto-correction draft created.']
        );
    }

    private function handleUnknownDrift(string $tenantId): void
    {
        Log::warning("AUTONOMOUS RESPONDER [Tenant: {$tenantId}]: Unknown drift risk pattern.");
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resiliency;

use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Intelligence\DraftImpactSimulator;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Domain\PropertyHub\Observability\GovernanceIncidentService;
use App\Domain\PropertyHub\Observability\GovernanceTimelineService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Class HealthAutoRecoveryService
 *
 * Monitors health trends and suggests recovery candidates.
 * ✅ SAB: Safe, non-mutating recovery proposals.
 */
class HealthAutoRecoveryService
{
    private const CRITICAL_HEALTH_THRESHOLD = 70;
    private const DEGRADATION_TREND_COUNT = 3;

    public function __construct(
        private readonly DraftImpactSimulator $impactSimulator,
        private readonly GovernanceTimelineService $timelineService,
        private readonly GovernanceIncidentService $incidentService
    ) {}

    /**
     * Analyze health history and propose recovery if degradation is detected.
     */
    public function monitorAndProposeRecovery(string $tenantId): ?PropertyConfigVersion
    {
        // 1. Get recent lineage health scores
        $lineage = $this->timelineService->getLineage($tenantId);
        $nodes = $lineage['nodes'] ?? [];
        $healthScores = collect($nodes)->pluck('risk_score')->toArray();

        if (count($healthScores) < self::DEGRADATION_TREND_COUNT) {
            return null;
        }

        // 2. Detect Degradation Trend (Example: Increasing Risk/Decreasing Quality)
        if ($this->isDegrading($healthScores)) {
            Log::channel('governance_events')->warning("HEALTH MONITOR [Tenant: {$tenantId}]: Degradation trend detected.");

            return $this->proposeRecoveryCandidate($tenantId);
        }

        return null;
    }

    /**
     * Check if the last X scores show a negative trend.
     */
    private function isDegrading(array $scores): bool
    {
        $recent = array_slice($scores, 0, self::DEGRADATION_TREND_COUNT);

        // If risk scores are increasing (bad)
        $isWorsening = true;
        for ($i = 0; $i < count($recent) - 1; $i++) {
            if ($recent[$i] <= $recent[$i + 1]) {
                $isWorsening = false;
                break;
            }
        }

        return $isWorsening;
    }

    /**
     * Find the last "Green" version and propose it as a correction draft.
     */
    private function proposeRecoveryCandidate(string $tenantId): ?PropertyConfigVersion
    {
        $durumKolonu = Schema::hasColumn('property_config_versions', 'yonetim_durumu')
            ? 'yonetim_durumu'
            : 'governance_state';

        // Find a version with Risk Score < 30 (Safe) in history
        $safeCandidate = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where($durumKolonu, VersionStateMachine::DURUM_ARSIVLENDI)
            ->where('risk_score', '<', 30)
            ->latest('applied_at')
            ->first();

        if ($safeCandidate) {
            $parmakIzi = hash('sha256', json_encode([
                'tenant_id' => $tenantId,
                'base_version' => $safeCandidate->version_hash,
                'snapshot' => $safeCandidate->snapshot_json,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

            $proposalHash = 'RECOVERY_PROPOSAL_' . strtoupper(substr($parmakIzi, 0, 12));

            $existingDraft = PropertyConfigVersion::query()
                ->where('tenant_id', $tenantId)
                ->where('version_hash', $proposalHash)
                ->where($durumKolonu, VersionStateMachine::DURUM_TASLAK)
                ->first();

            if ($existingDraft) {
                return $existingDraft;
            }

            // Propose as DRAFT fix
            $proposal = PropertyConfigVersion::create([
                'tenant_id' => $tenantId,
                'version_hash' => $proposalHash,
                'yonetim_durumu' => VersionStateMachine::DURUM_TASLAK,
                'snapshot_json' => $safeCandidate->snapshot_json,
                'description' => "SELF-HEALING PROPOSAL: Based on version [{$safeCandidate->version_hash}] to arrest health degradation trend.",
                'signature' => \App\Services\PropertyHub\ConfigSnapshotService::computeSignature($safeCandidate->snapshot_json),
            ]);

            $this->incidentService->record(
                'AUTO_RECOVERY_PROPOSAL',
                'HealthMonitor',
                'MEDIUM',
                $tenantId,
                null,
                null,
                ['proposal_id' => $proposal->id, 'base_version' => $safeCandidate->version_hash]
            );

            return $proposal;
        }

        return null;
    }
}

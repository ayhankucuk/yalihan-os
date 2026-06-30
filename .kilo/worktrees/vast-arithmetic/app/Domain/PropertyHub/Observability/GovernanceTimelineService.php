<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Observability;

use App\Models\PropertyConfigVersion;
use App\Models\GovernanceIncident;
use Illuminate\Support\Collection;

/**
 * Class GovernanceTimelineService
 *
 * Provides visual lineage and drift overlay data for the governance engine.
 * ✅ SAB: Deterministic graph generation.
 */
class GovernanceTimelineService
{
    /**
     * Get the full governance lineage for a specific tenant.
     */
    public function getLineage(string $tenantId = 'SYSTEM', ?int $limit = 10): array
    {
        $versions = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $nodes = $versions->map(function ($version) {
            return [
                'id' => $version->id,
                'hash' => $version->version_hash,
                'state' => $version->yonetim_durumu,
                'risk_score' => $version->risk_score,
                'created_at' => $version->created_at->toIso8601String(),
                'aktiflik_durumu' => $version->yonetim_durumu === \App\Modules\GovernanceCore\Core\VersionStateMachine::DURUM_AKTIF,
                'has_drift' => $this->hasDriftIncidents($version),
            ];
        });

        $edges = [];
        foreach ($versions as $index => $version) {
            if ($version->parent_version_hash) {
                $edges[] = [
                    'from' => $version->parent_version_hash,
                    'to' => $version->version_hash,
                    'type' => 'evolution'
                ];
            }
        }

        return [
            'nodes' => $nodes->values()->toArray(),
            'edges' => $edges,
            'summary' => [
                'total_versions' => PropertyConfigVersion::count(),
                'active_hash' => $versions->firstWhere('yonetim_durumu', 'AKTIF')?->version_hash,
            ]
        ];
    }

    private function hasDriftIncidents(PropertyConfigVersion $version): bool
    {
        return GovernanceIncident::where('snapshot_id', $version->id)
            ->whereIn('olay_tipi', ['drift_detected', 'drift_storm', 'signature_mismatch'])
            ->exists();
    }
}

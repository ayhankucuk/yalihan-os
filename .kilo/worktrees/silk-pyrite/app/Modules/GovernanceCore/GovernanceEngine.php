<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore;

use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Contracts\GovernanceEngineInterface;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use App\Modules\GovernanceCore\Core\DriftDetectionService;
use App\Modules\GovernanceCore\Core\GovernanceRiskScorer;
use App\Modules\GovernanceCore\Intelligence\DraftImpactSimulator;
use App\Modules\GovernanceCore\Intelligence\PredictiveDriftAnalyzer;
use Illuminate\Support\Facades\Log;

/**
 * GovernanceEngine — Concrete Implementation of GovernanceEngineInterface
 *
 * Orchestrates all GovernanceCore services behind a single public API.
 * Each method is tenant-first and deterministic.
 *
 * Fix #57: This class provides the missing binding for GovernanceEngineInterface.
 * (2026-05-15)
 *
 * Sprint plan: Methods marked TODO are post-launch scope.
 */
final class GovernanceEngine implements GovernanceEngineInterface
{
    use ValidatesGovernanceChain;
    public function __construct(
        private readonly ConfigSnapshotService   $snapshotService,
        private readonly DriftDetectionService   $driftService,
        private readonly GovernanceRiskScorer    $riskScorer,
        private readonly DraftImpactSimulator    $impactSimulator,
        private readonly PredictiveDriftAnalyzer $predictiveDriftAnalyzer,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getActiveVersion(string $tenantId): array
    {
        $version = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where('yonetim_durumu', 'AKTIF')
            ->latest()
            ->first();

        if (!$version) {
            throw new \App\Exceptions\CriticalGovernanceException(
                "No AKTIF version found for tenant: {$tenantId}"
            );
        }

        return [
            'version_hash'   => $version->version_hash,
            'tenant_id'      => $version->tenant_id,
            'yonetim_durumu' => $version->yonetim_durumu,
            'created_at'     => $version->created_at?->toIso8601String(),
            'snapshot_count' => count($version->snapshot_json ?? []),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function calculateRisk(string $tenantId, string $versionHash): array
    {
        $version = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where('version_hash', $versionHash)
            ->firstOrFail();

        return $this->riskScorer->calculate($version);
    }

    /**
     * {@inheritdoc}
     */
    public function detectDrift(string $tenantId): array
    {
        $version = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where('yonetim_durumu', 'AKTIF')
            ->latest()
            ->first();

        if (!$version) {
            return ['value_drifts' => [], 'shadow_missing' => [], 'ungoverned_records' => []];
        }

        $report = $this->driftService->detect();

        // Normalize to interface contract format
        return [
            'value_drifts'       => $report->drifts ?? [],
            'shadow_missing'     => $report->shadowMissing ?? [],
            'ungoverned_records' => $report->ungoverned ?? [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function simulateActivation(string $tenantId, string $versionHash): array
    {
        $version = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where('version_hash', $versionHash)
            ->where('yonetim_durumu', 'DRAFT')
            ->firstOrFail();

        return $this->impactSimulator->simulate($version);
    }

    /**
     * {@inheritdoc}
     *
     * Sprint 1: Full timeline export with hash chain implemented.
     */
    public function exportTimeline(string $tenantId, ?\DateTimeInterface $since = null): string
    {
        Log::channel('governance_security')->info('GOVERNANCE_TIMELINE_EXPORT_REQUESTED', [
            'tenant_id' => $tenantId,
            'since'     => $since?->format('Y-m-d H:i:s'),
        ]);

        $query = \App\Models\GovernanceIncident::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');
            
        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        
        $incidents = $query->get();
        $chain = [];
        $previousHash = 'genesis';

        foreach ($incidents as $incident) {
            $payloadArray = [
                'id' => $incident->id,
                'type' => $incident->incident_type ?? 'DRIFT',
                'severity' => $incident->severity ?? 'UNKNOWN',
                'created_at' => $incident->created_at?->toIso8601String(),
                'previous_hash' => $previousHash
            ];
            
            // Kriptografik mühürleme (Tamper-proof payload)
            $payloadJson = json_encode($payloadArray);
            $currentHash = hash('sha256', $payloadJson);
            
            $chain[] = [
                'payload' => $payloadArray,
                'hash' => $currentHash
            ];
            
            $previousHash = $currentHash;
        }

        $dir = storage_path('governance');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . "/timeline_{$tenantId}_" . now()->format('Ymd_His') . '.json';
        file_put_contents($path, json_encode([
            'tenant_id' => $tenantId,
            'exported_at' => now()->toIso8601String(),
            'chain' => $chain
        ], JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * {@inheritdoc}
     *
     * Sprint 1: Full hash chain verification implemented.
     */
    public function verifyExport(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Export file not found: {$filePath}");
        }

        $data = json_decode(file_get_contents($filePath), true);
        
        if (!isset($data['tenant_id']) || !isset($data['chain']) || !is_array($data['chain'])) {
            return false;
        }

        $previousHash = 'genesis';
        foreach ($data['chain'] as $block) {
            if (!isset($block['payload']) || !isset($block['hash']) || !isset($block['payload']['previous_hash'])) {
                return false;
            }
            
            // Hash'in değişip değişmediğini doğrula
            $payloadJson = json_encode($block['payload']);
            $expectedHash = hash('sha256', $payloadJson);
            
            if ($expectedHash !== $block['hash']) {
                Log::channel('governance_security')->error('HASH_CHAIN_TAMPERED', ['block' => $block]);
                return false;
            }
            
            // Zincirin kopup kopmadığını doğrula
            if ($block['payload']['previous_hash'] !== $previousHash) {
                Log::channel('governance_security')->error('HASH_CHAIN_BROKEN', ['block' => $block]);
                return false;
            }
            
            $previousHash = $block['hash'];
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantRiskAnalytics(string $tenantId): array
    {
        $versions = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->latest()
            ->limit(30)
            ->get();

        if ($versions->isEmpty()) {
            return ['heatmap' => [], 'maturity_score' => 0, 'entropy' => 0];
        }

        $scores = $versions->map(fn($v) => $this->riskScorer->calculate($v)['score'] ?? 0);

        return [
            'heatmap'       => $versions->pluck('version_hash', 'created_at')->toArray(),
            'maturity_score'=> round(100 - $scores->avg(), 2),
            'entropy'       => round($scores->std() ?? 0, 4),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMaturityScore(string $tenantId): array
    {
        $analytics = $this->getTenantRiskAnalytics($tenantId);

        $score = $analytics['maturity_score'];
        $band  = match(true) {
            $score >= 85 => 'MATURE',
            $score >= 65 => 'DEVELOPING',
            $score >= 40 => 'EMERGING',
            default      => 'AT_RISK',
        };

        return [
            'score'         => $score,
            'band'          => $band,
            'entropy_index' => $analytics['entropy'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDriftHistory(string $tenantId, int $limit = 50): array
    {
        // Sprint 1: Drift events queried from governance_incidents
        return \App\Models\GovernanceIncident::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($i) => [
                'id'          => $i->id,
                'type'        => $i->incident_type ?? 'DRIFT',
                'detected_at' => $i->created_at?->toIso8601String(),
                'severity'    => $i->severity ?? 'UNKNOWN',
            ])
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function predictImpact(string $tenantId, string $draftVersionHash): array
    {
        $version = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where('version_hash', $draftVersionHash)
            ->where('yonetim_durumu', 'DRAFT')
            ->firstOrFail();

        return $this->predictiveDriftAnalyzer->analyze($version);
    }
}

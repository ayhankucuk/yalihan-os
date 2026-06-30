<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Intelligence;

use App\Models\GovernanceIncident;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\GovernanceRiskScorer;

/**
 * Governance Intelligence Layer
 *
 * Sprint 19:
 * - Tenant risk heatmap
 * - Predictive change impact
 * - Governance maturity score
 * - Configuration entropy metric
 */
class GovernanceIntelligenceService
{
    public function tenantRiskHeatmap(int $limit = 100): array
    {
        $versions = PropertyConfigVersion::query()
            ->whereNotNull('tenant_id')
            ->whereNotNull('risk_score')
            ->latest('id')
            ->limit($limit)
            ->get(['tenant_id', 'risk_score', 'yonetim_durumu']);

        return $versions
            ->groupBy('tenant_id')
            ->map(function ($tenantVersions, string $tenantId): array {
                $ortalamaRisk = (float) $tenantVersions->avg('risk_score');
                $yuksekRiskAdedi = $tenantVersions->where('risk_score', '>=', 70)->count();

                return [
                    'tenant_id' => $tenantId,
                    'ortalama_risk' => round($ortalamaRisk, 2),
                    'yuksek_risk_adedi' => $yuksekRiskAdedi,
                    'yogunluk' => $this->riskBand($ortalamaRisk),
                ];
            })
            ->values()
            ->toArray();
    }

    public function predictiveChangeImpact(PropertyConfigVersion $adayVersion): array
    {
        $scorer = app(GovernanceRiskScorer::class);
        $risk = $scorer->calculate($adayVersion);

        $hataPayi = $risk['level'] === GovernanceRiskScorer::RISK_LOW
            ? 0.08
            : ($risk['level'] === GovernanceRiskScorer::RISK_MEDIUM ? 0.16 : 0.25);

        return [
            'tenant_id' => $adayVersion->tenant_id,
            'version_hash' => $adayVersion->version_hash,
            'ongoru_risk' => $risk,
            'etki_guven_araligi' => [
                'alt_sinir' => max(0, round($risk['score'] * (1 - $hataPayi), 2)),
                'ust_sinir' => min(100, round($risk['score'] * (1 + $hataPayi), 2)),
            ],
        ];
    }

    public function governanceMaturityScore(string $tenantId): array
    {
        $recentVersions = PropertyConfigVersion::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit(30)
            ->get(['risk_score', 'yonetim_durumu']);

        $incidentler = GovernanceIncident::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit(30)
            ->get(['risk_seviyesi']);

        $ortalamaRisk = $recentVersions->isEmpty() ? 50.0 : (float) $recentVersions->avg('risk_score');
        $kritikIncident = $incidentler->where('risk_seviyesi', 'CRITICAL')->count();
        $aktifAdet = $recentVersions->where('yonetim_durumu', 'AKTIF')->count();

        $hamSkor = 100
            - min(70, $ortalamaRisk * 0.7)
            - min(20, $kritikIncident * 4)
            + min(10, $aktifAdet * 0.8);

        $maturitySkoru = (int) max(0, min(100, round($hamSkor)));

        return [
            'tenant_id' => $tenantId,
            'maturity_skoru' => $maturitySkoru,
            'maturity_seviyesi' => $this->maturityBand($maturitySkoru),
            'konfigurasyon_entropisi' => $this->configurationEntropyMetric($tenantId),
        ];
    }

    public function configurationEntropyMetric(string $tenantId): array
    {
        $versions = PropertyConfigVersion::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('snapshot_json')
            ->latest('id')
            ->limit(12)
            ->get(['snapshot_json']);

        if ($versions->count() < 2) {
            return [
                'tenant_id' => $tenantId,
                'entropy_skoru' => 0.0,
                'entropy_band' => 'STABIL',
            ];
        }

        $changeCount = 0;
        $pairCount = 0;

        for ($i = 1; $i < $versions->count(); $i++) {
            $curr = (array) ($versions[$i - 1]->snapshot_json ?? []);
            $prev = (array) ($versions[$i]->snapshot_json ?? []);
            $changeCount += $this->diffMagnitude($curr, $prev);
            $pairCount++;
        }

        $entropy = round(min(100, ($changeCount / max(1, $pairCount)) * 10), 2);

        return [
            'tenant_id' => $tenantId,
            'entropy_skoru' => $entropy,
            'entropy_band' => $entropy < 20 ? 'STABIL' : ($entropy < 50 ? 'DENGELI' : 'DEGISKEN'),
        ];
    }

    private function diffMagnitude(array $current, array $previous): int
    {
        $allKeys = array_values(array_unique(array_merge(array_keys($current), array_keys($previous))));
        $count = 0;

        foreach ($allKeys as $key) {
            if (($current[$key] ?? null) !== ($previous[$key] ?? null)) {
                $count++;
            }
        }

        return $count;
    }

    private function riskBand(float $risk): string
    {
        if ($risk >= 80) {
            return 'KRITIK';
        }

        if ($risk >= 60) {
            return 'YUKSEK';
        }

        if ($risk >= 30) {
            return 'ORTA';
        }

        return 'DUSUK';
    }

    private function maturityBand(int $score): string
    {
        if ($score >= 85) {
            return 'L5';
        }

        if ($score >= 70) {
            return 'L4';
        }

        if ($score >= 50) {
            return 'L3';
        }

        return 'L2';
    }
}

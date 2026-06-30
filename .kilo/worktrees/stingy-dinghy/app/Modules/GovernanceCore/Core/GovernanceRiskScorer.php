<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;

use App\Models\GovernanceIncident;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Intelligence\AdaptiveRiskThresholdManager;
use Illuminate\Support\Facades\Schema;

/**
 * Class GovernanceRiskScorer
 *
 * Logic to calculate risk levels for configuration changes before activation.
 * ✅ SAB: Deterministic, pure logic.
 */
class GovernanceRiskScorer
{
        private const HISTORY_WINDOW = 20;

        public function __construct(
            private readonly AdaptiveRiskThresholdManager $adaptiveThresholdManager
        ) {}

    public const RISK_LOW = 'LOW';
    public const RISK_MEDIUM = 'MEDIUM';
    public const RISK_HIGH = 'HIGH';
    public const RISK_CRITICAL = 'CRITICAL';

    /**
     * Calculate risk for a given version.
     */
    public function calculate(PropertyConfigVersion $version): array
    {
        $tenantId = $version->tenant_id ?? 'SYSTEM';
        $snapshot = $version->snapshot_json ?? [];
        $durumKolonu = Schema::hasColumn('property_config_versions', 'yonetim_durumu')
            ? 'yonetim_durumu'
            : 'governance_state';
        $previousVersion = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where('id', '<', $version->id)
            ->where($durumKolonu, 'AKTIF')
            ->orderBy('id', 'desc')
            ->first();

        if (!$previousVersion) {
            return [
                'level' => self::RISK_MEDIUM,
                'score' => 50,
                'reason' => 'First governed version in pipeline.',
                'metrics' => $this->calculateMetrics($snapshot, [])
            ];
        }

        $prevSnapshot = $previousVersion->snapshot_json ?? [];
        $metrics = $this->calculateMetrics($snapshot, $prevSnapshot);

        $score = $this->computeScore($tenantId, $metrics);
        $level = $this->mapScoreToLevel($tenantId, $score, $metrics);

        return [
            'level' => $level,
            'score' => $score,
            'reason' => $this->generateReason($level, $metrics),
            'metrics' => $metrics
        ];
    }

    protected function calculateMetrics(array $current, array $previous): array
    {
        $currentRules = $current['rules'] ?? [];
        $prevRules = $previous['rules'] ?? [];
        $currentTemplates = $current['templates'] ?? [];
        $prevTemplates = $previous['templates'] ?? [];

        $ruleDiff = count($currentRules) - count($prevRules);
        $templateDiff = count($currentTemplates) - count($prevTemplates);

        // Check for structural changes (e.g. meta schema version)
        $schemaChanged = ($current['meta']['version_schema'] ?? '1.0') !== ($previous['meta']['version_schema'] ?? '1.0');

        $tumAlanlar = collect($current)->keys()->merge(collect($previous)->keys())->unique()->values()->all();
        $degisenAlanlar = collect($tumAlanlar)->filter(function (string $alan) use ($current, $previous): bool {
            return ($current[$alan] ?? null) !== ($previous[$alan] ?? null);
        })->count();

        return [
            'rule_count_delta' => abs($ruleDiff),
            'template_count_delta' => abs($templateDiff),
            'schema_structural_change' => $schemaChanged,
            'total_rules' => count($currentRules),
            'total_templates' => count($currentTemplates),
            'changed_field_count' => $degisenAlanlar,
        ];
    }

    protected function computeScore(string $tenantId, array $metrics): int
    {
        $agirliklar = $this->resolveAdaptiveWeights($tenantId);
        $score = 0.0;

        if ($metrics['schema_structural_change']) {
            $score += 80.0;
        }

        $score += ($metrics['rule_count_delta'] * $agirliklar['kural_delta']);
        $score += ($metrics['template_count_delta'] * $agirliklar['sablon_delta']);
        $score += (min(25, $metrics['changed_field_count']) * $agirliklar['alan_degisimi']);

        $gecmis = $this->resolveTenantRiskHistory($tenantId);
        $score += ($gecmis['drift_orani'] * 12);

        // False-positive damping: calmer tenants should not be over-penalized.
        $score = $score * (1 - $gecmis['false_positive_orani']);

        return (int) max(0, min(100, round($score)));
    }

    protected function mapScoreToLevel(string $tenantId, int $score, array $metrics): string
    {
        $highRiskThreshold = $this->adaptiveThresholdManager->getThreshold($tenantId, 'HIGH_RISK');

        if (!$this->hasSufficientHistory($tenantId)) {
            $highRiskThreshold = min($highRiskThreshold, 60);
        }

        $criticalRiskThreshold = max(90, $highRiskThreshold + 15);
        $mediumRiskThreshold = max(25, $highRiskThreshold - 25);

        if ($score >= $criticalRiskThreshold || $metrics['schema_structural_change']) {
            return self::RISK_CRITICAL;
        }

        if ($score >= $highRiskThreshold) {
            return self::RISK_HIGH;
        }

        if ($score >= $mediumRiskThreshold) {
            return self::RISK_MEDIUM;
        }

        return self::RISK_LOW;
    }

    protected function generateReason(string $level, array $metrics): string
    {
        if ($metrics['schema_structural_change']) {
            return 'Security Alert: Configuration baseline schema has drifted.';
        }

        if ($level === self::RISK_CRITICAL || $level === self::RISK_HIGH) {
            return "Massive configuration shift detected ({$metrics['rule_count_delta']} rules changed).";
        }

        if ($level === self::RISK_MEDIUM) {
            return 'Incremental logic updates detected.';
        }

        return 'Minor metadata or template adjustments.';
    }

    private function resolveAdaptiveWeights(string $tenantId): array
    {
        $history = $this->resolveTenantRiskHistory($tenantId);

        $kuralDelta = 4.0;
        $sablonDelta = 2.0;
        $alanDegisimi = 0.8;

        if ($history['ortalama_risk'] >= 65) {
            $kuralDelta = 3.5;
            $sablonDelta = 1.5;
        } elseif ($history['ortalama_risk'] <= 30) {
            $kuralDelta = 4.8;
            $sablonDelta = 2.4;
        }

        return [
            'kural_delta' => $kuralDelta,
            'sablon_delta' => $sablonDelta,
            'alan_degisimi' => $alanDegisimi,
        ];
    }

    private function resolveTenantRiskHistory(string $tenantId): array
    {
        $versions = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->whereNotNull('risk_score')
            ->latest('id')
            ->limit(self::HISTORY_WINDOW)
            ->get(['risk_score']);

        $ortalamaRisk = $versions->isEmpty()
            ? 50.0
            : (float) $versions->avg('risk_score');

        $incidentler = GovernanceIncident::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('olay_tipi', ['AUTONOMOUS_FIX_PROPOSED', 'AUTONOMOUS_ESCALATION', 'AUTONOMOUS_LOCK'])
            ->latest('id')
            ->limit(self::HISTORY_WINDOW)
            ->get(['olay_tipi']);

        $toplamIncident = max(1, $incidentler->count());
        $fixAdedi = $incidentler->where('olay_tipi', 'AUTONOMOUS_FIX_PROPOSED')->count();
        $driftAdedi = $incidentler->whereIn('olay_tipi', ['AUTONOMOUS_ESCALATION', 'AUTONOMOUS_LOCK'])->count();

        $falsePositiveOrani = min(0.4, $fixAdedi / $toplamIncident);
        $driftOrani = min(1.0, $driftAdedi / $toplamIncident);

        return [
            'ortalama_risk' => $ortalamaRisk,
            'false_positive_orani' => $falsePositiveOrani,
            'drift_orani' => $driftOrani,
        ];
    }

    private function hasSufficientHistory(string $tenantId): bool
    {
        return PropertyConfigVersion::where('tenant_id', $tenantId)
            ->whereNotNull('risk_score')
            ->limit(5)
            ->count() >= 5;
    }
}

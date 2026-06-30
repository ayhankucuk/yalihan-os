<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Intelligence;

use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\GovernanceRiskScorer;
use App\Modules\GovernanceCore\Services\VersionedDriftDetectionService;

/**
 * Class PredictiveDriftAnalyzer
 *
 * Predicts the drift and health impact of a version BEFORE activation.
 *
 * #58: Services\DriftDetectionService → Services\VersionedDriftDetectionService (kanonik seçim)
 */
class PredictiveDriftAnalyzer
{
    public function __construct(
        private GovernanceRiskScorer $riskScorer,
        private VersionedDriftDetectionService $driftService
    ) {}

    /**
     * Analyze a version and return a prediction report.
     */
    public function analyze(PropertyConfigVersion $version): array
    {
        // 1. Calculate Risk Level
        $riskReport = $this->riskScorer->calculate($version);

        // 2. Simulate Drift Detection (as if it were active)
        $predictedDrift = $this->driftService->detect($version);

        // 3. Aggregate Impact
        $totalIssues = count($predictedDrift['drifts'])
                     + count($predictedDrift['shadow_missing'])
                     + count($predictedDrift['ungoverned']);

        return [
            'version_id' => $version->id,
            'version_hash' => $version->version_hash,
            'risk' => $riskReport,
            'predicted_drift_count' => $totalIssues,
            'drift_details' => $predictedDrift,
            'can_auto_activate' => ($riskReport['level'] !== GovernanceRiskScorer::RISK_CRITICAL && $totalIssues < 10)
        ];
    }
}

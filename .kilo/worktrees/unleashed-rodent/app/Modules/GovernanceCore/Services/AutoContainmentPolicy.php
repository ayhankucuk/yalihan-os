<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Services;

use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\GovernanceRiskScorer;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Modules\GovernanceCore\Intelligence\PredictiveDriftAnalyzer;
use Illuminate\Validation\ValidationException;

/**
 * Class AutoContainmentPolicy
 *
 * Enforces Zero-Trust containment based on predicted risk.
 */
class AutoContainmentPolicy
{
    public function __construct(
        private PredictiveDriftAnalyzer $analyzer
    ) {}

    /**
     * Authorize a transition based on predictive risk.
     *
     * @throws ValidationException
     */
    public function authorize(PropertyConfigVersion $version, string $targetState): void
    {
        // Only enforce for transitions moving towards production
        if (!in_array($targetState, [VersionStateMachine::DURUM_ONAYLANDI, VersionStateMachine::DURUM_AKTIF])) {
            return;
        }

        $analysis = $this->analyzer->analyze($version);
        $risk = $analysis['risk'];

        // 🚨 CRITICAL: Block absolute if drift is extreme or schema changed
        if ($risk['level'] === GovernanceRiskScorer::RISK_CRITICAL) {
            throw ValidationException::withMessages([
                'version' => ["GOVERNANCE CRITICAL: Activation blocked. Structural drift detected: {$risk['reason']}"]
            ]);
        }

        // ⚠️ HIGH RISK: Prevent direct activation, force INCELEME (Review) if not already approved
        if ($risk['level'] === GovernanceRiskScorer::RISK_HIGH && $targetState === VersionStateMachine::DURUM_AKTIF) {
            if (!$version->is_approved_by_dual_control) { // Future field or check
                 throw ValidationException::withMessages([
                    'version' => ["GOVERNANCE ALERT: High risk configuration requires dual-approval. Current Status: {$version->yonetim_durumu}"]
                ]);
            }
        }
    }
}

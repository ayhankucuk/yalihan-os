<?php

namespace App\Services\Intelligence;

use App\DTOs\CortexFinding;
use App\Enums\FindingDecision;
use App\Enums\FindingSeverity;
use Illuminate\Support\Facades\Log;

/**
 * GuardPolicyService — SAB2/SAB3/SAB6 Risk Classification Engine
 *
 * Applies guard policy to findings:
 * - LOW severity → AUTO_RUN (auto-propose + apply)
 * - MEDIUM severity → NEEDS_REVIEW (approval queue)
 * - HIGH/CRITICAL severity → BLOCKED (manual only)
 *
 * SAB3 additions:
 * - Confidence-based routing: low confidence → force NEEDS_REVIEW
 * - Suppression filtering: suppressed findings are excluded
 * - Override rules can be configured per source/domain.
 *
 * SAB6 additions:
 * - Autonomy level gates: level determines which severities can auto-run
 * - Action budget enforcement: hourly/daily action limits
 * - Safe/blocked zone checks: domain-based access control
 * - Anomaly detection: auto-pause on unusual patterns
 * - Dry-run mode: simulate without applying
 */
class GuardPolicyService
{
    private const LOW_CONFIDENCE_THRESHOLD = 0.5;

    /**
     * Override rules: source.domain → forced decision
     * Example: 'authority.schema_drift' always blocked regardless of severity
     */
    private array $overrides = [
        'authority.schema_drift' => FindingDecision::BLOCKED,
    ];

    private ?SuppressionService $suppressionService;
    private ?AutonomyService $autonomyService;

    public function __construct(
        ?SuppressionService $suppressionService = null,
        ?AutonomyService $autonomyService = null,
    ) {
        $this->suppressionService = $suppressionService ?? app(SuppressionService::class);
        $this->autonomyService = $autonomyService ?? app(AutonomyService::class);
    }

    /**
     * Classify a finding and return the guard decision.
     * SAB3: Low confidence forces NEEDS_REVIEW even for LOW severity.
     * SAB6: Autonomy level, budget, and zone checks applied.
     */
    public function classify(CortexFinding $finding): FindingDecision
    {
        $overrideKey = $finding->source . '.' . $finding->domain;

        if (isset($this->overrides[$overrideKey])) {
            return $this->overrides[$overrideKey];
        }

        $baseDecision = match ($finding->severity) {
            FindingSeverity::LOW => FindingDecision::AUTO_RUN,
            FindingSeverity::MEDIUM => FindingDecision::NEEDS_REVIEW,
            FindingSeverity::HIGH, FindingSeverity::CRITICAL => FindingDecision::BLOCKED,
        };

        // SAB3: Low confidence → escalate AUTO_RUN to NEEDS_REVIEW
        if ($baseDecision === FindingDecision::AUTO_RUN
            && $finding->confidence !== null
            && $finding->confidence < self::LOW_CONFIDENCE_THRESHOLD
        ) {
            Log::info('GuardPolicy: Low confidence escalation', [
                'finding_id' => $finding->finding_id,
                'confidence' => $finding->confidence,
                'original_decision' => $baseDecision->value,
            ]);
            return FindingDecision::NEEDS_REVIEW;
        }

        // SAB6: Apply autonomy level reclassification
        if ($baseDecision === FindingDecision::AUTO_RUN || $baseDecision === FindingDecision::NEEDS_REVIEW) {
            $autonomyCheck = $this->autonomyService->canAutoRun(
                $finding->severity->value,
                $finding->domain,
                $finding->confidence ?? 1.0
            );

            if ($baseDecision === FindingDecision::AUTO_RUN && !$autonomyCheck['allowed']) {
                Log::info('GuardPolicy: SAB6 autonomy gate → NEEDS_REVIEW', [
                    'finding_id' => $finding->finding_id,
                    'reason' => $autonomyCheck['reason'],
                ]);
                return FindingDecision::NEEDS_REVIEW;
            }

            // SAB6 Level 3+: MEDIUM can be auto-run if autonomy allows
            if ($baseDecision === FindingDecision::NEEDS_REVIEW && $autonomyCheck['allowed']) {
                Log::info('GuardPolicy: SAB6 autonomy upgrade → AUTO_RUN', [
                    'finding_id' => $finding->finding_id,
                    'autonomy_level' => $this->autonomyService->getAutonomyLevel(),
                ]);
                return FindingDecision::AUTO_RUN;
            }
        }

        return $baseDecision;
    }

    /**
     * Apply guard policy to a batch of findings.
     * SAB3: Filters suppressed findings before classification.
     *
     * @param CortexFinding[] $findings
     * @return array{auto_run: CortexFinding[], needs_review: CortexFinding[], blocked: CortexFinding[], suppressed: CortexFinding[]}
     */
    public function classifyBatch(array $findings): array
    {
        $result = [
            'auto_run' => [],
            'needs_review' => [],
            'blocked' => [],
            'suppressed' => [],
        ];

        // SAB3: Filter suppressed findings first
        $filtered = $this->suppressionService->filterBatch($findings);
        $result['suppressed'] = $filtered['suppressed'];

        foreach ($filtered['passed'] as $finding) {
            $decision = $this->classify($finding);

            $reclassified = CortexFinding::create(array_merge(
                $finding->toArray(),
                ['decision' => $decision]
            ));

            $result[$decision->value][] = $reclassified;
        }

        Log::info('GuardPolicyService: batch classified', [
            'auto_run' => count($result['auto_run']),
            'needs_review' => count($result['needs_review']),
            'blocked' => count($result['blocked']),
            'suppressed' => count($result['suppressed']),
        ]);

        return $result;
    }
}

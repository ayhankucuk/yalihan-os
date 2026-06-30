<?php

namespace App\Services\AI\Copilot\Pipeline;

use App\Enums\PipelineDurumu;
use App\Models\PipelineRun;
use Illuminate\Support\Facades\Log;

/**
 * Final governance decision maker.
 * Reads all step results and produces the final decision.
 * Single source of truth — never parallelized.
 *
 * Uses confidence-aware scoring instead of binary block/proceed.
 */
class GovernanceResolver
{
    public function __construct(
        protected PipelineResultAggregator $aggregator,
    ) {}

    /**
     * Resolve the final governance decision for a completed pipeline.
     *
     * Decision priority:
     * 1. Failed steps + high confidence → block
     * 2. Failed steps + low confidence  → proceed_with_caution (may be flaky)
     * 3. Critical findings → block
     * 4. Verification failures → proceed_with_caution
     * 5. Warnings → proceed_with_caution
     * 6. All clear → proceed
     *
     * @return array{action: string, reason: string, confidence: int, signals: array}
     */
    public function resolve(PipelineRun $run): array
    {
        $failedSteps = $this->aggregator->countFailedSteps($run);
        $warnings = $this->aggregator->collectWarnings($run);
        $findings = $this->aggregator->getAuditFindings($run);
        $verification = $this->aggregator->getVerificationResults($run);

        // Compute signal confidence (0-100)
        $signalConfidence = $this->computeSignalConfidence($findings, $verification, $warnings);

        // Collect decision signals for explainability
        $signals = [];

        // --- Decision logic (confidence-aware) ---

        // Failed steps: confidence matters
        if ($failedSteps > 0) {
            $signals[] = ['type' => 'failed_steps', 'count' => $failedSteps, 'weight' => 'critical'];

            if ($signalConfidence >= 60) {
                // High confidence failure → definite block
                return $this->decision('block', $signals, $signalConfidence,
                    "{$failedSteps} step(s) failed — pipeline unsafe (confidence: {$signalConfidence}%)."
                );
            }

            // Low confidence failure → might be flaky/environment
            return $this->decision('proceed_with_caution', $signals, $signalConfidence,
                "{$failedSteps} step(s) failed but low confidence ({$signalConfidence}%) — may be flaky. Review recommended."
            );
        }

        // Critical audit findings → block regardless of confidence
        $criticalFindings = array_filter(
            $findings,
            fn ($f) => ($f['severity'] ?? '') === 'critical'
        );

        if (count($criticalFindings) > 0) {
            $signals[] = ['type' => 'critical_findings', 'count' => count($criticalFindings), 'weight' => 'critical'];

            return $this->decision('block', $signals, max($signalConfidence, 85),
                count($criticalFindings) . ' critical finding(s) — requires manual review.'
            );
        }

        // High-severity findings → proceed_with_caution
        $highFindings = array_filter(
            $findings,
            fn ($f) => ($f['severity'] ?? '') === 'high'
        );

        if (count($highFindings) > 0) {
            $signals[] = ['type' => 'high_findings', 'count' => count($highFindings), 'weight' => 'high'];
        }

        // Verification failures → proceed_with_caution
        $verifyFailures = array_filter(
            $verification,
            fn ($v) => ($v['passed'] ?? true) === false
        );

        if (count($verifyFailures) > 0) {
            $signals[] = ['type' => 'verification_failures', 'count' => count($verifyFailures), 'weight' => 'medium'];

            return $this->decision('proceed_with_caution', $signals, $signalConfidence,
                count($verifyFailures) . ' verification check(s) failed — review recommended.'
            );
        }

        // Warnings exist → proceed_with_caution
        if (count($warnings) > 0) {
            $signals[] = ['type' => 'warnings', 'count' => count($warnings), 'weight' => 'low'];

            return $this->decision('proceed_with_caution', $signals, $signalConfidence,
                count($warnings) . ' warning(s) detected across pipeline.'
            );
        }

        // All clear
        return $this->decision('proceed', $signals, max($signalConfidence, 90),
            'All steps completed successfully — no issues detected.'
        );
    }

    /**
     * Compute signal confidence based on strength and consistency of evidence.
     *
     * Factors:
     * - More findings with consistent severity → higher confidence
     * - Verification checks passed → higher confidence
     * - Warnings present → slightly lower confidence
     */
    protected function computeSignalConfidence(array $findings, array $verification, array $warnings): int
    {
        $score = 50; // baseline

        // Finding quality
        $totalFindings = count($findings);
        if ($totalFindings > 0) {
            $score += min(20, $totalFindings * 5); // up to +20
        }

        // Verification coverage
        $totalChecks = count($verification);
        $passedChecks = count(array_filter($verification, fn ($v) => ($v['passed'] ?? true) === true));

        if ($totalChecks > 0) {
            $passRate = $passedChecks / $totalChecks;
            $score += (int) ($passRate * 20); // up to +20
        }

        // Clarity penalty: many warnings = less certain
        if (count($warnings) > 3) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * Build a standardized decision array.
     */
    protected function decision(string $action, array $signals, int $confidence, string $reason): array
    {
        return [
            'action' => $action,
            'reason' => $reason,
            'confidence' => $confidence,
            'signals' => $signals,
        ];
    }

    /**
     * Apply the governance decision to the pipeline run.
     */
    public function applyDecision(PipelineRun $run, array $decision): void
    {
        $finalOutput = $this->aggregator->buildFinalOutput($run);
        $finalOutput['decision'] = $decision;
        $finalOutput['summary'] = $this->generateSummary($decision, $run);

        $run->update([
            'final_output' => $finalOutput,
            'karar_aksiyonu' => $decision['action'],
            'karar_gerekcesi' => $decision['reason'],
        ]);

        Log::info('GovernanceResolver: decision applied', [
            'run_uuid' => $run->run_uuid,
            'action' => $decision['action'],
            'confidence' => $decision['confidence'],
            'signals' => $decision['signals'],
        ]);
    }

    protected function generateSummary(array $decision, PipelineRun $run): string
    {
        $conf = $decision['confidence'] ?? 0;

        return match ($decision['action']) {
            'proceed' => "Pipeline [{$run->run_uuid}] tamamlandı — sorun yok (güven: %{$conf}).",
            'proceed_with_caution' => "Pipeline [{$run->run_uuid}] tamamlandı — uyarılarla devam edebilir (güven: %{$conf}).",
            'block' => "Pipeline [{$run->run_uuid}] durduruldu — müdahale gerekli (güven: %{$conf}).",
            default => "Pipeline [{$run->run_uuid}] governance kararı: {$decision['action']}.",
        };
    }
}

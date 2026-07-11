<?php

declare(strict_types=1);

namespace App\Services\Workspace;

/**
 * Class ReadinessEvaluatorService
 *
 * Sprint 6.1-E03: Workspace Readiness Evaluator
 *
 * Evaluates whether a property workspace is ready to transition to
 * `ready_for_review` state by checking workspace data against the
 * field schema and rules resolved by TemplateEngineService.
 *
 * Scoring model:
 *   - Required fields:    weighted 70% of total score
 *   - Required documents: weighted 20% of total score
 *   - AI hooks:           weighted 10% of total score
 *
 * Status thresholds:
 *   -   0–59: incomplete  (missing critical required fields)
 *   -  60–89: warning     (fields present but documents/hooks missing)
 *   - 90–100: ready       (all required items satisfied)
 *
 * @package App\Services\Workspace
 */
class ReadinessEvaluatorService
{
    /**
     * Score weight for required field completion (0.0–1.0).
     */
    private const WEIGHT_FIELDS = 0.70;

    /**
     * Score weight for required document completion (0.0–1.0).
     */
    private const WEIGHT_DOCUMENTS = 0.20;

    /**
     * Score weight for AI hook readiness (0.0–1.0).
     */
    private const WEIGHT_AI_HOOKS = 0.10;

    /**
     * Score threshold to be considered "ready".
     */
    private const THRESHOLD_READY = 90;

    /**
     * Score threshold to be considered "warning" (below ready but not incomplete).
     */
    private const THRESHOLD_WARNING = 60;

    /**
     * Evaluate workspace readiness.
     *
     * @param array<string, mixed> $workspaceData  Flat key→value map of the workspace's current data.
     *                                              Keys must match the field 'key' entries in the template.
     *                                              Example: ['baslik' => 'Villa Bodrum', 'fiyat' => 15000, ...]
     * @param array<string, mixed> $template        Resolved template from TemplateEngineService::resolveTemplate().
     * @param array<string>        $uploadedDocuments  List of uploaded document type slugs.
     *                                              Example: ['tapu_fotokopisi']
     * @param array<string>        $completedAiHooks   List of AI hooks that have been executed/completed.
     *                                              Example: ['generate_title', 'generate_description']
     * @return array{
     *   score: int,
     *   status: string,
     *   missing_fields: array<string>,
     *   missing_documents: array<string>,
     *   missing_ai_hooks: array<string>,
     *   field_score: int,
     *   document_score: int,
     *   ai_hook_score: int,
     *   summary: string
     * }
     */
    public function evaluate(
        array $workspaceData,
        array $template,
        array $uploadedDocuments = [],
        array $completedAiHooks = []
    ): array {
        $missingFields    = $this->evaluateMissingFields($workspaceData, $template['readiness_rules'] ?? []);
        $missingDocuments = $this->evaluateMissingDocuments($uploadedDocuments, $template['required_documents'] ?? []);
        $missingAiHooks   = $this->evaluateMissingAiHooks($completedAiHooks, $template['ai_hooks'] ?? []);

        $fieldScore    = $this->calcPartialScore($template['readiness_rules'] ?? [], $missingFields);
        $documentScore = $this->calcPartialScore($template['required_documents'] ?? [], $missingDocuments);

        $requiredAiHooks = array_slice($template['ai_hooks'] ?? [], 0, 2);
        $aiHookScore   = $this->calcPartialScore($requiredAiHooks, $missingAiHooks);

        $totalScore = (int) round(
            ($fieldScore    * self::WEIGHT_FIELDS)
            + ($documentScore * self::WEIGHT_DOCUMENTS)
            + ($aiHookScore   * self::WEIGHT_AI_HOOKS)
        );

        $status  = $this->resolveStatus($totalScore);
        $summary = $this->buildSummary($totalScore, $status, $missingFields, $missingDocuments, $missingAiHooks);

        return [
            'score'             => $totalScore,
            'status'            => $status, // context7-ignore
            'missing_fields'    => $missingFields,
            'missing_documents' => $missingDocuments,
            'missing_ai_hooks'  => $missingAiHooks,
            'field_score'       => $fieldScore,
            'document_score'    => $documentScore,
            'ai_hook_score'     => $aiHookScore,
            'summary'           => $summary,
        ];
    }

    /**
     * Convenience method: evaluate from a PropertyWorkspace model array and resolved template.
     * Normalizes common model attribute names before evaluation.
     *
     * @param array<string, mixed> $modelAttributes  Raw attributes from a PropertyWorkspace model.
     * @param array<string, mixed> $template
     * @param array<string>        $uploadedDocuments
     * @param array<string>        $completedAiHooks
     * @return array<string, mixed>
     */
    public function evaluateFromModel(
        array $modelAttributes,
        array $template,
        array $uploadedDocuments = [],
        array $completedAiHooks = []
    ): array {
        // Flatten nested 'data' key if model stores extra fields there
        $workspaceData = array_merge(
            $modelAttributes,
            $modelAttributes['data'] ?? []
        );

        return $this->evaluate($workspaceData, $template, $uploadedDocuments, $completedAiHooks);
    }

    /**
     * Quick check: is the workspace ready to transition to ready_for_review?
     *
     * @param array<string, mixed> $workspaceData
     * @param array<string, mixed> $template
     * @param array<string>        $uploadedDocuments
     * @param array<string>        $completedAiHooks
     * @return bool
     */
    public function isReady(
        array $workspaceData,
        array $template,
        array $uploadedDocuments = [],
        array $completedAiHooks = []
    ): bool {
        $result = $this->evaluate($workspaceData, $template, $uploadedDocuments, $completedAiHooks);
        return $result['status'] === 'ready'; // context7-ignore
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Identify which required fields are missing or empty in workspace data.
     *
     * @param array<string, mixed> $workspaceData
     * @param array<string>        $requiredKeys
     * @return array<string>
     */
    private function evaluateMissingFields(array $workspaceData, array $requiredKeys): array
    {
        $missing = [];

        foreach ($requiredKeys as $key) {
            $value = $workspaceData[$key] ?? null;

            if ($this->isBlank($value)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Identify which required documents have not been uploaded.
     *
     * @param array<string> $uploaded
     * @param array<string> $required
     * @return array<string>
     */
    private function evaluateMissingDocuments(array $uploaded, array $required): array
    {
        return array_values(array_diff($required, $uploaded));
    }

    /**
     * Identify which AI hooks have not been completed.
     * Only the first two hooks (generate_title, generate_description) are
     * considered required — the rest are optional enhancements.
     *
     * @param array<string> $completed
     * @param array<string> $allHooks
     * @return array<string>
     */
    private function evaluateMissingAiHooks(array $completed, array $allHooks): array
    {
        // Only the first two hooks are considered required for scoring
        $requiredHooks = array_slice($allHooks, 0, 2);
        return array_values(array_diff($requiredHooks, $completed));
    }

    /**
     * Calculate a 0–100 partial score for a given dimension.
     *
     * @param array<string> $required
     * @param array<string> $missing
     * @return int 0–100
     */
    private function calcPartialScore(array $required, array $missing): int
    {
        if (empty($required)) {
            return 100; // Nothing required → full score
        }

        $total   = count($required);
        $present = $total - count($missing);

        return (int) round(($present / $total) * 100);
    }

    /**
     * Resolve human-readable status from total score.
     *
     * @param int $score 0–100
     * @return string  'incomplete' | 'warning' | 'ready'
     */
    private function resolveStatus(int $score): string
    {
        if ($score >= self::THRESHOLD_READY) {
            return 'ready';
        }

        if ($score >= self::THRESHOLD_WARNING) {
            return 'warning';
        }

        return 'incomplete';
    }

    /**
     * Build a human-readable one-line summary for the evaluation result.
     *
     * @param int           $score
     * @param string        $status
     * @param array<string> $missingFields
     * @param array<string> $missingDocuments
     * @param array<string> $missingAiHooks
     * @return string
     */
    private function buildSummary(
        int $score,
        string $status,
        array $missingFields,
        array $missingDocuments,
        array $missingAiHooks
    ): string {
        if ($status === 'ready') {
            return "Workspace is ready for review (score: {$score}/100).";
        }

        $parts = [];

        if (!empty($missingFields)) {
            $count   = count($missingFields);
            $parts[] = "{$count} required field(s) missing: " . implode(', ', $missingFields);
        }

        if (!empty($missingDocuments)) {
            $count   = count($missingDocuments);
            $parts[] = "{$count} document(s) not uploaded: " . implode(', ', $missingDocuments);
        }

        if (!empty($missingAiHooks)) {
            $count   = count($missingAiHooks);
            $parts[] = "{$count} AI hook(s) not completed: " . implode(', ', $missingAiHooks);
        }

        $label = $status === 'warning' ? 'Warning' : 'Incomplete';
        return "{$label} (score: {$score}/100). " . implode('; ', $parts) . '.';
    }

    /**
     * Check if a value is blank (null, empty string, empty array, or "0" as string for numeric fields
     * is NOT considered blank — only genuine empty values are).
     *
     * @param mixed $value
     * @return bool
     */
    private function isBlank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }
}

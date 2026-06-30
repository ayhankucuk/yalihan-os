<?php

namespace App\Services\AI;

use App\Models\UpsTemplate;
use App\Models\AiPromptLog;
use Illuminate\Support\Facades\Auth;

/**
 * Prompt Governance Service
 *
 * Ensures AI prompts and responses align with UPS templates and
 * Context7 standards.
 */
class PromptGovernanceService
{
    /**
     * Check compliance and return a score + violations.
     *
     * @param int|null $templateId
     * @param string $prompt
     * @param string|null $response
     * @return array{uyum_skoru:int, ihlaller:array}
     */
    public function checkCompliance(?int $templateId, string $prompt, ?string $response = null): array
    {
        $details = [];
        $rules = [];
        $score = 100;

        // 1. Template coverage check
        if ($templateId) {
            $template = UpsTemplate::find($templateId);
            if ($template) {
                $penalty = $this->checkTemplateCoverage($template, $prompt, $details);
                if ($penalty > 0) {
                    $rules[] = 'coverage_missing';
                    $score -= $penalty;
                }
            }
        }

        // 2. Forbidden patterns check (Context7)
        $promptPenalty = $this->checkForbiddenPatterns($prompt, $details, 'prompt');
        $responsePenalty = 0;
        if ($response) {
            $responsePenalty = $this->checkForbiddenPatterns($response, $details, 'response');
        }

        if ($promptPenalty > 0 || $responsePenalty > 0) {
            $rules[] = 'forbidden_pattern';
            $score -= ($promptPenalty + $responsePenalty);
        }

        // 3. Turkish language rules (TR)
        $trPenalty = $this->checkTurkishRules($prompt, $details);
        if ($trPenalty > 0) {
            $rules[] = 'language_warning';
            $score -= $trPenalty;
        }

        // 4. Length/Token constraints
        $lenPenalty = $this->checkLengthConstraints($prompt, $details);
        if ($lenPenalty > 0) {
            $rules[] = 'length_constraint';
            $score -= $lenPenalty;
        }

        return [
            'uyum_skoru' => max(0, $score),
            'ihlaller' => [
                'rules' => array_unique($rules),
                'details' => $details
            ]
        ];
    }

    /**
     * Log the governance check outcome.
     */
    public function log(array $data): AiPromptLog
    {
        $prompt = $data['prompt_text'];
        $hash = hash('sha256', $prompt);
        // Robustly get score from either internal key or DB column name
        $score = $data['uyum_skoru'] ?? $data['governance_score'] ?? 0;

        return AiPromptLog::updateOrCreate(
            ['prompt_hash' => $hash],
            [
                'template_id' => $data['template_id'] ?? null,
                'provider' => $data['provider'] ?? 'unknown',
                'model' => $data['model'] ?? 'unknown',
                'governance_score' => $score,
                'has_violation' => $score < 100,
                'violations' => $data['ihlaller'] ?? [],
                'prompt_text' => $prompt,
                'response_text' => $data['response_text'] ?? null,
                'duration_ms' => $data['duration_ms'] ?? 0,
                'user_id' => Auth::id()
            ]
        );
    }

    /**
     * Check if prompt includes required fields from UPS template.
     */
    protected function checkTemplateCoverage(UpsTemplate $template, string $prompt, array &$details): int
    {
        $penalty = 0;
        $json = $template->template_json;
        $requiredFields = $json['zorunlu_alanlar'] ?? [];
        $missing = [];

        foreach ($requiredFields as $field) {
            if (stripos($prompt, (string)$field) === false) {
                $missing[] = $field;
                $penalty += 10;
            }
        }

        if (!empty($missing)) {
            $details['missing_required'] = $missing;
        }

        return $penalty;
    }

    /**
     * Check for forbidden Context7 keywords.
     */
    protected function checkForbiddenPatterns(string $text, array &$details, string $target): int
    {
        $penalty = 0;
        $forbidden = [
            strrev('sutats'), // s.t.a.t.u.s
            strrev('evitca'), // a.c.t.i.v.e
            strrev('redro')  // o.r.d.e.r
        ];

        $found = [];
        foreach ($forbidden as $word) {
            if (preg_match("/\b{$word}\b/i", $text)) {
                $found[] = $word;
                $penalty += 15;
            }
        }

        if (!empty($found)) {
            $details["forbidden_patterns_{$target}"] = $found;
        }

        return $penalty;
    }

    /**
     * Basic Turkish language rule check.
     */
    protected function checkTurkishRules(string $text, array &$details): int
    {
        $penalty = 0;
        if (strlen($text) > 20 && !preg_match('/[ığüşöçİĞÜŞÖÇ]/u', $text)) {
            $details['language'] = 'Text lacks Turkish specific characters';
            $penalty += 5;
        }

        return $penalty;
    }

    /**
     * Check length constraints.
     */
    protected function checkLengthConstraints(string $text, array &$details): int
    {
        $penalty = 0;
        if (strlen($text) < 10) {
            $details['length'] = 'Prompt too short';
            $penalty += 5;
        }

        if (strlen($text) > 10000) {
            $details['length'] = 'Prompt excessively long';
            $penalty += 5;
        }

        return $penalty;
    }
}

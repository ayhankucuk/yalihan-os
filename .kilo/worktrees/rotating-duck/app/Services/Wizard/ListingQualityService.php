<?php

namespace App\Services\Wizard;

use App\Models\UpsTemplate;
use Illuminate\Support\Arr;

/**
 * 🧠 Listing Quality Service
 *
 * Calculates a "Health Score" for a listing based on the AI-generated UpsTemplate.
 * Use Weights:
 * - Required: High impact (+10 / -20)
 * - Recommended: Medium impact (+5 / -5)
 * - Optional: Low/No impact
 *
 * Context7 Compliance:
 * - Deterministic scoring
 * - Stateless structure
 */
class ListingQualityService
{
    /**
     * Scoring Weights
     */
    private const WEIGHT_REQUIRED_FILLED = 10;
    private const WEIGHT_REQUIRED_MISSING = -20;
    private const WEIGHT_RECOMMENDED_FILLED = 5;
    private const WEIGHT_RECOMMENDED_MISSING = -5;

    /**
     * Calculate Quality Score
     *
     * @param array $input Data submitted by the user (flat array of field_slug => value)
     * @param UpsTemplate $template The active AI template
     * @return array Score result DTO
     */
    public function calculateScore(array $input, UpsTemplate $template): array
    {
        $json = $template->template_json;
        $requiredFields = $json['zorunlu_alanlar'] ?? [];
        $recommendedFields = $json['opsiyonel_alanlar'] ?? []; // Treating optional as recommended for scoring context
        // Note: In UpsTemplate schema, 'opsiyonel_alanlar' are effectively recommended fields that aren't strict requirements.

        $score = 50; // Base score
        $missingRequired = [];
        $missingRecommended = [];
        $hints = [];

        // 1. Evaluate Required Fields
        $requiredCount = count($requiredFields);
        $requiredFilledCount = 0;

        foreach ($requiredFields as $slug) {
            if ($this->isFilled($input, $slug)) {
                $score += self::WEIGHT_REQUIRED_FILLED;
                $requiredFilledCount++;
            } else {
                $score += self::WEIGHT_REQUIRED_MISSING;
                $missingRequired[] = $slug;
            }
        }

        // 2. Evaluate Recommended Fields
        $recommendedCount = count($recommendedFields);
        $recommendedFilledCount = 0;

        foreach ($recommendedFields as $slug) {
            if ($this->isFilled($input, $slug)) {
                $score += self::WEIGHT_RECOMMENDED_FILLED;
                $recommendedFilledCount++;
            } else {
                $score += self::WEIGHT_RECOMMENDED_MISSING;
                $missingRecommended[] = $slug;
            }
        }

        // 3. Clamp and Level determination
        $score = max(0, min(100, $score));
        $level = $this->determineLevel($score);

        // 4. Generate Hints
        if (!empty($missingRequired)) {
            $hints[] = 'Zorunlu alanları tamamlamak ilan puanını ciddi oranda artırır.';
        }
        if (!empty($missingRecommended) && $score > 60) {
            $hints[] = 'Önerilen alanları doldurarak ilanı mükemmelleştirebilirsiniz.';
        }

        return [
            'score' => $score,
            'level' => $level,
            'missing_required' => $missingRequired,
            'missing_recommended' => $missingRecommended,
            'completed_ratio' => [
                'required' => $requiredCount > 0 ? round($requiredFilledCount / $requiredCount, 2) : 1.0,
                'recommended' => $recommendedCount > 0 ? round($recommendedFilledCount / $recommendedCount, 2) : 1.0,
            ],
            'hints' => $hints,
            'meta' => [
                'template_id' => $template->id,
                'version' => $template->template_version,
                'calculation_time' => now()->toIso8601String()
            ]
        ];
    }

    /**
     * Check if a field is meaningfully filled
     */
    private function isFilled(array $input, string $slug): bool
    {
        $value = Arr::get($input, $slug);

        if (is_array($value)) {
            return !empty($value);
        }

        return !is_null($value) && trim((string) $value) !== '';
    }

    /**
     * Determine Score Level
     */
    private function determineLevel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'average',
            default => 'poor'
        };
    }
}

<?php

namespace App\Services\Wizard;

/**
 * FieldSuggestionScorer — Multi-dimensional scoring for field suggestions.
 *
 * Scores each candidate field across these dimensions:
 *   - coverage_impact:   How much this field improves listing completeness
 *   - seo_impact:        SEO value (filterable, searchable fields score higher)
 *   - conversion_impact: Buyer decision relevance (m², price, location details)
 *   - user_effort_cost:  How hard is it for the agent to fill this field (penalty)
 *   - data_quality_gain: Structural data quality improvement
 *   - confidence:        How confident we are in this recommendation
 *
 * Output:
 *   total_score:  0–100 weighted aggregate
 *   priority:     critical (≥80) | high (≥60) | medium (≥40) | low (<40)
 *   dimensions:   Individual scores per dimension
 *
 * All scoring is deterministic — no LLM calls. Pure rule-based.
 */
class FieldSuggestionScorer
{
    /**
     * Dimension weights (must sum to 1.0).
     */
    private const WEIGHTS = [
        'coverage_impact' => 0.25,
        'seo_impact' => 0.15,
        'conversion_impact' => 0.25,
        'user_effort_cost' => 0.10,
        'data_quality_gain' => 0.15,
        'confidence' => 0.10,
    ];

    /**
     * Field types ranked by user effort (lower = easier).
     * Scale: 0–100 where 100 = maximum effort (penalty).
     */
    private const TYPE_EFFORT_MAP = [
        'boolean' => 10,
        'select' => 20,
        'number' => 30,
        'multiselect' => 40,
        'text' => 50,
        'textarea' => 70,
    ];

    /**
     * High-value field slugs for SEO scoring.
     */
    private const SEO_VALUABLE_SLUGS = [
        'brut-metrekare', 'net-metrekare', 'oda-sayisi', 'salon-sayisi',
        'banyo-sayisi', 'bina-yasi', 'kat-sayisi', 'bulundugu-kat',
        'isitma-tipi', 'tapu-durumu', 'aidat', 'cephe',
    ];

    /**
     * High-value field slugs for conversion scoring.
     */
    private const CONVERSION_VALUABLE_SLUGS = [
        'brut-metrekare', 'net-metrekare', 'oda-sayisi', 'tapu-durumu',
        'aidat', 'bina-yasi', 'isitma-tipi', 'otopark', 'asansor',
        'balkon', 'teras', 'havuz', 'deniz-manzarasi', 'site-icerisinde',
    ];

    /**
     * Score a single candidate field.
     *
     * @param array $candidate Candidate from FieldGapAnalyzer
     * @param array $context Scoring context
     * @return array Scored candidate with dimensions
     */
    public function score(array $candidate, array $context = []): array
    {
        $dimensions = [
            'coverage_impact' => $this->scoreCoverageImpact($candidate, $context),
            'seo_impact' => $this->scoreSeoImpact($candidate),
            'conversion_impact' => $this->scoreConversionImpact($candidate),
            'user_effort_cost' => $this->scoreUserEffort($candidate),
            'data_quality_gain' => $this->scoreDataQuality($candidate),
            'confidence' => $this->scoreConfidence($candidate, $context),
        ];

        $totalScore = $this->calculateWeightedScore($dimensions);
        $priority = $this->determinePriority($totalScore);

        return [
            'feature_id' => $candidate['feature_id'] ?? null,
            'slug' => $candidate['slug'] ?? '',
            'name' => $candidate['name'] ?? '',
            'type' => $candidate['type'] ?? 'text', // context7-ignore
            'total_score' => $totalScore,
            'priority' => $priority,
            'dimensions' => $dimensions,
            'source' => $candidate['source'] ?? 'unknown',
        ];
    }

    /**
     * Score and sort multiple candidates.
     *
     * @param array $candidates Array of candidates from FieldGapAnalyzer
     * @param array $context Scoring context
     * @return array Scored and sorted candidates (highest score first)
     */
    public function scoreAll(array $candidates, array $context = []): array
    {
        $scored = array_map(
            fn ($candidate) => $this->score($candidate, $context),
            $candidates
        );

        usort($scored, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

        return $scored;
    }

    /**
     * Coverage impact: How much does adding this field improve listing completeness?
     *
     * Required fields score higher. Fields from a gap (missing from scope) score higher
     * when the current schema is small.
     */
    private function scoreCoverageImpact(array $candidate, array $context): int
    {
        $score = 30; // base

        // Required fields have higher coverage impact
        if (!empty($candidate['is_required'])) {
            $score += 40;
        }

        // If current schema has few fields, each new field has more impact
        $currentFieldCount = $context['current_field_count'] ?? 0;
        if ($currentFieldCount < 5) {
            $score += 20;
        } elseif ($currentFieldCount < 10) {
            $score += 10;
        }

        // Cross-scope popular fields (used in many listing types) have proven value
        $scopeCount = $candidate['scope_count'] ?? 0;
        if ($scopeCount >= 3) {
            $score += 10;
        } elseif ($scopeCount >= 2) {
            $score += 5;
        }

        return min(100, max(0, $score));
    }

    /**
     * SEO impact: Does this field improve search/filter capabilities?
     */
    private function scoreSeoImpact(array $candidate): int
    {
        $slug = $candidate['slug'] ?? '';
        $score = 20; // base

        if (in_array($slug, self::SEO_VALUABLE_SLUGS, true)) {
            $score += 50;
        }

        // Filterable fields type (select, boolean, number) are good for SEO
        $type = $candidate['type'] ?? 'text'; // context7-ignore
        if (in_array($type, ['select', 'boolean', 'number'])) {
            $score += 20;
        }

        // Searchable fields improve text-based discovery
        if (!empty($candidate['is_searchable'])) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * Conversion impact: Does this field help buyers make purchase decisions?
     */
    private function scoreConversionImpact(array $candidate): int
    {
        $slug = $candidate['slug'] ?? '';
        $score = 15; // base

        if (in_array($slug, self::CONVERSION_VALUABLE_SLUGS, true)) {
            $score += 55;
        }

        // Boolean "amenity" fields (balkon, asansor, havuz) have moderate conversion impact
        $type = $candidate['type'] ?? 'text'; // context7-ignore
        if ($type === 'boolean') {
            $score += 15;
        }

        // Fields with units typically represent measurable attributes buyers care about
        if (!empty($candidate['unit'])) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * User effort cost: How much work for the agent to fill this field?
     *
     * Inverted: high effort = LOW score (penalty dimension).
     */
    private function scoreUserEffort(array $candidate): int
    {
        $type = $candidate['type'] ?? 'text'; // context7-ignore
        $effortPenalty = self::TYPE_EFFORT_MAP[$type] ?? 50;

        // Invert: high effort = low score
        return 100 - $effortPenalty;
    }

    /**
     * Data quality gain: Does this field improve structural data quality?
     */
    private function scoreDataQuality(array $candidate): int
    {
        $score = 25; // base

        $type = $candidate['type'] ?? 'text'; // context7-ignore

        // Select/multiselect fields enforce controlled vocabulary → higher quality
        if (in_array($type, ['select', 'multiselect'])) {
            $score += 35;
        }

        // Number fields with units are measurable and comparable
        if ($type === 'number' && !empty($candidate['unit'])) {
            $score += 30;
        }

        // Boolean fields are unambiguous
        if ($type === 'boolean') {
            $score += 25;
        }

        // Required fields signal importance
        if (!empty($candidate['is_required'])) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * Confidence: How confident are we in this recommendation?
     *
     * Higher confidence when:
     *   - Feature comes from canonical library (vs ad-hoc)
     *   - Has cross-scope usage evidence
     *   - Has description/metadata
     */
    private function scoreConfidence(array $candidate, array $context): int
    {
        $score = 40; // base

        // Feature library source = higher confidence than ad-hoc
        $source = $candidate['source'] ?? 'unknown';
        if ($source === 'feature_library') {
            $score += 20;
        } elseif ($source === 'cross_scope_usage') {
            $score += 30; // even higher — proven usage
        }

        // Cross-scope count boosts confidence
        $scopeCount = $candidate['scope_count'] ?? 0;
        if ($scopeCount >= 3) {
            $score += 15;
        } elseif ($scopeCount >= 1) {
            $score += 5;
        }

        // Has description = better documented = higher confidence
        if (!empty($candidate['description'])) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * Calculate weighted total score from dimension scores.
     */
    private function calculateWeightedScore(array $dimensions): int
    {
        $total = 0.0;

        foreach (self::WEIGHTS as $dimension => $weight) {
            $total += ($dimensions[$dimension] ?? 0) * $weight;
        }

        return (int) round($total);
    }

    /**
     * Determine priority label from total score.
     */
    private function determinePriority(int $score): string
    {
        return match (true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 40 => 'medium',
            default => 'low',
        };
    }
}

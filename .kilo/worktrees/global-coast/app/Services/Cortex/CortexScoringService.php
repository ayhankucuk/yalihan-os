<?php

namespace App\Services\Cortex;

use App\Services\Ilan\IlanFeatureService;
use App\Services\Logging\LogService;

interface CortexScoringContract
{
    /**
     * Calculates Cortex Score for a listing based on filled feature values.
     *
     * @param int   $categoryId   IlanKategori ID
     * @param array $filledFields [feature_id => mixed value]
     *
     * @return array{
     *     score:int,
     *     breakdown:array,
     *     missing_critical:array,
     *     state:string
     * }
     */
    public function calculateScore(int $categoryId, array $filledFields): array;

    /**
     * Returns improvement suggestions based on missing / weak fields.
     *
     * @param int   $categoryId
     * @param array $filledFields
     *
     * @return array
     */
    public function getImprovementSuggestions(int $categoryId, array $filledFields): array;
}

class CortexScoringService implements CortexScoringContract
{
    private IlanFeatureService $featureService;

    public function __construct(IlanFeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    public function calculateScore(int $categoryId, array $filledFields): array
    {
        return $this->calculateNexusScore($categoryId, $filledFields);
    }

    /**
     * Core Nexus blueprint scoring (0–100) based on required/optional completion.
     */
    private function calculateNexusScore(int $categoryId, array $filledFields): array
    {
        // 1. Fetch blueprint from Cortex-Nexus engine
        $blueprint = $this->featureService->getFeaturesByCategory($categoryId);

        $requiredTotal = 0;
        $requiredFilled = 0;
        $optionalTotal = 0;
        $optionalFilled = 0;
        $missingCritical = [];

        $groups = $blueprint['feature_categories'] ?? [];

        foreach ($groups as $group) {
            $features = $group['features'] ?? [];

            foreach ($features as $feature) {
                $id = $feature['id'] ?? null;
                if ($id === null) {
                    continue;
                }

                $isRequired = !empty($feature['required']);
                $isFilled = $this->isFieldFilled($id, $filledFields);

                if ($isRequired) {
                    $requiredTotal++;
                    if ($isFilled) {
                        $requiredFilled++;
                    } else {
                        $missingCritical[] = $feature['name'] ?? ('#'.$id);
                    }
                } else {
                    $optionalTotal++;
                    if ($isFilled) {
                        $optionalFilled++;
                    }
                }
            }
        }

        // 2. Weighted scoring (Cortex Algorithm v1)
        $requiredWeight = 70;
        $optionalWeight = 30;

        $requiredScore = $requiredTotal > 0
            ? ($requiredFilled / $requiredTotal) * $requiredWeight
            : $requiredWeight;

        $optionalScore = $optionalTotal > 0
            ? ($optionalFilled / $optionalTotal) * $optionalWeight
            : $optionalWeight;

        $totalScore = (int) round($requiredScore + $optionalScore);
        $totalScore = max(0, min(100, $totalScore));

        $state = $this->resolveState($totalScore);

        LogService::debug('CortexScoringService.calculateScore', [
            'category_id' => $categoryId,
            'score' => $totalScore,
            'required_total' => $requiredTotal,
            'required_filled' => $requiredFilled,
            'optional_total' => $optionalTotal,
            'optional_filled' => $optionalFilled,
            'state' => $state, // context7-ignore
        ]);

        return [
            'score' => $totalScore,
            'breakdown' => [
                'required_filled' => $requiredTotal > 0
                    ? sprintf('%d/%d', $requiredFilled, $requiredTotal)
                    : '0/0',
                'optional_filled' => $optionalTotal > 0
                    ? sprintf('%d/%d', $optionalFilled, $optionalTotal)
                    : '0/0',
            ],
            'missing_critical' => $missingCritical,
            'state' => $state, // context7-ignore
        ];
    }

    /**
     * Full Cortex Algorithm v2 scoring: blueprint + photos + description.
     */
    public function calculateFullScore(int $categoryId, array $filledFields, int $photoCount, string $description): array
    {
        $weights = config('cortex.weights', [
            'nexus' => 60,
            'visual' => 25,
            'content' => 15,
        ]);

        // 1) Nexus (blueprint) score: normalize 0–100 → 0–weights['nexus']
        $nexus = $this->calculateNexusScore($categoryId, $filledFields);
        $nexusPoints = ($nexus['score'] / 100) * ($weights['nexus'] ?? 60);

        // 2) Visual score (photos)
        $visualPoints = $this->resolvePhotoPoints($photoCount, $weights['visual'] ?? 25);

        // 3) Content score (description length)
        $contentPoints = $this->resolveContentPoints($description, $weights['content'] ?? 15);

        $totalScore = (int) round($nexusPoints + $visualPoints + $contentPoints);
        $totalScore = max(0, min(100, $totalScore));

        $state = $this->resolveState($totalScore);

        LogService::debug('CortexScoringService.calculateFullScore', [
            'category_id' => $categoryId,
            'score' => $totalScore,
            'nexus_points' => $nexusPoints,
            'visual_points' => $visualPoints,
            'content_points' => $contentPoints,
            'state' => $state, // context7-ignore
        ]);

        return [
            'score' => $totalScore,
            'state' => $state, // context7-ignore
            'metrics' => [
                'blueprint' => (int) round($nexusPoints),
                'visuals' => (int) round($visualPoints),
                'content' => (int) round($contentPoints),
            ],
            'missing_critical' => $nexus['missing_critical'],
        ];
    }

    private function resolvePhotoPoints(int $photoCount, int $maxWeight): int
    {
        $config = config('cortex.thresholds.photo', []);
        $min = $config['min']['count'] ?? 5;
        $minPoints = $config['min']['points'] ?? min(15, $maxWeight);
        $max = $config['max']['count'] ?? 10;
        $maxPoints = $config['max']['points'] ?? $maxWeight;

        if ($photoCount >= $max) {
            return $maxPoints;
        }

        if ($photoCount >= $min) {
            return $minPoints;
        }

        return 0;
    }

    private function resolveContentPoints(string $description, int $maxWeight): int
    {
        $len = mb_strlen($description ?? '');
        $config = config('cortex.thresholds.description', []);
        $shortLen = $config['short']['len'] ?? 100;
        $shortPoints = $config['short']['points'] ?? min(7, $maxWeight);
        $longLen = $config['long']['len'] ?? 300;
        $longPoints = $config['long']['points'] ?? $maxWeight;

        if ($len > $longLen) {
            return $longPoints;
        }

        if ($len > $shortLen) {
            return $shortPoints;
        }

        return 0;
    }

    private function isFieldFilled(int $featureId, array $filledFields): bool
    {
        if (!array_key_exists($featureId, $filledFields)) {
            return false;
        }

        $value = $filledFields[$featureId];

        // Consider null or empty string as not filled
        if ($value === null || $value === '') {
            return false;
        }

        // 0, false, and other scalar values are considered valid entries
        return true;
    }

    private function resolveState(int $score): string
    {
        $limits = config('cortex.status_limits', [ // context7-ignore
            'excellent' => 90,
            'good' => 70,
            'average' => 40,
        ]);

        if ($score >= ($limits['excellent'] ?? 90)) {
            return 'excellent';
        }

        if ($score >= ($limits['good'] ?? 70)) {
            return 'good';
        }

        if ($score >= ($limits['average'] ?? 40)) {
            return 'average';
        }

        return 'poor';
    }

    /**
     * Returns improvement suggestions based on missing or weak fields.
     *
     * @param int   $categoryId
     * @param array $filledFields
     *
     * @return array
     */
    public function getImprovementSuggestions(int $categoryId, array $filledFields): array
    {
        $result = $this->calculateNexusScore($categoryId, $filledFields);
        $suggestions = [];

        // Add suggestions for missing critical fields
        if (! empty($result['missing_critical'])) {
            foreach ($result['missing_critical'] as $field) {
                $suggestions[] = [
                    'type' => 'critical', // context7-ignore
                    'field' => $field,
                    'message' => "{$field} alanı kritik ve doldurulmalıdır.",
                    'impact' => 'high',
                ];
            }
        }

        // Add suggestions based on score state
        if ($result['state'] === 'poor') { // context7-ignore
            $suggestions[] = [
                'type' => 'general', // context7-ignore
                'message' => 'Skorunuz düşük. Daha fazla özellik ekleyerek iyileştirebilirsiniz.',
                'impact' => 'high',
            ];
        } elseif ($result['state'] === 'average') { // context7-ignore
            $suggestions[] = [
                'type' => 'general', // context7-ignore
                'message' => 'İyi bir skorunuz var. Tavsiye edilen alanları doldurarak mükemmel seviyeye çıkabilirsiniz.',
                'impact' => 'medium',
            ];
        }

        return $suggestions;
    }
}

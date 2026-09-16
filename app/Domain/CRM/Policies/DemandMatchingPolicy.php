<?php

namespace App\Domain\CRM\Policies;

/**
 * DemandMatchingPolicy — Domain Policy
 *
 * Encapsulates scoring weights and classification thresholds for demand matching.
 */
class DemandMatchingPolicy
{
    public const WEIGHT_LOCATION = 40;
    public const WEIGHT_BUDGET = 35;
    public const WEIGHT_PROPERTY_TYPE = 25;

    public const THRESHOLD_IGNORE_MAX = 49;
    public const THRESHOLD_WEAK_MIN = 50;
    public const THRESHOLD_WEAK_MAX = 69;
    public const THRESHOLD_GOOD_MIN = 70;
    public const THRESHOLD_GOOD_MAX = 84;
    public const THRESHOLD_STRONG_MIN = 85;

    public const MIN_ACCEPTABLE_SCORE = 50;

    public function __construct(
        public readonly int $locationWeight = self::WEIGHT_LOCATION,
        public readonly int $budgetWeight = self::WEIGHT_BUDGET,
        public readonly int $propertyTypeWeight = self::WEIGHT_PROPERTY_TYPE,
        public readonly int $minScoreThreshold = self::MIN_ACCEPTABLE_SCORE,
    ) {}

    /**
     * Determine the qualitative match level based on score.
     */
    public function classifyLevel(int $score): string
    {
        return match (true) {
            $score >= self::THRESHOLD_STRONG_MIN => 'STRONG',
            $score >= self::THRESHOLD_GOOD_MIN   => 'GOOD',
            $score >= self::THRESHOLD_WEAK_MIN   => 'WEAK',
            default                              => 'IGNORE',
        };
    }

    /**
     * Check if the score meets the minimum threshold for action.
     */
    public function isMatch(int $score): bool
    {
        return $score >= $this->minScoreThreshold;
    }
}

<?php

namespace App\Services\AI\Optimization;

use Illuminate\Support\Collection;

/**
 * Provider Score Calculator
 *
 * Yalıhan Cortex 11.3 - Provider Optimization v3
 *
 * Weighted decision engine for selecting the optimal AI provider
 * based on ROI, Latency, and Cost metrics.
 *
 * Formula:
 * Score = (ROI * 0.5) + (LatencyScore * 0.3) + (CostScore * 0.2)
 */
class ProviderScoreCalculator
{
    // Weights
    protected const WEIGHT_ROI = 0.5;
    protected const WEIGHT_LATENCY = 0.3;
    protected const WEIGHT_COST = 0.2;

    /**
     * Calculate scores for valid candidates.
     *
     * @param array $candidates Array of providers with metrics:
     *                          ['provider_name' => ['roi' => 0.95, 'latency' => 500 (ms), 'cost' => 0.002 ($)]]
     * @param string $taskType Optional task context (e.g. 'creative', 'factual')
     * @return array Sorted array: ['provider' => score] (Descending)
     */
    public function calculateScores(array $candidates, string $taskType = 'general'): array
    {
        $scored = [];

        foreach ($candidates as $provider => $metrics) {
            $roi = $metrics['roi'] ?? 0.5; // Default average
            $latency = $metrics['latency'] ?? 1000; // Default 1s
            $cost = $metrics['cost'] ?? 0.01; // Default low cost

            // Normalize Metrics
            // 1. ROI is already 0.0 - 1.0 (Higher is better)
            $roiScore = $roi;

            // 2. Latency Score (Lower is better)
            // 200ms -> 1.0, 2000ms -> 0.1
            // Formula: 1000 / (latency_ms + 100)
            $latencyScore = 1000 / ($latency + 100);
            $latencyScore = min(1.0, $latencyScore); // Cap at 1.0

            // 3. Cost Score (Lower is better)
            // 0.0 (Local) -> 1.0
            // Formula: 1 / (1 + (cost * 100))
            // Example: $0.00 -> 1.0, $0.01 -> 0.5, $0.10 -> 0.09
            $costScore = 1 / (1 + ($cost * 100));

            // Weighted Sum
            $finalScore = ($roiScore * self::WEIGHT_ROI) +
                          ($latencyScore * self::WEIGHT_LATENCY) +
                          ($costScore * self::WEIGHT_COST);

            $scored[$provider] = round($finalScore, 4);
        }

        // Sort descending (Best first)
        arsort($scored);

        return $scored;
    }

    /**
     * Get the best provider from scored list.
     *
     * @param array $scores
     * @return string|null
     */
    public function getBestProvider(array $scores): ?string
    {
        return array_key_first($scores);
    }

    /**
     * Determine Fallback Chain
     *
     * @param array $scores
     * @return array List of providers in fallback sequence
     */
    public function getFallbackChain(array $scores): array
    {
        return array_keys($scores);
    }
}

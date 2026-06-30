<?php

namespace App\Services\AI;

class ProviderScoringEngine
{
    /**
     * Calculate normalized score (0-1) for a provider based on metrics and weights
     */
    public function calculateScore(array $metrics, array $weights): float
    {
        // 1. Acceptance Rate (Higher is better)
        $sAccept = $metrics['accept_rate'] ?? 0;

        // 2. Latency (Lower is better, normalized against max expected 5000ms)
        $latency = $metrics['avg_latency_ms'] ?? 5000;
        $sLatency = 1 - ($latency / 5000);
        $sLatency = max(0, min(1, $sLatency));

        // 3. Cost (Lower is better, normalized against max expected 0.1 USD per request)
        $cost = $metrics['avg_cost_usd'] ?? 0.1;
        $sCost = 1 - ($cost / 0.1);
        $sCost = max(0, min(1, $sCost));

        // 4. Error Rate (Lower is better)
        $sError = 1 - ($metrics['error_rate'] ?? 1);
        $sError = max(0, min(1, $sError));

        // 5. Cache Hit Rate (Higher is better)
        $sCache = $metrics['cache_hit_rate'] ?? 0;

        // Final Weighted Score
        $totalScore = (
            ($sAccept * $weights['accept_rate']) +
            ($sLatency * $weights['latency']) +
            ($sCost * $weights['cost']) +
            ($sError * $weights['error']) +
            ($sCache * $weights['cache'])
        );

        return round($totalScore, 3);
    }

    /**
     * Get weights based on category context
     */
    public function getWeights(?int $categoryId): array
    {
        $categorySlug = $this->getCategorySlug($categoryId);
        $overrides = config('provider-optimization.weights.overrides', []);
        
        return $overrides[$categorySlug] ?? config('provider-optimization.weights.default');
    }

    private function getCategorySlug(?int $categoryId): string
    {
        if (!$categoryId) return 'unknown';

        $map = [
            1 => 'konut',
            2 => 'isyeri',
            3 => 'arsa',
            4 => 'turistik',
            5 => 'yazlik',
        ];

        return $map[$categoryId] ?? 'other';
    }
}

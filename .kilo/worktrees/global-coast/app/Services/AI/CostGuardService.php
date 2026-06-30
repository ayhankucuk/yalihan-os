<?php

namespace App\Services\AI;

use App\Models\AiLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * AI Cost Guard Service
 * 
 * Context7: Financial Security Guard
 * Purpose: Prevent AI cost explosions by enforcing daily limits and cost tracking
 */
class CostGuardService
{
    /**
     * Daily analysis limit per user
     */
    protected int $dailyAnalysisLimit = 20;

    /**
     * Daily cost limit (in USD)
     */
    protected float $dailyCostLimit = 100.00;

    /**
     * Estimated cost per analysis (in USD)
     */
    protected float $estimatedCostPerAnalysis = 0.05;

    /**
     * Check if user can perform AI analysis
     * 
     * @param int|null $userId
     * @return array ['allowed' => bool, 'reason' => string, 'remaining' => int]
     */
    public function checkDailyLimit(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return [
                'allowed' => false,
                'reason' => 'Kullanıcı kimlik doğrulaması gerekli',
                'remaining' => 0,
            ];
        }

        $todayCount = $this->getTodayAnalysisCount($userId);
        $remaining = max(0, $this->dailyAnalysisLimit - $todayCount);

        if ($todayCount >= $this->dailyAnalysisLimit) {
            return [
                'allowed' => false,
                'reason' => "Günlük analiz limitine ulaştınız ({$this->dailyAnalysisLimit} analiz/gün). Yarın tekrar deneyebilirsiniz.",
                'remaining' => 0,
                'limit' => $this->dailyAnalysisLimit,
                'used' => $todayCount,
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'OK',
            'remaining' => $remaining,
            'limit' => $this->dailyAnalysisLimit,
            'used' => $todayCount,
        ];
    }

    /**
     * Check daily cost limit
     * 
     * @return array ['allowed' => bool, 'reason' => string, 'current_cost' => float, 'limit' => float]
     */
    public function checkDailyCostLimit(): array
    {
        $todayCost = $this->getTodayCost();
        $remaining = max(0, $this->dailyCostLimit - $todayCost);

        if ($todayCost >= $this->dailyCostLimit) {
            return [
                'allowed' => false,
                'reason' => "Günlük maliyet limitine ulaşıldı (\${$this->dailyCostLimit}/gün). Yarın tekrar deneyebilirsiniz.",
                'current_cost' => round($todayCost, 2),
                'limit' => $this->dailyCostLimit,
                'remaining' => 0,
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'OK',
            'current_cost' => round($todayCost, 2),
            'limit' => $this->dailyCostLimit,
            'remaining' => round($remaining, 2),
        ];
    }

    /**
     * Estimate cost for batch operation
     * 
     * @param int $itemCount
     * @param string $model
     * @return array
     */
    public function estimateBatchCost(int $itemCount, string $model = 'gpt-4'): array
    {
        $prices = [
            'gpt-4' => ['input' => 0.03, 'output' => 0.06],
            'gpt-3.5-turbo' => ['input' => 0.0015, 'output' => 0.002],
            'claude-3-sonnet' => ['input' => 0.003, 'output' => 0.015],
            'gemini-pro' => ['input' => 0.00025, 'output' => 0.0005],
        ];

        $avgInputTokens = 500;
        $avgOutputTokens = 1000;

        $modelPrices = $prices[$model] ?? $prices['gpt-4'];

        $inputCost = ($itemCount * $avgInputTokens / 1000) * $modelPrices['input'];
        $outputCost = ($itemCount * $avgOutputTokens / 1000) * $modelPrices['output'];
        $totalCost = $inputCost + $outputCost;

        return [
            'item_count' => $itemCount,
            'estimated_cost' => round($totalCost, 2),
            'input_cost' => round($inputCost, 2),
            'output_cost' => round($outputCost, 2),
            'model' => $model,
            'formatted' => '$' . number_format($totalCost, 2),
        ];
    }

    /**
     * Get estimated cost for single analysis
     * 
     * @return float
     */
    public function getEstimatedCostPerAnalysis(): float
    {
        return $this->estimatedCostPerAnalysis;
    }

    /**
     * Get today's analysis count for user
     * 
     * @param int $userId
     * @return int
     */
    protected function getTodayAnalysisCount(int $userId): int
    {
        $cacheKey = "ai_cost_guard:daily_count:{$userId}:" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, now()->endOfDay(), function () use ($userId) {
            return AiLog::where('user_id', $userId)
                ->whereDate('created_at', today())
                ->whereIn('action', ['analyze', 'generate', 'suggest'])
                ->count();
        });
    }

    /**
     * Get today's total cost
     * 
     * @return float
     */
    protected function getTodayCost(): float
    {
        $cacheKey = 'ai_cost_guard:daily_cost:' . now()->format('Y-m-d');

        return Cache::remember($cacheKey, now()->endOfDay(), function () {
            return (float) AiLog::whereDate('created_at', today())
                ->sum('cost');
        });
    }

    /**
     * Increment user's daily analysis count
     * 
     * @param int $userId
     * @return void
     */
    public function incrementDailyCount(int $userId): void
    {
        $cacheKey = "ai_cost_guard:daily_count:{$userId}:" . now()->format('Y-m-d');
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, now()->endOfDay());
    }
}


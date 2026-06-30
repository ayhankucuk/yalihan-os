<?php

namespace App\Services\Admin;

use App\Models\AiLog;
use Illuminate\Support\Carbon;

/**
 * 🛡️ SAB SEALED
 * Domain: Admin / Analytics
 * Purpose: Abstract AiLog database queries for analytics from the controller.
 */
class AiLogService
{
    /**
     * Get comprehensive AI analytics for a given period.
     */
    public function getAnalytics(?Carbon $since): array
    {
        $query = AiLog::query();
        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        // Genel İstatistikler
        $total = (clone $query)->count();
        $successful = (clone $query)->where('calisma_durumu', 'success')->count();
        $failed = (clone $query)->whereIn('calisma_durumu', ['failed', 'error', 'timeout'])->count();
        $successRate = $total > 0 ? round(($successful / $total) * 100, 2) : 0;
        $avgResponseTime = (clone $query)->where('calisma_durumu', 'success')->avg('duration_ms') ?? 0;
        $totalCost = 0; // Cost field not in current schema
        $totalTokens = (clone $query)->sum('total_tokens') ?? 0;

        // Provider Bazlı İstatistikler
        $providerUsage = $this->getProviderUsage($since);

        // Model Bazlı İstatistikler
        $modelUsage = $this->getModelUsage($query, $since);

        // Request Type Bazlı İstatistikler
        $requestTypeUsage = $this->getRequestTypeUsage($query);

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'successRate' => $successRate,
            'avgResponseTime' => $avgResponseTime,
            'totalCost' => $totalCost,
            'totalTokens' => $totalTokens,
            'providerUsage' => $providerUsage,
            'modelUsage' => $modelUsage,
            'requestTypeUsage' => $requestTypeUsage,
        ];
    }

    private function getProviderUsage(?Carbon $since): array
    {
        $baseQuery = AiLog::query();
        if ($since) {
            $baseQuery->where('created_at', '>=', $since);
        }

        return $baseQuery
            ->selectRaw('provider, count(*) as total')
            ->groupBy('provider')
            ->get()
            ->map(function ($item) use ($since) {
                $providerQuery = AiLog::where('provider', $item->provider);
                if ($since) {
                    $providerQuery->where('created_at', '>=', $since);
                }

                $providerTotal = $providerQuery->count();
                $providerSuccess = $providerQuery->where('calisma_durumu', 'success')->count();
                $providerAvgTime = $providerQuery->where('calisma_durumu', 'success')->avg('duration_ms') ?? 0;
                $providerCost = 0;

                return [
                    'provider' => $item->provider,
                    'total' => $providerTotal,
                    'success' => $providerSuccess,
                    'failed' => $providerTotal - $providerSuccess,
                    'success_rate' => $providerTotal > 0 ? round(($providerSuccess / $providerTotal) * 100, 2) : 0,
                    'avg_response_time' => round($providerAvgTime, 2),
                    'total_cost' => round($providerCost, 6),
                ];
            })
            ->values()
            ->toArray();
    }

    private function getModelUsage($query, ?Carbon $since): array
    {
        return (clone $query)
            ->whereNotNull('model')
            ->selectRaw('model, count(*) as total')
            ->groupBy('model')
            ->orderByDesc('total') // context7-ignore
            ->limit(10)
            ->get()
            ->map(function ($item) use ($since) {
                $modelQuery = AiLog::where('model', $item->model);
                if ($since) {
                    $modelQuery->where('created_at', '>=', $since);
                }

                $modelTotal = $modelQuery->count();
                $modelSuccess = $modelQuery->where('calisma_durumu', 'success')->count();

                return [
                    'model' => $item->model,
                    'total' => $modelTotal,
                    'success' => $modelSuccess,
                    'success_rate' => $modelTotal > 0 ? round(($modelSuccess / $modelTotal) * 100, 2) : 0,
                ];
            })
            ->values()
            ->toArray();
    }

    private function getRequestTypeUsage($query): array
    {
        return (clone $query)
            ->whereNotNull('request_type')
            ->selectRaw('request_type, count(*) as total')
            ->groupBy('request_type')
            ->orderByDesc('total') // context7-ignore
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->request_type, // context7-ignore
                    'total' => $item->total,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get daily trend for the last 7 days.
     */
    public function getDailyTrend(): array
    {
        $dailyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $dayTotal = AiLog::whereDate('olusturma_tarihi', $date)->count();
            $daySuccess = AiLog::whereDate('olusturma_tarihi', $date)->where('calisma_durumu', 'success')->count();

            $dailyTrend[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('d M'),
                'total' => $dayTotal,
                'success' => $daySuccess,
                'failed' => $dayTotal - $daySuccess,
            ];
        }
        return $dailyTrend;
    }
}

<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\AI\TelemetryAggregator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AITelemetryController extends \App\Http\Controllers\Controller
{
    public function __construct(
        private readonly TelemetryAggregator $aggregator
    ) {}

    public function index()
    {
        return view('admin.ai.telemetry.dashboard');
    }

    public function getMetrics(Request $request): JsonResponse
    {
        $metrics = $this->aggregator->getDashboardMetrics();
        return response()->json([
            'success' => true,
            'data' => $metrics,
            'filters' => [
                'period' => $request->get('period', '24h'),
                'provider' => $request->get('provider'),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function getCostOverview(Request $request): JsonResponse
    {
        $hours = match($request->get('period', '24h')) {
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        $data = $this->aggregator->getCostTimeline($hours);
        $totalCost = $data->sum('maliyet');
        $dailyLimit = config('services.ai.daily_limit_usd', 10.0);

        return response()->json([
            'success' => true,
            'data' => [
                'timeline' => $data,
                'total_cost' => round($totalCost, 4),
                'daily_limit' => $dailyLimit,
                'usage_percent' => $dailyLimit > 0 ? round(($totalCost / $dailyLimit) * 100, 2) : 0,
            ]
        ]);
    }

    public function getProviderPerformance(): JsonResponse
    {
        $data = $this->aggregator->getProviderPerformanceBreakdown()->map(function ($row) {
            return [
                'provider' => $row->provider_adi,
                'requests' => $row->total_requests,
                'basari_orani' => $row->total_requests > 0
                    ? round(($row->successful_requests / $row->total_requests) * 100, 2)
                    : 0,
                'avg_latency_ms' => (int) $row->avg_latency,
                'cost_usd' => round($row->total_cost, 4),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getRequestVolume(Request $request): JsonResponse
    {
        $hours = match($request->get('period', '24h')) {
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        return response()->json([
            'success' => true,
            'data' => $this->aggregator->getVolumeTimeline($hours)
        ]);
    }

    public function getErrorAnalytics(): JsonResponse
    {
        $analytics = $this->aggregator->getErrorAnalyticsData();
        $errorRate = $analytics['total_requests'] > 0
            ? round(($analytics['total_errors'] / $analytics['total_requests']) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'error_rate_percent' => $errorRate,
                'total_errors' => $analytics['total_errors'],
                'recent_errors' => $analytics['recent_errors'],
            ]
        ]);
    }

    public function getTokenLeaderboard(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->aggregator->getTokenLeaderboard()
        ]);
    }
}

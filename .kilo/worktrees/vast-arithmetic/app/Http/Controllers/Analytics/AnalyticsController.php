<?php

namespace App\Http\Controllers\Analytics;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsMetricsService;
use App\Services\Analytics\AnalyticsReportsService;
use App\Services\Analytics\AnalyticsDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * AnalyticsController
 *
 * Phase 6: Analytics Dashboard & Reporting
 * Context7 Compliance: Uses canonical fields throughout
 */
class AnalyticsController extends Controller
{
    private AnalyticsMetricsService $metricsService;
    private AnalyticsReportsService $reportsService;
    private AnalyticsDashboardService $dashboardService;

    public function __construct(
        AnalyticsMetricsService $metricsService,
        AnalyticsReportsService $reportsService,
        AnalyticsDashboardService $dashboardService
    ) {
        $this->metricsService = $metricsService;
        $this->reportsService = $reportsService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * GET /api/v1/analytics/metrics/{ilanId}
     * Get all metrics for a property listing
     */
    public function getMetrics($ilanId): JsonResponse
    {
        try {
            $metriksler = $this->metricsService->getAllMetrics($ilanId);

            return response()->json([
                'success' => true,
                'data' => $metriksler,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Metrik hesaplaması başarısız',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/analytics/engagement/{ilanId}
     * Get engagement metrics for a listing
     */
    public function getEngagementMetrics($ilanId): JsonResponse
    {
        try {
            $engagement = $this->metricsService->calculateEngagementMetrics($ilanId);

            return response()->json([
                'success' => true,
                'data' => $engagement,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Engagement metriği hesaplanamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/analytics/market-competitiveness/{ilanId}
     * Get market competitiveness score
     */
    public function getMarketCompetitiveness($ilanId, Request $request): JsonResponse
    {
        try {
            $radius = $request->input('radius', 2);
            $market = $this->metricsService->calculateMarketCompetitiveness($ilanId, $radius);

            return response()->json([
                'success' => true,
                'data' => $market,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Pazar rekabetçiliği hesaplanamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/analytics/roi/{ilanId}
     * Get ROI potential for a land property
     */
    public function getROIPotential($ilanId): JsonResponse
    {
        try {
            $roi = $this->metricsService->calculateROIPotential($ilanId);

            return response()->json([
                'success' => true,
                'data' => $roi,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'ROI potansiyeli hesaplanamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/analytics/dashboard/summary
     * Get dashboard summary for authenticated user
     */
    public function getDashboardSummary(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $summary = $this->dashboardService->getDashboardSummary($userId);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Dashboard özeti alınamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/analytics/dashboard/widgets
     * Get dashboard widget data
     */
    public function getWidgetData(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $widgets = $this->dashboardService->getWidgetData($userId);

            return response()->json([
                'success' => true,
                'data' => $widgets,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Widget verileri alınamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

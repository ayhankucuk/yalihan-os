<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsDashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the analytics dashboard
     */
    public function index()
    {
        $dashboardData = $this->analyticsService->getDashboardData();
        $complianceSummary = $this->analyticsService->getComplianceSummary();
        $velocityMetrics = $this->analyticsService->getVelocityMetrics();

        return view('admin.analytics.dashboard', compact(
            'dashboardData',
            'complianceSummary',
            'velocityMetrics'
        ));
    }

    /**
     * Get real-time dashboard data via AJAX
     */
    public function getData()
    {
        return response()->json([
            'dashboard' => $this->analyticsService->getDashboardData(),
            'compliance' => $this->analyticsService->getComplianceSummary(),
            'velocity' => $this->analyticsService->getVelocityMetrics(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get Context7 compliance trends
     */
    public function complianceTrends(Request $request)
    {
        $days = $request->input('days', 7);
        $summary = $this->analyticsService->getComplianceSummary($days);

        return response()->json($summary);
    }

    /**
     * Get development velocity chart data
     */
    public function velocityChart(Request $request)
    {
        $days = $request->input('days', 7);
        $metrics = $this->analyticsService->getVelocityMetrics($days);

        return response()->json($metrics);
    }

    /**
     * Force recalculate project health
     */
    public function recalculateHealth()
    {
        try {
            $snapshot = $this->analyticsService->calculateProjectHealth();

            return response()->json([
                'success' => true,
                'message' => 'Proje sağlığı yeniden hesaplandı',
                'health' => [
                    'overall_score' => $snapshot->overall_health_score,
                    'durum' => $snapshot->health_status,
                    'snapshot_at' => $snapshot->snapshot_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hesaplama sırasında hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }
}

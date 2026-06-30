<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Domain\PropertyHub\Resiliency\CircuitBreaker;
use App\Http\Controllers\Controller;
use App\Models\PropertyEngineShadowEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShadowDashboardController extends Controller
{
    public function __construct(
        private readonly CircuitBreaker $circuitBreaker
    ) {}

    /**
     * Render the Shadow Mode Dashboard.
     */
    public function index(): View
    {
        return view('admin.propertyhub.shadow-dashboard');
    }

    /**
     * Get aggregate statistics for the dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $hours = (int) $request->get('hours', 24);
        $since = now()->subHours($hours);

        // Overall stats
        $stats = PropertyEngineShadowEvent::where('created_at', '>=', $since)
            ->select([
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN match = 1 THEN 1 ELSE 0 END) as match_count'),
                DB::raw('SUM(CASE WHEN match = 0 THEN 1 ELSE 0 END) as mismatch_count'),
                DB::raw('AVG(latency_ms_v2) as avg_latency_v2'),
                DB::raw('AVG(latency_ms_v3) as avg_latency_v3'),
                DB::raw('SUM(CASE WHEN error_v3 IS NOT NULL THEN 1 ELSE 0 END) as error_count'),
            ])
            ->first();

        // Mismatch rate over time (hourly)
        $trends = PropertyEngineShadowEvent::where('created_at', '>=', $since)
            ->select([
                DB::raw("strftime('%Y-%m-%d %H:00', created_at) as hour"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN match = 0 THEN 1 ELSE 0 END) as mismatches')
            ])
            ->groupBy('hour')
            ->orderBy('hour') // context7-ignore
            ->get();

        // Drift by Template (Top 10 problematic templates)
        $templateDrift = PropertyEngineShadowEvent::where('created_at', '>=', $since)
            ->where('match', false)
            ->select([
                'template_id_v2',
                DB::raw('COUNT(*) as drift_count')
            ])
            ->groupBy('template_id_v2')
            ->orderByDesc('drift_count') // context7-ignore
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'total' => (int) $stats->total_count,
                'matches' => (int) $stats->match_count,
                'mismatches' => (int) $stats->mismatch_count,
                'match_rate' => $stats->total_count > 0 ? round(($stats->match_count / $stats->total_count) * 100, 2) : 100,
                'avg_latency_v2' => round((float) $stats->avg_latency_v2, 2),
                'avg_latency_v3' => round((float) $stats->avg_latency_v3, 2),
                'errors' => (int) $stats->error_count,
                'circuit_state' => $this->circuitBreaker->isDisabled() ? 'TRIPPED' : 'HEALTHY',
                'mismatch_slope' => round($this->circuitBreaker->getSlope('mismatch') * 100, 2) . '%',
            ],
            'trends' => $trends,
            'template_drift' => $templateDrift,
        ]);
    }

    /**
     * Get recent mismatch details.
     */
    public function recentMismatches(): JsonResponse
    {
        $mismatches = PropertyEngineShadowEvent::where('match', false)
            ->orderByDesc('created_at') // context7-ignore
            ->limit(50)
            ->get();

        return response()->json($mismatches);
    }
}

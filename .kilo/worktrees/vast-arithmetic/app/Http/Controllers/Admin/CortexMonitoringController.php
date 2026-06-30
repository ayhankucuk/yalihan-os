<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\CortexMonitoringService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * Phase K: Cortex Monitoring Controller
 *
 * Operational monitoring dashboard for AI workflows
 * Context7: Read-only, observer mode, no mutations
 */
class CortexMonitoringController extends Controller
{
    protected CortexMonitoringService $monitoringService;

    public function __construct(CortexMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * HTML dashboard
     */
    public function index(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = $this->monitoringService->getMetrics($hours);
        $publishMetrics = $this->monitoringService->getPublishMetrics($hours);

        return view('admin.ai.monitoring', compact('metrics', 'publishMetrics'));
    }

    /**
     * JSON endpoint (for external monitoring)
     */
    public function json(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = $this->monitoringService->getMetrics($hours);
        $publishMetrics = $this->monitoringService->getPublishMetrics($hours);

        return ResponseService::success([
            'general' => $metrics,
            'publish' => $publishMetrics,
        ], 'Monitoring data retrieved');
    }
}

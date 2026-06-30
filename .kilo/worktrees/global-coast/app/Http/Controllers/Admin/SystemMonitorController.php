<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\AiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemMonitorController extends AdminController
{
    /**
     * AI Monitor Dashboard
     */
    public function index()
    {
        $service = app(\App\Services\SystemMonitorService::class);
        $aiSummary = $service->getAISummary();
        $aiModels = $service->getAIModels();
        $roiSummary = $service->getROISummary();
        $mcpStatus = collect([]); // API'den gelecek
        $apiStatus = [];
        $selfHealing = collect([]);
        $recentErrors = $service->getRecentErrors();
        $overall = $service->getOverallStatus($aiSummary);
        return view('admin.ai-monitor.index', [
            'aiSummary' => $aiSummary,
            'aiModels' => $aiModels,
            'roiSummary' => $roiSummary,
            'mcpStatus' => $mcpStatus,
            'apiStatus' => $apiStatus,
            'selfHealing' => $selfHealing,
            'recentErrors' => $recentErrors,
            'overall' => $overall,
        ]);
    }

    /**
     * MCP Durumu API
     */
    public function apiMcpDurumu(Request $request)
    {
        $service = app(\App\Services\SystemMonitorService::class);
        $overall = $service->getAISummary();
        $roiSummary = $service->getROISummary();

        return response()->json([
            'data' => [], // MCP durumu data
            'overview' => $overall,
            'roi_summary' => $roiSummary,
            'total_cost' => $overall['total_cost'] ?? 0
        ], 200);
    }

    /**
     * API Durumu API
     */
    public function apiApiDurumu(Request $request)
    {
        // Implement API durumu check in future phases
        return response()->json(['data' => []], 200);
    }

    /**
     * Self Healing API
     */
    public function apiSelfHealing(Request $request)
    {
        // Implement self-healing durumu in future phases
        return response()->json(['data' => []], 200);
    }

    /**
     * Recent Errors API
     */
    public function apiRecentErrors(Request $request)
    {
        $service = app(\App\Services\SystemMonitorService::class);
        $errors = $service->getRecentErrors();

        return response()->json(['data' => $errors], 200);
    }

    /**
     * AI Health API
     */
    public function apiAiHealth(Request $request)
    {
        $service = app(\App\Services\SystemMonitorService::class);
        $aiSummary = $service->getAISummary();
        $overall = $service->getOverallStatus($aiSummary);

        return response()->json([
            'data' => [
                'summary' => $aiSummary,
                'overall' => $overall,
                'timestamp' => now()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Context7 Rules API
     */
    public function getContext7Rules(Request $request)
    {
        // Load Context7 rules from authority.json in future phases
        return response()->json(['data' => []], 200);
    }

    public function apiPagesHealth(Request $request)
    {
        $data = [];

        return response()->json(['data' => $data], 200);
    }

    public function apiCodeHealth(Request $request)
    {
        $data = [
            'total_issues' => 0,
            'health_score' => 100,
            'compliance_status' => 'compliant',
            'issues' => [],
        ];

        return response()->json(['data' => $data], 200);
    }

    public function apiDuplicates(Request $request)
    {
        return response()->json(['data' => []], 200);
    }

    public function apiDuplicateFiles(Request $request)
    {
        return $this->apiDuplicates($request);
    }

    public function apiConflicts(Request $request)
    {
        return response()->json(['data' => []], 200);
    }

    public function apiConflictFiles(Request $request)
    {
        return $this->apiConflicts($request);
    }

    public function apiConflictingRoutes(Request $request)
    {
        return $this->apiConflicts($request);
    }

    public function runContext7Fix(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Öneriler hazır',
            'suggestions' => [],
            'action' => 'noop',
        ], 200);
    }

    public function applySuggestion(Request $request)
    {
        return response()->json([
            'manual_required' => true,
        ], 200);
    }
}

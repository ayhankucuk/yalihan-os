<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kisi;
use App\Services\CRM\CRMOrchestratorService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * 🛰️ CRMController
 *
 * Consolidated thin proxy for CRM Dashboard, Pipeline, and Segment management.
 * Business logic is fully delegated to CRMOrchestrator.
 */
class CRMController extends Controller
{
    public function __construct(
        private readonly CRMOrchestratorService $orchestratorService
    ) {}

    /**
     * CRM Primary Dashboard
     */
    public function index(): View
    {
        return view('admin.crm.dashboard', [
            'stats'               => $this->orchestratorService->getDashboardStats(),
            'customerSegments'    => $this->orchestratorService->getCustomerSegments(),
            'recentActivities'    => $this->orchestratorService->getRecentActivities(),
            'upcomingFollowUps'   => collect([]), // Pending authority integration
            'topROIOpportunities' => collect([]), // Pending matching engine integration
        ]);
    }

    /**
     * Pipeline Kanban View
     */
    public function pipeline(): View
    {
        return view('admin.crm.pipeline', [
            'stages'        => $this->orchestratorService->getPipelineStages(),
            'kaybedilenler' => $this->orchestratorService->getLostPipelineCount(),
        ]);
    }

    /**
     * Update Pipeline Stage (AJAX)
     */
    public function updatePipelineStage(Request $request, Kisi $kisi): JsonResponse
    {
        $validated = $request->validate([
            'stage' => 'required|integer|in:0,1,2,3,4,5',
        ]);

        $updatedKisi = $this->orchestratorService->updatePipelineStage($kisi, $validated['stage']);

        return response()->json([
            'success' => true,
            'message' => 'Pipeline güncellendi',
            'kisi' => $updatedKisi,
        ]);
    }

    /**
     * Update Segment (AJAX)
     */
    public function updateSegment(Request $request, Kisi $kisi): JsonResponse
    {
        $validated = $request->validate([
            'segment' => 'required|in:potansiyel,aktif,eski,vip',
        ]);

        $this->orchestratorService->updateSegment($kisi, $validated['segment']);

        return response()->json([
            'success' => true,
            'message' => 'Segment güncellendi',
        ]);
    }

    /**
     * Recalculate All Scores (Global Maintenance)
     */
    public function recalculateScores()
    {
        $this->orchestratorService->recalculateAllScores();

        return redirect()->back()->with('success', 'Tüm skorlar yeniden hesaplandı');
    }

    /**
     * Lead Source Analytics
     */
    public function leadSourceAnalytics(): View
    {
        return view('admin.crm.lead-sources', [
            'leadSources' => $this->orchestratorService->getLeadSourceAnalytics(),
        ]);
    }
}

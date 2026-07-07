<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Workspace\WorkspaceSummaryService;
use App\Services\Workspace\WorkspaceHealthService;
use App\Services\Workspace\WorkspaceNextActionService;
use App\Services\Workspace\WorkspaceTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * WorkspaceDashboardController
 *
 * Sprint 4.6: Property Digital Twin Cockpit
 *
 * Delivers the single operational cockpit screen for a PortfolioDriveWorkspace.
 * Route: GET /admin/workspace/{id}
 *
 * Sub-routes (API):
 *   GET  /admin/workspace/{id}/summary  → full cockpit data
 *   GET  /admin/workspace/{id}/events    → timeline
 *   GET  /admin/workspace/{id}/health    → health score + dimensions
 */
/**
 * @sab-ignore-thin
 */
class WorkspaceDashboardController extends Controller
{
    public function __construct(
        private readonly WorkspaceSummaryService $summaryService,
        private readonly WorkspaceHealthService $healthService,
        private readonly WorkspaceNextActionService $nextActionService,
        private readonly WorkspaceTimelineService $timelineService,
    ) {}

    /**
     * Primary deliverable: Property Digital Twin Cockpit.
     * GET /admin/workspace/{id}
     */
    public function show(int $id): View|JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return $this->notFound();
        }

        $this->authorizeWorkspace($workspace);

        try {
            $summary = $this->summaryService->getSummary($workspace);
            return view('admin.workspace.cockpit', ['workspace' => $summary]);
        } catch (\Exception $e) {
            Log::error('WorkspaceDashboardController: cockpit render failed', [
                'workspace_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackView($workspace, $e);
        }
    }

    /**
     * Workspace Summary API.
     * GET /admin/workspace/{id}/summary
     */
    public function summary(int $id): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        try {
            return response()->json(['workspace' => $this->summaryService->getSummary($workspace)]);
        } catch (\Exception $e) {
            Log::error('WorkspaceDashboard: summary API failed', ['id' => $id, 'msg' => $e->getMessage()]);
            return $this->apiError($e);
        }
    }

    /**
     * Workspace Timeline Events API.
     * GET /admin/workspace/{id}/events
     */
    public function events(int $id, Request $request): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        $limit = min((int) $request->get('limit', 50), 200);

        try {
            $timeline = $this->timelineService->build($workspace, $limit);
            return response()->json([
                'workspace_id' => $workspace->id,
                'ilan_id'      => $workspace->ilan_id,
                'count'        => count($timeline),
                'events'       => $timeline,
                'generated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('WorkspaceDashboard: events API failed', ['id' => $id, 'msg' => $e->getMessage()]);
            return $this->apiError($e);
        }
    }

    /**
     * Workspace Health API.
     * GET /admin/workspace/{id}/health
     */
    public function health(int $id): JsonResponse
    {
        $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        $this->authorizeWorkspace($workspace);

        try {
            return response()->json($this->healthService->calculate($workspace));
        } catch (\Exception $e) {
            Log::error('WorkspaceDashboard: health API failed', ['id' => $id, 'msg' => $e->getMessage()]);
            return $this->apiError($e);
        }
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function findWorkspace(int $id): ?PortfolioDriveWorkspace
    {
        return PortfolioDriveWorkspace::query()
            ->withoutGlobalScopes()
            ->find($id);
    }

    private function authorizeWorkspace(PortfolioDriveWorkspace $workspace): void
    {
        // Tenant isolation gate
        if ($workspace->tenant_id !== null) {
            $this->authorize('view', $workspace);
        }
    }

    private function notFound(): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['error' => 'Workspace bulunamadı'], 404);
        }

        abort(404, 'Çalışma alanı bulunamadı.');
    }

    private function fallbackView(PortfolioDriveWorkspace $workspace, \Throwable $e): View
    {
        $ilan = null;
        if ($workspace->ilan_id) {
            $ilan = \App\Models\Ilan::query()
                ->withoutGlobalScopes()
                ->find($workspace->ilan_id);
        }

        $ilanFallback = $ilan ? [
            'id'          => $ilan->id,
            'baslik'     => $ilan->baslik ?? '—',
            'yayin_durumu'=> $ilan->yayin_durumu?->value,
            'yayin_durumu_label' => $ilan->yayin_durumu?->label(),
            'il_adi'     => $ilan->il?->adi ?? null,
            'ilce_adi'   => $ilan->ilce?->adi ?? null,
            'fiyat'      => $ilan->fiyat,
            'para_birimi'=> $ilan->para_birimi,
            'photo_count'=> $ilan->fotograflar()->count(),
            'has_video'  => !empty($ilan->youtube_video_url),
            'view_count' => $ilan->view_count,
            'danisman'   => $ilan->danisman?->name ?? null,
            'ilan_sahibi'=> $ilan->ilanSahibi?->ad_soyad ?? null,
        ] : null;

        return view('admin.workspace.cockpit', [
            'workspace' => [
                'workspace'   => [
                    'id'                  => $workspace->id,
                    'ilan_id'             => $workspace->ilan_id,
                    'tenant_id'           => $workspace->tenant_id,
                    'portfolio_no'        => $workspace->portfolio_no,
                    'drive_folder_id'     => $workspace->drive_folder_id,
                    'drive_folder_url'    => $workspace->drive_folder_url,
                    'root_folder_name'   => $workspace->root_folder_name,
                    'workspace_status'    => $workspace->workspace_status,
                    'lifecycle_state'     => $workspace->lifecycle_state?->value,
                    'lifecycle_label'     => $workspace->lifecycle_state?->label(),
                    'ai_completion_pct'   => $workspace->ai_completion_percent,
                    'workspace_created_at'=> $workspace->workspace_created_at?->toIso8601String(),
                    'created_at'          => $workspace->created_at?->toIso8601String(),
                    'updated_at'          => $workspace->updated_at?->toIso8601String(),
                ],
                'ilan'        => $ilanFallback,
                'health'      => [
                    'score' => 0, 'label' => 'Hata', 'color' => 'red',
                    'dimensions' => [],
                    'next_action' => null,
                    'calculated_at' => now()->toIso8601String(),
                ],
                'lifecycle'   => ['step' => 0, 'total' => 7, 'percent' => 0, 'is_live' => false],
                'ai'          => ['percent' => 0, 'all_done' => false, 'agents' => []],
                'drive'       => ['subfolders' => [], 'subfolder_count' => 0],
                'finance'     => null,
                'reservations'=> null,
                'error'       => $e->getMessage(),
            ],
        ]);
    }

    private function apiError(\Throwable $e, int $status = 500): JsonResponse
    {
        Log::error('WorkspaceDashboardController API error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'error'   => 'İşlem sırasında bir hata oluştu.',
            'detail'  => config('app.debug') ? $e->getMessage() : null,
        ], $status);
    }
}

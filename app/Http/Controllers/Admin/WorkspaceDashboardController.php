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

            $ilan = $workspace->ilan_id ? \App\Models\Ilan::query()->withoutGlobalScopes()->find($workspace->ilan_id) : null;
            $intent = $workspace->intent ?? ($ilan?->islem_tipi === 'kiralama' ? 'kiralik' : 'satilik');
            $template = app(\App\Services\Workspace\TemplateEngineService::class)->resolveTemplate($intent);

            $ilModel = $ilan?->il()->withoutGlobalScopes()->first();
            $ilceModel = $ilan?->ilce()->withoutGlobalScopes()->first();
            $ilName = ($ilan?->il_id && $ilModel && $ilModel->il_adi !== 'Belirtilmemiş') ? $ilModel->il_adi : null;
            $ilceName = ($ilan?->ilce_id && $ilceModel && $ilceModel->ilce_adi !== 'Belirtilmemiş') ? $ilceModel->ilce_adi : null;

            $workspace_data = $ilan ? [
                'baslik'          => $ilan->baslik,
                'aciklama'        => $ilan->aciklama,
                'fiyat'           => $ilan->fiyat,
                'para_birimi'     => $ilan->para_birimi,
                'kapak_resmi'     => $ilan->kapak_fotografi ? 'present' : null,
                'il'              => $ilName,
                'ilce'            => $ilceName,
                'lat'             => $ilan->lat,
                'lng'             => $ilan->lng,
                'brut_metrekare'  => $ilan->brut_m2,
                'net_metrekare'   => $ilan->net_m2,
                'oda_sayisi'      => $ilan->oda_sayisi,
                'bina_yasi'       => $ilan->bina_yasi,
                'kat'             => $ilan->kat,
                'toplam_kat'      => $ilan->toplam_kat,
                'isitma_tipi'     => $ilan->isinma_tipi ?? $ilan->isitma,
                'tapusu_var'      => $ilan->tapu_durumu ?? null,
                'depozito'        => $ilan->depozito,
                'aidat'           => $ilan->aidat,
                'esyali'          => $ilan->esyali,
            ] : [];

            if ($ilan && ($intent === 'sezonluk' || $intent === 'gunluk')) {
                $turizmDetail = $ilan->turizmDetail;
                $yazlikDetail = $ilan->yazlikDetail;
                $workspace_data['kapasite']      = $turizmDetail?->max_misafir ?? $yazlikDetail?->max_misafir;
                $workspace_data['yatak_odasi']   = $ilan->yatak_odasi;
                $workspace_data['banyo_sayisi']  = $ilan->banyo_sayisi;
                $workspace_data['havuz']         = $turizmDetail?->havuz_var ?? $yazlikDetail?->havuz;
                $workspace_data['min_konaklama'] = $turizmDetail?->min_konaklama ?? $yazlikDetail?->min_konaklama;
                $workspace_data['musait_tarihler'] = ($ilan->propertyAvailabilities()->exists() || $ilan->yazlikFiyatlandirma()->exists()) ? 'present' : null;
            }

            return view('admin.workspace.cockpit', [
                'workspace'      => $summary,
                'template_fields' => $template['fields'] ?? [],
                'workspace_data'  => $workspace_data,
            ]);
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
     * Workspace dynamic fields save handler.
     * POST /admin/workspace/{id}/save
     */
    public function save(int $id, Request $request)
    {
        try {
            $workspace = $this->findWorkspace($id);
        if (!$workspace) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Workspace bulunamadı'], 404);
            }
            abort(404);
        }

        $this->authorizeWorkspace($workspace);

        $ilan = \App\Models\Ilan::query()->withoutGlobalScopes()->find($workspace->ilan_id);
        if (!$ilan) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'İlan bulunamadı'], 404);
            }
            abort(404);
        }

        // Find or create PropertyWorkspace aggregate record linked to this Ilan
        $propertyWorkspaceService = app(\App\Services\PropertyWorkspace\PropertyWorkspaceService::class);
        $propWorkspace = \App\Models\PropertyWorkspace::where('property_id', $ilan->id)->first();
        if (!$propWorkspace) {
            $intent = $ilan->islem_tipi === 'kiralama' ? 'kiralik' : 'satilik';
            $propWorkspace = $propertyWorkspaceService->createWorkspace($ilan->id, $intent);
        }

        $submittedData = $request->get('data', []);

        // Resolve template schema
        $templateEngine = app(\App\Services\Workspace\TemplateEngineService::class);
        $intent = $propWorkspace->intent ?? ($ilan->islem_tipi === 'kiralama' ? 'kiralik' : 'satilik');

        try {
            $template = $templateEngine->resolveTemplate($intent);
        } catch (\InvalidArgumentException $e) {
            \Illuminate\Support\Facades\Log::warning('WorkspaceDashboardController: template resolution failed, falling back to satilik', [
                'intent' => $intent,
                'error'  => $e->getMessage(),
            ]);
            $template = $templateEngine->resolveTemplate('satilik');
        }

        $fields = $template['fields'] ?? [];

        // Dynamic validation against template field attributes
        $validationErrors = [];
        foreach ($fields as $field) {
            $key = $field['key'];
            
            // Only validate fields that are present in the submitted payload (allows partial draft saves)
            if (!array_key_exists($key, $submittedData)) {
                continue;
            }

            $val = $submittedData[$key] ?? null;

            if (($field['required'] ?? false) && ($val === null || $val === '')) {
                $validationErrors[$key] = [sprintf('%s alanı zorunludur.', $field['label'])];
                continue;
            }

            if ($val !== null && $val !== '') {
                if (isset($field['max']) && strlen((string)$val) > $field['max']) {
                    $validationErrors[$key] = [sprintf('%s alanı en fazla %d karakter olmalıdır.', $field['label'], $field['max'])];
                }
                if (isset($field['min']) && (float)$val < $field['min']) {
                    $validationErrors[$key] = [sprintf('%s alanı en az %d olmalıdır.', $field['label'], $field['min'])];
                }
                if (isset($field['options']) && !in_array($val, $field['options'], true)) {
                    $validationErrors[$key] = [sprintf('Geçersiz %s seçimi.', $field['label'])];
                }
            }
        }

        if (!empty($validationErrors)) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validationErrors], 422);
            }
            return redirect()->back()->withErrors($validationErrors)->withInput();
        }

        // Map dynamic fields to Ilan properties safely
        $ilanFieldsMap = [
            'baslik' => 'baslik',
            'aciklama' => 'aciklama',
            'fiyat' => 'fiyat',
            'para_birimi' => 'para_birimi',
            'brut_metrekare' => 'brut_m2',
            'net_metrekare' => 'net_m2',
            'oda_sayisi' => 'oda_sayisi',
            'bina_yasi' => 'bina_yasi',
            'kat' => 'kat',
            'toplam_kat' => 'toplam_kat',
            'isitma_tipi' => 'isinma_tipi',
            'depozito' => 'depozito',
            'aidat' => 'aidat',
            'esyali' => 'esyali',
            'lat' => 'lat',
            'lng' => 'lng',
            'yatak_odasi' => 'yatak_odasi',
            'banyo_sayisi' => 'banyo_sayisi',
            'tapusu_var' => 'tapu_durumu',
        ];

        // Prepare raw data update, merging with existing values to satisfy IlanCrudService expectations
        $updateData = [
            'baslik' => $submittedData['baslik'] ?? $ilan->baslik,
            'aciklama' => $submittedData['aciklama'] ?? $ilan->aciklama,
            'danisman_id' => $ilan->danisman_id ?? \Illuminate\Support\Facades\Auth::id(),
            'ana_kategori_id' => $ilan->ana_kategori_id,
            'alt_kategori_id' => $ilan->alt_kategori_id,
            'yayin_tipi_id' => $ilan->yayin_tipi_id,
            'il' => $ilan->il,
            'ilce' => $ilan->ilce,
            'mahalle' => $ilan->mahalle,
            'il_id' => $ilan->il_id,
            'ilce_id' => $ilan->ilce_id,
            'mahalle_id' => $ilan->mahalle_id,
            'lat' => $submittedData['lat'] ?? $ilan->lat,
            'lng' => $submittedData['lng'] ?? $ilan->lng,
            'adres' => $submittedData['adres'] ?? $ilan->adres,
        ];

        foreach ($fields as $field) {
            $key = $field['key'];
            if (array_key_exists($key, $submittedData)) {
                $val = $submittedData[$key];
                if (isset($ilanFieldsMap[$key])) {
                    $mappedColumn = $ilanFieldsMap[$key];
                    $updateData[$mappedColumn] = $val;
                    if ($mappedColumn === 'isinma_tipi') {
                        // Pass both warm-up properties for backward compatibility
                        $updateData['isitma'] = $val;
                    }
                }
            }
        }

        // Persist flexible/metadata fields to Ilan metadata via CrudService
        if (isset($updateData['tapu_durumu'])) {
            $updateData['metadata'] = ['tapu_durumu' => $updateData['tapu_durumu']];
        }

        // Call IlanCrudService to persist updates to the database safely
        $ilanCrudService = app(\App\Services\Ilan\IlanCrudService::class);
        call_user_func([$ilanCrudService, 'update'], $ilan, $updateData);

        // Re-evaluate readiness
        $summary = $this->summaryService->getSummary($workspace);
        $readiness = $summary['readiness'] ?? null;

        if ($readiness) {
            $status = $readiness['readiness_status'] ?? 'incomplete'; // context7-ignore

            // Transition aggregate state
            if ($status === 'ready') {
                if ($propWorkspace->state === \App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED) { // context7-ignore
                    $propertyWorkspaceService->transitionToDraft($propWorkspace->workspace_uuid);
                    $propWorkspace->refresh();
                }
                if ($propWorkspace->state === \App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate::STATE_DRAFT) { // context7-ignore
                    $propertyWorkspaceService->transitionToReadyForReview($propWorkspace->workspace_uuid);
                }
            } else {
                if ($propWorkspace->state === \App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED) { // context7-ignore
                    $propertyWorkspaceService->transitionToDraft($propWorkspace->workspace_uuid);
                } elseif ($propWorkspace->state === \App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW) { // context7-ignore
                    $propertyWorkspaceService->transitionToDraft($propWorkspace->workspace_uuid);
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Workspace başarıyla güncellendi.',
                'readiness' => $readiness,
                'lifecycle_state' => $propWorkspace->fresh()->state, // context7-ignore
            ]);
        }
        return redirect()->back()->with('success', 'Değişiklikler başarıyla kaydedildi.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WorkspaceDashboardController save failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ], 500);
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

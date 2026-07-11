<?php

namespace App\Services\Workspace;

use App\Domain\Workspace\Enums\WorkspaceState;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Workspace\TemplateEngineService;
use App\Services\Workspace\ReadinessEvaluatorService;
use App\Services\Workspace\AutomationTelemetryService;

/**
 * WorkspaceSummaryService
 *
 * Sprint 4.6: Property Digital Twin Cockpit
 *
 * Aggregates all cockpit data for a single workspace into a single payload:
 *   - Workspace core
 *   - Ilan core
 *   - Health score + dimensions
 *   - AI completion summary
 *   - Lifecycle progress
 *   - Next recommended action
 */
class WorkspaceSummaryService
{
    public function __construct(
        private readonly WorkspaceHealthService $healthService,
        private readonly WorkspaceTimelineService $timelineService,
        private readonly WorkspaceExecutionService $executionService,
        private readonly TemplateEngineService $templateEngine,
        private readonly ReadinessEvaluatorService $readinessEvaluator,
        private readonly AutomationTelemetryService $telemetryService,
        private readonly CapabilityRuntimeEngine $capabilityEngine,
    ) {}

    /**
     * Full cockpit payload for /admin/workspace/{id}.
     */
    public function getSummary(PortfolioDriveWorkspace $workspace): array
    {
        $ilan = $this->resolveIlan($workspace);

        return [
            'workspace'    => $this->workspaceCore($workspace),
            'ilan'        => $ilan ? $this->ilanCore($ilan) : null,
            'health'      => $this->healthService->calculate($workspace),
            'lifecycle'   => $this->lifecycleInfo($workspace),
            'ai'          => $this->aiInfo($workspace),
            'drive'       => $this->driveInfo($workspace),
            'finance'     => $ilan ? $this->financeInfo($ilan) : null,
            'reservations' => $ilan ? $this->reservationsInfo($ilan) : null,
            'executions'  => $this->executionService->getSummary($workspace->id),
            'readiness'   => $this->readinessInfo($workspace, $ilan),
            'capabilities'=> $this->capabilityEngine->evaluate($workspace),
            'telemetry'   => [
                'bai_score' => $this->telemetryService->calculateBusinessAutomationIndex($workspace->tenant_id),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Timeline events for the workspace.
     */
    public function getTimeline(PortfolioDriveWorkspace $workspace, int $limit = 50): array
    {
        return $this->timelineService->build($workspace, $limit);
    }

    /**
     * Health + dimensions only.
     */
    public function getHealth(PortfolioDriveWorkspace $workspace): array
    {
        return $this->healthService->calculate($workspace);
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function workspaceCore(PortfolioDriveWorkspace $w): array
    {
        return [
            'id'                  => $w->id,
            'ilan_id'             => $w->ilan_id,
            'tenant_id'           => $w->tenant_id,
            'portfolio_no'        => $w->portfolio_no,
            'drive_folder_id'     => $w->drive_folder_id,
            'drive_folder_url'    => $w->drive_folder_url,
            'root_folder_name'   => $w->root_folder_name,
            'workspace_status'    => $w->workspace_status,
            'lifecycle_state'     => $w->lifecycle_state?->value,
            'lifecycle_label'    => $w->lifecycle_state?->label(),
            'lifecycle_color'    => $w->lifecycle_state?->color(),
            'ai_completion_pct'  => $w->ai_completion_percent,
            'state_changed_at'   => $this->parseDate($w->state_changed_at),
            'workspace_created_at'=> $this->parseDate($w->workspace_created_at),
            'created_at'         => $this->parseDate($w->created_at),
            'updated_at'         => $this->parseDate($w->updated_at),
        ];
    }

    private function parseDate($date): ?string
    {
        if ($date === null) {
            return null;
        }
        if ($date instanceof \Carbon\Carbon) {
            return $date->toIso8601String();
        }
        if (is_string($date)) {
            return \Carbon\Carbon::parse($date)->toIso8601String();
        }
        return (string) $date;
    }

    private function ilanCore(Ilan $ilan): array
    {
        return [
            'id'               => $ilan->id,
            'baslik'          => $ilan->baslik,
            'fiyat'           => $ilan->fiyat,
            'para_birimi'     => $ilan->para_birimi,
            'yayin_durumu'    => $ilan->yayin_durumu?->value,
            'yayin_durumu_label' => $ilan->yayin_durumu?->label(),
            'il_adi'          => $this->getRelationModel($ilan, 'il')?->il_adi ?? $this->getRelationModel($ilan, 'il')?->adi ?? (is_string($ilan->il) ? $ilan->il : null),
            'ilce_adi'        => $this->getRelationModel($ilan, 'ilce')?->ilce_adi ?? $this->getRelationModel($ilan, 'ilce')?->adi ?? (is_string($ilan->ilce) ? $ilan->ilce : null),
            'kategori'        => $ilan->altKategori?->adi ?? $ilan->anaKategori?->adi ?? null,
            'danisman'        => $ilan->danisman?->name ?? null,
            'ilan_sahibi'     => $ilan->ilanSahibi?->ad_soyad ?? null,
            'photo_count'     => $ilan->fotograflar()->count(),
            'has_video'       => !empty($ilan->youtube_video_url),
            'view_count'      => $ilan->view_count,
            'created_at'      => $this->parseDate($ilan->created_at),
        ];
    }

    private function lifecycleInfo(PortfolioDriveWorkspace $w): array
    {
        $state = $w->lifecycle_state;
        if (!$state) {
            return ['step' => 0, 'total' => 7, 'percent' => 0, 'is_live' => false];
        }

        return [
            'step'        => $state->step(),
            'total'       => $state->activeLifecycleSteps(),
            'percent'     => $state->completionPercent(),
            'is_live'     => $state->isLive(),
            'is_terminal' => $state->isTerminal(),
            'is_pre_pub'  => $state->isPrePublishing(),
        ];
    }

    private function aiInfo(PortfolioDriveWorkspace $w): array
    {
        $summary = $w->getAiCompletionSummary();
        $state   = $w->lifecycle_state;

        $agents = [];
        foreach ($summary['agents'] ?? [] as $key => $agent) {
            $agents[] = [
                'key'         => $key,
                'name'        => $this->agentLabel($key),
                'complete'    => $agent['complete'] ?? false,
                'completed_at' => $agent['completed_at'] ?? null,
                'color'       => ($agent['complete'] ?? false) ? 'emerald' : 'slate',
            ];
        }

        return [
            'percent'    => $summary['percent'] ?? 0,
            'all_done'   => $summary['all_complete'] ?? false,
            'agents'     => $agents,
            'lifecycle'  => $state?->value, // context7-ignore
            'lifecycle_label'=> $state?->label(),
        ];
    }

    private function driveInfo(PortfolioDriveWorkspace $w): array
    {
        $subfolders = $w->subfolders_json ?? [];
        $channel    = $w->getWebhookChannel();
        $files      = $w->getTrackedFiles();

        return [
            'folder_id'  => $w->drive_folder_id,
            'folder_url' => $w->drive_folder_url,
            'durum'     => $w->workspace_status, // context7-ignore
            'durum_ok'  => $w->workspace_status === 'ready',
            'subfolders' => array_map(fn($sf) => [
                'name' => $sf['name'] ?? '?',
                'url'  => $sf['url'] ?? null,
                'id'   => $sf['id'] ?? null,
            ], $subfolders),
            'subfolder_count' => count($subfolders),
            'ready'      => count($subfolders) > 0,
            // ─── Sprint 4.8: Drive Integration ──────────────────────────
            'webhook' => [
                'connected'      => $w->hasActiveChannel(),
                'channel_id'    => $channel['channel_id'] ?? null,
                'webhook_url'   => $channel['webhook_url'] ?? null,
                'expiration'    => $channel['expiration'] ?? null,
                'expiration_ts' => ($channel['expiration'] ?? null)
                    ? now()->parse($channel['expiration'])->timestamp
                    : null,
                'last_sync_at'  => $channel['last_sync_at'] ?? null,
                'last_error'    => $channel['last_error'] ?? null,
                'last_count'    => $channel['last_sync_count'] ?? 0,
                'needs_renewal' => $w->hasActiveChannel()
                    ? (now()->parse($channel['expiration'])->diffInHours(now()) < 24)
                    : false,
            ],
            'files' => [
                'total'         => count($files),
                'docs'          => count($w->getGoogleDocFiles()),
                'sheets'        => count($w->getGoogleSheetFiles()),
                'list'          => array_slice($files, 0, 10), // last 10 for panel
            ],
        ];
    }

    private function resolveIlan(PortfolioDriveWorkspace $w): ?Ilan
    {
        if (!$w->ilan_id) {
            return null;
        }
        $ilan = Ilan::query()
            ->withoutGlobalScopes()
            ->with([
                'anaKategori',    // context7-ignore
                'altKategori',    // context7-ignore
                'danisman',
                'ilanSahibi',     // context7-ignore
                'fotograflar',
            ])
            ->find($w->ilan_id);

        if ($ilan) {
            $directIl = \App\Models\Il::withoutGlobalScopes()->find($ilan->il_id);
            $directIlce = \App\Models\Ilce::withoutGlobalScopes()->find($ilan->ilce_id);
            if ($directIl) {
                $ilan->setRelation('il', $directIl);
            }
            if ($directIlce) {
                $ilan->setRelation('ilce', $directIlce);
            }
        }

        return $ilan;
    }

    /**
     * Safely resolve relation model bypassing any attribute name collisions.
     */
    private function getRelationModel(Ilan $ilan, string $relation): ?\Illuminate\Database\Eloquent\Model
    {
        if ($ilan->relationLoaded($relation)) {
            $model = $ilan->getRelation($relation);
            if ($model instanceof \Illuminate\Database\Eloquent\Model) {
                return $model;
            }
        }
        return null;
    }

    private function financeInfo(Ilan $ilan): array
    {
        $fiyat          = (float) ($ilan->fiyat ?? 0);
        $purchasePrice  = (float) ($ilan->purchase_price ?? 0);
        $dailyRate      = (float) ($ilan->gunluk_fiyat ?? 0);
        $currency       = $ilan->para_birimi ?? 'TL';
        $roiTarget      = (float) ($ilan->investor_target_roi ?? 0);

        $roiEstimate = 0;
        if ($purchasePrice > 0 && $dailyRate > 0) {
            $annualRevenue = $dailyRate * 365 * 0.6; // 60% occupancy assumption
            $roiEstimate   = round(($annualRevenue / $purchasePrice) * 100, 1);
        }

        return [
            'listing_price'  => $fiyat,
            'listing_formatted' => $fiyat > 0 ? number_format($fiyat, 0, ',', '.') . ' ' . $currency : null,
            'purchase_price' => $purchasePrice,
            'purchase_formatted' => $purchasePrice > 0 ? number_format($purchasePrice, 0, ',', '.') . ' ' . $currency : null,
            'daily_rate'     => $dailyRate,
            'daily_formatted' => $dailyRate > 0 ? number_format($dailyRate, 0, ',', '.') . ' ' . $currency : null,
            'currency'       => $currency,
            'roi_target'     => $roiTarget,
            'roi_estimate'   => $roiEstimate,
            'has_investment_data' => $purchasePrice > 0 || $dailyRate > 0 || $roiTarget > 0,
        ];
    }

    private function reservationsInfo(Ilan $ilan): array
    {
        $reservations = $ilan->rezervasyonlar()
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();

        $active = $ilan->rezervasyonlar()
            ->where('reservation_state', 'confirmed')
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->count();

        return [
            'total_count'   => $ilan->rezervasyonlar()->count(),
            'active_count'  => $active,
            'recent'        => $reservations->map(fn($r) => [
                'id'          => $r->id,
                'guest_name'  => $r->guest_name ?? null,
                'baslangic'   => $r->start_date?->format('d.m.Y'),
                'bitis'       => $r->end_date?->format('d.m.Y'),
                'durum'       => $r->reservation_state ?? null,
                'toplam_tutar'=> $r->total_amount ?? null,
            ])->toArray(),
            'has_reservations' => $ilan->rezervasyonlar()->exists(),
        ];
    }

    private function agentLabel(string $key): string
    {
        return match ($key) {
            'photo_agent'           => 'Fotoğraf Analizi',
            'description_agent'     => 'Açıklama Üretimi',
            'property_score_agent'  => 'Mülk Skoru',
            'publish_decision_agent' => 'Yayın Kararı',
            default                 => $key,
        };
    }

    /**
     * Get readiness evaluation info for the workspace (Sprint 6.1-E04).
     */
    private function readinessInfo(PortfolioDriveWorkspace $workspace, ?Ilan $ilan): ?array
    {
        if (!$ilan) {
            return null;
        }


        // 1. Resolve intent (PropertyWorkspace table -> falls back to Ilan characteristics)
        $propWorkspace = \App\Models\PropertyWorkspace::where('ilan_id', $ilan->id)->first();
        $intent = $propWorkspace?->intent;

        if (!$intent) {
            if ($ilan->islem_tipi === 'kiralama' || $ilan->ilan_turu === 'kiralik') {
                $intent = 'kiralik';
                // Check if it's a seasonal/holiday rental
                $kategoriSlug = strtolower($ilan->anaKategori?->slug ?? '');
                if ($kategoriSlug === 'yazlık' || $kategoriSlug === 'yazlik') {
                    $intent = 'sezonluk';
                }
            } else {
                $intent = 'satilik';
            }
        }

        try {
            $template = $this->templateEngine->resolveTemplate($intent);
        } catch (\InvalidArgumentException $e) {
            \Illuminate\Support\Facades\Log::warning('WorkspaceSummaryService: template resolution failed, falling back to satilik', [
                'intent' => $intent,
                'error'  => $e->getMessage(),
            ]);
            $template = $this->templateEngine->resolveTemplate('satilik');
        }

        // 3. Gather workspace data from Ilan model attributes
        $workspaceData = [
            'baslik'          => $ilan->baslik,
            'aciklama'        => $ilan->aciklama,
            'fiyat'           => $ilan->fiyat,
            'para_birimi'     => $ilan->para_birimi,
            'kapak_resmi'     => $ilan->kapak_fotografi ? 'present' : null,
            'il'              => $this->getRelationModel($ilan, 'il')?->il_adi ?? $this->getRelationModel($ilan, 'il')?->adi ?? $ilan->il_adi ?? (is_string($ilan->il) ? $ilan->il : null),
            'ilce'            => $this->getRelationModel($ilan, 'ilce')?->ilce_adi ?? $this->getRelationModel($ilan, 'ilce')?->adi ?? $ilan->ilce_adi ?? (is_string($ilan->ilce) ? $ilan->ilce : null),
            'lat'             => $ilan->lat,
            'lng'             => $ilan->lng,
            'brut_metrekare'  => $ilan->brut_m2,
            'net_metrekare'   => $ilan->net_m2,
            'oda_sayisi'      => $ilan->oda_sayisi,
            'bina_yasi'       => $ilan->bina_yasi,
            'kat'             => $ilan->kat,
            'toplam_kat'      => $ilan->toplam_kat,
            'isitma_tipi'     => $ilan->isitma,
            'tapusu_var'      => $ilan->metadata['tapu_durumu'] ?? $ilan->tapu_durumu ?? ($ilan->tapu_id ? 'present' : null),
            'depozito'        => $ilan->depozito,
            'aidat'           => $ilan->aidat,
            'esyali'          => $ilan->esyali,
        ];

        // If seasonal, merge additional fields from turizmDetail/yazlikDetail
        if ($intent === 'sezonluk' || $intent === 'gunluk') {
            $turizmDetail = $ilan->turizmDetail;
            $yazlikDetail = $ilan->yazlikDetail;

            $workspaceData['kapasite']      = $turizmDetail?->max_misafir ?? $yazlikDetail?->max_misafir;
            $workspaceData['yatak_odasi']   = $ilan->yatak_odasi;
            $workspaceData['banyo_sayisi']  = $ilan->banyo_sayisi;
            $workspaceData['havuz']         = $turizmDetail?->havuz_var ?? $yazlikDetail?->havuz;
            $workspaceData['min_konaklama'] = $turizmDetail?->min_konaklama ?? $yazlikDetail?->min_konaklama;
            $workspaceData['musait_tarihler'] = ($ilan->propertyAvailabilities()->exists() || $ilan->yazlikFiyatlandirma()->exists()) ? 'present' : null;
        }

        // 4. Gather uploaded documents from belgeler table
        $uploadedDocuments = \App\Models\Belge::where('ilan_id', $ilan->id)
            ->pluck('belge_turu')
            ->filter()
            ->toArray();

        // 5. Gather completed AI hooks mapped from workspace AI agent flags
        $completedAiHooks = [];
        if ($workspace->isAgentComplete('photo_agent')) {
            $completedAiHooks[] = 'detect_property_type';
        }
        if ($workspace->isAgentComplete('description_agent')) {
            $completedAiHooks[] = 'generate_title';
            $completedAiHooks[] = 'generate_description';
        }
        if ($workspace->isAgentComplete('property_score_agent')) {
            $completedAiHooks[] = 'suggest_price';
        }

        // 6. Evaluate readiness
        $evaluation = $this->readinessEvaluator->evaluate(
            $workspaceData,
            $template,
            $uploadedDocuments,
            $completedAiHooks
        );

        return [
            'intent'            => $intent,
            'template_id'       => $template['template_id'],
            'readiness_score'   => $evaluation['score'],
            'readiness_status'  => $evaluation['status'], // context7-ignore
            'missing_fields'    => $evaluation['missing_fields'],
            'missing_documents' => $evaluation['missing_documents'],
            'missing_ai_hooks'  => $evaluation['missing_ai_hooks'],
            'summary'           => $evaluation['summary'],
        ];
    }
}

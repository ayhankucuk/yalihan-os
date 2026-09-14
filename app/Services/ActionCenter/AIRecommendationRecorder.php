<?php

namespace App\Services\ActionCenter;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\AI\AdvisorCommandCenterService;
use App\Services\AI\BuyerMatchQueueService;
use App\Services\AI\DealRadarService;
use App\Services\AI\OpportunityEngineService;
use App\Services\AI\PortfolioDoctorService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\Log;

/**
 * AIRecommendationRecorder — Sprint 16 Phase 1.
 *
 * Bridges AI advisory modules (DealRadar, OpportunityEngine, PortfolioDoctor,
 * BuyerMatch) with the Action Center by materializing AI-generated recommendations
 * as persisted Gorev records with full provenance.
 *
 * Design rules:
 * - Thin: delegates to AdvisorCommandCenterService for data aggregation
 * - Idempotent: same source_event + entity_id → no duplicate Gorev
 * - Tenant-scoped: all Gorev records belong to the current tenant
 * - Human-in-the-loop: AI Gorev records are created in 'onay_bekliyor' state,
 *   requiring explicit human approval before becoming actionable
 *
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §Phase 4
 */
class AIRecommendationRecorder
{
    private const MODULE_VERSION = 'v1.0.0-sprint16';

    /** Map AI module names to Gorev gorev_tipi values */
    private const MODULE_TIPI_MAP = [
        'deal_radar'         => 'ai_deal_radar',
        'opportunity_engine' => 'ai_opportunity',
        'portfolio_doctor'   => 'ai_portfolio_review',
        'buyer_match'        => 'ai_buyer_match',
    ];

    /** Map AI execution_priority to Gorev oncelik values */
    private const PRIORITY_MAP = [
        'CRITICAL' => 'acil',
        'HIGH'     => 'yuksek',
        'MEDIUM'   => 'normal',
        'LOW'      => 'dusuk',
    ];

    public function __construct(
        private AdvisorCommandCenterService $advisorService,
        private TenantContextService        $tenantService,
    ) {}

    /**
     * Materialize all AI recommendations from AdvisorCommandCenterService
     * into Gorev records for the current tenant.
     *
     * @return array{created: int, skipped: int, errors: int}
     */
    public function recordAllRecommendations(array $filters = []): array
    {
        $tenantId = $this->tenantService->getTenant()?->id;
        if (!$tenantId) {
            Log::warning('AIRecommendationRecorder: no tenant context, skipping');
            return ['created' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $data = $this->advisorService->getCommandCenterData($filters);
        $result = ['created' => 0, 'skipped' => 0, 'errors' => 0];

        foreach (['deal_radar', 'opportunity_engine', 'portfolio_doctor'] as $module) {
            if (empty($data[$module])) {
                continue;
            }
            foreach ($data[$module] as $item) {
                try {
                    $outcome = $this->recordModuleItem($module, $item, $tenantId);
                    $result[$outcome]++;
                } catch (\Throwable $e) {
                    Log::error('AIRecommendationRecorder: failed to record item', [
                        'module' => $module,
                        'error' => $e->getMessage(),
                    ]);
                    $result['errors']++;
                }
            }
        }

        // Buyer match requires listing context — process as separate pass
        if (!empty($data['buyer_matches'])) {
            foreach ($data['buyer_matches'] as $match) {
                try {
                    $outcome = $this->recordModuleItem('buyer_match', $match, $tenantId);
                    $result[$outcome]++;
                } catch (\Throwable $e) {
                    Log::error('AIRecommendationRecorder: buyer_match item failed', [
                        'error' => $e->getMessage(),
                    ]);
                    $result['errors']++;
                }
            }
        }

        Log::info('AIRecommendationRecorder: batch complete', [
            'tenant_id' => $tenantId,
            ...$result,
        ]);

        return $result;
    }

    /**
     * Record a single AI module item as a Gorev record.
     *
     * @return string 'created' | 'skipped'
     */
    public function recordModuleItem(string $module, array $item, int $tenantId): string
    {
        $ilanId = $item['listing_id'] ?? null;

        // ── Idempotency guard ──────────────────────────────────────────────
        $sourceEvent = "ai_recording.{$module}." . ($ilanId ?? 'global');
        if ($ilanId && $this->gorevExistsForEvent($sourceEvent, $ilanId, $tenantId)) {
            return 'skipped';
        }

        $priority = $this->mapPriority($item['execution_priority'] ?? 'LOW');
        $baslik = $this->buildBaslik($module, $item);
        $aciklama = $item['reason'] ?? $item['action_label'] ?? $item['suggested_action'] ?? 'AI önerisi';
        $oncelikScore = $this->computeConfidenceScore($item);

        $gorev = Gorev::withoutTenant()->create([
            'baslik'               => $baslik,
            'aciklama'            => $aciklama,
            'gorev_tipi'          => self::MODULE_TIPI_MAP[$module] ?? 'diger',
            'gorev_durumu'        => 'onay_bekliyor',  // Human-in-the-loop gate
            'oncelik'             => $priority,
            'olusturan_user_id'   => null,
            'atanan_user_id'       => null,
            'ilan_id'             => $ilanId,
            'reservation_id'       => null,
            'lead_id'             => $item['lead_id'] ?? null,
            'tenant_id'           => $tenantId,
            'source_event'        => $sourceEvent,
            'source_module'       => $module,
            'ai_confidence_score' => $oncelikScore,
            'ai_reasoning'        => $this->buildAIReasoning($module, $item),
            'ai_model_version'    => self::MODULE_VERSION,
            'baslangic_tarihi'    => now(),
            'bitis_tarihi'        => $this->computeDeadline($module, $priority),
            'tamamlanma_yuzdesi'  => 0,
            'notlar'              => null,
        ]);

        Log::info('AIRecommendationRecorder: Gorev created', [
            'gorev_id'     => $gorev->id,
            'source_module' => $module,
            'ilan_id'      => $ilanId,
            'tenant_id'    => $tenantId,
        ]);

        return 'created';
    }

    /**
     * Approve an AI-generated Gorev — transitions from 'onay_bekliyor' to 'bekliyor'.
     */
    public function approveRecommendation(int $gorevId, int $approverUserId): bool
    {
        $gorev = Gorev::where('id', $gorevId)
            ->where('gorev_durumu', 'onay_bekliyor')
            ->first();

        if (!$gorev) {
            return false;
        }

        $gorev->update([
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => $approverUserId,
            'assigned_at'    => now(),
        ]);

        Log::info('AIRecommendationRecorder: recommendation approved', [
            'gorev_id' => $gorevId,
            'approver' => $approverUserId,
        ]);

        return true;
    }

    /**
     * Reject an AI-generated Gorev.
     */
    public function rejectRecommendation(int $gorevId, int $rejecterUserId, ?string $reason = null): bool
    {
        $gorev = Gorev::where('id', $gorevId)
            ->where('gorev_durumu', 'onay_bekliyor')
            ->first();

        if (!$gorev) {
            return false;
        }

        $gorev->update([
            'gorev_durumu' => 'iptal',
            'cancel_reason' => $reason ?? 'AI önerisi reddedildi',
        ]);

        Log::info('AIRecommendationRecorder: recommendation rejected', [
            'gorev_id'  => $gorevId,
            'rejecter'  => $rejecterUserId,
            'reason'    => $reason,
        ]);

        return true;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function gorevExistsForEvent(string $sourceEvent, int $ilanId, int $tenantId): bool
    {
        return Gorev::where('source_event', $sourceEvent)
            ->where('ilan_id', $ilanId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    private function mapPriority(string $executionPriority): string
    {
        return self::PRIORITY_MAP[$executionPriority] ?? 'normal';
    }

    private function buildBaslik(string $module, array $item): string
    {
        // AdvisorCommandCenterService returns 'listing_title' or 'title' — normalise to baslik
        $baslik = $item['baslik'] ?? $item['listing_title'] ?? $item['title'] ?? 'İlan';
        $buyerName = $item['buyer_name'] ?? '';

        $prefixes = [
            'deal_radar'          => 'AI Deal Radar: ',
            'opportunity_engine'  => 'AI Fırsat: ',
            'portfolio_doctor'    => 'AI Portföy İnceleme: ',
            'buyer_match'         => 'AI Alıcı Eşleştirme: ',
        ];

        $baslik = ($prefixes[$module] ?? 'AI Öneri: ') . $baslik;

        if ($module === 'buyer_match' && $buyerName) {
            $baslik .= ' → ' . $buyerName;
        }

        return $baslik;
    }

    private function buildAIReasoning(string $module, array $item): string
    {
        $reason = $item['reason'] ?? '';
        $actionLabel = $item['action_label'] ?? '';
        $suggestedAction = $item['suggested_action'] ?? '';

        return trim("Kaynak: {$module} | Öneri: {$actionLabel} | Gerekçe: {$reason} | AI Action: {$suggestedAction}");
    }

    private function computeConfidenceScore(array $item): float
    {
        // Map execution_priority to a 0-1 confidence score
        $scores = [
            'CRITICAL' => 0.95,
            'HIGH'     => 0.80,
            'MEDIUM'   => 0.60,
            'LOW'      => 0.40,
        ];

        return $scores[$item['execution_priority'] ?? 'LOW'] ?? 0.50;
    }

    private function computeDeadline(string $module, string $priority): ?\DateTime
    {
        $daysMap = [
            'acil'   => 1,
            'yuksek' => 3,
            'normal' => 7,
            'dusuk'  => 14,
        ];

        $days = $daysMap[$priority] ?? 7;
        return now()->addDays($days)->toDateTime();
    }
}

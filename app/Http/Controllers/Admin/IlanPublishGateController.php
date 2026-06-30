<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\IlanReservation;
use App\Services\AI\YalihanCortex;
use App\Services\Listing\YalihanLifecycle;
use App\Services\Listing\ListingScoreService;
use App\Services\Response\ResponseService;
use App\Services\Logging\LogService;
use App\Support\YayinTipiRules;
use App\Enums\IlanDurumu;
use DomainException;
use Illuminate\Http\Request;

/**
 * Ilan Publish Gate Controller
 *
 * Phase D: AI-assisted publish control with soft-gate
 *
 * Kurallar:
 * - UPS SSOT: Feature context via UpsFeatureContextService + FeatureTemplateResolver
 * - Cortex observer mode: Sadece okur + değerlendirir + loglar
 * - Publish kararı audit edilir (AiLog + MCP)
 * - Soft-gate: Block engellemez ama override + reason zorunlu
 *
 * Endpoint: POST /admin/ilanlar/{ilan}/publish
 */
class IlanPublishGateController extends AdminController
{
    public function __construct(
        private YalihanCortex $cortex,
        private YalihanLifecycle $lifecycleService,
        private ListingScoreService $scoreService
    ) {
        parent::__construct();
    }

    /**
     * AI-assisted publish with quality gate
     *
     * Request payload:
     * {
     *   "override": false,
     *   "override_reason": "string|null",
     *   "draft_features": {...}
     * }
     *
     * Response (success):
     * {
     *   "success": true,
     *   "message": "İlan yayınlandı",
     *   "data": {
     *     "ilan_id": 123,
     *     "quality_score": 82,
     *     "recommendation": "ok",
     *     "override_applied": false
     *   }
     * }
     *
     * Response (needs review):
     * {
     *   "success": false,
     *   "message": "Yayın için kullanıcı onayı gerekli",
     *   "code": "NEEDS_REVIEW",
     *   "data": {
     *     "quality_score": 65,
     *     "recommendation": "needs_review",
     *     "issues": [...],
     *     "suggested_fixes": [...]
     *   }
     * }
     *
     * Response (block):
     * {
     *   "success": false,
     *   "message": "AI kalite kontrolü yayını engelliyor",
     *   "code": "QUALITY_BLOCK",
     *   "data": {
     *     "quality_score": 35,
     *     "recommendation": "block",
     *     "issues": [...],
     *     "override_required": true
     *   }
     * }
     */
    public function publish(Request $request, Ilan $ilan)
    {
        $startTime = LogService::startTimer('publish_gate_check');

        try {
            // 1. Completion & Quality update before attempting publish
            // SAB v1.9.2: Mutation moved to Service layer
            $this->scoreService->refreshAndPersistScores($ilan);
            $completionScore = $ilan->completion_score;
            $qualityScore    = $ilan->quality_score;

            // 2. Perform Cortex Analysis for Dashboard metrics (optional side effect)
            $quality = $this->cortex->evaluateListingQualityForIlan($ilan, []);
            $recommendation = $quality['data']['recommendation'] ?? 'ok';

            LogService::info('Publish request triggered via Gate', [
                'ilan_id'          => $ilan->id,
                'completion_score' => $completionScore,
                'quality_score'    => $qualityScore,
            ]);

            // 3. HARD GATE: Delegate transition to canonical service
            // This will internally verify `$score >= 100` and `$template != null`
            // and throw DomainException if anything is wrong.
            $this->lifecycleService->transition(
                $ilan,
                IlanDurumu::YAYINDA,
                aktanId: auth()->id(),
                meta: [
                    'source'           => 'admin_publish_gate',
                    'quality_score'    => $qualityScore,
                    'completion_score' => $completionScore,
                    'ip'               => $request->ip(),
                    'user_id'          => auth()->id(),
                ]
            );

            $durationMs = LogService::stopTimer($startTime);

            return ResponseService::success(
                data: [
                    'ilan_id'          => $ilan->id,
                    'completion_score' => $completionScore,
                    'quality_score'    => $qualityScore,
                    'recommendation'   => $recommendation,
                    'published_at'     => now()->toISOString(),
                ],
                message: 'İlan başarıyla yayına alındı.'
            );

        } catch (DomainException $e) {
            $durationMs = LogService::stopTimer($startTime);

            return ResponseService::error(
                message: $e->getMessage(),
                yanitKodu: 422,
                errors: [
                    'completion_score' => $ilan->completion_score,
                    'breakdown'        => $this->scoreService->computeBreakdown($ilan)
                ],
                code: 'PUBLISH_BLOCK'
            );
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            LogService::error('Publish gate check failed', [
                'ilan_id'     => $ilan->id,
                'error'       => $e->getMessage(),
                'duration_ms' => $durationMs,
            ], $e);

            return ResponseService::serverError(
                message: 'Yayın kontrolü başarısız, tekrar deneyin.',
                exception: $e
            );
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ActionCenter\AIRecommendationManagementService;
use App\Services\ActionCenter\AIRecommendationRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AIRecommendationController — Sprint 16 Phase 1.
 *
 * Thin controller: validates + delegates to AIRecommendationManagementService
 * and AIRecommendationRecorder. Contains zero business logic.
 *
 * Routes: routes/api/v1/action-center.php
 *         (registered in the action-center route group)
 */
class AIRecommendationController extends Controller
{
    public function __construct(
        private AIRecommendationManagementService $mgmt,
        private AIRecommendationRecorder        $recorder,
    ) {}

    /**
     * GET /api/v1/action-center/explain/{id}
     *
     * Returns full explainability payload for a Gorev record.
     * Reconstructs the AI provenance chain and returns structured rationale.
     */
    public function explain(Request $request, int $id): JsonResponse
    {
        $result = $this->mgmt->explain($id, $request->user()->tenant_id);
        return response()->json($result, $result['http_status']);
    }

    /**
     * POST /api/v1/action-center/recommendations/record
     *
     * Materializes all AI recommendations from AdvisorCommandCenterService
     * as Gorev records in 'onay_bekliyor' state for human review.
     *
     * Idempotent: re-running skips already-recorded recommendations.
     */
    public function record(Request $request): JsonResponse
    {
        $result = $this->recorder->recordAllRecommendations();

        return response()->json([
            'success' => true,
            'message' => 'AI önerileri kaydedildi.',
            'data' => [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors'  => $result['errors'],
            ],
        ]);
    }

    /**
     * POST /api/v1/action-center/recommendations/{id}/approve
     *
     * Approve an AI-generated Gorev — transitions from 'onay_bekliyor' to 'bekliyor'.
     * After approval the Gorev becomes actionable and assignable.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $result = $this->mgmt->approve(
            $id,
            $request->user()->tenant_id,
            $request->user()->id,
        );

        return response()->json($result, $result['http_status']);
    }

    /**
     * POST /api/v1/action-center/recommendations/{id}/reject
     *
     * Reject an AI-generated Gorev. Transitions to 'iptal' with cancel_reason.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->mgmt->reject(
            $id,
            $request->user()->tenant_id,
            $request->user()->id,
            $validated['reason'] ?? null,
        );

        return response()->json($result, $result['http_status']);
    }
}

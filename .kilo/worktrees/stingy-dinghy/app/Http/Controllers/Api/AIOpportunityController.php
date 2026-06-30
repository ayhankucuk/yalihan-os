<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\OpportunityInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🎯 AI Opportunity Controller
 *
 * Phase 18 MVP: AI Fırsat Avcısı (Opportunity Inbox)
 * SAB §5: Thin Controller — request → service → response.
 */
class AIOpportunityController extends Controller
{
    public function __construct(
        private OpportunityInboxService $inboxService,
    ) {}

    /**
     * GET /api/v1/ai/opportunities
     *
     * Fırsat inbox listesi.
     * Query params: min_score (default: 60), limit (default: 20)
     */
    public function index(Request $request): JsonResponse
    {
        $minScore = (int) $request->get('min_score', 60);
        $limit = min((int) $request->get('limit', 20), 50);

        $opportunities = $this->inboxService->getInbox($minScore, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'opportunities' => $opportunities,
                'meta' => [
                    'total' => $opportunities->count(),
                    'min_score' => $minScore,
                    'generated_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/ai/opportunities/{id}
     *
     * Tek fırsat detayı.
     */
    public function show(int $id): JsonResponse
    {
        $detail = $this->inboxService->getDetail($id);

        if (! $detail) {
            return response()->json([
                'success' => false,
                'message' => 'Fırsat bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }
}

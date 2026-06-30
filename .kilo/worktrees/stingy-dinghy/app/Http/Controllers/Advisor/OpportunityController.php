<?php

namespace App\Http\Controllers\Advisor;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\OpportunityInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🎯 Advisor Opportunity Controller
 *
 * Phase 18 MVP: AI Fırsat Avcısı (Opportunity Inbox)
 * SAB §5: Thin Controller — request → service → response.
 *
 * Guard:
 *   - Controller içinde AI logic yasak
 *   - proj_listings projection'dan okunur
 *   - Service layer orkestre eder
 */
class OpportunityController extends Controller
{
    public function __construct(
        private OpportunityInboxService $inboxService,
    ) {}

    /**
     * GET /api/advisor/opportunities
     *
     * Fırsat inbox listesi.
     * Query params: min_score (default: 60), limit (default: 50)
     */
    public function index(Request $request): JsonResponse
    {
        $minScore = (int) $request->get('min_score', 60);
        $limit = min((int) $request->get('limit', 50), 50);

        $opportunities = $this->inboxService->getInbox($minScore, $limit);
        $stats = $this->inboxService->getStats();

        return response()->json([
            'success' => true,
            'data' => [
                'opportunities' => $opportunities,
                'meta' => [
                    'total' => $opportunities->count(),
                    'min_score' => $minScore,
                    'stats' => $stats,
                    'generated_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/advisor/opportunities/{ilanId}
     *
     * Tek fırsat detayı (ilan_id bazlı).
     */
    public function show(int $ilanId): JsonResponse
    {
        $detail = $this->inboxService->getDetail($ilanId);

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

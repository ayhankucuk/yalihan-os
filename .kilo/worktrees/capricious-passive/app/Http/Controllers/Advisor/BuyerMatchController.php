<?php

namespace App\Http\Controllers\Advisor;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\BuyerMatchInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🤝 Advisor Buyer Match Controller
 *
 * Phase 18 MVP: AI Alıcı Bulucu (Buyer Match Queue)
 * SAB §5: Thin Controller — request → service → response.
 *
 * Guard:
 *   - Controller içinde AI logic yasak
 *   - Projections üzerinden okunur
 *   - Service layer orkestre eder
 */
class BuyerMatchController extends Controller
{
    public function __construct(
        private BuyerMatchInboxService $inboxService,
    ) {}

    /**
     * GET /api/advisor/listings/{ilan}/buyer-matches
     *
     * İlan için en uygun alıcı eşleşmelerini döndür.
     * Query params: limit (default: 10, max: 20)
     */
    public function matches(Ilan $ilan, Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 20);

        $result = $this->inboxService->getMatchesForListing($ilan, $limit);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * GET /advisor/listing/{ilan}/buyers (web view)
     *
     * Buyer Match Queue blade view'ını döndür.
     */
    public function index(Ilan $ilan)
    {
        return view('advisor.listing_buyers', [
            'ilan' => $ilan,
        ]);
    }
}

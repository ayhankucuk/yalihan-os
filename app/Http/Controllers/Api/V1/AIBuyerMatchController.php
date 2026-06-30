<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\BuyerMatchLog;
use App\Services\AI\YalihanCortex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ️ SAB SEALED
 * AI Buyer Match Controller
 * Thin controller pattern — All logic in YalihanCortex.
 */
class AIBuyerMatchController extends Controller
{
    public function __construct(
        private YalihanCortex $cortex
    ) {}

    /**
     * Get buyer matches for a specific listing.
     * GET /api/v1/ai/buyer-matches/{ilan}
     */
    public function show(Ilan $ilan): JsonResponse
    {
        $result = $this->cortex->detectBuyerMatches($ilan);

        return response()->json($result);
    }

    /**
     * Get historical match logs for a listing.
     * GET /api/v1/ai/buyer-matches/{ilan}/history
     */
    public function history(Ilan $ilan): JsonResponse
    {
        $logs = BuyerMatchLog::where('ilan_id', $ilan->id)
            ->with('buyer:id,ad,soyad')
            ->latest()
            ->get();

        return response()->json([
            'listing_id' => $ilan->id,
            'history' => $logs
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use App\Http\Requests\ListingIdAliasRequest;
use Illuminate\Http\JsonResponse;

/**
 * ️ SAB SEALED
 * AI Deal Predictor Controller (Thin Controller)
 */
class AIDealPredictorController extends Controller
{
    protected YalihanCortex $cortex;

    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * Get deal prediction for a specific listing.
     *
     * GET /api/v1/ai/deal-predictor?listing_id=123
     *
     * Phase 17 Adapter: dış dünya `listing_id` gönderir,
     * ListingIdAliasRequest bunu canonical `ilan_id`'ye eşler.
     */
    public function predict(ListingIdAliasRequest $request): JsonResponse
    {
        // Validation ListingIdAliasRequest üzerinden otomatik çalışır
        // locale hâlâ query string'den gelir
        $request->validate(['locale' => 'nullable|string|max:5']);

        $ilan = Ilan::findOrFail($request->ilanId());

        $prediction = $this->cortex->predictDeal($ilan, [
            'locale' => $request->locale ?? app()->getLocale(),
            'trigger' => 'api',
        ]);

        if (!$prediction['success']) {
            return response()->json([
                'success' => false,
                'error' => $prediction['error'] ?? 'Prediction failed',
            ], 500);
        }

        return response()->json($prediction);
    }
}

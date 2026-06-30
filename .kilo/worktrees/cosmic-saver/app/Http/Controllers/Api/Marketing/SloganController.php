<?php

namespace App\Http\Controllers\Api\Marketing;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Marketing\DynamicSloganService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Slogan API Controller
 *
 * Phase 8.0: Pazarlama ve Sosyal Medya Motoru
 * Context7 Standardı: C7-SLOGAN-API-2025-12-23
 */
class SloganController extends Controller
{
    /**
     * DynamicSloganService instance
     */
    private DynamicSloganService $sloganService;

    public function __construct(DynamicSloganService $sloganService)
    {
        $this->sloganService = $sloganService;
    }

    /**
     * Generate slogan for listing
     *
     * @param Request $request
     * @param int $ilanId
     * @return JsonResponse
     */
    public function generate(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);
        $type = $request->input('type', 'medium'); // short, medium, long, hashtag // context7-ignore

        try {
            $result = $this->sloganService->generateSlogan($ilan, $type);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slogan oluşturulamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate multiple slogan variations
     *
     * @param Request $request
     * @param int $ilanId
     * @return JsonResponse
     */
    public function variations(Request $request, int $ilanId): JsonResponse
    {
        $ilan = Ilan::findOrFail($ilanId);
        $count = (int) $request->input('count', 3);

        try {
            $variations = $this->sloganService->generateVariations($ilan, $count);

            return response()->json([
                'success' => true,
                'data' => [
                    'variations' => $variations,
                    'count' => count($variations),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slogan varyasyonları oluşturulamadı: ' . $e->getMessage(),
            ], 500);
        }
    }
}


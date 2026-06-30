<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\CortexHeatmapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CortexHeatmapController extends Controller
{
    public function __construct(
        protected CortexHeatmapService $heatmapService
    ) {}

    public function generateHeatmap(Request $request): JsonResponse
    {
        try {
            $filters = array_filter($request->only(['il_id', 'min_roi', 'category_id', 'min_price', 'max_price']), fn($v) => $v !== null);
            $result = $this->heatmapService->generateROIHeatmap($filters, $request->input('use_cache', true));

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['message']], 400);
            }
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Heatmap generation error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getCellProperties(string $cellId): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->heatmapService->getPropertiesInCell($cellId)]);
        } catch (\Exception $e) {
            Log::error('Cell properties error', ['cell_id' => $cellId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getHeatmapMetadata(): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->heatmapService->getHeatmapMetadata()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

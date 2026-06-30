<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\CortexVisualAnalyzer;
use App\Services\Analytics\CortexAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CortexVisualController extends Controller
{
    public function __construct(
        protected CortexVisualAnalyzer $visualAnalyzer,
        private readonly CortexAnalyticsService $analyticsService
    ) {}

    public function analyzePhotos(int $id, Request $request): JsonResponse
    {
        try {
            $ilan = Ilan::with(['fotograflar'])->findOrFail($id);
            $result = $this->visualAnalyzer->analyzePropertyPhotos($ilan, $request->input('force', false));

            return response()->json([
                'success' => true,
                'data' => $result,
                'ilan_id' => $ilan->id,
                'ilan_title' => $ilan->baslik,
            ]);
        } catch (\Exception $e) {
            Log::error('Visual analysis error', ['ilan_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function batchAnalyze(Request $request): JsonResponse
    {
        $ilanIds = $request->input('ilan_ids', []);
        if (empty($ilanIds) || count($ilanIds) > 20) {
            return response()->json(['success' => false, 'message' => 'Geçersiz ilan_ids'], 400);
        }

        $results = [];
        foreach ($ilanIds as $ilanId) {
            $ilan = Ilan::with(['fotograflar'])->find($ilanId);
            if (!$ilan) {
                $results[] = ['ilan_id' => $ilanId, 'success' => false, 'message' => 'Not found'];
                continue;
            }
            $results[] = array_merge(['ilan_id' => $ilanId], $this->visualAnalyzer->analyzePropertyPhotos($ilan, false));
        }

        return response()->json(['success' => true, 'data' => $results]);
    }

    public function getAutomationStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->analyticsService->getVisualAutomationStats()
        ]);
    }
}

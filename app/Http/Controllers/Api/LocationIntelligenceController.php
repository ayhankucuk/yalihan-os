<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LocationAnalyzeRequest;
use App\Services\Ilan\IlanLocationSyncService;
use App\Services\Location\LocationOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Location Intelligence API Controller — Sprint 6.2
 *
 * Thin controller — sadece HTTP katmanı.
 * İş mantığı LocationOrchestrator ve IlanLocationSyncService'te.
 */
class LocationIntelligenceController extends Controller
{
    public function __construct(
        private readonly LocationOrchestrator $orchestrator,
        private readonly IlanLocationSyncService $syncService,
    ) {}

    /**
     * POST /api/location/analyze
     *
     * Tam konum analizi çalıştır + Ilan'a kaydet.
     *
     * @param  LocationAnalyzeRequest  $request
     * @return JsonResponse
     */
    public function analyze(LocationAnalyzeRequest $request): JsonResponse
    {
        try {
            $ilanId = (int) $request->validated('ilan_id');
            $includeAiSummary = (bool) $request->validated('include_ai_summary', false);

            $result = $this->syncService->sync($ilanId, $includeAiSummary);

            return response()->json($result->toApiResponse(), $result->isOk() ? 200 : 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            /** @sab-ignore-catch: 404 = expected flow */
            return response()->json([
                'status' => 'ilan_not_found',
                'message' => 'İlan bulunamadı.',
                'data' => ['score' => null, 'confidence' => 'VERY_LOW'],
            ], 404);
        } catch (\Throwable $e) {
            Log::error('LocationController: analyze error', [
                'ilan_id' => $request->validated('ilan_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Analiz sırasında hata oluştu.',
                'data' => ['score' => null, 'confidence' => 'VERY_LOW'],
            ], 500);
        }
    }

    /**
     * GET /api/location/score/{ilanId}
     *
     * Sadece score + confidence döndür (cached).
     *
     * @param  int  $ilanId
     * @return JsonResponse
     */
    public function score(int $ilanId): JsonResponse
    {
        try {
            $summary = $this->orchestrator->getScoreSummary($ilanId);
            return response()->json($summary);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            /** @sab-ignore-catch: 404 = expected flow */
            return response()->json(['error' => 'İlan bulunamadı.'], 404);
        } catch (\Throwable $e) {
            Log::error('LocationController: score error', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Sunucu hatası.'], 500);
        }
    }

    /**
     * POST /api/location/batch
     *
     * Birden fazla Ilan'ı kuyruğa ekle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function batch(\Illuminate\Http\Request $request): JsonResponse
    {
        $ilanIds = $request->validated('ilan_ids', []);

        if (empty($ilanIds)) {
            return response()->json(['error' => 'ilan_ids boş olamaz.'], 422);
        }

        if (count($ilanIds) > 100) {
            return response()->json(['error' => 'En fazla 100 ilan eklenebilir.'], 422);
        }

        $queued = $this->syncService->scheduleBatch(array_map('intval', $ilanIds));

        return response()->json([
            'queued' => $queued,
            'total' => count($ilanIds),
            'message' => "{$queued} ilan kuyruğa eklendi.",
        ]);
    }
}

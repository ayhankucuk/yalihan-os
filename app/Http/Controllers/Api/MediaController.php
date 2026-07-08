<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaAnalyzeRequest;
use App\Jobs\AnalyzeMediaJob;
use App\Services\Media\MediaIntelligenceEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Media Intelligence API Controller — Sprint 6.3
 *
 * Thin controller — sadece HTTP katmanı.
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly MediaIntelligenceEngine $engine,
    ) {}

    /**
     * POST /api/media/analyze
     *
     * Fotoğraf analizi başlat (sync veya async).
     *
     * @param  MediaAnalyzeRequest  $request
     * @return JsonResponse
     */
    public function analyze(MediaAnalyzeRequest $request): JsonResponse
    {
        try {
            $ilanId = (int) $request->validated('ilan_id');
            $async = (bool) $request->validated('async', false);

            if ($async) {
                AnalyzeMediaJob::dispatch($ilanId);

                return response()->json([
                    'status' => 'queued',
                    'message' => 'Fotoğraf analizi kuyruğa eklendi.',
                    'ilan_id' => $ilanId,
                ], 202);
            }

            $result = $this->engine->analyze($ilanId);

            return response()->json([
                'status' => 'ok',
                'data' => $result->toArray(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status' => 'ilan_not_found',
                'message' => 'İlan bulunamadı.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('MediaController: analyze error', [
                'ilan_id' => $request->validated('ilan_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Analiz sırasında hata oluştu.',
            ], 500);
        }
    }

    /**
     * GET /api/media/score/{ilanId}
     *
     * Sadece media health skorunu döndür (cached).
     *
     * @param  int  $ilanId
     * @return JsonResponse
     */
    public function score(int $ilanId): JsonResponse
    {
        try {
            $ilan = \App\Models\Ilan::findOrFail($ilanId);

            return response()->json([
                'ilan_id' => $ilanId,
                'media_health_score' => $ilan->media_health_score,
                'health' => $this->healthLabel($ilan->media_health_score ?? 0),
                'quality_score' => $ilan->media_quality_score,
                'total_photos' => $ilan->fotograflar()->count(),
                'missing_rooms' => $ilan->eksik_odalar ?? [],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'İlan bulunamadı.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Sunucu hatası.'], 500);
        }
    }

    private function healthLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'EXCELLENT',
            $score >= 60 => 'GOOD',
            $score >= 40 => 'FAIR',
            $score >= 20 => 'POOR',
            default => 'MISSING',
        };
    }
}

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
 * API Contract: success | data | meta | error
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

                return $this->ok([
                    'ilan_id' => $ilanId,
                    'status' => 'queued',
                    'message' => 'Fotoğraf analizi kuyruğa eklendi.',
                ]);
            }

            $result = $this->engine->analyze($ilanId);

            return $this->ok($result->toArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->error('ilan_not_found', 'İlan bulunamadı.', 404);
        } catch (\Throwable $e) {
            Log::error('MediaController: analyze error', [
                'ilan_id' => $request->validated('ilan_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->error('analyze_failed', 'Analiz sırasında hata oluştu.', 500);
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
            $ilan = \App\Models\Ilan::with('fotograflar')->findOrFail($ilanId);

            return $this->ok([
                'ilan_id' => $ilanId,
                'media_health_score' => $ilan->media_health_score,
                'health' => $this->healthLabel($ilan->media_health_score ?? 0),
                'quality_score' => $ilan->media_quality_score,
                'total_photos' => $ilan->fotograflar->count(),
                'missing_rooms' => $ilan->eksik_odalar ?? [],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->error('ilan_not_found', 'İlan bulunamadı.', 404);
        } catch (\Throwable $e) {
            return $this->error('server_error', 'Sunucu hatası.', 500);
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

    /**
     * Build a success response conforming to API contract.
     */
    private function ok(array $data, int $httpStatus = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
            'error' => null,
        ], $httpStatus);
    }

    /**
     * Build an error response conforming to API contract.
     */
    private function error(string $code, string $message, int $httpStatus = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $httpStatus);
    }
}

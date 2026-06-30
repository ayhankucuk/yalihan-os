<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\Domains\CortexQualityService;
use Illuminate\Http\JsonResponse;

/**
 * OwnerIntelligenceController
 *
 * Owner portal readiness/intelligence endpoints.
 * Portföy hazırlık analizi endpoint'leri sağlar.
 *
 * SAB v3.4.3 — Sprint 3.4.3: Portföy Hazırlık Analizi
 * 
 * v1: Tamamen deterministic. AI/LLM yok. Sadece veri analizi.
 */
class OwnerIntelligenceController extends Controller
{
    public function __construct(
        private CortexQualityService $qualityService
    ) {}

    /**
     * Portföy hazırlık analizi döndürür.
     *
     * Tamamen deterministic: hiçbir AI/LLM çağrısı yok.
     * Sadece mevcut veritabanı verilerini analiz eder.
     *
     * @param Ilan $ilan
     * @return JsonResponse
     */
    public function readiness(Ilan $ilan): JsonResponse
    {
        if ($ilan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu portföye erişim yetkiniz yok.',
            ], 403);
        }

        $qualityResult = $this->qualityService->checkIlanQuality($ilan);

        return response()->json([
            'success' => true,
            'ilan_id' => $ilan->id,
            'data' => $qualityResult,
            'type' => 'deterministic_quality_check',
        ]);
    }
}

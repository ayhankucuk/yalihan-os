<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\Domains\CortexContentService;
use App\Services\AI\IlanStorytellingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * OwnerContentController
 *
 * AI destekli içerik üretimi endpoint'leri sağlar.
 * Portföy için başlık optimizasyonu ve açıklama üretimi.
 *
 * SAB v3.4.4 — Sprint 3.4.4: AI Açıklama Üretimi
 */
class OwnerContentController extends Controller
{
    public function __construct(
        private CortexContentService $contentService,
        private IlanStorytellingService $storytellingService
    ) {}

    /**
     * AI destekli başlık önerisi üretir.
     */
    public function generateTitle(Ilan $ilan): JsonResponse
    {
        if ($ilan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu portföye erişim yetkiniz yok.',
            ], 403);
        }

        $ilanData = [
            'baslik' => $ilan->baslik,
            'ana_kategori_id' => $ilan->ana_kategori_id,
            'kategori' => $ilan->anaKategori?->name ?? 'Konut',
            'il_id' => $ilan->il_id,
            'ilce_id' => $ilan->ilce_id,
            'mahalle_id' => $ilan->mahalle_id,
            'ozellik_ids' => $ilan->ozellikler->pluck('id')->toArray(),
        ];

        $result = $this->contentService->optimizeIlanTitle($ilanData);

        return response()->json([
            'success' => $result['success'],
            'ilan_id' => $ilan->id,
            'data' => $result,
        ]);
    }

    /**
     * AI destekli açıklama taslağı üretir.
     */
    public function generateDescription(Ilan $ilan): JsonResponse
    {
        if ($ilan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu portföye erişim yetkiniz yok.',
            ], 403);
        }

        try {
            // StorytellingService kullanarak AI açıklama üret
            $metin = $this->storytellingService->olustur($ilan->id, 'profesyonel');

            return response()->json([
                'success' => true,
                'ilan_id' => $ilan->id,
                'data' => [
                    'baslik' => $metin->baslik,
                    'aciklama' => $metin->aciklama,
                    'ton' => $metin->ton,
                    'metin_id' => $metin->id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI description generation failed: ' . $e->getMessage(), [
                'ilan_id' => $ilan->id,
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'AI açıklama üretilemedi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI içerik özetini döndürür (readiness ile birlikte).
     */
    public function contentSummary(Ilan $ilan): JsonResponse
    {
        if ($ilan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu portföye erişim yetkiniz yok.',
            ], 403);
        }

        $summary = $this->contentService->getContentSummary($ilan);

        return response()->json([
            'success' => true,
            'ilan_id' => $ilan->id,
            'data' => $summary,
        ]);
    }
}

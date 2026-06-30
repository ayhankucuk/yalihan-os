<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Services\Integrations\TKGMService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TKGM API Controller
 *
 * Context7 Standardı: C7-TKGM-API-2025-10-11
 * Context7 Kural #70: TKGM Entegrasyonu
 */
class TKGMController extends Controller
{
    use ValidatesApiRequests;

    protected $tkgmService;

    public function __construct(TKGMService $tkgmService)
    {
        $this->tkgmService = $tkgmService;
    }

    /**
     * Parsel sorgulama
     *
     * POST /api/tkgm/parsel-sorgu
     */
    public function parselSorgula(Request $request): JsonResponse
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ada' => 'required|string|max:20',
            'parsel' => 'required|string|max:20',
            'il' => 'required|string|max:100',
            'ilce' => 'required|string|max:100',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $result = $this->tkgmService->parselSorgula(
            $validated['ada'],
            $validated['parsel'],
            $validated['il'],
            $validated['ilce']
        );

        // ✅ REFACTORED: Using ResponseService
        if (isset($result['success']) && $result['success']) {
            return ResponseService::success($result['parsel_bilgileri'] ?? $result, 'Parsel sorgulama başarıyla tamamlandı');
        }

        return ResponseService::error($result['message'] ?? 'Parsel sorgulama başarısız', 400);
    }

    /**
     * Yatırım analizi
     *
     * POST /api/tkgm/yatirim-analizi
     */
    public function yatirimAnalizi(Request $request): JsonResponse
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ada' => 'required|string',
            'parsel' => 'required|string',
            'il' => 'required|string',
            'ilce' => 'required|string',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        // Önce parsel bilgilerini al
        $parselSonuc = $this->tkgmService->parselSorgula(
            $validated['ada'],
            $validated['parsel'],
            $validated['il'],
            $validated['ilce']
        );

        if (! $parselSonuc['success']) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::error('Parsel bilgileri alınamadı', 400);
        }

        // Yatırım analizi yap
        $analiz = $this->tkgmService->yatirimAnalizi($parselSonuc['parsel_bilgileri']);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success([
            'parsel_bilgileri' => $parselSonuc['parsel_bilgileri'],
            'yatirim_analizi' => $analiz,
        ], 'Yatırım analizi başarıyla tamamlandı');
    }

    /**
     * TKGM health check
     *
     * GET /api/tkgm/health
     */
    public function healthCheck(): JsonResponse
    {
        $health = $this->tkgmService->healthCheck();

        // ✅ REFACTORED: Using success boolean over forbidden durum word Check
        if (isset($health['success']) && $health['success'] === true) {
            return ResponseService::success($health, 'TKGM servisi sağlıklı');
        }

        return ResponseService::error('TKGM servisi sağlıksız', 503);
    }

    /**
     * Cache temizle
     *
     * POST /api/tkgm/clear-cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $this->tkgmService->clearCache(
            $request->get('ada'),
            $request->get('parsel'),
            $request->get('il'),
            $request->get('ilce')
        );

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(null, 'TKGM cache temizlendi');
    }
}

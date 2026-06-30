<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Intelligence\AdaptiveUIUXIntelligenceService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Adaptive UI/UX API Controller
 * Context7: Uyarlanabilir Arayüz Zekası API Endpoint'leri
 */
class AdaptiveUIUXController extends Controller
{
    public function __construct(
        private AdaptiveUIUXIntelligenceService $adaptive
    ) {}

    /**
     * Kullanıcı davranışını analiz et
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function analyzeBehavior(int $userId): JsonResponse
    {
        try {
            $result = $this->adaptive->analyzeUserBehavior($userId);

            return ResponseService::success($result, 'Kullanıcı davranış analizi başarıyla tamamlandı.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Davranış analizi yapılamadı', $e);
        }
    }

    /**
     * UI optimizasyon önerileri
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getOptimizations(int $userId): JsonResponse
    {
        try {
            $result = $this->adaptive->getUIOptimizations($userId);

            return ResponseService::success($result, 'UI optimizasyon önerileri başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Optimizasyon önerileri oluşturulamadı', $e);
        }
    }
}


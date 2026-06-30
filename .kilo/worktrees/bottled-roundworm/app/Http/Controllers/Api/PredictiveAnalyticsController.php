<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Intelligence\PredictiveAnalyticsEngineService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Predictive Analytics API Controller
 * Context7: Tahmine Dayalı Analiz Motoru API Endpoint'leri
 */
class PredictiveAnalyticsController extends Controller
{
    public function __construct(
        private PredictiveAnalyticsEngineService $predictive
    ) {}

    /**
     * Satış tahmini
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function predictSales(Request $request): JsonResponse
    {
        try {
            $context = $request->input('context', []);
            $result = $this->predictive->predictSales($context);

            return ResponseService::success($result, 'Satış tahmini başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Satış tahmini yapılamadı', $e);
        }
    }

    /**
     * Gelir tahmini
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function predictRevenue(Request $request): JsonResponse
    {
        try {
            $context = $request->input('context', []);
            $result = $this->predictive->predictRevenue($context);

            return ResponseService::success($result, 'Gelir tahmini başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Gelir tahmini yapılamadı', $e);
        }
    }
}

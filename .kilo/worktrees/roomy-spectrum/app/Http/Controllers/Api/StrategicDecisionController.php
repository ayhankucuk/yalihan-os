<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Intelligence\StrategicDecisionEngineService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Strategic Decision API Controller
 * Context7: Stratejik Karar Motoru API Endpoint'leri
 */
class StrategicDecisionController extends Controller
{
    public function __construct(
        private StrategicDecisionEngineService $decisionEngine
    ) {}

    /**
     * Stratejik karar analizi
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyzeAndDecide(Request $request): JsonResponse
    {
        try {
            $context = $request->input('context', []);
            $result = $this->decisionEngine->analyzeAndDecide($context);

            return ResponseService::success($result, 'Stratejik karar analizi başarıyla tamamlandı.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Stratejik karar analizi yapılamadı', $e);
        }
    }
}


<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Intelligence\AutoLearningService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Auto-Learning API Controller
 * Context7: Otomatik Öğrenme Sistemi API Endpoint'leri
 */
class AutoLearningController extends Controller
{
    public function __construct(
        private AutoLearningService $learning
    ) {}

    /**
     * Başarılı pattern'leri tespit et
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detectSuccessPatterns(Request $request): JsonResponse
    {
        try {
            $module = $request->input('module');
            $action = $request->input('action');
            $days = (int) $request->input('days', 30);

            if (!$module || !$action) {
                return ResponseService::error('Module ve action parametreleri gerekli', 400);
            }

            $result = $this->learning->detectSuccessPatterns($module, $action, $days);

            return ResponseService::success($result, 'Pattern\'ler başarıyla tespit edildi.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Pattern tespit edilemedi', $e);
        }
    }

    /**
     * Başarısız işlemleri analiz et
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyzeFailures(Request $request): JsonResponse
    {
        try {
            $module = $request->input('module');
            $action = $request->input('action');
            $days = (int) $request->input('days', 30);

            if (!$module || !$action) {
                return ResponseService::error('Module ve action parametreleri gerekli', 400);
            }

            $result = $this->learning->analyzeFailures($module, $action, $days);

            return ResponseService::success($result, 'Hata analizi başarıyla tamamlandı.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Hata analizi yapılamadı', $e);
        }
    }

    /**
     * İyileştirme önerileri
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getImprovements(Request $request): JsonResponse
    {
        try {
            $module = $request->input('module');
            $result = $this->learning->getImprovementSuggestions($module);

            return ResponseService::success($result, 'İyileştirme önerileri başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('İyileştirme önerileri oluşturulamadı', $e);
        }
    }
}

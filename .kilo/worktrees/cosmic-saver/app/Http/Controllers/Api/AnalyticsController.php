<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Analytics\AIDecisionInconsistencyAnalyzer;
use App\Services\Analytics\CommissionRiskAnalyzer;
use App\Services\Analytics\CSSViolationScanner;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Analytics Controller
 *
 * Context7 Standardı: C7-ANALYTICS-CONTROLLER-2025-11-25
 *
 * Analiz ve raporlama endpoint'leri
 */
class AnalyticsController extends Controller
{
    /**
     * AI Karar Tutarsızlığı Analizi
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function aiDecisionInconsistency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min_records' => 'sometimes|integer|min:1|max:100',
            'threshold' => 'sometimes|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $analyzer = new AIDecisionInconsistencyAnalyzer;

            $minRecords = $request->input('min_records', 5);
            $threshold = $request->input('threshold', 2.0);

            $result = $analyzer->analyze($minRecords, $threshold);

            return ResponseService::success($result, 'AI karar tutarsızlığı analizi tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('AI karar tutarsızlığı analizi başarısız', $e);
        }
    }

    /**
     * Belirli bir request_data için detaylı analiz
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function aiDecisionByRequestData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_data_hash' => 'required|string',
            'threshold' => 'sometimes|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $analyzer = new AIDecisionInconsistencyAnalyzer;

            $requestDataHash = $request->input('request_data_hash');
            $threshold = $request->input('threshold', 2.0);

            $result = $analyzer->analyzeByRequestData($requestDataHash, $threshold);

            return ResponseService::success($result, 'Request data analizi tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Request data analizi başarısız', $e);
        }
    }

    /**
     * Komisyon Eksikliği Risk Analizi
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissionRisk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'sometimes|integer|min:2020|max:'.(now()->year + 1),
            'simulation_percentage' => 'sometimes|numeric|min:0|max:1',
            'use_simulation' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $analyzer = new CommissionRiskAnalyzer;

            $year = $request->input('year');
            $simulationPercentage = $request->input('simulation_percentage', 0.30); // %30
            $useSimulation = $request->input('use_simulation', true);

            $result = $analyzer->analyze($year, $simulationPercentage, $useSimulation);

            return ResponseService::success($result, 'Komisyon risk analizi tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Komisyon risk analizi başarısız', $e);
        }
    }

    /**
     * CSS İhlalleri Taraması
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cssViolations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min_violations' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $scanner = new CSSViolationScanner;

            $minViolations = $request->input('min_violations', 3);

            $result = $scanner->scan($minViolations);

            return ResponseService::success($result, 'CSS ihlali taraması tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('CSS ihlali taraması başarısız', $e);
        }
    }
}

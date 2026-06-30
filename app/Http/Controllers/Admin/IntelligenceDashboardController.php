<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Intelligence\ActionScoreService;
use App\Services\Intelligence\BudgetCorrectionService;
use App\Services\Intelligence\ContractGuardService;
use App\Services\Intelligence\SentimentAnalysisService;
use App\Services\Intelligence\MultilingualService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Intelligence Dashboard Controller
 * Context7: Fırsat Sentezi (Opportunity Synthesis) için dashboard controller
 */
class IntelligenceDashboardController extends Controller
{
    public function __construct(
        private \App\Services\Intelligence\IntelligenceOrchestrator $orchestrator
    ) {}

    /**
     * Opportunity Board View
     *
     * @return \Illuminate\View\View
     */
    public function opportunityBoard()
    {
        $topOpportunities = $this->orchestrator->actionScore->getTopOpportunities(5);

        return view('admin.intelligence.opportunity-board', [
            'opportunities' => $topOpportunities,
        ]);
    }

    /**
     * API: Get Top Opportunities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function apiOpportunities(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);
        $limit = min(max($limit, 1), 50); // 1-50 arası

        $opportunities = $this->orchestrator->actionScore->getTopOpportunities($limit);

        return ResponseService::success([
            'opportunities' => $opportunities,
            'total' => count($opportunities),
            'limit' => $limit,
        ], 'Fırsatlar başarıyla alındı');
    }

    /**
     * API: Calculate Action Score for specific customer
     *
     * @param Request $request
     * @param int $kisiId
     * @return JsonResponse
     */
    public function apiActionScore(Request $request, int $kisiId): JsonResponse
    {
        $kisi = \App\Models\Kisi::find($kisiId);

        if (!$kisi) {
            return ResponseService::error('Müşteri bulunamadı', 404);
        }

        $actionScore = $this->orchestrator->actionScore->calculateActionScore($kisi);

        return ResponseService::success($actionScore, 'Action Score hesaplandı');
    }

    /**
     * Clear cache for specific customer
     *
     * @param Request $request
     * @param int $kisiId
     * @return JsonResponse
     */
    public function clearCache(Request $request, int $kisiId): JsonResponse
    {
        $this->orchestrator->actionScore->clearCache($kisiId);

        return ResponseService::success(null, 'Cache temizlendi');
    }

    /**
     * API: Calculate Budget Correction for specific customer
     *
     * @param Request $request
     * @param int $kisiId
     * @return JsonResponse
     */
    public function apiBudgetCorrection(Request $request, int $kisiId): JsonResponse
    {
        $kisi = \App\Models\Kisi::find($kisiId);

        if (!$kisi) {
            return ResponseService::error('Müşteri bulunamadı', 404);
        }

        $budgetCorrection = $this->orchestrator->budgetCorrection->calculateRealPurchasePower($kisi);

        return ResponseService::success($budgetCorrection, 'Bütçe düzeltmesi hesaplandı');
    }

    /**
     * API: Analyze Legal Risks for listing
     *
     * @param Request $request
     * @param int $ilanId
     * @return JsonResponse
     */
    public function apiContractGuard(Request $request, int $ilanId): JsonResponse
    {
        $ilan = \App\Models\Ilan::find($ilanId);

        if (!$ilan) {
            return ResponseService::error('İlan bulunamadı', 404);
        }

        $contractPrice = $request->get('contract_price');
        $analysis = $this->orchestrator->contractGuard->analyzeLegalRisks($ilan, $contractPrice);

        return ResponseService::success($analysis, 'Hukuki risk analizi tamamlandı');
    }

    /**
     * API: Analyze Sentiment for customer
     *
     * @param Request $request
     * @param int $kisiId
     * @return JsonResponse
     */
    public function apiSentimentAnalysis(Request $request, int $kisiId): JsonResponse
    {
        $kisi = \App\Models\Kisi::find($kisiId);

        if (!$kisi) {
            return ResponseService::error('Müşteri bulunamadı', 404);
        }

        $sentiment = $this->orchestrator->sentimentAnalysis->analyzeSentiment($kisi);

        return ResponseService::success($sentiment, 'Hissiyat analizi tamamlandı');
    }

    /**
     * API: Generate Multilingual Description
     *
     * @param Request $request
     * @param int $ilanId
     * @return JsonResponse
     */
    public function apiMultilingualDescription(Request $request, int $ilanId): JsonResponse
    {
        $ilan = \App\Models\Ilan::find($ilanId);

        if (!$ilan) {
            return ResponseService::error('İlan bulunamadı', 404);
        }

        $targetLanguage = $request->get('language', 'en');
        $result = $this->orchestrator->multilingual->generateLocalizedDescription($ilan, $targetLanguage);

        if (!$result['success']) {
            return ResponseService::error($result['error'] ?? 'Çeviri hatası', 500);
        }

        return ResponseService::success($result, 'Çok dilli açıklama üretildi');
    }
}

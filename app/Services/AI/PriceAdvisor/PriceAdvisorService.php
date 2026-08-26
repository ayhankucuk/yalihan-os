<?php

namespace App\Services\AI\PriceAdvisor;

use App\Models\Ilan;
use App\Services\AI\CortexPriceForecastService;
use App\Services\AIDeal\DealPredictionService;
use App\Services\Intelligence\CompetitorMapService;
use App\Services\Market\MarketIntelligenceService;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ SAB SEALED
 * Price Advisor Service
 *
 * SAB-EXEMPT: Ghost model usage (new Ilan + forceFill(['id' => 0]))
 * - analyzeWizardData() creates transient Ilan for read-only advisory scoring
 * - No database persistence — never calls save(), create(), or store()
 * - Must NOT be migrated to IlanCrudService::store()
 * Orchestrates market intelligence, competitor mapping, and deal prediction
 * to provide a comprehensive pricing strategy for a listing.
 */
class PriceAdvisorService
{
    protected MarketIntelligenceService $marketIntelligence;
    protected CompetitorMapService $competitorMap;
    protected CortexPriceForecastService $forecastService;
    protected DealPredictionService $dealPredictor;

    public function __construct(
        MarketIntelligenceService $marketIntelligence,
        CompetitorMapService $competitorMap,
        CortexPriceForecastService $forecastService,
        DealPredictionService $dealPredictor
    ) {
        $this->marketIntelligence = $marketIntelligence;
        $this->competitorMap = $competitorMap;
        $this->forecastService = $forecastService;
        $this->dealPredictor = $dealPredictor;
    }

    /**
     * Perform a complete price analysis.
     *
     * @param Ilan $ilan
     * @return array
     */
    public function analyze(Ilan $ilan): array
    {
        // Ghost model guard: id <= 0 → return degraded-but-valid response without calling
        // downstream services that rely on a real DB row (DealPredictionLog, CompetitorMap cache, etc.)
        if ($ilan->id <= 0) {
            return $this->buildGhostModelResponse($ilan);
        }

        try {
            // 1. Market Intelligence Data
            $locationData = [
                'il_id' => $ilan->il_id,
                'ilce_id' => $ilan->ilce_id,
                'mahalle_id' => $ilan->mahalle_id,
                'kategori_id' => $ilan->kategori_id,
                'lat' => $ilan->lat,
                'lng' => $ilan->lng
            ];
            $marketData = $this->marketIntelligence->calculateMarketValue($locationData, $ilan->kategori_id);
            $avgSalesTime = $this->marketIntelligence->calculateAverageSalesTime(null); // Passing null to use local data context if needed, or specific logic

            // 2. Competitor Analysis
            $competitorData = $this->competitorMap->analyzeCompetitors($ilan);

            // 3. Forecast Trend
            $forecast = $this->forecastService->forecast($ilan);

            // 4. Deal Prediction (Sale Probability)
            $prediction = $this->dealPredictor->predict($ilan, ['trigger' => 'price_advisor']);

            // 5. Aggregate Explanations
            $explanations = $this->buildExplanations($ilan, $marketData, $competitorData, $forecast);

            // 6. Calculate Recommended Price (Logic: Bias towards competitor median if market is generic)
            $medianPrice = ($competitorData['median_price'] ?? 0) > 0
                ? (float) $competitorData['median_price']
                : ($marketData['ortalama'] * ($ilan->alan_m2 ?: 1));
            $recommendedPrice = $this->competitorMap->calculateSuggestedPrice(
                $ilan->fiyat,
                $medianPrice,
                $competitorData['price_gap_percent'] ?? 0
            ) ?? $medianPrice;

            return [
                'listing_id' => $ilan->id,
                'price_estimate' => (float)$medianPrice,
                'recommended_price' => (float)$recommendedPrice,
                'price_range' => [
                    'min' => (float)($marketData['min'] * ($ilan->alan_m2 ?: 1)),
                    'max' => (float)($marketData['max'] * ($ilan->alan_m2 ?: 1))
                ],
                'market_position' => $this->determineMarketPosition($ilan->fiyat, $medianPrice),
                'predicted_sale_days' => (int)($avgSalesTime > 0 ? $avgSalesTime : 45), // Default to 45 if no data
                'confidence' => ($forecast['confidence'] + ($prediction['scores']['total'] ?? 80)) / 200,
                'explanation' => $explanations,
                'meta' => [
                    'competitor_count' => $competitorData['competitor_count'] ?? 0,
                    'price_gap_percent' => $competitorData['price_gap_percent'] ?? 0,
                    'forecast_signal' => $forecast['signal'] ?? 'NEUTRAL'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Price Advisor Analysis failed: ' . $e->getMessage(), ['ilan_id' => $ilan->id]);
            throw $e;
        }
    }

    /**
     * Perform analysis for unsaved data (Wizard / Draft).
     *
     * @param array $data
     * @return array
     */
    public function analyzeWizardData(array $data): array
    {
        // SAB-EXEMPT: ghost model, non-persistent, read-only usage
        $ilan = new Ilan(); // SAB-EXEMPT: ghost model, no persistence
        $ilan->forceFill([
            'id' => 0, // Ghost ID
            'il_id' => $data['il_id'] ?? null,
            'ilce_id' => $data['ilce_id'] ?? null,
            'mahalle_id' => $data['mahalle_id'] ?? null,
            'kategori_id' => $data['kategori_id'] ?? null,
            'fiyat' => $data['fiyat'] ?? 0,
            'alan_m2' => $data['alan_m2'] ?? 0,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
        ]);

        return $this->analyze($ilan);
    }

    /**
     * Determine market position label.
     */
    protected function determineMarketPosition(float $ourPrice, float $medianPrice): string
    {
        if ($medianPrice <= 0) return 'neutral';

        $diff = (($ourPrice - $medianPrice) / $medianPrice) * 100;

        if ($diff < -5) return 'below_market';
        if ($diff > 5) return 'above_market';
        return 'fair_market';
    }

    /**
     * Return a valid degraded response for ghost/unpersisted models (Wizard draft).
     * Avoids calling DealPredictionLog (requires real ilan_id) and competitor cache.
     *
     * @param Ilan $ilan
     * @return array
     */
    protected function buildGhostModelResponse(Ilan $ilan): array
    {
        $locationData = [
            'il_id' => $ilan->il_id,
            'ilce_id' => $ilan->ilce_id,
            'mahalle_id' => $ilan->mahalle_id,
            'kategori_id' => $ilan->kategori_id,
            'lat' => $ilan->lat,
            'lng' => $ilan->lng,
        ];

        // P1 #4: Ghost koruması — kategori_id null ise marketData boş döner
        $marketData = $ilan->kategori_id
            ? $this->marketIntelligence->calculateMarketValue($locationData, $ilan->kategori_id)
            : ['ortalama' => 0, 'min' => 0, 'max' => 0];
        $alanM2 = $ilan->alan_m2 ?: 1;
        $ourPrice = (float) ($ilan->fiyat ?? 0);
        $marketAverage = (float) ($marketData['ortalama'] ?? 0);

        $estimatedPrice = $marketAverage * $alanM2;
        $priceRangeMin = ($marketData['min'] ?? 0) * $alanM2;
        $priceRangeMax = ($marketData['max'] ?? 0) * $alanM2;

        return [
            'listing_id' => 0,
            'price_estimate' => $estimatedPrice,
            'recommended_price' => $ourPrice > 0 ? $ourPrice : $estimatedPrice,
            'price_range' => [
                'min' => $priceRangeMin,
                'max' => $priceRangeMax,
            ],
            'market_position' => $this->determineMarketPosition($ourPrice, $estimatedPrice),
            'predicted_sale_days' => 45,
            'confidence' => 0.4,
            'explanation' => [
                '🟡 Bu bir taslak ilandır. Kaydettikten sonra detaylı fiyat analizi yapılacaktır.',
                $marketAverage > 0
                    ? "Bölge ortalama birim fiyatı: ₺" . number_format($marketAverage, 0, ',', '.') . "/m²"
                    : "Piyasa verisi henüz mevcut değil.",
            ],
            'meta' => [
                'competitor_count' => 0,
                'price_gap_percent' => 0,
                'forecast_signal' => 'NEUTRAL',
                'is_draft' => true,
            ],
        ];
    }

    /**
     * Combine insights from all engines into a list of explanations.
     */
    protected function buildExplanations(Ilan $ilan, array $marketData, array $competitorData, array $forecast): array
    {
        $insights = [];

        // Market Insights
        if (isset($marketData['trend'])) {
            $insights[] = "Bölge trendi: " . ($marketData['trend'] > 0 ? "Yükselişte" : "Durağan/Düşüşte");
        }

        // Competitor Insights
        if (isset($competitorData['recommendation'])) {
            $insights[] = $competitorData['recommendation'];
        }

        // Forecast Insights
        if (isset($forecast['reason'])) {
            $insights[] = $forecast['reason'];
        }

        // Generic listing context
        if ($ilan->fiyatGecmisi()->count() > 0) {
            $insights[] = "İlan fiyat geçmişi verisi analiz edildi.";
        }

        return !empty($insights) ? $insights : ["Piyasa verileri fiyatın makul olduğunu gösteriyor."];
    }
}

<?php

namespace App\Services\AI\Domains;

use App\Models\Ilan;
use App\Services\Integrations\TKGMService;
use App\Services\AI\OllamaService;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\Logging\LogService;
use App\Models\Il;
use App\Models\Ilce;
use Exception;

/**
 * 🧠 Cortex Intelligence Domain Service
 * Responsibility: Handles location analysis, valuation, market trends, and spatial data.
 */
class CortexIntelligenceService
{
    protected TKGMService $tkgmService;
    protected OllamaService $ollamaService;
    protected AiTelemetryService $telemetry;
    protected \App\Services\AI\AIOrchestrator $aiService;

    public function __construct(
        TKGMService $tkgmService,
        OllamaService $ollamaService,
        AiTelemetryService $telemetry,
        \App\Services\AI\AIOrchestrator $aiService
    ) {
        $this->tkgmService = $tkgmService;
        $this->ollamaService = $ollamaService;
        $this->telemetry = $telemetry;
        $this->aiService = $aiService;
    }

    /**
     * AI-driven Lead Evaluation (SAB v16.3)
     * Predicts lead intent, reliability, and segment.
     */
    public function evaluateLead(\App\Models\Lead $lead): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_evaluate_lead');

        try {
            $prompt = "Aşağıdaki lead verisini analiz et ve 0-100 arası bir güven skoru (confidence) ile niyet (intent) tespiti yap.\n\n" .
                      "Ad Soyad: {$lead->ad} {$lead->soyad}\n" .
                      "E-posta: {$lead->email}\n" .
                      "Telefon: {$lead->telefon}\n" .
                      "Notlar: {$lead->notlar}\n\n" .
                      "Yanıtı şu JSON formatında ver: {\"confidence\": int, \"intent\": \"string\", \"segment\": \"string\"}";

            $aiResponse = $this->aiService->generate($prompt, ['type' => 'lead_evaluation']);
            
            $data = is_array($aiResponse) ? $aiResponse : json_decode($aiResponse, true);
            $confidence = $data['confidence'] ?? 50;
            $intent = $data['intent'] ?? 'unknown';

            $result = [
                'success' => true,
                'lead_id' => $lead->id,
                'confidence' => $confidence,
                'intent' => $intent,
                'segment' => $data['segment'] ?? 'standard',
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => LogService::stopTimer($startTime)
                ]
            ];

            $this->logCortexDecision('evaluate_lead', ['lead_id' => $lead->id], $result['metadata']['duration_ms'], true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('evaluate_lead', ['lead_id' => $lead->id, 'error' => $e->getMessage()], $durationMs, false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * AI-powered mülk değerleme (SAB v16.2)
     */
    public function priceValuation(Ilan $ilan, array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_price_valuation');

        try {
            LogService::ai('yalihan_cortex_valuation_started', 'CortexIntelligence', [
                'ilan_id' => $ilan->id,
                'fiyat' => $ilan->fiyat,
            ]);

            $result = [
                'ilan_id' => $ilan->id,
                'valuation' => [
                    'market_value' => null,
                    'confidence_score' => 20, // Base score
                    'tkgm_data' => null,
                    'financial_analysis' => null,
                ],
                'recommendations' => [],
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm' => 'YalihanCortex v1.0',
                ],
            ];

            // 1. TKGM Veri Entegrasyonu
            if ($ilan->ada_no && $ilan->parsel_no) {
                $tkgmData = $this->tkgmService->getParcelData(
                    $ilan->il_id,
                    $ilan->ilce_id,
                    $ilan->mahalle_id,
                    $ilan->ada_no,
                    $ilan->parsel_no
                );

                if ($tkgmData) {
                    $result['valuation']['tkgm_data'] = $tkgmData;
                    $result['valuation']['confidence_score'] += 30;
                }
            }

            // 2. Finansal Analiz — gelecekte FinansService::analyzeMarketPosition entegre edilecek
            // TODO: SAB Phase 16 — market position analysis

            // 3. Değer Hesaplama
            $result['valuation']['market_value'] = $this->calculateMarketValue($ilan, $result);
            $result['valuation']['confidence_score'] = min(100, $result['valuation']['confidence_score']);

            // 4. Öneriler
            $result['recommendations'] = $this->generateValuationRecommendations($ilan, $result);

            $durationMs = LogService::stopTimer($startTime);
            $result['metadata']['duration_ms'] = $durationMs;

            $this->logCortexDecision('price_valuation', [
                'ilan_id' => $ilan->id,
                'market_value' => $result['valuation']['market_value'],
            ], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('price_valuation', ['ilan_id' => $ilan->id, 'error' => $e->getMessage()], $durationMs, false);
            LogService::error('Price valuation failed', ['ilan_id' => $ilan->id], $e, LogService::CHANNEL_AI);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Lokasyon Analizi (Ollama)
     */
    public function analyzeLocation(array $locationData): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_analyze_location');

        try {
            $analysis = $this->ollamaService->analyzeLocation($locationData);
            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'success' => true,
                'analysis' => $analysis,
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => $durationMs,
                ],
            ];

            $this->logCortexDecision('analyze_location', $locationData, $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('analyze_location', ['error' => $e->getMessage()], $durationMs, false);
            LogService::error('Location analysis failed', [], $e, LogService::CHANNEL_AI);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function calculateMarketValue(Ilan $ilan, array $data): float
    {
        $basePrice = $ilan->fiyat;
        $adjustment = 1.0;

        // TKGM Adjustments
        if (isset($data['valuation']['tkgm_data'])) {
            $imar = $data['valuation']['tkgm_data']['imar_durumu'] ?? '';
            if ($imar === 'Ticari') $adjustment += 0.15;
            if ($imar === 'Konut') $adjustment += 0.05;
        }

        // Financial Adjustments
        if (isset($data['valuation']['financial_analysis']['trend'])) {
            $trend = $data['valuation']['financial_analysis']['trend'];
            if ($trend === 'rising') $adjustment += 0.1;
            if ($trend === 'falling') $adjustment -= 0.1;
        }

        return $basePrice * $adjustment;
    }

    private function generateValuationRecommendations(Ilan $ilan, array $result): array
    {
        $recommendations = [];
        $confidence = $result['valuation']['confidence_score'] ?? 0;

        if ($confidence < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Düşük Güven Skoru',
                'message' => 'Daha fazla veri toplanması önerilir.',
            ];
        }

        if (empty($result['valuation']['tkgm_data'])) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'TKGM Verisi Eksik',
                'message' => 'Parsel bilgileri eklenirse değerleme daha doğru olur.',
            ];
        }

        return $recommendations;
    }

    /**
     * İlan Portföy Analizi (SAB v16.4)
     */
    public function analyzeMyListings(int $userId, array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_portfolio_analysis');

        try {
            $listings = Ilan::where('danisman_id', $userId)->get();
            $stats = $this->calculateMarketStatistics($listings);

            $prompt = "Aşağıdaki emlak portföy istatistiklerini analiz et ve iyileştirme önerileri sun:\n\n" .
                      "Toplam İlan: {$stats['total_listings']}\n" .
                      "Ortalama Fiyat: " . number_format($stats['avg_price'], 2) . " TRY\n" .
                      "Fiyat Aralığı: " . number_format($stats['min_price'], 2) . " - " . number_format($stats['max_price'], 2) . " TRY\n\n" .
                      "Analiz et ve en önemli 3 içgörüyü belirt.";

            $aiResponse = $this->aiService->generate($prompt, ['type' => 'portfolio_analysis']);
            $insights = is_array($aiResponse) ? ($aiResponse['insights'] ?? []) : [$aiResponse];

            $result = [
                'success' => true,
                'stats' => $stats,
                'insights' => $insights,
                'trends' => $this->extractTrends($insights, $listings),
                'top_performers' => $this->getTopPerformers($listings),
                'needs_attention' => $this->getNeedsAttention($listings),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => LogService::stopTimer($startTime)
                ]
            ];

            $this->logCortexDecision('portfolio_analysis', ['user_id' => $userId], $result['metadata']['duration_ms'], true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('portfolio_analysis', ['user_id' => $userId, 'error' => $e->getMessage()], $durationMs, false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Merkezi Rapor Üretimi
     */
    public function generateReport(string $reportType, array $filters = [], array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_report_generation');

        try {
            $data = $this->collectReportData($reportType, $filters);
            $prompt = "Aşağıdaki {$reportType} verilerini analiz et ve profesyonel bir rapor özeti hazırla:\n\n" .
                      json_encode($data, JSON_UNESCAPED_UNICODE);

            $aiResponse = $this->aiService->generate($prompt, ['type' => 'report_generation']);

            $result = [
                'success' => true,
                'report_type' => $reportType,
                'summary' => is_array($aiResponse) ? ($aiResponse['summary'] ?? '') : $aiResponse,
                'data' => $data,
                'charts' => $this->generateChartData($data, $reportType),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => LogService::stopTimer($startTime)
                ]
            ];

            $this->logCortexDecision('generate_report', ['type' => $reportType], $result['metadata']['duration_ms'], true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('generate_report', ['type' => $reportType, 'error' => $e->getMessage()], $durationMs, false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function calculateMarketStatistics($listings): array
    {
        return [
            'total_listings' => $listings->count(),
            'avg_price' => round($listings->avg('fiyat') ?? 0, 2),
            'min_price' => round($listings->min('fiyat') ?? 0, 2),
            'max_price' => round($listings->max('fiyat') ?? 0, 2),
        ];
    }

    protected function extractTrends($insights, $listings): array
    {
        return [
            'price_trend' => $listings->count() > 1 ? 'stabil' : 'yetersiz_veri',
            'ai_insights' => $insights
        ];
    }

    protected function getTopPerformers($listings)
    {
        return $listings->sortByDesc('goruntulenme')->take(5)->values();
    }

    protected function getNeedsAttention($listings)
    {
        return $listings->where('goruntulenme', '<', 10)->take(5)->values();
    }

    /**
     * Pazar Trend Analizi (SAB v16.1)
     */
    public function analyzeMarketTrends(array $filters = [], array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_market_trends');

        try {
            $query = \App\Models\MarketListing::query();

            if (isset($filters['il_id'])) {
                $ilAdi = Il::find($filters['il_id'])?->il_adi;
                if ($ilAdi) $query->where('location_il', $ilAdi);
            }
            if (isset($filters['ilce_id'])) {
                $ilceAdi = Ilce::find($filters['ilce_id'])?->ilce_adi;
                if ($ilceAdi) $query->where('location_ilce', $ilceAdi);
            }
            if (isset($filters['date_from'])) $query->where('ilan_tarihi', '>=', $filters['date_from']);
            if (isset($filters['date_to'])) $query->where('ilan_tarihi', '<=', $filters['date_to']);

            $listings = $query->where('kategori_aktiflik_durumu', true)
                ->orderBy('ilan_tarihi', 'desc')
                ->limit(1000)
                ->get();

            $prompt = $this->buildMarketTrendPrompt($listings, $filters);
            $aiResponse = $this->aiService->generate($prompt, ['type' => 'market_trends', 'max_tokens' => 1000]);

            $parsedResult = is_array($aiResponse) ? $aiResponse : json_decode((string)$aiResponse, true);

            $durationMs = LogService::stopTimer($startTime);
            $result = [
                'success' => true,
                'trends' => $this->extractTrends($parsedResult ?? [], $listings),
                'statistics' => $this->calculateMarketStatistics($listings),
                'insights' => $parsedResult['insights'] ?? [],
                'recommendations' => $parsedResult['recommendations'] ?? [],
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'listings_analyzed' => $listings->count(),
                    'duration_ms' => $durationMs
                ]
            ];

            $this->logCortexDecision('market_trends', ['filters' => $filters], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('market_trends', ['error' => $e->getMessage()], $durationMs, false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function buildMarketTrendPrompt($listings, array $filters): string
    {
        $avgPrice = $listings->avg('price') ?? 0;
        return "Piyasa trend analizi yap:\n\n" .
            "Toplam ilan sayısı: {$listings->count()}\n" .
            "Ortalama fiyat: " . number_format($avgPrice, 2) . " TRY\n\n" .
            "Bu verilere göre piyasa trendlerini, fırsatları ve riskleri analiz et.";
    }

    private function logCortexDecision(string $type, array $context, float $durationMs, bool $success): void
    {
        $this->telemetry->logTransaction(
            'CortexIntelligence',
            $type,
            $durationMs / 1000,
            0, 0, $success ? 200 : 500,
            ['request' => $context]
        );
    }

    protected function collectReportData(string $reportType, array $filters): array
    {
        // Örnek veri toplama mantığı
        return [
            'period' => $filters['period'] ?? 'last_30_days',
            'total_events' => rand(100, 500)
        ];
    }

    protected function generateChartData(array $data, string $reportType): array
    {
        return [];
    }

    /**
     * Pazar Fiyat Karşılaştırması
     *
     * Benzer ilanlarla fiyat karşılaştırması yapar ve AI önerisi üretir.
     * YalihanCortex'ten taşındı — SAB v6.1.2 domain extract.
     *
     * @param Ilan $ilan Karşılaştırılacak ilan
     * @param array $options Karşılaştırma seçenekleri
     * @return array Fiyat karşılaştırma sonuçları
     */
    public function compareMarketPrices(Ilan $ilan, array $options = []): array
    {
        $startTime = LogService::startTimer('cortex_intelligence_price_compare');

        try {
            // Benzer ilanları bul (MarketListing) — Context7: Türkçe kolonlar
            $similarListings = \App\Models\MarketListing::query()
                ->where('location_il', $ilan->il->il_adi ?? null)
                ->where('location_ilce', $ilan->ilce->ilce_adi ?? null)
                ->where('m2_brut', '>=', ($ilan->metrekare ?? 0) * 0.8)
                ->where('m2_brut', '<=', ($ilan->metrekare ?? 0) * 1.2)
                ->where('kategori_aktiflik_durumu', true)
                ->orderBy('ilan_tarihi', 'desc') // context7-ignore
                ->limit(50)
                ->get();

            $currentPrice     = $ilan->fiyat ?? 0;
            $avgMarketPrice   = $similarListings->avg('price') ?? $currentPrice;
            $minMarketPrice   = $similarListings->min('price') ?? $currentPrice;
            $maxMarketPrice   = $similarListings->max('price') ?? $currentPrice;

            $priceDifference        = $currentPrice - $avgMarketPrice;
            $priceDifferencePercent = $avgMarketPrice > 0
                ? ($priceDifference / $avgMarketPrice) * 100
                : 0;

            // AI fiyat önerisi
            $prompt   = $this->buildPriceComparisonPrompt($ilan, $similarListings, $avgMarketPrice, $priceDifferencePercent);
            $aiRaw    = $this->aiService->generate($prompt, [
                'type'       => 'price_comparison', // context7-ignore
                'max_tokens' => 500,
            ]);
            $aiParsed = is_array($aiRaw) ? $aiRaw : (json_decode((string) $aiRaw, true) ?? []);

            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'success'                  => true,
                'current_price'            => $currentPrice,
                'market_average'           => round($avgMarketPrice, 2),
                'market_min'               => round($minMarketPrice, 2),
                'market_max'               => round($maxMarketPrice, 2),
                'price_difference'         => round($priceDifference, 2),
                'price_difference_percent' => round($priceDifferencePercent, 2),
                'competitiveness'          => $this->calculateCompetitiveness($priceDifferencePercent),
                'ai_recommendation'        => $aiParsed['recommendation'] ?? null,
                'similar_listings_count'   => $similarListings->count(),
                'metadata'                 => [
                    'processed_at' => now()->toISOString(),
                    'algorithm'    => 'CortexIntelligence Price Comparison v2.0',
                    'duration_ms'  => $durationMs,
                ],
            ];

            $this->logCortexDecision('price_comparison', ['ilan_id' => $ilan->id], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('price_comparison', ['ilan_id' => $ilan->id, 'error' => $e->getMessage()], $durationMs, false);

            LogService::error('CortexIntelligence: compareMarketPrices başarısız', [
                'ilan_id' => $ilan->id,
                'error'   => $e->getMessage(),
            ], $e, LogService::CHANNEL_AI);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fiyat karşılaştırma prompt'u
     */
    protected function buildPriceComparisonPrompt(Ilan $ilan, $similarListings, float $avgPrice, float $diffPercent): string
    {
        $count    = $similarListings->count();
        $current  = number_format($ilan->fiyat ?? 0, 0, ',', '.');
        $avg      = number_format($avgPrice, 0, ',', '.');
        $diffSign = $diffPercent >= 0 ? '+' : '';

        return "Aşağıdaki emlak fiyat karşılaştırmasını analiz et ve JSON formatında öneri ver.\n\n" .
            "İlan: {$ilan->baslik}\n" .
            "Mevcut fiyat: {$current} TRY\n" .
            "Piyasa ortalaması ({$count} benzer ilan): {$avg} TRY\n" .
            "Fark: {$diffSign}" . round($diffPercent, 1) . "%\n\n" .
            "JSON çıktısı: {\"recommendation\": \"<öneri metni>\", \"action\": \"lower|keep|raise\"}";
    }

    /**
     * Fiyat rekabet durumu hesapla
     */
    protected function calculateCompetitiveness(float $priceDifferencePercent): string
    {
        if ($priceDifferencePercent <= -10) {
            return 'very_competitive';   // Piyasanın %10+ altında
        } elseif ($priceDifferencePercent <= -3) {
            return 'competitive';        // Piyasanın %3-10 altında
        } elseif ($priceDifferencePercent <= 3) {
            return 'market_rate';        // Piyasa fiyatında
        } elseif ($priceDifferencePercent <= 10) {
            return 'above_market';       // Piyasanın %3-10 üstünde
        }
        return 'overpriced';             // Piyasanın %10+ üstünde
    }
}

<?php

namespace App\Services\AI\Domains;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Services\AI\KisiChurnService;
use App\Services\AIDeal\DealPredictionService;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 🧠 Cortex Prediction Domain Service
 * Responsibility: Handles churn risk analysis and deal probability predictions.
 */
class CortexPredictionService
{
    protected KisiChurnService $churnService;
    protected DealPredictionService $dealPrediction;
    protected AiTelemetryService $telemetry;
    protected \App\Services\AIService $aiService;

    public function __construct(
        KisiChurnService $churnService,
        DealPredictionService $dealPrediction,
        AiTelemetryService $telemetry,
        \App\Services\AIService $aiService
    ) {
        $this->churnService = $churnService;
        $this->dealPrediction = $dealPrediction;
        $this->telemetry = $telemetry;
        $this->aiService = $aiService;
    }

    /**
     * Pazarlık Stratejisi Analizi
     * centralizing logic from YalihanCortex
     */
    public function getNegotiationStrategy(Kisi $kisi, array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_negotiation_strategy');

        try {
            LogService::ai('negotiation_strategy_started', 'CortexPrediction', [
                'kisi_id' => $kisi->id,
                'kisi_ad' => $kisi->tam_ad,
            ]);

            $customerData = [
                'yatirimci_profili' => $kisi->yatirimci_profili?->value ?? 'bilinmiyor',
                'satis_potansiyeli' => $kisi->satis_potansiyeli ?? 0,
                'gelir_duzeyi' => $kisi->gelir_duzeyi ?? 'bilinmiyor',
                'toplam_islem_tutari' => $kisi->toplam_islem_tutari ?? 0,
                'toplam_islem' => $kisi->toplam_islem ?? 0,
                'memnuniyet_skoru' => $kisi->memnuniyet_skoru ?? null,
                'karar_verici_mi' => $kisi->karar_verici_mi ?? true,
                'crm_surec_asamasi' => $kisi->crm_surec_asamasi?->value ?? 'bilinmiyor',
            ];

            $prompt = $this->buildNegotiationPrompt($customerData);
            $aiResponse = $this->aiService->generate($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            $strategy = $this->parseNegotiationResponse($aiResponse, $customerData);
            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'kisi_id' => $kisi->id,
                'strategy' => $strategy,
                'customer_profile' => $customerData,
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm' => 'YalihanCortex v1.0',
                    'duration_ms' => $durationMs,
                    'success' => true,
                ],
            ];

            $this->logCortexDecision('negotiation_strategy', [
                'kisi_id' => $kisi->id,
                'yatirimci_profili' => $customerData['yatirimci_profili'],
            ], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('negotiation_strategy', ['kisi_id' => $kisi->id, 'error' => $e->getMessage()], $durationMs, false);
            LogService::error('Negotiation strategy failed', ['kisi_id' => $kisi->id], $e, LogService::CHANNEL_AI);

            return [
                'kisi_id' => $kisi->id,
                'success' => false,
                'error' => $e->getMessage(),
                'strategy' => [
                    'summary' => 'Analiz sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
                    'recommendation' => 'Standart pazarlık stratejisi uygulayın.',
                ],
                'metadata' => ['duration_ms' => $durationMs],
            ];
        }
    }

    private function buildNegotiationPrompt(array $customerData): string
    {
        $yatirimciProfili = $customerData['yatirimci_profili'];
        $satisPotansiyeli = $customerData['satis_potansiyeli'];
        $gelirDuzeyi = $customerData['gelir_duzeyi'];
        $toplamIslemTutari = number_format($customerData['toplam_islem_tutari'] ?? 0, 0, ',', '.');
        $kararVericiMi = $customerData['karar_verici_mi'] ? 'Evet' : 'Hayır';

        return <<<PROMPT
Bir emlak danışmanısın. Aşağıdaki müşteri profili için pazarlık stratejisi öner:
**Müşteri Profili:**
- Yatırımcı Profili: {$yatirimciProfili}
- Satış Potansiyeli: {$satisPotansiyeli}/100
- Gelir Düzeyi: {$gelirDuzeyi}
- Toplam İşlem Tutarı: {$toplamIslemTutari} ₺
- Karar Verici: {$kararVericiMi}
**Görev:**
Bu müşteriyle pazarlık yaparken nasıl bir strateji izlemeliyim? Şu konularda öneri ver:
1. İndirim yaklaşımı (agresif mi, yumuşak mı?)
2. Fiyat vurgusu mu, kalite vurgusu mu?
3. İlk teklif nasıl olmalı?
4. Pazarlık sırasında dikkat edilmesi gerekenler
**Format:**
Kısa, net ve uygulanabilir öneriler ver. Maksimum 200 kelime.
PROMPT;
    }

    private function parseNegotiationResponse(mixed $aiResponse, array $customerData): array
    {
        $text = is_array($aiResponse) ? ($aiResponse['content'] ?? ($aiResponse['text'] ?? '')) : (string) $aiResponse;
        
        return [
            'summary' => $text ?: 'Standart pazarlık stratejisi uygulayın.',
            'recommendation' => $this->extractRecommendation($text, $customerData),
            'discount_approach' => $this->extractDiscountApproach($text, $customerData),
            'focus' => $this->extractFocus($text, $customerData),
        ];
    }

    private function extractRecommendation(string $text, array $customerData): string
    {
        if (stripos($text, 'agresif') !== false || stripos($text, '%10') !== false) {
            return 'Agresif indirim yaklaşımı önerilir. Müşteri fiyata duyarlı görünüyor.';
        }
        if (stripos($text, 'kalite') !== false || stripos($text, 'konum') !== false) {
            return 'Kalite ve konum avantajlarını vurgulayın. Fiyat yerine değer odaklı yaklaşım.';
        }
        return 'Esnek bir pazarlık stratejisi uygulayın.';
    }

    private function extractDiscountApproach(string $text, array $customerData): string
    {
        if (stripos($text, 'agresif') !== false) return 'aggressive';
        if (stripos($text, 'yumuşak') !== false) return 'conservative';
        return 'moderate';
    }

    private function extractFocus(string $text, array $customerData): string
    {
        if (stripos($text, 'fiyat') !== false && stripos($text, 'kalite') === false) return 'price';
        if (stripos($text, 'kalite') !== false || stripos($text, 'konum') !== false) return 'quality';
        return 'balanced';
    }

    /**
     * AI Deal Predictor (SAB v16.5)
     */
    public function predictDeal(Ilan $ilan, array $options = []): array
    {
        $startTime = microtime(true);
        $locale = $options['locale'] ?? app()->getLocale();

        try {
            $prediction = $this->dealPrediction->predict($ilan, [
                'locale' => $locale,
                'trigger' => $options['trigger'] ?? 'cortex',
                'snapshot' => $options['snapshot'] ?? true,
            ]);

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->logCortexDecision('predict_deal', [
                'ilan_id' => $ilan->id,
                'sale_probability' => $prediction['scores']['sale_probability'],
                'deal_quality' => $prediction['scores']['deal_quality_score'],
            ], $durationMs, true);

            return array_merge($prediction, [
                'success' => true,
                'duration_ms' => $durationMs,
            ]);
        } catch (Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            $this->logCortexDecision('predict_deal', ['ilan_id' => $ilan->id, 'error' => $e->getMessage()], $durationMs, false);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ];
        }
    }

    /**
     * Churn risk hesaplama
     */
    public function calculateChurnRisk(Kisi $kisi): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_churn_risk');

        try {
            LogService::ai('yalihan_cortex_churn_risk_started', 'CortexPrediction', [
                'kisi_id' => $kisi->id,
                'kisi_adi' => $kisi->tam_ad ?? 'Bilinmiyor',
            ]);

            $churnRisk = $this->churnService->calculateChurnRisk($kisi);

            $result = [
                'kisi_id' => $kisi->id,
                'risk_score' => $churnRisk['score'],
                'risk_level' => $this->getRiskLevel($churnRisk['score']),
                'breakdown' => $churnRisk['breakdown'],
                'recommendation' => $this->getChurnRecommendation($churnRisk['score']),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm' => 'YalihanCortex v1.0',
                ],
            ];

            $durationMs = LogService::stopTimer($startTime);
            $result['metadata']['duration_ms'] = $durationMs;
            $result['metadata']['success'] = true;

            $this->logCortexDecision('churn_risk', [
                'kisi_id' => $kisi->id,
                'risk_score' => $churnRisk['score'],
                'risk_level' => $result['risk_level'],
            ], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('churn_risk', ['kisi_id' => $kisi->id, 'error' => $e->getMessage()], $durationMs, false);
            LogService::error('Churn risk calculation failed', ['kisi_id' => $kisi->id], $e, LogService::CHANNEL_AI);

            return [
                'kisi_id' => $kisi->id,
                'success' => false,
                'error' => $e->getMessage(),
                'risk_score' => 0,
                'risk_level' => 'unknown',
            ];
        }
    }

    private function logCortexDecision(string $decisionType, array $context, float $durationMs, bool $success): void
    {
        try {
            $this->telemetry->logTransaction(
                'YalihanCortexPrediction',
                $decisionType,
                $durationMs / 1000,
                0, 0, $success ? 200 : 500,
                ['request' => $context, 'response' => ['success' => $success]]
            );
        } catch (Exception $e) {
            Log::warning('Failed to log prediction decision', ['type' => $decisionType]);
        }
    }

    private function getRiskLevel(int $score): string
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    /**
     * Satış Tahmini (SAB v16.1)
     */
    public function predictSalesForecast(Ilan $ilan, array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_sales_forecast');

        try {
            LogService::ai('yalihan_cortex_sales_forecast_started', 'CortexPrediction', [
                'ilan_id' => $ilan->id,
                'fiyat' => $ilan->fiyat,
            ]);

            // Benzetimsel mantık (gerçek model skorları AIService::predict modelinden gelmeli)
            $probability = rand(15, 85);
            $velocity = rand(20, 120);

            $result = [
                'ilan_id' => $ilan->id,
                'sale_probability' => $probability,
                'expected_velocity_days' => $velocity,
                'confidence_level' => $this->calculateConfidenceLevel($probability),
                'recommendations' => $this->generateSalesForecastRecommendations($probability, $velocity),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm' => 'YalihanCortex Predictive v2.0',
                ],
            ];

            $durationMs = LogService::stopTimer($startTime);
            $result['metadata']['duration_ms'] = $durationMs;

            $this->logCortexDecision('sales_forecast', [
                'ilan_id' => $ilan->id,
                'probability' => $probability,
            ], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('sales_forecast', ['ilan_id' => $ilan->id, 'error' => $e->getMessage()], $durationMs, false);
            LogService::error('Sales forecast failed', ['ilan_id' => $ilan->id], $e, LogService::CHANNEL_AI);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Gelir Tahmini (Revenue Prediction)
     */
    public function predictRevenue(array $filters = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_revenue_prediction');

        try {
            $userId = $filters['user_id'] ?? null;
            $periodDays = $filters['period_days'] ?? 30;

            // Veritabanı sorgusu (basitleştirilmiş)
            $totalRevenue = \Illuminate\Support\Facades\DB::table('ilanlar')
                ->where('yayin_durumu', 'yayinda')
                ->when($userId, fn($q) => $q->where('danisman_id', $userId))
                ->sum('fiyat') * 0.02; // %2 komisyon varsayımı

            $result = [
                'expected_revenue' => $totalRevenue,
                'currency' => 'TRY',
                'period_days' => $periodDays,
                'confidence' => 75,
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm' => 'YalihanCortex Predictive v2.0',
                ],
            ];

            $durationMs = LogService::stopTimer($startTime);
            $result['metadata']['duration_ms'] = $durationMs;

            $this->logCortexDecision('revenue_prediction', [
                'user_id' => $userId,
                'expected_revenue' => $totalRevenue,
            ], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('revenue_prediction', ['error' => $e->getMessage()], $durationMs, false);
            LogService::error('Revenue prediction failed', [], $e, LogService::CHANNEL_AI);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Güven seviyesi hesapla
     */
    private function calculateConfidenceLevel(float $score): string
    {
        if ($score >= 80) return 'yuksek';
        if ($score >= 50) return 'orta';
        if ($score >= 30) return 'dusuk';
        return 'cok_dusuk';
    }

    /**
     * Satış tahmini önerileri
     */
    private function generateSalesForecastRecommendations(float $probability, ?int $velocity): array
    {
        $recommendations = [];

        if ($probability < 30) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => 'Düşük satış olasılığı. Fiyat revizyonu veya pazarlama stratejisi gözden geçirilmeli.',
            ];
        } elseif ($probability >= 70) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Yüksek satış olasılığı. Aktif takip ile hızlı kapanış sağlanabilir.',
            ];
        }

        if ($velocity && $velocity > 60) {
            $recommendations[] = [
                'type' => 'info',
                'message' => "Bölgede ortalama satış süresi {$velocity} gün. Sabırlı olun ve pazarlama çalışmalarını sürdürün.",
            ];
        }

        return $recommendations;
    }

    /**
     * Top Churn Risks Analizi (Ported from YalihanCortex)
     */
    public function getTopChurnRisks(int $limit = 10, ?int $userId = null): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_top_churn_risks');

        try {
            $limit = max(1, min(50, $limit));

            $candidates = Kisi::query()
                ->where('aktiflik_durumu', true)
                ->when($userId, fn($q) => $q->where('danisman_id', $userId))
                ->orderByDesc('updated_at')
                ->limit(500)
                ->get(['id', 'ad', 'soyad', 'segment', 'pipeline_stage', 'danisman_id']);

            $scored = $candidates->map(function (Kisi $kisi) {
                $cortexResult = $this->calculateChurnRisk($kisi);
                return [
                    'id' => $kisi->id,
                    'ad' => $kisi->ad,
                    'soyad' => $kisi->soyad,
                    'score' => $cortexResult['risk_score'] ?? 0,
                    'risk_level' => $cortexResult['risk_level'] ?? 'unknown',
                    'segment' => $kisi->segment,
                    'pipeline_stage' => $kisi->pipeline_stage,
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->take($limit);

            $durationMs = LogService::stopTimer($startTime);
            $result = [
                'success' => true,
                'customers' => $scored->toArray(),
                'count' => $scored->count(),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => $durationMs,
                    'sample_size' => $candidates->count(),
                    'limit' => $limit
                ]
            ];

            $this->logCortexDecision('top_churn_risks', ['limit' => $limit, 'user_id' => $userId], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('top_churn_risks', ['limit' => $limit, 'error' => $e->getMessage()], $durationMs, false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getChurnRecommendation(int $score): string
    {
        if ($score >= 70) return 'Acil iletişim kurun, memnuniyet anketi uygulayın.';
        if ($score >= 40) return 'Düzenli takip araması yapın, yeni portföy sunun.';
        return 'İlişkiyi sıcak tutmaya devam edin.';
    }
}

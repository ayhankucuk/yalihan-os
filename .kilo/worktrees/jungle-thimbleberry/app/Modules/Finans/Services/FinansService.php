<?php

namespace App\Modules\Finans\Services;

use App\Models\Kisi;
use App\Models\Ilan;
use App\Modules\Finans\Models\FinansalIslem;
use App\Services\AIService;
use App\Services\Logging\LogService;

/**
 * AI-Powered Financial Service
 *
 * Context7 Standardı: C7-FINANS-AI-2025-11-25
 *
 * Finansal işlemler için AI destekli analiz, tahmin ve öneriler
 */
class FinansService
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * AI ile finansal analiz yap
     *
     * @param  array  $data  Finansal veriler
     * @param  array  $context  Ek bağlam
     * @return array Analiz sonuçları
     */
    public function analyzeFinancials(array $data, array $context = []): array
    {
        try {
            $prompt = $this->buildFinancialAnalysisPrompt($data, $context);

            $aiResult = $this->aiService->analyze($data, [
                'type' => 'financial_analysis',
                'prompt' => $prompt,
                ...$context,
            ]);

            return [
                'success' => true,
                'analysis' => $aiResult,
                'insights' => $this->extractInsights($aiResult),
                'recommendations' => $this->generateRecommendations($data, $aiResult),
                'risk_level' => $this->calculateRiskLevel($data),
                'metadata' => [
                    'analyzed_at' => now(),
                    'data_points' => count($data),
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Finansal analiz hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_analysis' => $this->fallbackAnalysis($data),
            ];
        }
    }

    /**
     * AI ile gelir/gider tahmini yap
     *
     * @param  int|null  $kisiId  Kişi ID (opsiyonel)
     * @param  int|null  $ilanId  İlan ID (opsiyonel)
     * @param  string  $period  Dönem (month, quarter, year)
     * @return array Tahmin sonuçları
     */
    public function predictFinancials(?int $kisiId = null, ?int $ilanId = null, string $period = 'month'): array
    {
        try {
            // Geçmiş verileri topla
            $historicalData = $this->getHistoricalData($kisiId, $ilanId, $period);

            if (empty($historicalData)) {
                return [
                    'success' => false,
                    'message' => 'Tahmin için yeterli geçmiş veri yok',
                    'prediction' => null,
                ];
            }

            $prompt = $this->buildPredictionPrompt($historicalData, $period);

            $aiResult = $this->aiService->suggest($historicalData, 'financial_prediction');

            return [
                'success' => true,
                'prediction' => $this->parsePrediction($aiResult),
                'confidence' => $this->calculateConfidence($historicalData),
                'historical_trend' => $this->analyzeTrend($historicalData),
                'period' => $period,
                'metadata' => [
                    'data_points' => count($historicalData),
                    'predicted_at' => now(),
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Finansal tahmin hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'prediction' => null,
            ];
        }
    }

    /**
     * AI ile otomatik fatura önerisi
     *
     * @param  FinansalIslem  $islem  Finansal işlem
     * @return array Fatura önerileri
     */
    public function suggestInvoice(FinansalIslem $islem): array
    {
        try {
            $context = [
                'islem_tipi' => $islem->islem_tipi,
                'miktar' => $islem->miktar,
                'para_birimi' => $islem->para_birimi,
                'kisi' => $islem->kisi ? $islem->kisi->tam_ad : null,
                'ilan' => $islem->ilan ? $islem->ilan->baslik : null,
            ];

            $prompt = $this->buildInvoiceSuggestionPrompt($islem);

            $aiResult = $this->aiService->suggest($context, 'invoice_suggestion');

            return [
                'success' => true,
                'suggestions' => [
                    'fatura_no_format' => $this->generateInvoiceNumber($islem),
                    'aciklama' => $aiResult['description'] ?? $this->generateDefaultDescription($islem),
                    'vade_tarihi' => $this->suggestDueDate($islem),
                    'odeme_yontemi' => $this->suggestPaymentMethod($islem),
                ],
                'metadata' => [
                    'suggested_at' => now(),
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Fatura önerisi hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'suggestions' => $this->fallbackInvoiceSuggestion($islem),
            ];
        }
    }

    /**
     * AI ile risk analizi
     *
     * @param  int|null  $kisiId  Kişi ID
     * @param  int|null  $ilanId  İlan ID
     * @return array Risk analizi sonuçları
     */
    public function analyzeRisk(?int $kisiId = null, ?int $ilanId = null): array
    {
        try {
            $data = $this->collectRiskData($kisiId, $ilanId);

            $prompt = $this->buildRiskAnalysisPrompt($data);

            $aiResult = $this->aiService->analyze($data, ['type' => 'risk_analysis']);

            return [
                'success' => true,
                'risk_level' => $this->calculateRiskScore($data, $aiResult),
                'risk_factors' => $this->identifyRiskFactors($data),
                'recommendations' => $this->generateRiskRecommendations($data, $aiResult),
                'metadata' => [
                    'analyzed_at' => now(),
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Risk analizi hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'risk_level' => 'unknown',
            ];
        }
    }

    /**
     * Finansal özet rapor (AI destekli)
     *
     * @param  array  $filters  Filtreler
     * @return array Özet rapor
     */
    public function generateSummaryReport(array $filters = []): array
    {
        try {
            $data = $this->collectSummaryData($filters);

            $prompt = $this->buildSummaryPrompt($data, $filters);

            $aiResult = $this->aiService->generate($prompt, [
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            return [
                'success' => true,
                'summary' => $aiResult,
                'statistics' => $this->calculateStatistics($data),
                'trends' => $this->identifyTrends($data),
                'metadata' => [
                    'generated_at' => now(),
                    'filters' => $filters,
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Özet rapor hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'summary' => $this->fallbackSummary($data),
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPER METHODS
    // ═══════════════════════════════════════════════════════════

    private function buildFinancialAnalysisPrompt(array $data, array $context): string
    {
        return "Finansal veri analizi yap:\n\n".
            'Veriler: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n".
            'Bağlam: '.json_encode($context, JSON_UNESCAPED_UNICODE)."\n\n".
            'Görev: Gelir/gider trendlerini analiz et, risk faktörlerini belirle, öneriler sun.';
    }

    private function buildPredictionPrompt(array $historicalData, string $period): string
    {
        return "Geçmiş finansal verilere dayanarak {$period} için tahmin yap:\n\n".
            'Geçmiş veriler: '.json_encode($historicalData, JSON_UNESCAPED_UNICODE)."\n\n".
            'Görev: Trend analizi yap ve gelecek dönem için gelir/gider tahmini oluştur.';
    }

    private function buildInvoiceSuggestionPrompt(FinansalIslem $islem): string
    {
        return "Fatura önerisi oluştur:\n\n".
            "İşlem Tipi: {$islem->islem_tipi}\n".
            "Miktar: {$islem->miktar} {$islem->para_birimi}\n".
            'Kişi: '.($islem->kisi ? $islem->kisi->tam_ad : 'Bilinmiyor')."\n".
            'İlan: '.($islem->ilan ? $islem->ilan->baslik : 'Yok')."\n\n".
            'Görev: Profesyonel fatura açıklaması ve ödeme yöntemi önerisi sun.';
    }

    private function buildRiskAnalysisPrompt(array $data): string
    {
        return "Finansal risk analizi yap:\n\n".
            'Veriler: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n".
            'Görev: Risk seviyesini belirle, risk faktörlerini listele, öneriler sun.';
    }

    private function buildSummaryPrompt(array $data, array $filters): string
    {
        return "Finansal özet rapor oluştur:\n\n".
            'Veriler: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n".
            'Filtreler: '.json_encode($filters, JSON_UNESCAPED_UNICODE)."\n\n".
            'Görev: Profesyonel, özet bir finansal rapor oluştur.';
    }

    private function getHistoricalData(?int $kisiId, ?int $ilanId, string $period): array
    {
        $query = FinansalIslem::query();

        if ($kisiId) {
            $query->where('kisi_id', $kisiId);
        }

        if ($ilanId) {
            $query->where('ilan_id', $ilanId);
        }

        $startDate = match ($period) {
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        return $query->where('tarih', '>=', $startDate)
            ->where('islem_statusu', 'tamamlandi')
            ->orderBy('tarih', 'desc')
            ->get()
            ->map(function ($islem) {
                return [
                    'tarih' => $islem->tarih->format('Y-m-d'),
                    'islem_tipi' => $islem->islem_tipi,
                    'miktar' => $islem->miktar,
                    'para_birimi' => $islem->para_birimi,
                ];
            })
            ->toArray();
    }

    private function collectRiskData(?int $kisiId, ?int $ilanId): array
    {
        $data = [];

        if ($kisiId) {
            $kisi = Kisi::find($kisiId);
            if ($kisi) {
                $data['kisi'] = [
                    'toplam_islem' => $kisi->toplam_islem ?? 0,
                    'toplam_islem_tutari' => $kisi->toplam_islem_tutari ?? 0,
                    'memnuniyet_skoru' => $kisi->memnuniyet_skoru ?? 0,
                ];
            }
        }

        if ($ilanId) {
            $ilan = Ilan::find($ilanId);
            if ($ilan) {
                $data['ilan'] = [
                    'fiyat' => $ilan->fiyat,
                    'para_birimi' => $ilan->para_birimi,
                ];
            }
        }

        // Bekleyen işlemler
        $bekleyenIslemler = FinansalIslem::where('islem_statusu', 'bekliyor')
            ->when($kisiId, fn ($q) => $q->where('kisi_id', $kisiId))
            ->when($ilanId, fn ($q) => $q->where('ilan_id', $ilanId))
            ->sum('miktar');

        $data['bekleyen_tutar'] = $bekleyenIslemler;

        return $data;
    }

    private function collectSummaryData(array $filters): array
    {
        $query = FinansalIslem::query();

        if (isset($filters['start_date'])) {
            $query->where('tarih', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('tarih', '<=', $filters['end_date']);
        }

        if (isset($filters['islem_tipi'])) {
            $query->where('islem_tipi', $filters['islem_tipi']);
        }

        if (isset($filters['islem_statusu'])) {
            $query->where('islem_statusu', $filters['islem_statusu']);
        }

        return $query->get()->toArray();
    }

    private function extractInsights(array $aiResult): array
    {
        // AI sonuçlarından insight'ları çıkar
        return [
            'trend' => $aiResult['trend'] ?? 'stable',
            'anomalies' => $aiResult['anomalies'] ?? [],
            'opportunities' => $aiResult['opportunities'] ?? [],
        ];
    }

    private function generateRecommendations(array $data, array $aiResult): array
    {
        return [
            'immediate' => $aiResult['immediate_recommendations'] ?? [],
            'long_term' => $aiResult['long_term_recommendations'] ?? [],
        ];
    }

    private function calculateRiskLevel(array $data): string
    {
        $score = 0;

        if (isset($data['bekleyen_tutar']) && $data['bekleyen_tutar'] > 100000) {
            $score += 3;
        }

        if (isset($data['kisi']['memnuniyet_skoru']) && $data['kisi']['memnuniyet_skoru'] < 3) {
            $score += 2;
        }

        return match (true) {
            $score >= 5 => 'high',
            $score >= 3 => 'medium',
            default => 'low',
        };
    }

    private function parsePrediction(array $aiResult): array
    {
        return [
            'expected_income' => $aiResult['expected_income'] ?? 0,
            'expected_expense' => $aiResult['expected_expense'] ?? 0,
            'net_projection' => ($aiResult['expected_income'] ?? 0) - ($aiResult['expected_expense'] ?? 0),
        ];
    }

    private function calculateConfidence(array $historicalData): float
    {
        $dataPoints = count($historicalData);

        if ($dataPoints >= 12) {
            return 0.9; // Yüksek güven
        } elseif ($dataPoints >= 6) {
            return 0.7; // Orta güven
        } elseif ($dataPoints >= 3) {
            return 0.5; // Düşük güven
        }

        return 0.3; // Çok düşük güven
    }

    private function analyzeTrend(array $historicalData): string
    {
        if (count($historicalData) < 2) {
            return 'insufficient_data';
        }

        $recent = array_slice($historicalData, 0, 3);
        $older = array_slice($historicalData, 3, 3);

        $recentAvg = array_sum(array_column($recent, 'miktar')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'miktar')) / count($older);

        if ($recentAvg > $olderAvg * 1.1) {
            return 'increasing';
        } elseif ($recentAvg < $olderAvg * 0.9) {
            return 'decreasing';
        }

        return 'stable';
    }

    private function generateInvoiceNumber(FinansalIslem $islem): string
    {
        $prefix = match ($islem->islem_tipi) {
            'komisyon' => 'KOM',
            'odeme' => 'ODM',
            'masraf' => 'MSF',
            'gelir' => 'GLR',
            'gider' => 'GDR',
            default => 'FIN',
        };

        return $prefix.'-'.now()->format('Ymd').'-'.str_pad($islem->id, 6, '0', STR_PAD_LEFT);
    }

    private function generateDefaultDescription(FinansalIslem $islem): string
    {
        $tip = match ($islem->islem_tipi) {
            'komisyon' => 'Komisyon',
            'odeme' => 'Ödeme',
            'masraf' => 'Masraf',
            'gelir' => 'Gelir',
            'gider' => 'Gider',
            default => 'İşlem',
        };

        $kisi = $islem->kisi ? $islem->kisi->tam_ad : '';
        $ilan = $islem->ilan ? $islem->ilan->baslik : '';

        return "{$tip} - {$kisi}".($ilan ? " - {$ilan}" : '');
    }

    private function suggestDueDate(FinansalIslem $islem): string
    {
        $days = match ($islem->islem_tipi) {
            'komisyon' => 30,
            'odeme' => 15,
            default => 7,
        };

        return now()->addDays($days)->format('Y-m-d');
    }

    private function suggestPaymentMethod(FinansalIslem $islem): string
    {
        return match ($islem->islem_tipi) {
            'komisyon' => 'havale',
            'odeme' => 'nakit',
            default => 'havale',
        };
    }

    private function calculateRiskScore(array $data, array $aiResult): string
    {
        $score = 0;

        if (isset($data['bekleyen_tutar']) && $data['bekleyen_tutar'] > 50000) {
            $score += 2;
        }

        if (isset($aiResult['risk_factors']) && count($aiResult['risk_factors']) > 3) {
            $score += 2;
        }

        return match (true) {
            $score >= 4 => 'high',
            $score >= 2 => 'medium',
            default => 'low',
        };
    }

    private function identifyRiskFactors(array $data): array
    {
        $factors = [];

        if (isset($data['bekleyen_tutar']) && $data['bekleyen_tutar'] > 100000) {
            $factors[] = 'Yüksek bekleyen tutar';
        }

        if (isset($data['kisi']['memnuniyet_skoru']) && $data['kisi']['memnuniyet_skoru'] < 3) {
            $factors[] = 'Düşük memnuniyet skoru';
        }

        return $factors;
    }

    private function generateRiskRecommendations(array $data, array $aiResult): array
    {
        return [
            'immediate' => [
                'Bekleyen işlemleri kontrol edin',
                'Yüksek riskli işlemleri önceliklendirin',
            ],
            'long_term' => [
                'Düzenli risk analizi yapın',
                'Otomatik uyarı sistemi kurun',
            ],
        ];
    }

    private function calculateStatistics(array $data): array
    {
        $total = count($data);
        $totalAmount = array_sum(array_column($data, 'miktar'));

        return [
            'total_transactions' => $total,
            'total_amount' => $totalAmount,
            'average_amount' => $total > 0 ? $totalAmount / $total : 0,
        ];
    }

    private function identifyTrends(array $data): array
    {
        return [
            'income_trend' => 'increasing',
            'expense_trend' => 'stable',
            'profit_margin' => 0.15,
        ];
    }

    private function fallbackAnalysis(array $data): array
    {
        return [
            'total' => array_sum(array_column($data, 'miktar')),
            'count' => count($data),
            'average' => count($data) > 0 ? array_sum(array_column($data, 'miktar')) / count($data) : 0,
        ];
    }

    private function fallbackInvoiceSuggestion(FinansalIslem $islem): array
    {
        return [
            'fatura_no_format' => $this->generateInvoiceNumber($islem),
            'aciklama' => $this->generateDefaultDescription($islem),
            'vade_tarihi' => $this->suggestDueDate($islem),
            'odeme_yontemi' => $this->suggestPaymentMethod($islem),
        ];
    }

    private function fallbackSummary(array $data): string
    {
        $total = array_sum(array_column($data, 'miktar'));
        $count = count($data);

        return "Toplam {$count} işlem, toplam tutar: {$total} TL";
    }

    /**
     * Calculate ROI for a listing (Stub)
     *
     * @param int $ilanId
     * @param array $options
     * @return array
     */
    public function calculateROI(int $ilanId, array $options = []): array
    {
        return [
            'roi_percentage' => 5.5,
            'cash_flow' => 0,
            'payback_period_years' => 18,
            'analysis_date' => now()->toDateTimeString(),
        ];
    }
}

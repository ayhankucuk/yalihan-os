<?php

namespace App\Modules\Finans\Services;

use App\Models\Ilan;
use App\Modules\Finans\Models\Komisyon;
use App\Services\AIService;
use App\Services\Logging\LogService;

/**
 * AI-Powered Commission Service
 *
 * Context7 Standardı: C7-KOMISYON-AI-2025-11-25
 *
 * Komisyon hesaplama, optimizasyon ve öneriler için AI destekli servis
 */
class KomisyonService
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * AI ile optimal komisyon oranı önerisi
     *
     * @param  int  $ilanId  İlan ID
     * @param  string  $komisyonTipi  Komisyon tipi (satis, kiralama, danismanlik)
     * @param  float  $ilanFiyati  İlan fiyatı
     * @return array Komisyon önerileri
     */
    public function suggestOptimalRate(int $ilanId, string $komisyonTipi, float $ilanFiyati): array
    {
        try {
            $ilan = Ilan::find($ilanId);
            $context = $this->buildCommissionContext($ilan, $komisyonTipi, $ilanFiyati);

            $prompt = $this->buildCommissionSuggestionPrompt($context);

            $aiResult = $this->aiService->suggest($context, 'commission_optimization');

            return [
                'success' => true,
                'suggested_rate' => $this->parseSuggestedRate($aiResult, $komisyonTipi),
                'suggested_amount' => $this->calculateSuggestedAmount($aiResult, $ilanFiyati),
                'reasoning' => $aiResult['reasoning'] ?? $this->generateDefaultReasoning($context),
                'market_comparison' => $this->compareWithMarket($komisyonTipi, $ilanFiyati),
                'metadata' => [
                    'suggested_at' => now(),
                    'ilan_id' => $ilanId,
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Komisyon önerisi hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'suggested_rate' => $this->getDefaultRate($komisyonTipi),
                'suggested_amount' => $ilanFiyati * ($this->getDefaultRate($komisyonTipi) / 100),
            ];
        }
    }

    /**
     * AI ile komisyon hesaplama optimizasyonu
     *
     * @param  Komisyon  $komisyon  Komisyon modeli
     * @return array Optimizasyon sonuçları
     */
    public function optimizeCommission(Komisyon $komisyon): array
    {
        try {
            $currentRate = $komisyon->komisyon_orani;
            $currentAmount = $komisyon->komisyon_tutari;

            $context = [
                'current_rate' => $currentRate,
                'current_amount' => $currentAmount,
                'ilan_fiyati' => $komisyon->ilan_fiyati,
                'komisyon_tipi' => $komisyon->komisyon_tipi,
                'market_data' => $this->getMarketData($komisyon->komisyon_tipi),
            ];

            $prompt = $this->buildOptimizationPrompt($context);

            $aiResult = $this->aiService->analyze($context, ['type' => 'commission_optimization']);

            return [
                'success' => true,
                'current' => [
                    'rate' => $currentRate,
                    'amount' => $currentAmount,
                ],
                'optimized' => [
                    'rate' => $aiResult['optimized_rate'] ?? $currentRate,
                    'amount' => $aiResult['optimized_amount'] ?? $currentAmount,
                ],
                'improvement' => [
                    'rate_change' => ($aiResult['optimized_rate'] ?? $currentRate) - $currentRate,
                    'amount_change' => ($aiResult['optimized_amount'] ?? $currentAmount) - $currentAmount,
                    'percentage' => $currentAmount > 0
                        ? (($aiResult['optimized_amount'] ?? $currentAmount) - $currentAmount) / $currentAmount * 100
                        : 0,
                ],
                'recommendations' => $aiResult['recommendations'] ?? [],
                'metadata' => [
                    'optimized_at' => now(),
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Komisyon optimizasyon hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'current' => [
                    'rate' => $komisyon->komisyon_orani,
                    'amount' => $komisyon->komisyon_tutari,
                ],
            ];
        }
    }

    /**
     * AI ile otomatik komisyon hesaplama
     *
     * @param  int  $ilanId  İlan ID
     * @param  int  $kisiId  Kişi ID
     * @param  int  $danismanId  Danışman ID
     * @param  string  $komisyonTipi  Komisyon tipi
     * @return Komisyon Oluşturulan komisyon
     */
    public function calculateCommission(
        int $ilanId,
        int $kisiId,
        int $danismanId,
        string $komisyonTipi
    ): Komisyon {
        try {
            $ilan = Ilan::findOrFail($ilanId);
            $ilanFiyati = $ilan->fiyat ?? 0;

            // AI ile optimal oran önerisi al
            $suggestion = $this->suggestOptimalRate($ilanId, $komisyonTipi, $ilanFiyati);

            $komisyonOrani = $suggestion['success']
                ? $suggestion['suggested_rate']
                : $this->getDefaultRate($komisyonTipi);

            $komisyonTutari = $ilanFiyati * ($komisyonOrani / 100);

            $komisyon = Komisyon::create([
                'ilan_id' => $ilanId,
                'kisi_id' => $kisiId,
                'danisman_id' => $danismanId,
                'komisyon_tipi' => $komisyonTipi,
                'komisyon_orani' => $komisyonOrani,
                'komisyon_tutari' => $komisyonTutari,
                'para_birimi' => $ilan->para_birimi ?? 'TRY',
                'ilan_fiyati' => $ilanFiyati,
                'hesaplama_tarihi' => now(),
                'odeme_statusu' => Komisyon::DURUM_HESAPLANDI,
            ]);

            LogService::action(
                'commission_calculated',
                'komisyon',
                $komisyon->id,
                [
                    'ilan_id' => $ilanId,
                    'komisyon_tipi' => $komisyonTipi,
                    'komisyon_orani' => $komisyonOrani,
                    'komisyon_tutari' => $komisyonTutari,
                    'ai_suggested' => $suggestion['success'],
                ]
            );

            return $komisyon;
        } catch (\Exception $e) {
            LogService::error('Komisyon hesaplama hatası', ['error' => $e->getMessage()], $e);
            throw $e;
        }
    }

    public function calculateSplitCommission(
        int $ilanId,
        int $kisiId,
        ?int $saticiDanismanId,
        ?int $aliciDanismanId,
        string $komisyonTipi,
        ?string $splitRatio = null
    ): Komisyon {
        try {
            $ilan = Ilan::findOrFail($ilanId);
            $ilanFiyati = $ilan->fiyat ?? 0;

            $suggestion = $this->suggestOptimalRate($ilanId, $komisyonTipi, $ilanFiyati);
            $totalRate = $suggestion['success'] ? $suggestion['suggested_rate'] : $this->getDefaultRate($komisyonTipi);

            $ratio = $splitRatio ?: '60-40';
            [$satPerc, $aliPerc] = array_map(fn ($x) => (float) $x, explode('-', $ratio));
            $satPerc = max(0, min(100, $satPerc));
            $aliPerc = max(0, min(100, $aliPerc));
            $sumPerc = $satPerc + $aliPerc;
            if ($sumPerc <= 0) {
                $satPerc = 60;
                $aliPerc = 40;
                $sumPerc = 100;
            }

            $saticiRate = round($totalRate * ($satPerc / $sumPerc), 2);
            $aliciRate = round($totalRate * ($aliPerc / $sumPerc), 2);

            $totalAmount = $ilanFiyati * ($totalRate / 100);
            $saticiAmount = round($ilanFiyati * ($saticiRate / 100), 2);
            $aliciAmount = round($ilanFiyati * ($aliciRate / 100), 2);

            $komisyon = Komisyon::create([
                'ilan_id' => $ilanId,
                'kisi_id' => $kisiId,
                'danisman_id' => $saticiDanismanId ?? $aliciDanismanId,
                'komisyon_tipi' => $komisyonTipi,
                'komisyon_orani' => $totalRate,
                'komisyon_tutari' => $totalAmount,
                'satici_danisman_id' => $saticiDanismanId,
                'alici_danisman_id' => $aliciDanismanId,
                'satici_komisyon_orani' => $saticiRate,
                'alici_komisyon_orani' => $aliciRate,
                'satici_komisyon_tutari' => $saticiAmount,
                'alici_komisyon_tutari' => $aliciAmount,
                'para_birimi' => $ilan->para_birimi ?? 'TRY',
                'ilan_fiyati' => $ilanFiyati,
                'hesaplama_tarihi' => now(),
                'hesaplama_durumu' => 'hesaplandi',
            ]);

            LogService::action(
                'commission_calculated_split',
                'komisyon',
                $komisyon->id,
                [
                    'ilan_id' => $ilanId,
                    'komisyon_tipi' => $komisyonTipi,
                    'total_rate' => $totalRate,
                    'total_amount' => $totalAmount,
                    'satici_rate' => $saticiRate,
                    'alici_rate' => $aliciRate,
                    'satici_amount' => $saticiAmount,
                    'alici_amount' => $aliciAmount,
                    'split_ratio' => $ratio,
                ]
            );

            return $komisyon;
        } catch (\Exception $e) {
            LogService::error('Komisyon split hesaplama hatası', ['error' => $e->getMessage()], $e);
            throw $e;
        }
    }

    /**
     * AI ile komisyon analizi
     *
     * @param  int|null  $danismanId  Danışman ID (opsiyonel)
     * @param  string|null  $komisyonTipi  Komisyon tipi (opsiyonel)
     * @return array Analiz sonuçları
     */
    public function analyzeCommissions(?int $danismanId = null, ?string $komisyonTipi = null): array
    {
        try {
            $query = Komisyon::query();

            if ($danismanId) {
                $query->where('danisman_id', $danismanId);
            }

            if ($komisyonTipi) {
                $query->where('komisyon_tipi', $komisyonTipi);
            }

            $komisyonlar = $query->get();

            $data = $komisyonlar->map(function ($komisyon) {
                return [
                    'id' => $komisyon->id,
                    'komisyon_tipi' => $komisyon->komisyon_tipi,
                    'komisyon_orani' => $komisyon->komisyon_orani,
                    'komisyon_tutari' => $komisyon->komisyon_tutari,
                    'odeme_statusu' => $komisyon->odeme_statusu,
                    'hesaplama_tarihi' => $komisyon->hesaplama_tarihi?->format('Y-m-d'),
                ];
            })->toArray();

            $prompt = $this->buildAnalysisPrompt($data);

            $aiResult = $this->aiService->analyze($data, ['type' => 'commission_analysis']);

            return [
                'success' => true,
                'statistics' => [
                    'total_commissions' => $komisyonlar->count(),
                    'total_amount' => $komisyonlar->sum('komisyon_tutari'),
                    'average_rate' => $komisyonlar->avg('komisyon_orani'),
                    'average_amount' => $komisyonlar->avg('komisyon_tutari'),
                ],
                'insights' => $aiResult['insights'] ?? [],
                'recommendations' => $aiResult['recommendations'] ?? [],
                'trends' => $this->analyzeTrends($komisyonlar),
                'metadata' => [
                    'analyzed_at' => now(),
                    'filters' => [
                        'danisman_id' => $danismanId,
                        'komisyon_tipi' => $komisyonTipi,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Komisyon analizi hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPER METHODS
    // ═══════════════════════════════════════════════════════════

    private function buildCommissionContext($ilan, string $komisyonTipi, float $ilanFiyati): array
    {
        return [
            'ilan' => [
                'id' => $ilan->id ?? null,
                'baslik' => $ilan->baslik ?? null,
                'kategori' => $ilan->altKategori->name ?? null,
                'lokasyon' => ($ilan->il->name ?? '').', '.($ilan->ilce->name ?? ''),
                'fiyat' => $ilanFiyati,
            ],
            'komisyon_tipi' => $komisyonTipi,
            'market_rates' => $this->getMarketRates($komisyonTipi),
        ];
    }

    private function buildCommissionSuggestionPrompt(array $context): string
    {
        return "Optimal komisyon oranı önerisi:\n\n".
            "İlan: {$context['ilan']['baslik']}\n".
            "Fiyat: {$context['ilan']['fiyat']} TL\n".
            "Komisyon Tipi: {$context['komisyon_tipi']}\n".
            'Piyasa Oranları: '.json_encode($context['market_rates'], JSON_UNESCAPED_UNICODE)."\n\n".
            'Görev: Piyasa verilerine göre optimal komisyon oranı öner ve gerekçelendir.';
    }

    private function buildOptimizationPrompt(array $context): string
    {
        return "Komisyon optimizasyonu:\n\n".
            "Mevcut Oran: %{$context['current_rate']}\n".
            "Mevcut Tutar: {$context['current_amount']} TL\n".
            "İlan Fiyatı: {$context['ilan_fiyati']} TL\n".
            'Piyasa Verileri: '.json_encode($context['market_data'], JSON_UNESCAPED_UNICODE)."\n\n".
            'Görev: Mevcut komisyonu optimize et ve öneriler sun.';
    }

    private function buildAnalysisPrompt(array $data): string
    {
        return "Komisyon analizi:\n\n".
            'Veriler: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n".
            'Görev: Komisyon trendlerini analiz et, öneriler sun.';
    }

    private function parseSuggestedRate(array $aiResult, string $komisyonTipi): float
    {
        if (isset($aiResult['suggested_rate'])) {
            return (float) $aiResult['suggested_rate'];
        }

        return $this->getDefaultRate($komisyonTipi);
    }

    private function calculateSuggestedAmount(array $aiResult, float $ilanFiyati): float
    {
        if (isset($aiResult['suggested_amount'])) {
            return (float) $aiResult['suggested_amount'];
        }

        if (isset($aiResult['suggested_rate'])) {
            return $ilanFiyati * ($aiResult['suggested_rate'] / 100);
        }

        return 0;
    }

    private function generateDefaultReasoning(array $context): string
    {
        $tip = $context['komisyon_tipi'];
        $rate = $this->getDefaultRate($tip);

        return "Piyasa standartlarına göre {$tip} için %{$rate} komisyon oranı önerilir.";
    }

    private function compareWithMarket(string $komisyonTipi, float $ilanFiyati): array
    {
        $defaultRate = $this->getDefaultRate($komisyonTipi);
        $marketRates = $this->getMarketRates($komisyonTipi);

        return [
            'default_rate' => $defaultRate,
            'market_min' => $marketRates['min'] ?? $defaultRate - 0.5,
            'market_max' => $marketRates['max'] ?? $defaultRate + 0.5,
            'market_avg' => $marketRates['avg'] ?? $defaultRate,
        ];
    }

    private function getMarketRates(string $komisyonTipi): array
    {
        // Piyasa verilerini veritabanından veya config'den al
        $rates = [
            'satis' => ['min' => 2.5, 'max' => 4.0, 'avg' => 3.0],
            'kiralama' => ['min' => 0.5, 'max' => 1.5, 'avg' => 1.0],
            'danismanlik' => ['min' => 1.5, 'max' => 2.5, 'avg' => 2.0],
        ];

        return $rates[$komisyonTipi] ?? ['min' => 0, 'max' => 0, 'avg' => 0];
    }

    private function getMarketData(string $komisyonTipi): array
    {
        // Son 3 ayın piyasa verilerini getir
        $recentCommissions = Komisyon::where('komisyon_tipi', $komisyonTipi)
            ->where('hesaplama_tarihi', '>=', now()->subMonths(3))
            ->get();

        if ($recentCommissions->isEmpty()) {
            return $this->getMarketRates($komisyonTipi);
        }

        return [
            'min' => $recentCommissions->min('komisyon_orani'),
            'max' => $recentCommissions->max('komisyon_orani'),
            'avg' => $recentCommissions->avg('komisyon_orani'),
            'count' => $recentCommissions->count(),
        ];
    }

    private function getDefaultRate(string $komisyonTipi): float
    {
        return match ($komisyonTipi) {
            'satis' => 3.0,
            'kiralama' => 1.0,
            'danismanlik' => 2.0,
            default => 0.0,
        };
    }

    private function analyzeTrends($komisyonlar): array
    {
        if ($komisyonlar->isEmpty()) {
            return ['trend' => 'no_data'];
        }

        $recent = $komisyonlar->where('hesaplama_tarihi', '>=', now()->subMonth());
        $older = $komisyonlar->where('hesaplama_tarihi', '<', now()->subMonth());

        if ($recent->isEmpty() || $older->isEmpty()) {
            return ['trend' => 'insufficient_data'];
        }

        $recentAvg = $recent->avg('komisyon_tutari');
        $olderAvg = $older->avg('komisyon_tutari');

        return [
            'trend' => $recentAvg > $olderAvg ? 'increasing' : ($recentAvg < $olderAvg ? 'decreasing' : 'stable'),
            'recent_avg' => $recentAvg,
            'older_avg' => $olderAvg,
            'change_percentage' => $olderAvg > 0 ? (($recentAvg - $olderAvg) / $olderAvg) * 100 : 0,
        ];
    }
}

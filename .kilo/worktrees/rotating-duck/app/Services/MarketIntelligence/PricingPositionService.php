<?php

namespace App\Services\MarketIntelligence;

use App\DTOs\MarketIntelligence\PricingInsightDTO;
use App\Enums\MarketIntelligence\PricingPosition;
use App\Models\Ilan;
use App\Services\MarketIntelligence\OpportunityScoreService;
use App\Services\Pricing\ConfidenceCalculator;
use App\Services\Pricing\DemandScoreService;

/**
 * Pricing Position Service — MIE v1 Alpha
 *
 * BenchmarkService çıktısını alır, ilanın fiyat pozisyonunu belirler,
 * pricing_score hesaplar ve PricingInsightDTO üretir.
 *
 * Tamamen deterministik — rand() sıfır, AI sıfır.
 */
class PricingPositionService
{
    public function __construct(
        private readonly BenchmarkService $benchmarkService,
        private readonly ConfidenceCalculator $confidenceCalculator,
        private readonly DemandScoreService $demandScoreService,
        private readonly OpportunityScoreService $opportunityScoreService,
    ) {}

    /**
     * İlan için fiyat pozisyon insight'ı hesapla.
     */
    public function analyze(Ilan $ilan): PricingInsightDTO
    {
        $currentPrice = (float) ($ilan->fiyat ?? 0);
        $effectiveM2 = $this->getEffectiveM2($ilan);

        if ($currentPrice <= 0 || !$effectiveM2 || $effectiveM2 <= 0) {
            return $this->insufficientData($ilan, $currentPrice, 'İlan fiyatı veya metrekare bilgisi eksik.');
        }

        $benchmark = $this->benchmarkService->calculate($ilan);

        if ($benchmark['confidence'] === 'yetersiz' || $benchmark['median_m2_price'] === null) {
            return $this->insufficientData(
                $ilan,
                $currentPrice,
                $benchmark['sample_size'] > 0
                    ? "Yetersiz benchmark verisi: yalnızca {$benchmark['sample_size']} karşılaştırılabilir ilan bulundu."
                    : 'Bu segment ve lokasyon için karşılaştırılabilir ilan bulunamadı.'
            );
        }

        $ilanM2Price = $currentPrice / $effectiveM2;
        $medianM2 = $benchmark['median_m2_price'];

        // Sapma hesabı
        $deltaPercent = (($ilanM2Price - $medianM2) / $medianM2) * 100;

        // Pozisyon belirleme
        $position = $this->classifyPosition($deltaPercent);

        // Skor hesaplama
        $score = $this->calculateScore($deltaPercent, $benchmark['sample_size']);

        // Benchmark fiyatları toplam fiyat cinsine çevir
        $benchmarkPrice = round($medianM2 * $effectiveM2, 2);
        $benchmarkMin = $benchmark['min_price'] !== null ? round($benchmark['min_price'] * $effectiveM2, 2) : null;
        $benchmarkMax = $benchmark['max_price'] !== null ? round($benchmark['max_price'] * $effectiveM2, 2) : null;

        // Deterministik açıklama
        $reason = $this->buildReason($position, $deltaPercent, $benchmark['sample_size'], $benchmark['fallback_level']);

        // Confidence layer (MIE v1.1)
        $avgPriceM2 = (float) ($benchmark['avg_price_m2'] ?? 0);
        $stdDev = (float) ($benchmark['std_dev'] ?? 0);
        $validRatio = (float) ($benchmark['valid_ratio'] ?? 1.0);

        $confidenceScore = $this->confidenceCalculator->calculate(
            $benchmark['sample_size'],
            $avgPriceM2,
            $stdDev,
            $validRatio,
        );
        $confidenceLabel = $this->confidenceCalculator->label($confidenceScore);
        $confidenceReason = $this->confidenceCalculator->reason(
            $benchmark['sample_size'],
            $avgPriceM2,
            $stdDev,
            $validRatio,
        );

        // Demand layer (MIE v1.2)
        $demandData = [
            'avg_days_on_market' => $benchmark['avg_days_on_market'] ?? null,
            'trend_ratio' => $benchmark['trend_ratio'] ?? null,
            'drop_ratio' => $benchmark['drop_ratio'] ?? null,
        ];
        $demandScore = $this->demandScoreService->calculate($demandData);
        $demandLabel = $this->demandScoreService->label($demandScore);
        $demandReason = $this->demandScoreService->reason($demandData);

        // Opportunity layer (MIE v1.3)
        $opportunity = $this->opportunityScoreService->evaluate(
            $position->value,
            $score,
            $demandScore,
            $confidenceScore,
        );

        return new PricingInsightDTO(
            ilan_id: $ilan->id,
            current_price: $currentPrice,
            benchmark_price: $benchmarkPrice,
            benchmark_min: $benchmarkMin,
            benchmark_max: $benchmarkMax,
            sample_size: $benchmark['sample_size'],
            price_delta_percent: round($deltaPercent, 1),
            pricing_position: $position,
            pricing_score: $score,
            confidence: $benchmark['confidence'],
            insufficient_data: false,
            reason: $reason,
            confidence_score: $confidenceScore,
            confidence_label: $confidenceLabel,
            confidence_reason: $confidenceReason,
            demand_score: $demandScore,
            demand_label: $demandLabel,
            demand_reason: $demandReason,
            opportunity_score: $opportunity['opportunity_score'],
            opportunity_action: $opportunity['opportunity_action'],
            opportunity_reason: $opportunity['opportunity_reason'],
        );
    }

    /**
     * Fiyat pozisyonu sınıflandırması.
     *
     * Eşikler:
     *   sapma ±10%    → FAIR
     *   sapma -10%+   → UNDERPRICED
     *   sapma +10~25% → OVERPRICED
     *   sapma +25%+   → AGGRESSIVELY_OVERPRICED
     */
    private function classifyPosition(float $deltaPercent): PricingPosition
    {
        return match (true) {
            $deltaPercent > 25  => PricingPosition::AGGRESSIVELY_OVERPRICED,
            $deltaPercent > 10  => PricingPosition::OVERPRICED,
            $deltaPercent < -10 => PricingPosition::UNDERPRICED,
            default             => PricingPosition::FAIR,
        };
    }

    /**
     * Pricing score (0–100).
     *
     * Formül: base_score = max(0, 100 - abs(deltaPercent) * 2)
     * Sample bonus: min(10, sample_size / 5)
     * Final: clamp(0, 100, base + bonus)
     *
     * Yüksek skor = benchmark'a yakın + güçlü veri.
     * Düşük skor = büyük sapma veya zayıf veri.
     */
    private function calculateScore(float $deltaPercent, int $sampleSize): int
    {
        $baseScore = max(0, 100 - abs($deltaPercent) * 2);
        $sampleBonus = min(10, $sampleSize / 5);

        return (int) round(min(100, max(0, $baseScore + $sampleBonus)));
    }

    /**
     * Deterministik açıklama metni.
     * AI yok — kural tabanlı template.
     */
    private function buildReason(PricingPosition $position, float $deltaPercent, int $sampleSize, string $fallbackLevel): string
    {
        $levelLabel = match ($fallbackLevel) {
            'mahalle' => 'mahalle',
            'ilce' => 'ilçe',
            'il' => 'il',
            default => 'bölge',
        };

        $absDelta = abs(round($deltaPercent, 1));

        return match ($position) {
            PricingPosition::FAIR =>
                "Fiyat benchmark medyanına yakın (sapma: %{$absDelta}). {$sampleSize} karşılaştırılabilir ilan ({$levelLabel} bazlı).",

            PricingPosition::UNDERPRICED =>
                "Fiyat benchmark medyanının %{$absDelta} altında. {$sampleSize} karşılaştırılabilir ilan ({$levelLabel} bazlı).",

            PricingPosition::OVERPRICED =>
                "Fiyat benchmark medyanının %{$absDelta} üstünde. {$sampleSize} karşılaştırılabilir ilan ({$levelLabel} bazlı).",

            PricingPosition::AGGRESSIVELY_OVERPRICED =>
                "Fiyat benchmark medyanının %{$absDelta} üstünde — belirgin yüksek. {$sampleSize} karşılaştırılabilir ilan ({$levelLabel} bazlı).",

            PricingPosition::INSUFFICIENT_DATA =>
                "Yeterli karşılaştırma verisi bulunamadı.",
        };
    }

    private function getEffectiveM2(Ilan $ilan): ?float
    {
        $val = $ilan->alan_m2 ?? $ilan->brut_m2 ?? $ilan->net_m2 ?? null;

        return ($val && $val > 0) ? (float) $val : null;
    }

    private function insufficientData(Ilan $ilan, float $currentPrice, string $reason): PricingInsightDTO
    {
        return new PricingInsightDTO(
            ilan_id: $ilan->id,
            current_price: $currentPrice,
            benchmark_price: null,
            benchmark_min: null,
            benchmark_max: null,
            sample_size: 0,
            price_delta_percent: null,
            pricing_position: PricingPosition::INSUFFICIENT_DATA,
            pricing_score: 0,
            confidence: 'yetersiz',
            insufficient_data: true,
            reason: $reason,
        );
    }
}

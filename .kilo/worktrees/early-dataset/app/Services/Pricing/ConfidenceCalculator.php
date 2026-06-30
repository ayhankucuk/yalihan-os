<?php

namespace App\Services\Pricing;

/**
 * Confidence Calculator — MIE v1.1
 *
 * Benchmark verisinin güvenilirliğini 0–100 arası skorlar.
 * 3 bileşen: sample size (0–40), variance (0–30), data quality (0–30).
 *
 * Pure function — side-effect yok, rand() yok, AI yok.
 */
class ConfidenceCalculator
{
    /**
     * Confidence skoru hesapla.
     *
     * @param int   $sampleCount Karşılaştırılabilir ilan sayısı
     * @param float $avgPrice    Ortalama m2 fiyatı
     * @param float $stdDev      Standart sapma (m2 fiyatı)
     * @param float $validRatio  Geçerli kayıt oranı (0.0–1.0)
     *
     * @return int 0–100 arası confidence skoru
     */
    public function calculate(int $sampleCount, float $avgPrice, float $stdDev, float $validRatio): int
    {
        $sampleScore = $this->sampleSizeScore($sampleCount);
        $varianceScore = $this->varianceScore($avgPrice, $stdDev);
        $qualityScore = $this->dataQualityScore($validRatio);

        return (int) min(100, max(0, $sampleScore + $varianceScore + $qualityScore));
    }

    /**
     * Confidence label.
     */
    public function label(int $confidence): string
    {
        return match (true) {
            $confidence >= 80 => 'HIGH',
            $confidence >= 50 => 'MEDIUM',
            $confidence >= 20 => 'LOW',
            default           => 'VERY_LOW',
        };
    }

    /**
     * Explainable reason string.
     */
    public function reason(int $sampleCount, float $avgPrice, float $stdDev, float $validRatio): string
    {
        $parts = [];

        // Sample count
        $parts[] = "{$sampleCount} comps";

        // Variance description
        $cv = ($avgPrice > 0) ? ($stdDev / $avgPrice) : 1.0;
        $parts[] = match (true) {
            $cv < 0.10  => 'low variance',
            $cv <= 0.20 => 'medium variance',
            $cv <= 0.35 => 'high variance',
            default     => 'extreme variance',
        };

        // Quality description
        $parts[] = match (true) {
            $validRatio > 0.9  => 'high data quality',
            $validRatio >= 0.75 => 'medium data quality',
            $validRatio >= 0.5  => 'low data quality',
            default             => 'very low data quality',
        };

        return implode(', ', $parts);
    }

    /**
     * Sample Size Score (0–40).
     */
    private function sampleSizeScore(int $count): int
    {
        return match (true) {
            $count >= 20 => 40,
            $count >= 10 => 30,
            $count >= 5  => 20,
            default      => 5,
        };
    }

    /**
     * Variance Score (0–30).
     *
     * Coefficient of variation = stddev / avg_price_m2
     */
    private function varianceScore(float $avgPrice, float $stdDev): int
    {
        if ($avgPrice <= 0) {
            return 0;
        }

        $cv = $stdDev / $avgPrice;

        return match (true) {
            $cv < 0.10  => 30,
            $cv <= 0.20 => 20,
            $cv <= 0.35 => 10,
            default     => 0,
        };
    }

    /**
     * Data Quality Score (0–30).
     *
     * valid_ratio = valid_records / total_records
     */
    private function dataQualityScore(float $validRatio): int
    {
        return match (true) {
            $validRatio > 0.9  => 30,
            $validRatio >= 0.75 => 20,
            $validRatio >= 0.5  => 10,
            default             => 0,
        };
    }
}

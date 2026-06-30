<?php

namespace App\Services;

use App\Models\Ilan;
use Illuminate\Support\Facades\Log;

/**
 * Yalıhan Cortex AI: Golden Visa Investment Analyzer
 *
 * Context7 Standard: C7-GOLDEN-VISA-ANALYZER-2025-12-23
 * Version: 1.0.0
 *
 * Türkiye Golden Visa programı için yatırım analizi yapar.
 * Minimum yatırım: 400,000 USD (2023+ standartlar)
 *
 * Analiz Faktörleri:
 * - Konum Primi (0-30 puan): Stratejik lokasyonlar
 * - Fiyat Uygunluğu (0-25 puan): Optimal fiyat aralığı
 * - ROI Potansiyeli (0-25 puan): Mevcut Cortex ROI skoru
 * - Kira Getirisi (0-20 puan): Pasif gelir potansiyeli
 */
class CortexGoldenVisaAnalyzer
{
    /**
     * Golden Visa minimum yatırım (USD)
     */
    private const MIN_INVESTMENT_USD = 400000;

    /**
     * Golden Visa optimal maksimum (USD)
     */
    private const MAX_INVESTMENT_USD = 1000000;

    /**
     * USD/TRY kuru (yaklaşık - gerçekte API'den alınmalı)
     */
    private const USD_TRY_RATE = 32.5;

    /**
     * Golden Visa uygunluk ve yatırım analizi
     *
     * @param  Ilan  $ilan
     * @return array
     */
    public function analyzeGoldenVisaOpportunity(Ilan $ilan): array
    {
        // 1. Uygunluk kontrolü
        $eligibility = $this->checkEligibility($ilan);

        if (! $eligibility['eligible']) {
            return [
                'golden_visa_eligible' => false,
                'reason' => $eligibility['reason'],
                'minimum_required_usd' => self::MIN_INVESTMENT_USD,
                'current_price_usd' => round($ilan->fiyat / self::USD_TRY_RATE, 2),
            ];
        }

        // 2. Investment Score Hesaplama
        $scores = $this->calculateScores($ilan);

        // 3. ROI Estimate
        $roiEstimate = $this->calculateROIEstimate($ilan);

        // 4. Recommendations
        $recommendations = $this->generateRecommendations($ilan, $scores, $roiEstimate);

        // 5. Final Score
        $finalScore = array_sum($scores);

        return [
            'golden_visa_eligible' => true,
            'investment_score' => round($finalScore, 1),
            'score_category' => $this->getScoreCategory($finalScore),
            'score_breakdown' => $scores,
            'roi_estimate' => $roiEstimate,
            'property_details' => [
                'price_usd' => round($ilan->fiyat / self::USD_TRY_RATE, 2),
                'price_try' => $ilan->fiyat,
                'area_m2' => $ilan->alan_m2_net,
                'location' => $ilan->il?->name.' / '.$ilan->ilce?->name,
                'category' => $ilan->anaKategori?->name,
            ],
            'recommendations' => $recommendations,
            'risk_factors' => $this->identifyRiskFactors($ilan, $scores),
            'analysis_date' => now()->toIso8601String(),
        ];
    }

    /**
     * Golden Visa uygunluk kontrolü
     *
     * @param  Ilan  $ilan
     * @return array
     */
    private function checkEligibility(Ilan $ilan): array
    {
        $priceUSD = $ilan->fiyat / self::USD_TRY_RATE;

        // Minimum fiyat kontrolü
        if ($priceUSD < self::MIN_INVESTMENT_USD) {
            return [
                'eligible' => false,
                'reason' => 'Fiyat Golden Visa minimum yatırımın altında',
            ];
        }

        // Kategori kontrolü (Golden Visa için uygun kategoriler)
        $eligibleCategories = [1, 2, 3, 7]; // Konut, Yazlık, Villa, Golden Visa
        if ($ilan->ana_kategori_id && ! in_array($ilan->ana_kategori_id, $eligibleCategories)) {
            return [
                'eligible' => false,
                'reason' => 'Kategori Golden Visa için uygun değil',
            ];
        }

        // Yayın durumu kontrolü (Context7: yayin_durumu canonical field)
        if ($ilan->yayin_durumu !== 'Yayinda') {
            return [
                'eligible' => false,
                'reason' => 'İlan yayında değil',
            ];
        }

        return ['eligible' => true];
    }

    /**
     * Skor hesaplama (toplam 100 puan)
     *
     * @param  Ilan  $ilan
     * @return array
     */
    private function calculateScores(Ilan $ilan): array
    {
        return [
            'location_score' => $this->calculateLocationScore($ilan),      // 0-30
            'price_score' => $this->calculatePriceScore($ilan),            // 0-25
            'roi_score' => $this->calculateROIScore($ilan),                // 0-25
            'rental_yield_score' => $this->calculateRentalYieldScore($ilan), // 0-20
        ];
    }

    /**
     * Konum skoru (0-30)
     *
     * @param  Ilan  $ilan
     * @return float
     */
    private function calculateLocationScore(Ilan $ilan): float
    {
        $score = 10; // Base score

        // Şehir primi
        $premiumCities = [
            34 => 20, // İstanbul
            6 => 15,  // Ankara
            35 => 15, // İzmir
            7 => 18,  // Antalya
            48 => 16, // Muğla
            16 => 14, // Bursa
        ];

        if (isset($premiumCities[$ilan->il_id])) {
            $score += $premiumCities[$ilan->il_id];
        }

        // Sahil primi (turizm detail varsa)
        if ($ilan->turizmDetail) {
            $score += 5;
        }

        return min($score, 30);
    }

    /**
     * Fiyat skoru (0-25)
     *
     * @param  Ilan  $ilan
     * @return float
     */
    private function calculatePriceScore(Ilan $ilan): float
    {
        $priceUSD = $ilan->fiyat / self::USD_TRY_RATE;

        // Optimal fiyat aralığı: 400K-1M USD
        if ($priceUSD >= self::MIN_INVESTMENT_USD && $priceUSD <= self::MAX_INVESTMENT_USD) {
            // Merkezdeki fiyatlar daha yüksek skor
            $ratio = ($priceUSD - self::MIN_INVESTMENT_USD) / (self::MAX_INVESTMENT_USD - self::MIN_INVESTMENT_USD);

            return 15 + ($ratio * 10); // 15-25 arası
        }

        // Çok pahalı
        if ($priceUSD > self::MAX_INVESTMENT_USD) {
            return 10;
        }

        return 5;
    }

    /**
     * ROI skoru (0-25)
     *
     * @param  Ilan  $ilan
     * @return float
     */
    private function calculateROIScore(Ilan $ilan): float
    {
        // Mevcut Cortex ROI verisini kullan
        $metadata = $ilan->additional_metadata;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        $cortexAI = $metadata['cortex_ai'] ?? null;

        if (! $cortexAI) {
            return 10; // Neutral score
        }

        $roiPercentage = $cortexAI['roi_data']['roi_percentage'] ?? 0;

        // ROI skoru mapping
        if ($roiPercentage >= 15) {
            return 25;
        }
        if ($roiPercentage >= 10) {
            return 20;
        }
        if ($roiPercentage >= 7) {
            return 15;
        }
        if ($roiPercentage >= 5) {
            return 10;
        }

        return 5;
    }

    /**
     * Kira getirisi skoru (0-20)
     *
     * @param  Ilan  $ilan
     * @return float
     */
    private function calculateRentalYieldScore(Ilan $ilan): float
    {
        // Turizm detail varsa günlük kira
        if ($ilan->turizmDetail && $ilan->turizmDetail->gunluk_fiyat) {
            $dailyRate = $ilan->turizmDetail->gunluk_fiyat;
            $estimatedAnnualIncome = $dailyRate * 120; // 120 gün doluluk varsayımı
            $yieldPercentage = ($estimatedAnnualIncome / $ilan->fiyat) * 100;

            if ($yieldPercentage >= 8) {
                return 20;
            }
            if ($yieldPercentage >= 6) {
                return 15;
            }
            if ($yieldPercentage >= 4) {
                return 10;
            }

            return 5;
        }

        // Genel konut kira tahmini (m² bazlı)
        if ($ilan->alan_m2_net > 0) {
            $estimatedMonthlyRent = $ilan->alan_m2_net * 150; // TL/m² aylık kira tahmini
            $estimatedAnnualIncome = $estimatedMonthlyRent * 12;
            $yieldPercentage = ($estimatedAnnualIncome / $ilan->fiyat) * 100;

            if ($yieldPercentage >= 5) {
                return 15;
            }
            if ($yieldPercentage >= 3) {
                return 10;
            }

            return 5;
        }

        return 5; // Neutral
    }

    /**
     * ROI tahmini (5 yıllık)
     *
     * @param  Ilan  $ilan
     * @return array
     */
    private function calculateROIEstimate(Ilan $ilan): array
    {
        $annualIncome = 0;

        // Turizm geliri
        if ($ilan->turizmDetail && $ilan->turizmDetail->gunluk_fiyat) {
            $annualIncome = $ilan->turizmDetail->gunluk_fiyat * 120; // 120 gün
        } else {
            // Konut kira geliri
            $estimatedMonthlyRent = $ilan->alan_m2_net * 150;
            $annualIncome = $estimatedMonthlyRent * 12;
        }

        $annualCosts = $annualIncome * 0.15; // %15 maliyet
        $netAnnualIncome = $annualIncome - $annualCosts;

        $annualPercentage = ($netAnnualIncome / $ilan->fiyat) * 100;
        $fiveYearTotal = $annualPercentage * 5;

        return [
            'annual_percentage' => round($annualPercentage, 2),
            'five_year_total' => round($fiveYearTotal, 2),
            'annual_income_try' => round($annualIncome, 2),
            'annual_income_usd' => round($annualIncome / self::USD_TRY_RATE, 2),
            'net_annual_income_try' => round($netAnnualIncome, 2),
            'payback_years' => $netAnnualIncome > 0 ? round($ilan->fiyat / $netAnnualIncome, 1) : 0,
        ];
    }

    /**
     * Skor kategorisi
     *
     * @param  float  $score
     * @return string
     */
    private function getScoreCategory(float $score): string
    {
        return match (true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'very_good',
            $score >= 55 => 'good',
            $score >= 40 => 'moderate',
            default => 'low',
        };
    }

    /**
     * Öneriler oluştur
     *
     * @param  Ilan  $ilan
     * @param  array  $scores
     * @param  array  $roiEstimate
     * @return array
     */
    private function generateRecommendations(Ilan $ilan, array $scores, array $roiEstimate): array
    {
        $recommendations = [];

        // Konum bazlı
        if ($scores['location_score'] >= 25) {
            $recommendations[] = 'Prime location - High demand area for Golden Visa investors';
        }

        // ROI bazlı
        if ($roiEstimate['annual_percentage'] >= 7) {
            $recommendations[] = 'Strong rental yield potential';
        }

        // Fiyat bazlı
        $priceUSD = $ilan->fiyat / self::USD_TRY_RATE;
        if ($priceUSD < 600000) {
            $recommendations[] = 'Attractive entry price for Golden Visa program';
        }

        // Geri ödeme
        if (isset($roiEstimate['payback_years']) && $roiEstimate['payback_years'] < 15) {
            $recommendations[] = "Fast ROI payback (< {$roiEstimate['payback_years']} years)";
        }

        // Turizm potansiyeli
        if ($ilan->turizmDetail) {
            $recommendations[] = 'Tourism potential - Seasonal income opportunity';
        }

        return $recommendations;
    }

    /**
     * Risk faktörleri
     *
     * @param  Ilan  $ilan
     * @param  array  $scores
     * @return array
     */
    private function identifyRiskFactors(Ilan $ilan, array $scores): array
    {
        $risks = [];

        if ($scores['location_score'] < 15) {
            $risks[] = 'Secondary location - Lower liquidity';
        }

        if ($scores['rental_yield_score'] < 10) {
            $risks[] = 'Below average rental yield';
        }

        $priceUSD = $ilan->fiyat / self::USD_TRY_RATE;
        if ($priceUSD > self::MAX_INVESTMENT_USD) {
            $risks[] = 'Above optimal price range - Longer sale time';
        }

        if (! $ilan->fotograflar()->count()) {
            $risks[] = 'No photos available - Limited marketing';
        }

        return $risks;
    }

    /**
     * Analizi metadata'ya kaydet
     *
     * @param  Ilan  $ilan
     * @return bool
     */
    public function saveAnalysisToMetadata(Ilan $ilan): bool
    {
        try {
            $analysis = $this->analyzeGoldenVisaOpportunity($ilan);

            $metadata = $ilan->additional_metadata;
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            } elseif (is_null($metadata)) {
                $metadata = [];
            }

            $metadata['cortex_ai']['golden_visa_analysis'] = $analysis;

            $ilan->update(['additional_metadata' => $metadata]);

            Log::info('Golden Visa analysis saved', [
                'ilan_id' => $ilan->id,
                'score' => $analysis['investment_score'] ?? 'N/A',
                'eligible' => $analysis['golden_visa_eligible'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Golden Visa analysis save error', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

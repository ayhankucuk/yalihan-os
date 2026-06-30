<?php

namespace App\Services\AI;

use App\Models\Ilan;
use App\Models\Projections\MarketTrendProjection;
use App\Models\Projections\ListingVelocityProjection;
use App\Models\Projections\TalepMatchProjection;
use App\Models\Projections\BuyerInterestProjection;

/**
 * AI Seller Strategy Engine - Listing Pricing Intelligence
 * 🛡️ SAB Production Seal Compliant
 */
class SellerStrategyService
{
    /**
     * Danışman için fiyata ve pazara dayalı satıcı stratejisi üretir.
     */
    public function generateSellerStrategy(int $listingId): array
    {
        $ilan = Ilan::findOrFail($listingId);

        // Sinylalleri topla
        $signals = $this->gatherSignals($ilan);

        // Skoru Hesapla (0-100)
        $score = $this->calculatePriceStrategyScore($signals);

        // Stratejiyi belirle
        $strategy = $this->determinePricingStrategy($score);

        // Önerilen fiyat aralığı
        $priceRange = $this->generatePriceRecommendation($signals);

        // Satış hızı tahmini
        $estimatedVelocity = $this->estimateSaleVelocity($signals);

        // Risk Göstergesi
        $riskSignal = $this->determineRiskSignal($strategy);

        // Danışman tavsiyesi
        $advisorRecommendation = $this->generateAdvisorRecommendation($strategy, $signals);

        return [
            'listing_id' => $ilan->id,
            'listing_title' => $ilan->baslik,
            'current_price' => $ilan->fiyat,
            'price_strategy_score' => $score,
            'pricing_strategy' => $strategy,
            'recommended_price_range' => $priceRange,
            'estimated_sale_velocity' => $estimatedVelocity,
            'risk_signal' => $riskSignal,
            'advisor_recommendation' => $advisorRecommendation,
            'signals' => $signals // Transparency for debugging/UI
        ];
    }

    /**
     * Tüm CQRS projectionlarından sinyalleri derler.
     */
    protected function gatherSignals(Ilan $ilan): array
    {
        $city = $ilan->il_id;
        $district = $ilan->ilce_id;
        $category = $ilan->ana_kategori_id;
        $price = (float) $ilan->fiyat;

        // Market Trends (Bölgesel veri)
        // Note: These columns in MarketTrendProjection might be strings like city name, or IDs.
        // As a fallback, we use a basic query
        $marketTrend = MarketTrendProjection::where('city', $city)
            ->where('district', $district)
            ->first();

        $regionalMedian = $marketTrend ? (float) $marketTrend->median_price : $price;
        $marketDemandScore = $marketTrend ? (float) $marketTrend->demand_index : 50.0;

        // Velocity (Görüntülenme hızı)
        $velocity = ListingVelocityProjection::where('listing_id', $ilan->id)->first();
        $viewCount = $velocity ? $velocity->view_count : 0;
        $activityScore = $velocity ? $velocity->activity_score : 10;

        // Alıcı Eşleşmeleri (Buyer Math Density)
        // TalepMatchProjection is buyer-request based, not listing-based.
        // Match by location+price range as a proxy for buyer interest density.
        $matchCount = TalepMatchProjection::query()
            ->where(function ($q) use ($ilan) {
                if ($ilan->il_id) {
                    $q->where('city', $ilan->il_id);
                }
            })
            ->where(function ($q) use ($ilan) {
                if ($ilan->fiyat) {
                    $q->where('min_price', '<=', $ilan->fiyat)
                      ->where('max_price', '>=', $ilan->fiyat);
                }
            })
            ->count();
        $buyerMatchDensity = min(100, $matchCount * 10); // Simple proxy

        // Listing Age
        $ageDays = $ilan->created_at ? $ilan->created_at->diffInDays(now()) : 0;

        // Price Advantage
        $priceAdvantage = 0;
        if ($regionalMedian > 0) {
            $diffRatio = (($regionalMedian - $price) / $regionalMedian) * 100;
            // If price is 10% below median, it's a +10 advantage. If 10% above, it's -10.
            // Normalize to 0-100 where 50 is exact median.
            $priceAdvantage = max(0, min(100, 50 + $diffRatio));
        }

        // Region Alignment (How close are we to median? 100 = exact match)
        $regionalAlignment = 100;
        if ($regionalMedian > 0) {
            $diffRatioAbs = abs(($price - $regionalMedian) / $regionalMedian) * 100;
            $regionalAlignment = max(0, 100 - $diffRatioAbs);
        }

        return [
            'price' => $price,
            'regional_price_median' => $regionalMedian,
            'market_demand_score' => $marketDemandScore,
            'buyer_match_density' => $buyerMatchDensity,
            'listing_view_velocity' => min(100, $activityScore),
            'price_advantage_score' => $priceAdvantage,
            'regional_price_median_alignment' => $regionalAlignment,
            'listing_age_days' => max(1, $ageDays),
        ];
    }

    /**
     * Sinyalleri ağırlıklandırarak 0-100 arası tek bir strateji skoru hesaplar.
     */
    public function calculatePriceStrategyScore(array $signals): float
    {
        /*
          Ağırlıklar:
          market_demand_score * 0.25
          buyer_match_density * 0.20
          listing_view_velocity * 0.15
          price_advantage_score * 0.20
          regional_price_median_alignment * 0.20
        */

        $score = ($signals['market_demand_score'] * 0.25) +
                 ($signals['buyer_match_density'] * 0.20) +
                 ($signals['listing_view_velocity'] * 0.15) +
                 ($signals['price_advantage_score'] * 0.20) +
                 ($signals['regional_price_median_alignment'] * 0.20);

        return round(max(0, min(100, $score)), 1);
    }

    /**
     * Skor aralıklarına göre Context7 uyumlu strateji kimliği üretir.
     */
    public function determinePricingStrategy(float $score): string
    {
        // 0-35 : OVERPRICED_RISK
        // 36-55: MARKET_MATCH_PRICING
        // 56-75: BALANCED_PRICING
        // 76-85: AGGRESSIVE_PRICING
        // 86-100: UNDERPRICED_SIGNAL

        if ($score >= 86) {
            return 'UNDERPRICED_SIGNAL';
        } elseif ($score >= 76) {
            return 'AGGRESSIVE_PRICING';
        } elseif ($score >= 56) {
            return 'BALANCED_PRICING';
        } elseif ($score >= 36) {
            return 'MARKET_MATCH_PRICING';
        }

        return 'OVERPRICED_RISK';
    }

    /**
     * Strateji sınıfına göre risk seviyesi sinyali verir (Context7)
     */
    protected function determineRiskSignal(string $strategy): string
    {
        return match($strategy) {
            'OVERPRICED_RISK' => 'HIGH_RISK',
            'UNDERPRICED_SIGNAL' => 'MODERATE_RISK',
            'MARKET_MATCH_PRICING' => 'ELEVATED_RISK',
            default => 'LOW_RISK'
        };
    }

    /**
     * Mevcut pazar dinamiklerine dayanarak makul satış fiyatı aralığını önerir.
     */
    public function generatePriceRecommendation(array $signals): array
    {
        $median = $signals['regional_price_median'] > 0 ? $signals['regional_price_median'] : $signals['price'];

        // Very basic mock range calculation
        $min = $median * 0.90; // 10% below median
        $max = $median * 1.10; // 10% above median

        // If the property is currently underpriced, the max might be closer to current price
        return [
            'min' => round($min),
            'max' => round($max),
            'target' => round($median)
        ];
    }

    /**
     * Pazar skoru ve listeleme yaşına göre kaba bir satış hızı tahmini verir.
     */
    public function estimateSaleVelocity(array $signals): string
    {
        $demand = $signals['market_demand_score'];
        $advantage = $signals['price_advantage_score'];

        $combined = ($demand + $advantage) / 2;

        if ($combined > 75) {
            return '1-14 Days (Fast)';
        } elseif ($combined > 50) {
            return '15-45 Days (Average)';
        } elseif ($combined > 30) {
            return '45-90 Days (Slow)';
        }

        return '90+ Days (Stagnant)';
    }

    /**
     * Danışmanın alacağı aksiyonu özetler.
     */
    public function generateAdvisorRecommendation(string $strategy, array $signals): string
    {
        return match($strategy) {
            'OVERPRICED_RISK' => 'Bu ilanın fiyatı bölge ortalamalarının üzerinde ve talep yaratmıyor. Satıcı ile acil fiyat indirimi (Price Drop) toplantısı organize edin.',
            'MARKET_MATCH_PRICING' => 'İlan piyasa değerinde ancak rekabet avantajı yok. İlan vitrinini (Boost) öne çıkarın ve pazarlamayı artırın.',
            'BALANCED_PRICING' => 'Fiyatlama dengeli ve pazarla uyumlu. Düzenli alıcı aramalarına devam edin ve haftalık raporlar gönderin.',
            'AGGRESSIVE_PRICING' => 'Fiyat çok cazip, alıcı yoğunluğu yüksek. Kısa sürede teklif almaya odaklanın, olası müşterileri "Call to Action" ile sıkıştırın.',
            'UNDERPRICED_SIGNAL' => 'Bu ilan bölge ortalamasının oldukça altında. Çok hızlı satılabilir ancak satıcı zarar ediyor olabilir. Gerekirse fiyat optimizasyonu (Revize) uygulayın.',
            default => 'Piyasa verilerini izlemeye devam edin.'
        };
    }
}

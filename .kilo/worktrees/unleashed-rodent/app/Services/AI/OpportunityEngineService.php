<?php

namespace App\Services\AI;

use App\Models\Projections\ListingSearchProjection;
use App\Models\Projections\BuyerInterestProjection;
use App\Models\Projections\MarketTrendProjection;
use Illuminate\Support\Collection;

/**
 * 🛡️ SAB SEALED
 * Service Layer for AI Opportunity Inbox
 *
 * Rules:
 * - Read Model (CQRS) only. No direct reads from `Ilan`.
 * - No forbidden Context7 naming variants.
 */
class OpportunityEngineService
{
    public const OPPORTUNITY_UNDERPRICED = 'UNDERPRICED_LISTING';
    public const OPPORTUNITY_HIGH_BUYER_MATCH = 'HIGH_BUYER_MATCH';
    public const OPPORTUNITY_SEO_OPTIMIZATION = 'SEO_OPTIMIZATION';
    public const OPPORTUNITY_LOW_QUALITY = 'LOW_QUALITY_HIGH_POTENTIAL';
    public const OPPORTUNITY_STALE = 'STALE_LISTING_RECOVERY';

    /**
     * Get a sorted list of AI opportunities based on projections.
     */
    public function getOpportunities(array $filters = []): Collection
    {
        // Query the read model
        $query = ListingSearchProjection::query()
            ->select(['listing_id', 'title', 'price', 'city', 'district', 'property_type', 'portfolio_health', 'seo_score']);

        $listings = $query->get();

        $opportunities = collect();

        foreach ($listings as $listing) {
            $buyerSignal = BuyerInterestProjection::where('listing_id', $listing->listing_id)->first();
            $marketSignal = MarketTrendProjection::where('city', $listing->city)
                ->where('district', $listing->district)
                ->where('property_type', $listing->property_type)
                ->first();

            $scores = $this->calculateScores($listing, $buyerSignal, $marketSignal);

            // Only consider opportunities if the composite score is high enough, or if specific critical signals exist
            if ($scores['composite'] >= 40) {
                $opportunityType = $this->determineOpportunityType($scores);

                // If filter is active
                if (!empty($filters['opportunity_type']) && $filters['opportunity_type'] !== $opportunityType) {
                    continue;
                }

                $opportunities->push([
                    'id' => uniqid('opp_'),
                    'listing_id' => $listing->listing_id,
                    'title' => $listing->title ?? 'İlan #' . $listing->listing_id,
                    'price' => $listing->price,
                    'opportunity_score' => $scores['composite'],
                    'opportunity_type' => $opportunityType,
                    'reason' => $this->generateReason($opportunityType, $scores),
                    'suggested_action' => $this->generateSuggestedAction($opportunityType),
                ]);
            }
        }

        return $opportunities->sortByDesc('opportunity_score')->values();
    }

    /**
     * Calculate individual signal scores and the final composite.
     */
    protected function calculateScores(
        ListingSearchProjection $listing,
        ?BuyerInterestProjection $buyerSignal,
        ?MarketTrendProjection $marketSignal
    ): array {
        // 1. Market Score (0-100)
        $marketScore = 50;
        if ($marketSignal) {
            $marketScore = min(100, max(0, 50 + ($marketSignal->demand_index ?? 0)));
        }

        // 2. Price Deviation Score (0-100)
        $priceDeviationScore = 0;
        if ($marketSignal && $marketSignal->avg_price > 0 && $listing->price > 0) {
            $ratio = $listing->price / $marketSignal->avg_price;
            // Lower ratio = higher deviation score (underpriced)
            if ($ratio < 1.0) {
                $priceDeviationScore = min(100, (int) ((1.0 - $ratio) * 200));
            }
        }

        // 3. Quality & SEO Score (0-100)
        $qualityScore = $listing->portfolio_health ?? 50;
        $seoScore = $listing->seo_score ?? 50;

        // 4. Buyer Match Score (0-100)
        $buyerMatchScore = 0;
        if ($buyerSignal) {
            $buyerMatchScore = min(100, max(0, ($buyerSignal->avg_match_score ?? 0) + ($buyerSignal->high_intent_buyer_count * 5)));
        }

        // Composite Formula:
        // market_score * 0.25 + quality_score * 0.15 + seo_score * 0.10 + buyer_match_score * 0.30 + price_deviation_score * 0.20
        $composite = ($marketScore * 0.25)
                   + ($qualityScore * 0.15)
                   + ($seoScore * 0.10)
                   + ($buyerMatchScore * 0.30)
                   + ($priceDeviationScore * 0.20);

        return [
            'composite' => (int) round($composite),
            'market' => $marketScore,
            'quality' => $qualityScore,
            'seo' => $seoScore,
            'buyer' => $buyerMatchScore,
            'price_deviation' => $priceDeviationScore,
            'raw_price_ratio' => isset($ratio) ? $ratio : 1.0,
        ];
    }

    /**
     * Identify the primary opportunity type based on dominant score variants.
     */
    protected function determineOpportunityType(array $scores): string
    {
        if ($scores['price_deviation'] > 30) {
            return self::OPPORTUNITY_UNDERPRICED;
        }

        if ($scores['buyer'] > 60) {
            return self::OPPORTUNITY_HIGH_BUYER_MATCH;
        }

        if ($scores['quality'] < 50 && $scores['market'] > 60) {
            return self::OPPORTUNITY_LOW_QUALITY;
        }

        if ($scores['seo'] < 40) {
            return self::OPPORTUNITY_SEO_OPTIMIZATION;
        }

        return self::OPPORTUNITY_STALE;
    }

    /**
     * Generate the natural language reason for the opportunity.
     */
    protected function generateReason(string $type, array $scores): string
    {
        return match ($type) {
            self::OPPORTUNITY_UNDERPRICED => "Bölge ortalamasından %" . (int) ($scores['price_deviation'] / 2) . " düşük fiyat.",
            self::OPPORTUNITY_HIGH_BUYER_MATCH => "Bölgede yüksek alıcı eşleşme potansiyeli mevcut.",
            self::OPPORTUNITY_LOW_QUALITY => "Talep gören bölgede, ilan kalitesi düşük kaldığı için fırsat kaçıyor.",
            self::OPPORTUNITY_SEO_OPTIMIZATION => "SEO görünürlüğü düşük ancak pazar talebi yüksek. Güncelleme gerekli.",
            self::OPPORTUNITY_STALE => "İlan durağanlaştı. Pazar trendlerine göre yeniden konumlandırma fırsatı.",
            default => "Genel AI Fırsatı",
        };
    }

    /**
     * Generate natural language suggested action.
     */
    protected function generateSuggestedAction(string $type): string
    {
        return match ($type) {
            self::OPPORTUNITY_UNDERPRICED => "Fiyat güncellemesi önerilir.",
            self::OPPORTUNITY_HIGH_BUYER_MATCH => "Alıcılarla hızlı iletişim kur / Kampanya yap.",
            self::OPPORTUNITY_LOW_QUALITY => "Fotoğraf ve özellikleri zenginleştir.",
            self::OPPORTUNITY_SEO_OPTIMIZATION => "SEO başlık ve açıklamasını optimize et.",
            self::OPPORTUNITY_STALE => "Hızlı satış reklam kampanyasına dahil et.",
            default => "İlanı gözden geçir.",
        };
    }
}

<?php

namespace App\Services\AIFrontend;

use App\Services\AI\YalihanCortex;
use Illuminate\Database\Eloquent\Collection;

class AIPropertyAdvisorService
{
    protected YalihanCortex $cortex;

    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * Enrich search results with Cortex-driven intelligence.
     * Analyze opportunities, valuations, and reverse match scores.
     */
    public function analyzeResults(Collection $results, array $intent): array
    {
        $analysis = [
            'total_found' => $results->count(),
            'top_recommendations' => [],
            'market_insights' => [],
        ];

        foreach ($results as $listing) {
            // Find the actual Ilan model for deep analysis
            $ilan = \App\Models\Ilan::find($listing->listing_id);
            if (!$ilan) continue;

            $valuation = $this->cortex->priceValuation($ilan);

            // For opportunity score, we use the detection logic
            $opportunities = $this->cortex->detectOpportunities(['min_score' => 0]);
            $opp = collect($opportunities)->firstWhere('listing_id', $listing->listing_id);

            $analysis['top_recommendations'][] = [
                'listing_id' => $listing->listing_id,
                'score' => $opp['score'] ?? 50,
                'valuation_summary' => $valuation['summary'] ?? 'Fiyat değerlemesi yapıldı.',
            ];
        }

        // Sort by opportunity score
        usort($analysis['top_recommendations'], fn($a, $b) => $b['score'] <=> $a['score']);

        return $analysis;
    }
}

<?php

namespace App\Services\AIDeal;

use App\Models\DealPredictionLog;
use App\Models\DealPredictionSnapshot;
use App\Models\Ilan;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * ️ SAB SEALED
 * Deal Telemetry Service
 * Handles logging, auditing, and snapshotting of prediction events.
 */
class DealTelemetryService
{
    use GuardsAgentWrites;
    /**
     * Log a prediction to the database.
     */
    public function logPrediction(Ilan $ilan, array $scores, string $explanation, array $metadata = []): DealPredictionLog
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DealPredictionLog::create([
            'listing_id' => $ilan->id,
            'sale_probability' => $scores['sale_probability'],
            'estimated_days_to_sell' => $scores['estimated_days_to_sell'],
            'price_accuracy_score' => $scores['price_accuracy_score'],
            'market_heat_score' => $scores['market_heat_score'],
            'buyer_interest_score' => $scores['buyer_interest_score'],
            'deal_quality_score' => $scores['deal_quality_score'],
            'opportunity_score' => $scores['opportunity_score'] ?? null,
            'top_buyer_match_score' => $scores['top_buyer_match_score'] ?? null,
            'reason' => $explanation,
            'metadata' => $metadata,
            'locale' => app()->getLocale(),
            'model_version' => '1.0-sab',
        ]);
    }

    /**
     * Create a historical snapshot.
     */
    public function createSnapshot(Ilan $ilan, array $scores): DealPredictionSnapshot
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DealPredictionSnapshot::create([
            'listing_id' => $ilan->id,
            'snapshot_date' => now()->toDateString(),
            'sale_probability' => $scores['sale_probability'],
            'estimated_days_to_sell' => $scores['estimated_days_to_sell'],
            'deal_quality_score' => $scores['deal_quality_score'],
            'market_heat_score' => $scores['market_heat_score'],
            'metadata' => ['automated' => true],
        ]);
    }
}

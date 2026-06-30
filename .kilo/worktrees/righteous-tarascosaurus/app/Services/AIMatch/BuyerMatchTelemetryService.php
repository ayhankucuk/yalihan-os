<?php

namespace App\Services\AIMatch;

use App\Models\BuyerMatchLog;
use App\Models\BuyerMatchSnapshot;
use App\Models\Ilan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Buyer Match Telemetry Service
 *
 * Handles logging and snapshotting for match engine performance tracking.
 */
class BuyerMatchTelemetryService
{
    /**
     * Persist match results to logs.
     */
    public function logMatches(Ilan $ilan, Collection $matches, string $locale): void
    {
        try {
            foreach ($matches as $match) {
                BuyerMatchLog::create([
                    'ilan_id' => $ilan->id,
                    'buyer_id' => $match['buyer']->id,
                    'talep_id' => $match['talep_id'],
                    'match_score' => $match['score']['total'],
                    'price_fit_score' => $match['score']['breakdown']['price'],
                    'location_fit_score' => $match['score']['breakdown']['location'],
                    'feature_fit_score' => $match['score']['breakdown']['features'],
                    'intent_fit_score' => $match['score']['breakdown']['intent'],
                    'churn_risk_score' => $match['score']['breakdown']['churn'],
                    'action_score' => $match['score']['breakdown']['action'],
                    'reason' => $match['reason'] ?? '',
                    'metadata' => [
                        'breakdown' => $match['score']['breakdown']
                    ],
                    'locale' => $locale,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Match Logging failed for ilan {$ilan->id}: " . $e->getMessage());
        }
    }

    /**
     * Create a performance snapshot for the matching run.
     */
    public function recordSnapshot(Ilan $ilan, int $count, float $topScore, ?int $topBuyerId): void
    {
        try {
            BuyerMatchSnapshot::create([
                'ilan_id' => $ilan->id,
                'total_candidates' => $count,
                'top_match_score' => $topScore,
                'top_buyer_id' => $topBuyerId,
            ]);
        } catch (\Exception $e) {
            Log::error("Match Snapshot failed for ilan {$ilan->id}: " . $e->getMessage());
        }
    }
}

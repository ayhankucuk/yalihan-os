<?php

namespace App\Services\MarketIntelligence;

use App\Models\MarketIntelligence\ListingOutcome;
use Illuminate\Support\Facades\Log;

/**
 * Outcome Tracking Service — MIE v2.0
 *
 * İlan kapanınca (sold/rented/withdrawn/expired) outcome kaydeder.
 * Bu veri, FeedbackEvaluationService tarafından snapshot ile karşılaştırılır.
 *
 * Tamamen deterministik — AI sıfır, rand() sıfır.
 */
class OutcomeTrackingService
{
    /** Geçerli outcome tipleri. */
    public const VALID_OUTCOME_TYPES = ['sold', 'rented', 'withdrawn', 'expired'];

    /**
     * İlan outcome'unu kaydet.
     *
     * @param int $listingId
     * @param array{
     *   outcome_type: string,
     *   days_to_close?: int,
     *   final_price?: float,
     *   price_changes_count?: int,
     *   lead_count?: int,
     *   closed_at?: string,
     * } $outcomeData
     */
    public function recordOutcome(int $listingId, array $outcomeData): ListingOutcome
    {
        $outcomeType = $outcomeData['outcome_type'] ?? 'withdrawn';

        if (! in_array($outcomeType, self::VALID_OUTCOME_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid outcome_type: {$outcomeType}");
        }

        $outcome = ListingOutcome::create([
            'listing_id' => $listingId,
            'outcome_type' => $outcomeType,
            'days_to_close' => $outcomeData['days_to_close'] ?? null,
            'final_price' => $outcomeData['final_price'] ?? null,
            'price_changes_count' => $outcomeData['price_changes_count'] ?? 0,
            'lead_count' => $outcomeData['lead_count'] ?? 0,
            'closed_at' => $outcomeData['closed_at'] ?? now(),
        ]);

        Log::channel('daily')->info('mie_listing_outcome_recorded', [
            'listing_id' => $listingId,
            'outcome_type' => $outcomeType,
            'days_to_close' => $outcome->days_to_close,
            'final_price' => $outcome->final_price,
        ]);

        return $outcome;
    }

    /**
     * Bir listing'in en son outcome'unu getir.
     */
    public function getLatestOutcome(int $listingId): ?ListingOutcome
    {
        return ListingOutcome::where('listing_id', $listingId)
            ->orderByDesc('created_at')
            ->first();
    }
}

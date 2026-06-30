<?php

namespace App\Services\AIMatch;

use App\Models\Talep;
use App\Models\Kisi;
use App\Models\Projections\BuyerIntentProjection;
use App\Models\Projections\TalepMatchProjection;
use App\Services\AI\NLPProcessor;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Buyer Intent Extraction Service
 *
 * Captures structured intent from:
 * - Talep records
 * - Buyer activity/queries
 * - External lead data
 */
class BuyerIntentExtractionService
{
    public function __construct(
        private NLPProcessor $nlpProcessor
    ) {}

    /**
     * Sync intent projection for a specific buyer.
     */
    public function syncBuyerIntent(Kisi $buyer): void
    {
        try {
            $latestTalep = $buyer->talepler()->active()->latest()->first(); // context7-ignore

            $intentData = [
                'buyer_id' => $buyer->id,
                'locale' => $buyer->preferred_locale ?? 'tr',
                'preferred_city' => $latestTalep?->il?->il_adi,
                'preferred_district' => $latestTalep?->ilce?->ilce_adi,
                'min_budget' => $latestTalep?->min_fiyat,
                'max_budget' => $latestTalep?->max_fiyat,
                'property_types' => $latestTalep ? [$latestTalep->emlak_tipi] : [],
                'room_preferences' => $latestTalep ? [$latestTalep->oda_sayisi] : [],
                'feature_preferences' => $latestTalep?->aranan_ozellikler_json ?? [],
                'urgency_level' => $this->calculateUrgency($latestTalep),
                'last_contact_at' => $buyer->last_contact_at,
            ];

            BuyerIntentProjection::updateOrCreate(
                ['buyer_id' => $buyer->id],
                $intentData
            );
        } catch (\Exception $e) {
            Log::error("BuyerIntentExtraction failed for buyer: {$buyer->id}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync match projection for a specific Talep.
     */
    public function syncTalepMatch(Talep $talep): void
    {
        try {
            TalepMatchProjection::updateOrCreate(
                ['talep_id' => $talep->id],
                [
                    'buyer_id' => $talep->kisi_id,
                    'city' => $talep->il?->il_adi,
                    'district' => $talep->ilce?->ilce_adi,
                    'min_price' => $talep->min_fiyat,
                    'max_price' => $talep->max_fiyat,
                    'room_count' => $talep->oda_sayisi,
                    'features' => $talep->aranan_ozellikler_json,
                    'property_type' => $talep->emlak_tipi,
                    'purchase_intent_level' => $this->calculateUrgency($talep),
                ]
            );
        } catch (\Exception $e) {
            Log::error("TalepMatchProjection sync failed for talep: {$talep->id}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate 0-10 urgency level based on talep age and priority.
     */
    private function calculateUrgency(?Talep $talep): int
    {
        if (!$talep) return 0;

        $score = 0;

        // Priority base score
        $score += match($talep->oncelik) {
            'Düşük' => 2,
            'Orta' => 4,
            'Yüksek' => 7,
            'Acil' => 10,
            default => 5
        };

        // Recency bonus
        $days = $talep->created_at?->diffInDays(now()) ?? 0;
        if ($days < 7) $score += 2;
        elseif ($days < 30) $score += 1;

        return min(10, $score);
    }
}

<?php

namespace App\Services\MarketIntelligence;

use App\Models\MarketIntelligence\FeedbackResult;
use App\Models\MarketIntelligence\ListingOutcome;
use App\Models\MarketIntelligence\PredictionSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * Feedback Evaluation Service — MIE v2.0
 *
 * Prediction snapshot ile gerçek outcome'u karşılaştırır.
 * Sistemin kendi doğruluğunu ölçer.
 *
 * Tamamen deterministik — AI sıfır, rand() sıfır.
 *
 * Değerlendirme kuralları:
 *   pricing_correct:
 *     - UNDERPRICED → hızlı satış (days_to_close < 30) = doğru
 *     - OVERPRICED  → yavaş satış/withdrawn/expired = doğru
 *     - FAIR        → normal sürede satış = doğru
 *
 *   demand_correct:
 *     - HOT    → days_to_close < 30 = doğru
 *     - ACTIVE → days_to_close < 60 = doğru
 *     - SLOW   → days_to_close > 45 = doğru
 *     - WEAK   → days_to_close > 60 veya withdrawn/expired = doğru
 *
 *   opportunity_correct:
 *     - BUY  → sold/rented hızlı ve fiyat düşmedi = doğru
 *     - SELL → fiyat düşürülmüş veya yavaş kapandı = doğru
 *     - WAIT → normal süreç = doğru
 */
class FeedbackEvaluationService
{
    /**
     * Tek ilan için feedback değerlendirmesi yap.
     *
     * Snapshot + outcome alır, karşılaştırır, FeedbackResult kaydeder.
     */
    public function evaluate(int $listingId): ?FeedbackResult
    {
        $snapshot = PredictionSnapshot::where('listing_id', $listingId)
            ->orderByDesc('snapshot_at')
            ->first();

        $outcome = ListingOutcome::where('listing_id', $listingId)
            ->orderByDesc('created_at')
            ->first();

        if (! $snapshot || ! $outcome) {
            return null;
        }

        // Zaten değerlendirilmiş mi?
        $existing = FeedbackResult::where('snapshot_id', $snapshot->id)
            ->where('outcome_id', $outcome->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $pricingCorrect = $this->evaluatePricing($snapshot, $outcome);
        $demandCorrect = $this->evaluateDemand($snapshot, $outcome);
        $opportunityCorrect = $this->evaluateOpportunity($snapshot, $outcome);

        $reason = $this->buildFeedbackReason($snapshot, $outcome, $pricingCorrect, $demandCorrect, $opportunityCorrect);

        $result = FeedbackResult::create([
            'listing_id' => $listingId,
            'snapshot_id' => $snapshot->id,
            'outcome_id' => $outcome->id,
            'pricing_correct' => $pricingCorrect,
            'demand_correct' => $demandCorrect,
            'opportunity_correct' => $opportunityCorrect,
            'feedback_reason' => $reason,
        ]);

        Log::channel('daily')->info('mie_feedback_evaluated', [
            'listing_id' => $listingId,
            'pricing_correct' => $pricingCorrect,
            'demand_correct' => $demandCorrect,
            'opportunity_correct' => $opportunityCorrect,
        ]);

        return $result;
    }

    /**
     * Pure evaluation — snapshot ve outcome verisinden pricing doğruluğu ölç.
     * Unit test edilebilir — DB bağımlılığı yok.
     */
    public function evaluatePricing(PredictionSnapshot $snapshot, ListingOutcome $outcome): ?bool
    {
        $position = $snapshot->pricing_position;
        $daysToClose = $outcome->days_to_close;
        $outcomeType = $outcome->outcome_type;

        if ($position === null || $daysToClose === null) {
            return null;
        }

        return match ($position) {
            'underpriced' => $daysToClose < 30 && in_array($outcomeType, ['sold', 'rented'], true),
            'overpriced', 'aggressively_overpriced' => $daysToClose > 60
                || in_array($outcomeType, ['withdrawn', 'expired'], true),
            'fair' => $daysToClose >= 15 && $daysToClose <= 90
                && in_array($outcomeType, ['sold', 'rented'], true),
            default => null,
        };
    }

    /**
     * Pure evaluation — demand doğruluğu ölç.
     */
    public function evaluateDemand(PredictionSnapshot $snapshot, ListingOutcome $outcome): ?bool
    {
        $demandLabel = $snapshot->demand_label;
        $daysToClose = $outcome->days_to_close;
        $outcomeType = $outcome->outcome_type;

        if ($demandLabel === null || $daysToClose === null) {
            return null;
        }

        return match ($demandLabel) {
            'HOT' => $daysToClose < 30,
            'ACTIVE' => $daysToClose < 60,
            'SLOW' => $daysToClose > 45,
            'WEAK' => $daysToClose > 60
                || in_array($outcomeType, ['withdrawn', 'expired'], true),
            default => null,
        };
    }

    /**
     * Pure evaluation — opportunity doğruluğu ölç.
     */
    public function evaluateOpportunity(PredictionSnapshot $snapshot, ListingOutcome $outcome): ?bool
    {
        $action = $snapshot->opportunity_action;
        $daysToClose = $outcome->days_to_close;
        $outcomeType = $outcome->outcome_type;
        $priceChanges = $outcome->price_changes_count ?? 0;

        if ($action === null) {
            return null;
        }

        return match ($action) {
            'BUY' => in_array($outcomeType, ['sold', 'rented'], true)
                && $daysToClose !== null && $daysToClose < 45
                && $priceChanges <= 1,
            'SELL' => ($priceChanges > 0)
                || ($daysToClose !== null && $daysToClose > 60)
                || in_array($outcomeType, ['withdrawn', 'expired'], true),
            'WAIT' => in_array($outcomeType, ['sold', 'rented'], true)
                && $daysToClose !== null && $daysToClose >= 30 && $daysToClose <= 120,
            'INSUFFICIENT_DATA' => true, // veri yoktu, doğru/yanlış demek anlamsız
            default => null,
        };
    }

    /**
     * Deterministic feedback reason builder.
     */
    private function buildFeedbackReason(
        PredictionSnapshot $snapshot,
        ListingOutcome $outcome,
        ?bool $pricingCorrect,
        ?bool $demandCorrect,
        ?bool $opportunityCorrect,
    ): string {
        $parts = [];

        $positionLabel = $snapshot->pricing_position ?? 'belirsiz';
        $outcomeLabel = $outcome->outcome_type;
        $days = $outcome->days_to_close;

        // Pricing
        if ($pricingCorrect === true) {
            $parts[] = "fiyat pozisyonu ({$positionLabel}) doğru";
        } elseif ($pricingCorrect === false) {
            $parts[] = "fiyat pozisyonu ({$positionLabel}) hatalı";
        }

        // Demand
        $demandLabel = $snapshot->demand_label ?? 'belirsiz';
        if ($demandCorrect === true) {
            $parts[] = "talep tahmini ({$demandLabel}) doğru";
        } elseif ($demandCorrect === false) {
            $parts[] = "talep tahmini ({$demandLabel}) hatalı";
        }

        // Opportunity
        $action = $snapshot->opportunity_action ?? 'belirsiz';
        if ($opportunityCorrect === true) {
            $parts[] = "aksiyon önerisi ({$action}) doğru";
        } elseif ($opportunityCorrect === false) {
            $parts[] = "aksiyon önerisi ({$action}) hatalı";
        }

        if ($days !== null) {
            $parts[] = "{$outcomeLabel}, {$days} günde kapandı";
        } else {
            $parts[] = "sonuç: {$outcomeLabel}";
        }

        return implode(', ', $parts) . '.';
    }

    /**
     * Toplu accuracy hesapla — tüm feedback'leri analiz et.
     *
     * @return array{pricing_accuracy: float|null, demand_accuracy: float|null, opportunity_accuracy: float|null, total_evaluated: int}
     */
    public function calculateAccuracy(): array
    {
        $results = FeedbackResult::all();

        if ($results->isEmpty()) {
            return [
                'pricing_accuracy' => null,
                'demand_accuracy' => null,
                'opportunity_accuracy' => null,
                'total_evaluated' => 0,
            ];
        }

        $pricingResults = $results->whereNotNull('pricing_correct');
        $demandResults = $results->whereNotNull('demand_correct');
        $opportunityResults = $results->whereNotNull('opportunity_correct');

        return [
            'pricing_accuracy' => $pricingResults->isNotEmpty()
                ? round($pricingResults->where('pricing_correct', true)->count() / $pricingResults->count() * 100, 1)
                : null,
            'demand_accuracy' => $demandResults->isNotEmpty()
                ? round($demandResults->where('demand_correct', true)->count() / $demandResults->count() * 100, 1)
                : null,
            'opportunity_accuracy' => $opportunityResults->isNotEmpty()
                ? round($opportunityResults->where('opportunity_correct', true)->count() / $opportunityResults->count() * 100, 1)
                : null,
            'total_evaluated' => $results->count(),
        ];
    }
}

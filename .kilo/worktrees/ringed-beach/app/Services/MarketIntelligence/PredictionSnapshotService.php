<?php

namespace App\Services\MarketIntelligence;

use App\Models\MarketIntelligence\PredictionSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * Prediction Snapshot Service — MIE v2.0
 *
 * MIE analiz çıktısını snapshot olarak kaydeder.
 * İleride gerçek sonuçla karşılaştırma için kullanılır.
 *
 * Tamamen deterministik — AI sıfır, rand() sıfır.
 */
class PredictionSnapshotService
{
    /**
     * MIE intelligence payload'ını snapshot olarak kaydet.
     *
     * @param int $listingId
     * @param array{
     *   pricing_position?: string,
     *   pricing_score?: int,
     *   demand_score?: int,
     *   demand_label?: string,
     *   confidence_score?: int,
     *   confidence_label?: string,
     *   opportunity_action?: string,
     *   opportunity_score?: int,
     *   priority_score?: int,
     *   priority_label?: string,
     *   current_price?: float,
     *   benchmark_price?: float,
     * } $intelligencePayload
     */
    public function saveSnapshot(int $listingId, array $intelligencePayload): PredictionSnapshot
    {
        $snapshot = PredictionSnapshot::create([
            'listing_id' => $listingId,
            'pricing_position' => $intelligencePayload['pricing_position'] ?? null,
            'pricing_score' => $intelligencePayload['pricing_score'] ?? 0,
            'demand_score' => $intelligencePayload['demand_score'] ?? 0,
            'demand_label' => $intelligencePayload['demand_label'] ?? null,
            'confidence_score' => $intelligencePayload['confidence_score'] ?? 0,
            'confidence_label' => $intelligencePayload['confidence_label'] ?? null,
            'opportunity_action' => $intelligencePayload['opportunity_action'] ?? null,
            'opportunity_score' => $intelligencePayload['opportunity_score'] ?? 0,
            'priority_score' => $intelligencePayload['priority_score'] ?? 0,
            'priority_label' => $intelligencePayload['priority_label'] ?? null,
            'current_price' => $intelligencePayload['current_price'] ?? null,
            'benchmark_price' => $intelligencePayload['benchmark_price'] ?? null,
            'snapshot_at' => now(),
        ]);

        Log::channel('daily')->info('mie_prediction_snapshot_saved', [
            'listing_id' => $listingId,
            'pricing_position' => $snapshot->pricing_position,
            'opportunity_action' => $snapshot->opportunity_action,
            'priority_score' => $snapshot->priority_score,
        ]);

        return $snapshot;
    }

    /**
     * Bir listing için en son snapshot'ı getir.
     */
    public function getLatestSnapshot(int $listingId): ?PredictionSnapshot
    {
        return PredictionSnapshot::where('listing_id', $listingId)
            ->orderByDesc('snapshot_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Bir listing için tüm snapshot'ları getir.
     */
    public function getSnapshots(int $listingId): \Illuminate\Database\Eloquent\Collection
    {
        return PredictionSnapshot::where('listing_id', $listingId)
            ->orderByDesc('snapshot_at')
            ->get();
    }
}

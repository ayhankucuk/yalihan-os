<?php

namespace App\Services\AI;

use App\Models\AiSaglayiciProfili;
use App\Models\AiOgrenmeSinyali;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🤖 Provider Intelligence Selector
 * Phase 9: Selects the best AI provider based on cost, latency, and performance
 */
class ProviderSelectorService
{
    /**
     * Determine the best provider for a given context
     */
    public function getBestProvider(?int $categoryId, ?int $yayinTipiId): string
    {
        // 1. Check for explicit overrides or locks (Future implementation)
        
        // 2. Load profiles for this category
        $profiles = AiSaglayiciProfili::where('kategori_id', $categoryId)
            ->where('yayin_tipi_id', $yayinTipiId)
            ->get();

        if ($profiles->isEmpty()) {
            return config('vision.provider', 'mock'); // Fallback to default
        }

        // 3. Scoring Algorithm
        $bestProvider = null;
        $maxScore = -1;

        foreach ($profiles as $profile) {
            $score = $this->calculateProviderScore($profile);
            
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestProvider = $profile->saglayici;
            }
        }

        return $bestProvider ?? config('vision.provider', 'mock');
    }

    /**
     * Score a provider based on metrics
     * Higher is better
     */
    protected function calculateProviderScore(AiSaglayiciProfili $profile): float
    {
        // Weights
        $wAccept = 0.50; // Performance (acceptance rate)
        $wLatency = 0.30; // Speed
        $wCost = 0.20; // Economy

        // Normalized metrics (0 to 1)
        $nAccept = (float) ($profile->kabul_orani / 100);
        
        // Latency: 500ms -> 1.0, 5000ms -> 0.0
        $nLatency = max(0, 1 - ($profile->ort_gecikme_ms / 5000));
        
        // Cost: $0.00 -> 1.0, $0.10 -> 0.0
        $nCost = max(0, 1 - ($profile->ort_maliyet_usd / 0.10));

        return ($nAccept * $wAccept) + ($nLatency * $wLatency) + ($nCost * $wCost);
    }

    /**
     * Update provider performance metrics from telemetry
     */
    public function recordUsage(string $provider, int $latencyMs, float $cost, ?int $categoryId, ?int $yayinTipiId)
    {
        $profile = AiSaglayiciProfili::firstOrCreate([
            'kategori_id' => $categoryId,
            'yayin_tipi_id' => $yayinTipiId,
            'saglayici' => $provider
        ]);

        // Moving Average implementation
        $alpha = 0.1; // Weight for new data
        
        $newLatency = ($profile->ort_gecikme_ms * (1 - $alpha)) + ($latencyMs * $alpha);
        $newCost = ($profile->ort_maliyet_usd * (1 - $alpha)) + ($cost * $alpha);

        // Acceptance rate sync (will be updated via recalculate job)
        $profile->update([
            'ort_gecikme_ms' => $newLatency,
            'ort_maliyet_usd' => $newCost,
            'ornek_sayisi' => $profile->ornek_sayisi + 1
        ]);
    }

    /**
     * Recalculate acceptance rates for all providers
     */
    public function syncAcceptanceRates()
    {
        $stats = DB::table('ai_ogrenme_sinyalleri')
            ->select(
                'kategori_id',
                'yayin_tipi_id',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(sinyaller_json, "$.provider")) as provider'), // Placeholder
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN karar_tipi = "applied" THEN 1 ELSE 0 END) as applied')
            )
            ->groupBy('kategori_id', 'yayin_tipi_id', 'provider')
            ->get();

        foreach ($stats as $stat) {
            if (!$stat->provider) continue;

            $rate = $stat->applied / ($stat->total ?: 1);
            
            AiSaglayiciProfili::updateOrCreate(
                [
                    'kategori_id' => $stat->kategori_id,
                    'yayin_tipi_id' => $stat->yayin_tipi_id,
                    'saglayici' => $stat->provider
                ],
                ['kabul_orani' => $rate * 100]
            );
        }
    }
}

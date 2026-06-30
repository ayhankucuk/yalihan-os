<?php

namespace App\Services\AI\Monitoring;

/**
 * ProviderSelectorPolicy — Pure Scoring Function
 *
 * SAB v4.1 Kural 8: Telemetry-driven provider selection
 * Konum: AI/Monitoring (infra layer — domain service limitini yormaz)
 *
 * Bu sinif SAF bir fonksiyondur:
 * - IO yok (DB/Cache/HTTP cagirisi yok)
 * - Side effect yok
 * - Input: provider stats
 * - Output: provider adi + reason + scores
 *
 * Scoring Formula:
 *   score = (success_rate × W_SR) + (latency_score × W_LAT) + (cost_score × W_COST)
 *
 * Default Weights: SR=40%, Latency=35%, Cost=25%
 */
class ProviderSelectorPolicy
{
    // Scoring weights (toplamı 1.0)
    private const W_SUCCESS_RATE = 0.40;
    private const W_LATENCY = 0.35;
    private const W_COST = 0.25;

    // Minimum call count for a provider to be considered reliable
    private const MIN_CALL_THRESHOLD = 5;

    // Default provider when no telemetry data exists
    private const DEFAULT_PROVIDER = 'ollama';

    // Known providers (scoring sırasında kullanılır)
    private const KNOWN_PROVIDERS = ['ollama', 'deepseek', 'openai', 'google'];

    /**
     * Select best provider based on telemetry stats
     *
     * @param array<string, array{
     *   call_count: int,
     *   success_rate: float,
     *   p50_ms: float,
     *   p95_ms: float,
     *   avg_tokens: float,
     *   estimated_cost: float,
     * }> $providerStats Aggregator'dan gelen stats
     * @param string|null $taskType Task type (bilgi amaçlı, şu an scoring'i etkilemez)
     * @return array{provider: string, reason: string, scores: array}
     */
    public function select(array $providerStats, ?string $taskType = null): array
    {
        // Graceful fallback: veri yoksa default provider
        if (empty($providerStats)) {
            return [
                'provider' => self::DEFAULT_PROVIDER,
                'reason' => 'no_telemetry_data',
                'scores' => [],
            ];
        }

        // Yeterli call count'u olan provider'lari filtrele
        $eligibleProviders = array_filter(
            $providerStats,
            fn($stats) => $stats['call_count'] >= self::MIN_CALL_THRESHOLD
        );

        // Hicbir provider yeterli veriye sahip degilse fallback
        if (empty($eligibleProviders)) {
            return [
                'provider' => self::DEFAULT_PROVIDER,
                'reason' => 'insufficient_data',
                'scores' => [],
            ];
        }

        // Normalizasyon icin min/max hesapla
        $allP95 = array_column($eligibleProviders, 'p95_ms');
        $allCosts = array_column($eligibleProviders, 'estimated_cost');

        $maxP95 = max($allP95) ?: 1;
        $maxCost = max($allCosts) ?: 1; // 0 bolunme koruması

        // Her provider icin skor hesapla
        $scores = [];
        foreach ($eligibleProviders as $provider => $stats) {
            $srScore = $stats['success_rate']; // 0.0 - 1.0

            // Latency: dusuk = iyi. Normalize edilmis ters skor.
            $latencyScore = 1.0 - ($stats['p95_ms'] / $maxP95);
            // En yavas provider icin 0 olmasin
            $latencyScore = max(0.05, $latencyScore);

            // Cost: dusuk = iyi. Normalize edilmis ters skor.
            if ($maxCost > 0 && $stats['estimated_cost'] > 0) {
                $costScore = 1.0 - ($stats['estimated_cost'] / $maxCost);
                $costScore = max(0.05, $costScore);
            } else {
                $costScore = 1.0; // Free provider (ollama)
            }

            $totalScore = ($srScore * self::W_SUCCESS_RATE)
                + ($latencyScore * self::W_LATENCY)
                + ($costScore * self::W_COST);

            $scores[$provider] = [
                'total' => round($totalScore, 4),
                'sr' => round($srScore, 4),
                'latency' => round($latencyScore, 4),
                'cost' => round($costScore, 4),
                'p95_ms' => $stats['p95_ms'],
                'call_count' => $stats['call_count'],
            ];
        }

        // En yuksek skoru bul
        $bestProvider = '';
        $bestScore = -1;
        foreach ($scores as $provider => $scoreData) {
            if ($scoreData['total'] > $bestScore) {
                $bestScore = $scoreData['total'];
                $bestProvider = $provider;
            }
        }

        $reason = sprintf(
            'score=%.4f (sr=%.2f, lat=%.2f, cost=%.2f) | p95=%dms | calls=%d',
            $scores[$bestProvider]['total'],
            $scores[$bestProvider]['sr'],
            $scores[$bestProvider]['latency'],
            $scores[$bestProvider]['cost'],
            $scores[$bestProvider]['p95_ms'],
            $scores[$bestProvider]['call_count']
        );

        return [
            'provider' => $bestProvider,
            'reason' => $reason,
            'scores' => $scores,
        ];
    }
}

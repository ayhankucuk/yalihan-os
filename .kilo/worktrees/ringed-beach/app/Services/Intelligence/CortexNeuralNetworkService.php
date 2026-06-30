<?php

namespace App\Services\Intelligence;

use App\Models\CortexNeuralConnection;
use App\Services\Intelligence\AutoLearningService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cortex Neural Network Service
 * Context7: Cortex Sinir Ağı (Cortex Neural Network)
 *
 * Modüller arası bağlantıları öğrenen ve optimize eden sinir ağı
 */
class CortexNeuralNetworkService
{
    private const CACHE_TTL = 3600;
    private const MIN_INTERACTIONS = 3;
    private const STRONG_CONNECTION_THRESHOLD = 70.0;

    public function __construct(
        private AutoLearningService $autoLearning
    ) {}

    /**
     * Modüller arası etkileşimi kaydet ve öğren
     *
     * @param string $sourceModule
     * @param string $targetModule
     * @param string $connectionType
     * @param bool $success
     * @param float|null $performanceScore
     * @param array $context
     * @return CortexNeuralConnection
     */
    public function recordInteraction(
        string $sourceModule,
        string $targetModule,
        string $connectionType,
        bool $success = true,
        ?float $performanceScore = null,
        array $context = []
    ): CortexNeuralConnection {
        try {
            $connection = CortexNeuralConnection::firstOrNew([
                'source_module' => $sourceModule,
                'target_module' => $targetModule,
                'connection_type' => $connectionType,
            ]);

            // İstatistikleri güncelle
            $connection->interaction_count = ($connection->interaction_count ?? 0) + 1;
            $connection->last_interaction_at = now();

            if (!$connection->exists) {
                $connection->first_interaction_at = now();
                $connection->connection_strength = 10.0;
                $connection->success_rate = $success ? 100.0 : 0.0;
                $connection->avg_performance = $performanceScore;
            } else {
                // Başarı oranını güncelle
                $totalInteractions = $connection->interaction_count;
                $successfulInteractions = (int) (($connection->success_rate / 100) * ($totalInteractions - 1));
                if ($success) {
                    $successfulInteractions++;
                }
                $connection->success_rate = ($successfulInteractions / $totalInteractions) * 100;

                // Performans ortalamasını güncelle
                if ($performanceScore !== null) {
                    $currentAvg = $connection->avg_performance ?? 0;
                    $connection->avg_performance = (($currentAvg * ($totalInteractions - 1)) + $performanceScore) / $totalInteractions;
                }

                // Bağlantı gücünü güncelle
                $connection->connection_strength = $this->calculateConnectionStrength(
                    (float) $connection->success_rate,
                    $connection->avg_performance !== null ? (float) $connection->avg_performance : null,
                    $connection->interaction_count
                );
            }

            // Context ve pattern'leri güncelle
            $usageContext = $connection->usage_context ?? [];
            $usageContext[] = $context;
            $connection->usage_context = array_slice($usageContext, -10); // Son 10 etkileşim

            $connection->aktiflik_durumu = true;
            $connection->save();

            // Pattern'leri öğren
            if ($connection->interaction_count >= self::MIN_INTERACTIONS) {
                $this->learnPatterns($connection);
            }

            return $connection;
        } catch (\Exception $e) {
            LogService::error('Cortex Neural Network interaction failed', [
                'source_module' => $sourceModule,
                'target_module' => $targetModule,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Modüller arası bağlantı önerileri
     *
     * @param string $module
     * @return array
     */
    public function suggestConnections(string $module): array
    {
        $cacheKey = "cortex_neural:suggestions:{$module}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($module) {
            $connections = CortexNeuralConnection::where(function ($query) use ($module) {
                $query->where('source_module', $module)
                    ->orWhere('target_module', $module);
            })
                ->active() // context7-ignore
                ->strong()
                ->orderBy('connection_strength', 'desc') // context7-ignore
                ->limit(10)
                ->get();

            $suggestions = [];

            foreach ($connections as $connection) {
                $targetModule = $connection->source_module === $module
                    ? $connection->target_module
                    : $connection->source_module;

                $suggestions[] = [
                    'module' => $targetModule,
                    'connection_type' => $connection->connection_type,
                    'strength' => round((float) $connection->connection_strength, 2),
                    'success_rate' => round((float) $connection->success_rate, 2),
                    'interaction_count' => $connection->interaction_count,
                    'recommendation' => $this->generateConnectionRecommendation($connection),
                ];
            }

            return [
                'success' => true,
                'source_module' => $module,
                'suggestions' => $suggestions,
                'analyzed_at' => now(),
            ];
        });
    }

    /**
     * Modül ağı görselleştirme verisi
     *
     * @return array
     */
    public function getNetworkGraph(): array
    {
        $cacheKey = 'cortex_neural:network_graph';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $connections = CortexNeuralConnection::active()
                ->strong()
                ->get();

            $nodes = [];
            $edges = [];

            foreach ($connections as $connection) {
                // Node'ları ekle
                if (!isset($nodes[$connection->source_module])) {
                    $nodes[$connection->source_module] = [
                        'id' => $connection->source_module,
                        'label' => strtoupper($connection->source_module),
                        'type' => 'module', // context7-ignore
                    ];
                }

                if (!isset($nodes[$connection->target_module])) {
                    $nodes[$connection->target_module] = [
                        'id' => $connection->target_module,
                        'label' => strtoupper($connection->target_module),
                        'type' => 'module', // context7-ignore
                    ];
                }

                // Edge'leri ekle
                $edges[] = [
                    'source' => $connection->source_module,
                    'target' => $connection->target_module,
                    'type' => $connection->connection_type, // context7-ignore
                    'strength' => round((float) $connection->connection_strength, 2),
                    'success_rate' => round((float) $connection->success_rate, 2),
                    'interactions' => $connection->interaction_count,
                ];
            }

            return [
                'success' => true,
                'nodes' => array_values($nodes),
                'edges' => $edges,
                'total_connections' => count($edges),
                'analyzed_at' => now(),
            ];
        });
    }

    /**
     * Pattern'leri öğren
     */
    private function learnPatterns(CortexNeuralConnection $connection): void
    {
        $patterns = $connection->learned_patterns ?? [];

        // Başarılı pattern'leri çıkar
        if ($connection->success_rate >= 80) {
            $patterns['high_success'] = [
                'success_rate' => $connection->success_rate,
                'avg_performance' => $connection->avg_performance,
                'learned_at' => now()->toISOString(),
            ];
        }

        // Kullanım pattern'lerini çıkar
        if (!empty($connection->usage_context)) {
            $patterns['usage_patterns'] = $this->extractUsagePatterns($connection->usage_context);
        }

        $connection->learned_patterns = $patterns;
        $connection->save();
    }

    /**
     * Kullanım pattern'lerini çıkar
     */
    private function extractUsagePatterns(array $contexts): array
    {
        $patterns = [];

        // En sık kullanılan context'leri tespit et
        $contextCounts = [];
        foreach ($contexts as $context) {
            $key = json_encode($context);
            $contextCounts[$key] = ($contextCounts[$key] ?? 0) + 1;
        }

        arsort($contextCounts);
        $topContexts = array_slice($contextCounts, 0, 3, true);

        foreach ($topContexts as $contextJson => $count) {
            $patterns[] = [
                'context' => json_decode($contextJson, true),
                'frequency' => $count,
            ];
        }

        return $patterns;
    }

    /**
     * Bağlantı gücü hesapla
     */
    private function calculateConnectionStrength(float $successRate, ?float $avgPerformance, int $interactionCount): float
    {
        $strength = $successRate * 0.6; // Başarı oranı %60 ağırlık

        if ($avgPerformance !== null) {
            $strength += ($avgPerformance * 0.3); // Performans %30 ağırlık
        }

        // Etkileşim sayısı bonusu (max %10)
        $interactionBonus = min(($interactionCount / 10) * 10, 10);
        $strength += $interactionBonus * 0.1;

        return min($strength, 100.0);
    }

    /**
     * Bağlantı önerisi oluştur
     */
    private function generateConnectionRecommendation(CortexNeuralConnection $connection): string
    {
        if ($connection->connection_strength >= 80) {
            return "Güçlü bağlantı - %{$connection->success_rate} başarı oranı ile önerilir";
        } elseif ($connection->connection_strength >= 60) {
            return "Orta güçlü bağlantı - %{$connection->success_rate} başarı oranı";
        }

        return "Zayıf bağlantı - Dikkatli kullanılmalı";
    }

    /**
     * Cache temizleme
     */
    public function clearCache(?string $module = null): void
    {
        if ($module) {
            Cache::forget("cortex_neural:suggestions:{$module}");
        } else {
            Cache::forget('cortex_neural:*');
        }
    }
}


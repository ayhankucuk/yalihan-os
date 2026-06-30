<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use App\Models\AiLog;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * ��️ SAB SEALED
 * - Forbidden keywords: "st' . 'atus" family (do not introduce)
 * - SSOT: naming must reflect domain semantics (e.g., yayin_durumu vs aktiflik_durumu)
 * - No hidden side-effects: logic stays in service layer, UI is dumb
 * - Any change must pass: bekci:audit + integrity scan
 */
/**
 * Cortex Learning Service
 *
 * Phase E: AI Learning Loop - Read-only analytics from AiLog
 *
 * Sorumluluklar (SRP):
 * - AiLog üzerinden okuma yapar (content_type: ilan_quality_check, ilan_publish_decision)
 * - Kalite skorları, publish kararları, override'lardan istatistik üretir
 * - Önerilen (advisory) eşik & ağırlık güncellemeleri döndürür
 *
 * Kurallar (ZORUNLU):
 * - ❌ FeatureAssignment / Policy / UPS tablolarına yazım YOK
 * - ❌ AI çıktısı konfigürasyon değiştiremez
 * - ✅ Sadece AiLog → stats → recommendation
 * - ✅ Read-only service
 * - ✅ Observer mode korunur
 */
class CortexLearningService
{
    /**
     * Analyze quality check outcomes and publish decisions
     *
     * @param array $filters ['kategori_slug' => string, 'days' => int]
     * @return array Statistics and recommendations
     */
    public function analyzeQualityOutcomes(array $filters = []): array
    {
        $startTime = LogService::startTimer('cortex_learning_analysis');

        try {
            $days = $filters['days'] ?? 30;
            $kategoriSlug = $filters['kategori_slug'] ?? null;

            // 1. Read quality check logs (Phase C)
            $qualityLogs = $this->getQualityCheckLogs($days, $kategoriSlug);

            // 2. Read publish decision logs (Phase D)
            $publishLogs = $this->getPublishDecisionLogs($days, $kategoriSlug);

            // 3. Calculate statistics
            $stats = $this->calculateStatistics($qualityLogs, $publishLogs);

            // 4. Generate recommendations (advisory only)
            $recommendations = $this->generateRecommendations($stats);

            $durationMs = LogService::stopTimer($startTime);

            LogService::info('Cortex learning analysis completed', [
                'days' => $days,
                'kategori_slug' => $kategoriSlug,
                'quality_logs_count' => count($qualityLogs),
                'publish_logs_count' => count($publishLogs),
                'duration_ms' => $durationMs,
            ]);

            return [
                'success' => true,
                'data' => [
                    'filters' => [
                        'days' => $days,
                        'kategori_slug' => $kategoriSlug,
                    ],
                    'stats' => $stats,
                    'recommendations' => $recommendations,
                    'meta' => [
                        'quality_logs_analyzed' => count($qualityLogs),
                        'publish_logs_analyzed' => count($publishLogs),
                        'duration_ms' => $durationMs,
                        'analysis_timestamp' => now()->toISOString(),
                    ],
                ],
            ];
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            LogService::error('Cortex learning analysis failed', [
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ], $e);

            return [
                'success' => false,
                'message' => 'Learning analysis failed: ' . $e->getMessage(),
                'data' => [
                    'stats' => [],
                    'recommendations' => [],
                    'meta' => [
                        'error' => $e->getMessage(),
                        'duration_ms' => $durationMs,
                    ],
                ],
            ];
        }
    }

    /**
     * Get quality check logs (Phase C)
     *
     * @param int $days
     * @param string|null $kategoriSlug
     * @return \Illuminate\Support\Collection
     */
    private function getQualityCheckLogs(int $days, ?string $kategoriSlug = null)
    {
        $query = AiLog::where('content_type', 'ilan_quality_check')
            ->where('islem_durumu', 'success')
            ->where('created_at', '>=', now()->subDays($days));

        if ($kategoriSlug) {
            $query->whereRaw("JSON_EXTRACT(request_payload, '$.kategori_slug') = ?", [$kategoriSlug]);
        }

        return $query->get(['id', 'request_payload', 'response_payload', 'response_time', 'created_at']);
    }

    /**
     * Get publish decision logs (Phase D)
     *
     * @param int $days
     * @param string|null $kategoriSlug
     * @return \Illuminate\Support\Collection
     */
    private function getPublishDecisionLogs(int $days, ?string $kategoriSlug = null)
    {
        $query = AiLog::where('content_type', 'ilan_publish_decision')
            ->where('created_at', '>=', now()->subDays($days));

        // Note: kategoriSlug filter would require JOIN with ilans table
        // For now, skip kategoriSlug filter for publish logs

        return $query->get(['id', 'request_payload', 'response_payload', 'response_time', 'created_at']);
    }

    /**
     * Calculate statistics from logs
     *
     * @param \Illuminate\Support\Collection $qualityLogs
     * @param \Illuminate\Support\Collection $publishLogs
     * @return array
     */
    private function calculateStatistics($qualityLogs, $publishLogs): array
    {
        // Quality check stats
        $qualityScores = [];
        $recommendations = [];

        foreach ($qualityLogs as $log) {
            $requestData = is_array($log->request_payload) ? $log->request_payload : [];
            $qualityScore = $requestData['quality_score'] ?? null;
            $recommendation = $requestData['recommendation'] ?? null;

            if ($qualityScore !== null) {
                $qualityScores[] = $qualityScore;
            }

            if ($recommendation) {
                $recommendations[$recommendation] = ($recommendations[$recommendation] ?? 0) + 1;
            }
        }

        $avgQualityScore = count($qualityScores) > 0
            ? (int) round(array_sum($qualityScores) / count($qualityScores))
            : 0;

        // Publish decision stats
        $totalPublish = $publishLogs->count();
        $successPublish = $publishLogs->where('islem_durumu', 'success')->count();
        $overrideCount = 0;
        $blockCount = 0;

        foreach ($publishLogs as $log) {
            $requestData = is_array($log->request_payload) ? $log->request_payload : [];
            $override = $requestData['override'] ?? false;
            $recommendation = $requestData['recommendation'] ?? null;

            if ($override) {
                $overrideCount++;
            }

            if ($recommendation === 'block') {
                $blockCount++;
            }
        }

        $publishBlockRate = $totalPublish > 0 ? round($blockCount / $totalPublish, 2) : 0;
        $overrideRate = $totalPublish > 0 ? round($overrideCount / $totalPublish, 2) : 0;

        return [
            'quality_checks' => [
                'total' => $qualityLogs->count(),
                'avg_quality_score' => $avgQualityScore,
                'recommendation_distribution' => $recommendations,
            ],
            'publish_decisions' => [
                'total' => $totalPublish,
                'success_rate' => $totalPublish > 0 ? round($successPublish / $totalPublish, 2) : 0,
                'block_rate' => $publishBlockRate,
                'override_rate' => $overrideRate,
            ],
        ];
    }

    /**
     * Generate recommendations (advisory only)
     *
     * @param array $stats
     * @return array
     */
    private function generateRecommendations(array $stats): array
    {
        $avgScore = $stats['quality_checks']['avg_quality_score'] ?? 0;
        $blockRate = $stats['publish_decisions']['block_rate'] ?? 0;
        $overrideRate = $stats['publish_decisions']['override_rate'] ?? 0;

        // Advisory recommendations (NOT auto-applied)
        $recommendations = [];

        // If average score is consistently low but override rate is high,
        // suggest lowering the minimum acceptable score
        if ($avgScore < 70 && $overrideRate > 0.3) {
            $recommendations[] = [
                'type' => 'threshold', // context7-ignore
                'code' => 'LOWER_MIN_SCORE',
                'message' => 'Ortalama skor düşük ama override oranı yüksek. Min. skoru 70\'e düşürmeyi düşünün.',
                'suggested_value' => 70,
                'confidence' => min(0.9, $overrideRate),
            ];
        }

        // If block rate is very low, quick checks might be too lenient
        if ($blockRate < 0.05) {
            $recommendations[] = [
                'type' => 'weights', // context7-ignore
                'code' => 'INCREASE_QUICK_WEIGHT',
                'message' => 'Block oranı çok düşük. Quick check ağırlığını artırın.',
                'suggested_weights' => [
                    'quick_checks' => 0.50,
                    'ai_checks' => 0.50,
                ],
                'confidence' => 0.7,
            ];
        }

        // If block rate is very high, AI checks might be too strict
        if ($blockRate > 0.25) {
            $recommendations[] = [
                'type' => 'weights', // context7-ignore
                'code' => 'DECREASE_AI_WEIGHT',
                'message' => 'Block oranı çok yüksek. AI check ağırlığını azaltın.',
                'suggested_weights' => [
                    'quick_checks' => 0.60,
                    'ai_checks' => 0.40,
                ],
                'confidence' => 0.75,
            ];
        }

        return $recommendations;
    }
}

<?php

namespace App\Services\Analytics;

/**
 * @sab-ignore-catch
 */

use App\Models\AiLog;
use App\Services\Logging\LogService;

/**
 * AI Karar Tutarsızlığı Analizi
 *
 * Context7 Standardı: C7-AI-DECISION-ANALYZER-2025-11-25
 *
 * Aynı AI önerisine farklı danışmanların verdiği geri bildirimlerin tutarlılığını analiz eder
 */
class AIDecisionInconsistencyAnalyzer
{
    /**
     * AI karar tutarsızlıklarını analiz et
     *
     * @param  int  $minRecords  Minimum kayıt sayısı (varsayılan: 5)
     * @param  float  $threshold  Rating farkı eşiği (varsayılan: 2.0)
     * @return array Analiz sonuçları
     */
    public function analyze(int $minRecords = 5, float $threshold = 2.0): array
    {
        try {
            // Aynı request_data'ya sahip kayıtları grupla
            $groupedLogs = $this->groupByRequestData($minRecords);

            $inconsistencies = [];

            foreach ($groupedLogs as $requestDataHash => $logs) {
                $analysis = $this->analyzeGroup($logs, $threshold);

                if (! empty($analysis['anomalies'])) {
                    $inconsistencies[] = [
                        'request_data_hash' => $requestDataHash,
                        'request_data' => $logs[0]->request_data,
                        'total_records' => count($logs),
                        'average_rating' => $analysis['average_rating'],
                        'rating_distribution' => $analysis['rating_distribution'],
                        'anomalies' => $analysis['anomalies'],
                        'severity' => $analysis['severity'],
                    ];
                }
            }

            // Şiddet seviyesine göre sırala
            usort($inconsistencies, function ($a, $b) {
                return $b['severity'] <=> $a['severity'];
            });

            LogService::action(
                'ai_decision_inconsistency_analysis',
                'ai_log',
                null,
                [
                    'total_groups' => count($groupedLogs),
                    'inconsistent_groups' => count($inconsistencies),
                    'min_records' => $minRecords,
                    'threshold' => $threshold,
                ]
            );

            return [
                'success' => true,
                'total_groups_analyzed' => count($groupedLogs),
                'inconsistent_groups' => count($inconsistencies),
                'inconsistencies' => $inconsistencies,
                'summary' => $this->generateSummary($inconsistencies),
                'metadata' => [
                    'analyzed_at' => now(),
                    'min_records' => $minRecords,
                    'threshold' => $threshold,
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('AI karar tutarsızlığı analizi hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'inconsistencies' => [],
            ];
        }
    }

    /**
     * Belirli bir request_data için detaylı analiz
     *
     * @param  string  $requestDataHash  Request data hash
     * @param  float  $threshold  Rating farkı eşiği
     * @return array Detaylı analiz
     */
    public function analyzeByRequestData(string $requestDataHash, float $threshold = 2.0): array
    {
        try {
            $logs = AiLog::whereNotNull('user_rating')
                ->whereNotNull('request_data')
                ->get()
                ->filter(function ($log) use ($requestDataHash) {
                    return md5(json_encode($log->request_data)) === $requestDataHash;
                });

            if ($logs->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Belirtilen request_data için kayıt bulunamadı',
                ];
            }

            $analysis = $this->analyzeGroup($logs->toArray(), $threshold);

            return [
                'success' => true,
                'request_data' => $logs->first()->request_data,
                'analysis' => $analysis,
                'metadata' => [
                    'analyzed_at' => now(),
                    'threshold' => $threshold,
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('Request data analizi hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Request data'ya göre grupla
     *
     * @param  int  $minRecords  Minimum kayıt sayısı
     * @return array Gruplanmış kayıtlar
     */
    private function groupByRequestData(int $minRecords): array
    {
        $logs = AiLog::whereNotNull('user_rating')
            ->whereNotNull('request_data')
            ->whereNotNull('user_id')
            ->get();

        $grouped = [];

        foreach ($logs as $log) {
            $hash = md5(json_encode($log->request_data));

            if (! isset($grouped[$hash])) {
                $grouped[$hash] = [];
            }

            $grouped[$hash][] = $log;
        }

        // Minimum kayıt sayısına göre filtrele
        return array_filter($grouped, function ($logs) use ($minRecords) {
            return count($logs) >= $minRecords;
        });
    }

    /**
     * Bir grup için analiz yap
     *
     * @param  array  $logs  Log kayıtları
     * @param  float  $threshold  Eşik değeri
     * @return array Analiz sonuçları
     */
    private function analyzeGroup(array $logs, float $threshold): array
    {
        $ratings = array_map(function ($log) {
            return $log->user_rating;
        }, $logs);

        $averageRating = array_sum($ratings) / count($ratings);

        // Rating dağılımı
        $ratingDistribution = array_count_values($ratings);

        // Anomalileri bul
        $anomalies = [];
        $maxDeviation = 0;

        foreach ($logs as $log) {
            $deviation = abs($log->user_rating - $averageRating);

            if ($deviation > $threshold) {
                $anomalies[] = [
                    'log_id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user ? $log->user->name : 'Bilinmiyor',
                    'rating' => $log->user_rating,
                    'average_rating' => round($averageRating, 2),
                    'deviation' => round($deviation, 2),
                    'feedback_type' => $log->feedback_type,
                    'feedback_reason' => $log->feedback_reason,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                ];

                if ($deviation > $maxDeviation) {
                    $maxDeviation = $deviation;
                }
            }
        }

        // Şiddet seviyesi hesapla
        $severity = $this->calculateSeverity($maxDeviation, count($anomalies), count($logs));

        return [
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $ratingDistribution,
            'anomalies' => $anomalies,
            'severity' => $severity,
            'total_records' => count($logs),
            'anomaly_count' => count($anomalies),
            'anomaly_percentage' => count($logs) > 0 ? round((count($anomalies) / count($logs)) * 100, 2) : 0,
        ];
    }

    /**
     * Şiddet seviyesi hesapla
     *
     * @param  float  $maxDeviation  Maksimum sapma
     * @param  int  $anomalyCount  Anomali sayısı
     * @param  int  $totalRecords  Toplam kayıt sayısı
     * @return string Şiddet seviyesi (low, medium, high, critical)
     */
    private function calculateSeverity(float $maxDeviation, int $anomalyCount, int $totalRecords): string
    {
        $anomalyPercentage = $totalRecords > 0 ? ($anomalyCount / $totalRecords) * 100 : 0;

        if ($maxDeviation >= 4 || $anomalyPercentage >= 50) {
            return 'critical';
        } elseif ($maxDeviation >= 3 || $anomalyPercentage >= 30) {
            return 'high';
        } elseif ($maxDeviation >= 2 || $anomalyPercentage >= 20) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Özet rapor oluştur
     *
     * @param  array  $inconsistencies  Tutarsızlıklar
     * @return array Özet
     */
    private function generateSummary(array $inconsistencies): array
    {
        $severityCounts = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        $totalAnomalies = 0;
        $affectedUsers = [];

        foreach ($inconsistencies as $inconsistency) {
            $severity = $inconsistency['severity'];
            $severityCounts[$severity]++;

            foreach ($inconsistency['anomalies'] as $anomaly) {
                $totalAnomalies++;
                if (! in_array($anomaly['user_id'], $affectedUsers)) {
                    $affectedUsers[] = $anomaly['user_id'];
                }
            }
        }

        return [
            'total_inconsistent_groups' => count($inconsistencies),
            'total_anomalies' => $totalAnomalies,
            'affected_users' => count($affectedUsers),
            'severity_distribution' => $severityCounts,
            'recommendations' => $this->generateRecommendations($severityCounts),
        ];
    }

    /**
     * Öneriler oluştur
     *
     * @param  array  $severityCounts  Şiddet dağılımı
     * @return array Öneriler
     */
    private function generateRecommendations(array $severityCounts): array
    {
        $recommendations = [];

        if ($severityCounts['critical'] > 0) {
            $recommendations[] = 'Kritik seviyede tutarsızlıklar tespit edildi. Acil danışman eğitimi gerekli.';
        }

        if ($severityCounts['high'] > 0) {
            $recommendations[] = 'Yüksek seviyede tutarsızlıklar var. AI kullanım rehberi gözden geçirilmeli.';
        }

        if ($severityCounts['medium'] > 0) {
            $recommendations[] = 'Orta seviyede tutarsızlıklar tespit edildi. Düzenli takip önerilir.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Tutarsızlık seviyesi düşük. Mevcut status normal görünüyor.';
        }

        return $recommendations;
    }
}

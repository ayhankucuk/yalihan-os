<?php

namespace App\Services\Intelligence;

use App\Models\SystemLearningTransaction;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Auto-Learning Service
 * Context7: Otomatik Öğrenme Sistemi (Auto-Learning System)
 *
 * Her işlemden öğrenen ve kendini geliştiren sistem
 */
class AutoLearningService
{
    private const CACHE_TTL = 3600;
    private const MIN_SAMPLE_COUNT = 5;
    private const CONFIDENCE_THRESHOLD = 70.0;

    /**
     * İşlemi kaydet ve öğren
     *
     * @param string $transactionType
     * @param string $module
     * @param string $action
     * @param array $inputData
     * @param array $outputData
     * @param array $context
     * @param bool $success
     * @param float|null $performanceScore
     * @param int|null $executionTimeMs
     * @return SystemLearningTransaction
     */
    public function learn(
        string $transactionType,
        string $module,
        string $action,
        array $inputData = [],
        array $outputData = [],
        array $context = [],
        bool $success = true,
        ?float $performanceScore = null,
        ?int $executionTimeMs = null
    ): SystemLearningTransaction {
        try {
            $transaction = SystemLearningTransaction::create([
                'transaction_type' => $transactionType,
                'module' => $module,
                'action' => $action,
                'related_type' => $context['related_type'] ?? null,
                'related_id' => $context['related_id'] ?? null,
                'input_data' => $inputData,
                'output_data' => $outputData,
                'context' => $context,
                'success' => $success,
                'performance_score' => $performanceScore,
                'execution_time_ms' => $executionTimeMs,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'executed_at' => now(),
            ]);

            // Pattern'leri asenkron güncelle
            $this->updatePatternsAsync($transaction);

            return $transaction;
        } catch (\Exception $e) {
            LogService::error('Auto-learning failed', [
                'transaction_type' => $transactionType,
                'module' => $module,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Başarılı pattern'leri tespit et
     *
     * @param string $module
     * @param string $action
     * @param int $days
     * @return array
     */
    public function detectSuccessPatterns(string $module, string $action, int $days = 30): array
    {
        $cacheKey = "auto_learning:success_patterns:{$module}:{$action}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($module, $action, $days) {
            $transactions = SystemLearningTransaction::module($module)
                ->type("{$module}_{$action}") // context7-ignore
                ->successful()
                ->lastDays($days)
                ->get();

            if ($transactions->count() < self::MIN_SAMPLE_COUNT) {
                return [
                    'success' => false,
                    'message' => 'Yeterli veri yok',
                    'patterns' => [],
                ];
            }

            $patterns = $this->extractPatterns($transactions);

            return [
                'success' => true,
                'module' => $module,
                'action' => $action,
                'sample_count' => $transactions->count(),
                'patterns' => $patterns,
                'confidence' => $this->calculateConfidence($patterns),
                'analyzed_at' => now(),
            ];
        });
    }

    /**
     * Başarısız işlemleri analiz et
     *
     * @param string $module
     * @param string $action
     * @param int $days
     * @return array
     */
    public function analyzeFailures(string $module, string $action, int $days = 30): array
    {
        $cacheKey = "auto_learning:failure_analysis:{$module}:{$action}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($module, $action, $days) {
            $failures = SystemLearningTransaction::module($module)
                ->type("{$module}_{$action}") // context7-ignore
                ->failed()
                ->lastDays($days)
                ->get();

            if ($failures->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Başarısız işlem bulunamadı',
                    'analysis' => [],
                ];
            }

            $commonErrors = $this->extractCommonErrors($failures);
            $recommendations = $this->generateFailureRecommendations($commonErrors);

            return [
                'success' => true,
                'module' => $module,
                'action' => $action,
                'failure_count' => $failures->count(),
                'common_errors' => $commonErrors,
                'recommendations' => $recommendations,
                'analyzed_at' => now(),
            ];
        });
    }

    /**
     * Sürekli iyileştirme önerileri
     *
     * @param string|null $module
     * @return array
     */
    public function getImprovementSuggestions(?string $module = null): array
    {
        $cacheKey = "auto_learning:improvements:" . ($module ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($module) {
            $query = SystemLearningTransaction::query();

            if ($module) {
                $query->module($module);
            }

            $transactions = $query->lastDays(30)->get();

            $suggestions = [];

            // Performans analizi
            $avgPerformance = $transactions->avg('performance_score');
            if ($avgPerformance && $avgPerformance < 70) {
                $suggestions[] = [
                    'type' => 'performance', // context7-ignore
                    'priority' => 'high',
                    'message' => "Ortalama performans skoru düşük (%{$avgPerformance})",
                    'recommendation' => 'İşlem optimizasyonu gerekli',
                ];
            }

            // Başarı oranı analizi
            $successRate = ($transactions->where('success', true)->count() / $transactions->count()) * 100;
            if ($successRate < 90) {
                $suggestions[] = [
                    'type' => 'reliability', // context7-ignore
                    'priority' => 'high',
                    'message' => "Başarı oranı düşük (%{$successRate})",
                    'recommendation' => 'Hata yönetimi iyileştirmesi gerekli',
                ];
            }

            // Yavaş işlemler
            $slowTransactions = $transactions->where('execution_time_ms', '>', 5000)->count();
            if ($slowTransactions > 0) {
                $suggestions[] = [
                    'type' => 'speed', // context7-ignore
                    'priority' => 'medium',
                    'message' => "{$slowTransactions} yavaş işlem tespit edildi (>5 saniye)",
                    'recommendation' => 'Performans optimizasyonu gerekli',
                ];
            }

            return [
                'success' => true,
                'module' => $module ?? 'all',
                'suggestions' => $suggestions,
                'analyzed_at' => now(),
            ];
        });
    }

    /**
     * Pattern'leri asenkron güncelle
     */
    private function updatePatternsAsync(SystemLearningTransaction $transaction): void
    {
        // Queue job olarak çalıştırılabilir
        // Şimdilik sync çalışıyor
        $this->updatePatterns($transaction);
    }

    /**
     * Pattern'leri güncelle
     */
    private function updatePatterns(SystemLearningTransaction $transaction): void
    {
        if (!$transaction->success) {
            return;
        }

        $cacheKey = "auto_learning:pattern_update:{$transaction->module}:{$transaction->action}";

        if (Cache::has($cacheKey)) {
            return;
        }

        // Pattern güncelleme işlemleri burada yapılır
        Cache::put($cacheKey, true, 3600);
    }

    /**
     * Pattern'leri çıkar
     */
    private function extractPatterns($transactions): array
    {
        $patterns = [];

        // Ortalama performans
        $avgPerformance = $transactions->avg('performance_score');
        if ($avgPerformance) {
            $patterns['avg_performance'] = round($avgPerformance, 2);
        }

        // Ortalama süre
        $avgTime = $transactions->avg('execution_time_ms');
        if ($avgTime) {
            $patterns['avg_execution_time_ms'] = round($avgTime, 2);
        }

        // Başarı faktörleri
        $successFactors = $this->identifySuccessFactors($transactions);
        if (!empty($successFactors)) {
            $patterns['success_factors'] = $successFactors;
        }

        return $patterns;
    }

    /**
     * Başarı faktörlerini tespit et
     */
    private function identifySuccessFactors($transactions): array
    {
        $factors = [];

        // Yüksek performanslı işlemlerin ortak özellikleri
        $highPerf = $transactions->where('performance_score', '>=', 80);

        if ($highPerf->isNotEmpty()) {
            $factors['high_performance_count'] = $highPerf->count();
            $factors['high_performance_ratio'] = round(($highPerf->count() / $transactions->count()) * 100, 2);
        }

        return $factors;
    }

    /**
     * Güven seviyesi hesapla
     */
    private function calculateConfidence(array $patterns): float
    {
        if (empty($patterns)) {
            return 0.0;
        }

        $confidence = 50.0; // Base confidence

        if (isset($patterns['avg_performance'])) {
            $confidence += ($patterns['avg_performance'] / 2);
        }

        return min($confidence, 100.0);
    }

    /**
     * Ortak hataları çıkar
     */
    private function extractCommonErrors($failures): array
    {
        $errors = [];

        foreach ($failures as $failure) {
            $errorMsg = $failure->context['error'] ?? 'Bilinmeyen hata';
            $errors[$errorMsg] = ($errors[$errorMsg] ?? 0) + 1;
        }

        arsort($errors);

        return array_slice($errors, 0, 5, true);
    }

    /**
     * Hata önerileri oluştur
     */
    private function generateFailureRecommendations(array $commonErrors): array
    {
        $recommendations = [];

        foreach ($commonErrors as $error => $count) {
            $recommendations[] = [
                'error' => $error,
                'count' => $count,
                'recommendation' => $this->getErrorRecommendation($error),
            ];
        }

        return $recommendations;
    }

    /**
     * Hata önerisi getir
     */
    private function getErrorRecommendation(string $error): string
    {
        if (str_contains($error, 'timeout')) {
            return 'Timeout süresini artır veya işlemi optimize et';
        }

        if (str_contains($error, 'validation')) {
            return 'Validasyon kurallarını gözden geçir';
        }

        if (str_contains($error, 'database')) {
            return 'Veritabanı bağlantısını kontrol et';
        }

        return 'Hata loglarını incele ve düzelt';
    }

    /**
     * Cache temizleme
     */
    public function clearCache(?string $module = null): void
    {
        if ($module) {
            Cache::forget("auto_learning:success_patterns:{$module}:*");
            Cache::forget("auto_learning:failure_analysis:{$module}:*");
            Cache::forget("auto_learning:improvements:{$module}");
        } else {
            Cache::forget("auto_learning:*");
        }
    }
}

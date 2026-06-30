<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VelocityAnalyzer
{
    /**
     * Analyze AI learning effectiveness
     */
    public function analyzeAILearningEffectiveness(): array
    {
        try {
            $sessions = DB::table('ai_learning_sessions')
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            $totalSessions = $sessions->count();
            $successfulSessions = $sessions->where('success', true)->count();
            $avgConfidenceScore = $sessions->avg('confidence_score') ?? 0;

            $learningTopics = $sessions->groupBy('learned_topic')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'avg_confidence' => $group->avg('confidence_score'),
                        'success_rate' => $group->where('success', true)->count() / $group->count() * 100,
                    ];
                });

            return [
                'total_sessions' => $totalSessions,
                'success_rate' => $totalSessions > 0 ? ($successfulSessions / $totalSessions * 100) : 0,
                'avg_confidence' => round($avgConfidenceScore, 2),
                'learning_topics' => $learningTopics->toArray(),
                'effectiveness_score' => $this->calculateEffectivenessScore($sessions),
            ];

        } catch (\Exception $e) {
            Log::error('AI Learning Analysis Error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Analyze developer productivity patterns
     */
    public function analyzeProductivityPatterns(): array
    {
        try {
            // Get velocity metrics from last 30 days
            $velocityMetrics = DB::table('development_velocity_metrics')
                ->where('period_start', '>=', now()->subDays(30))
                ->get();

            if ($velocityMetrics->isEmpty()) {
                return [
                    'trend' => 'no_data',
                    'avg_productivity' => 0,
                    'peak_days' => [],
                    'improvement_suggestions' => ['Başlamak için geliştirme metriklerini toplamaya başlayın'],
                ];
            }

            // Calculate trends
            $avgProductivity = $velocityMetrics->avg('code_quality_score');
            $trendData = $this->calculateProductivityTrend($velocityMetrics);
            $peakDays = $this->identifyPeakPerformanceDays($velocityMetrics);

            return [
                'trend' => $trendData['direction'],
                'trend_percentage' => $trendData['percentage'],
                'avg_productivity' => round($avgProductivity, 2),
                'peak_days' => $peakDays,
                'total_commits' => $velocityMetrics->sum('commits_count'),
                'avg_quality_score' => round($velocityMetrics->avg('code_quality_score'), 2),
                'improvement_suggestions' => $this->generateImprovementSuggestions($velocityMetrics),
            ];

        } catch (\Exception $e) {
            Log::error('Productivity Pattern Analysis Error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Analyze Context7 compliance trends
     */
    public function analyzeComplianceTrends(): array
    {
        try {
            $violations = DB::table('context7_compliance_logs')
                ->where('detected_at', '>=', now()->subDays(30))
                ->get();

            $totalViolations = $violations->count();
            $autoFixed = $violations->where('auto_fixed', true)->count();
            $autoFixRate = $totalViolations > 0 ? ($autoFixed / $totalViolations * 100) : 100;

            // Group by violation type
            $violationTypes = $violations->groupBy('violation_type')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'auto_fix_rate' => $group->where('auto_fixed', true)->count() / $group->count() * 100,
                        'severity_avg' => $group->avg('severity_level') ?? 1,
                    ];
                })->toArray();

            // Daily violation trend
            $dailyTrend = $violations->groupBy(function ($item) {
                return Carbon::parse($item->detected_at)->format('Y-m-d');
            })->map->count()->toArray();

            return [
                'total_violations' => $totalViolations,
                'auto_fix_rate' => round($autoFixRate, 2),
                'violation_types' => $violationTypes,
                'daily_trend' => $dailyTrend,
                'compliance_score' => $this->calculateComplianceScore($violations),
                'improvement_trend' => $this->analyzeImprovementTrend($dailyTrend),
            ];

        } catch (\Exception $e) {
            Log::error('Compliance Trend Analysis Error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Generate comprehensive velocity insights
     */
    public function generateVelocityInsights(): array
    {
        $aiEffectiveness = $this->analyzeAILearningEffectiveness();
        $productivityPatterns = $this->analyzeProductivityPatterns();
        $complianceTrends = $this->analyzeComplianceTrends();

        return [
            'ai_learning' => $aiEffectiveness,
            'productivity' => $productivityPatterns,
            'compliance' => $complianceTrends,
            'overall_health_score' => $this->calculateOverallHealthScore(
                $aiEffectiveness,
                $productivityPatterns,
                $complianceTrends
            ),
            'recommendations' => $this->generateActionableRecommendations(
                $aiEffectiveness,
                $productivityPatterns,
                $complianceTrends
            ),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Calculate effectiveness score for AI learning
     */
    private function calculateEffectivenessScore($sessions): float
    {
        if ($sessions->isEmpty()) {
            return 0;
        }

        $successRate = $sessions->where('success', true)->count() / $sessions->count();
        $avgConfidence = $sessions->avg('confidence_score') ?? 0;
        $consistencyBonus = $this->calculateConsistencyBonus($sessions);

        $score = ($successRate * 40) + ($avgConfidence / 100 * 40) + ($consistencyBonus * 20);

        return round(min(100, max(0, $score)), 2);
    }

    /**
     * Calculate productivity trend
     */
    private function calculateProductivityTrend($metrics): array
    {
        $orderedMetrics = $metrics->sortBy('period_start');

        if ($orderedMetrics->count() < 2) {
            return ['direction' => 'stable', 'percentage' => 0];
        }

        $firstHalf = $orderedMetrics->take(ceil($orderedMetrics->count() / 2));
        $secondHalf = $orderedMetrics->skip(floor($orderedMetrics->count() / 2));

        $firstAvg = $firstHalf->avg('code_quality_score');
        $secondAvg = $secondHalf->avg('code_quality_score');

        if ($secondAvg > $firstAvg) {
            $percentage = (($secondAvg - $firstAvg) / $firstAvg) * 100;

            return ['direction' => 'improving', 'percentage' => round($percentage, 2)];
        } elseif ($secondAvg < $firstAvg) {
            $percentage = (($firstAvg - $secondAvg) / $firstAvg) * 100;

            return ['direction' => 'declining', 'percentage' => round($percentage, 2)];
        }

        return ['direction' => 'stable', 'percentage' => 0];
    }

    /**
     * Identify peak performance days
     */
    private function identifyPeakPerformanceDays($metrics): array
    {
        $avgQuality = $metrics->avg('code_quality_score');
        $threshold = $avgQuality * 1.2; // 20% above average

        return $metrics
            ->where('code_quality_score', '>', $threshold)
            ->sortByDesc('code_quality_score')
            ->take(5)
            ->map(function ($metric) {
                return [
                    'date' => $metric->period_start,
                    'quality_score' => $metric->code_quality_score,
                    'commits' => $metric->commits_count,
                    'violations' => $metric->context7_violations,
                ];
            })->values()->toArray();
    }

    /**
     * Generate improvement suggestions
     */
    private function generateImprovementSuggestions($metrics): array
    {
        $suggestions = [];

        $avgQuality = $metrics->avg('code_quality_score');
        $avgViolations = $metrics->avg('context7_violations');
        $avgCommits = $metrics->avg('commits_count');

        if ($avgQuality < 75) {
            $suggestions[] = "Code quality score ortalaması düşük ({$avgQuality}%). Context7 kurallarına daha dikkat edin.";
        }

        if ($avgViolations > 5) {
            $suggestions[] = "Context7 ihlalleri fazla (ortalama {$avgViolations}). Pre-commit hook'ları aktifleştirin.";
        }

        if ($avgCommits < 3) {
            $suggestions[] = "Commit sıklığı düşük (ortalama {$avgCommits}). Daha küçük ve sık commit'ler yapın.";
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Harika iş çıkarıyorsunuz! Mevcut kaliteyi korumaya devam edin.';
        }

        return $suggestions;
    }

    /**
     * Calculate compliance score
     */
    private function calculateComplianceScore($violations): float
    {
        $totalViolations = $violations->count();
        if ($totalViolations === 0) {
            return 100;
        }

        $autoFixed = $violations->where('auto_fixed', true)->count();
        $highSeverity = $violations->where('severity_level', '>=', 3)->count();

        $baseScore = 100;
        $baseScore -= min(50, $totalViolations * 2); // Max 50 points for violations
        $baseScore -= min(25, $highSeverity * 5); // Extra penalty for high severity
        $baseScore += min(25, $autoFixed * 3); // Bonus for auto-fixes

        return round(max(0, $baseScore), 2);
    }

    /**
     * Analyze improvement trend
     */
    private function analyzeImprovementTrend($dailyTrend): string
    {
        if (count($dailyTrend) < 7) {
            return 'insufficient_data';
        }

        $recent = array_slice($dailyTrend, -7, 7, true); // Last 7 days
        $earlier = array_slice($dailyTrend, -14, 7, true); // 7 days before

        $recentAvg = array_sum($recent) / count($recent);
        $earlierAvg = array_sum($earlier) / count($earlier);

        if ($recentAvg < $earlierAvg * 0.8) {
            return 'improving'; // Fewer violations = improvement
        } elseif ($recentAvg > $earlierAvg * 1.2) {
            return 'declining'; // More violations = decline
        }

        return 'stable';
    }

    /**
     * Calculate consistency bonus for AI learning
     */
    private function calculateConsistencyBonus($sessions): float
    {
        $dailySessions = $sessions->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('Y-m-d');
        });

        $activeDays = $dailySessions->count();
        $totalPossibleDays = now()->diffInDays($sessions->min('created_at')) + 1;

        $consistencyRate = $activeDays / $totalPossibleDays;

        return $consistencyRate * 100;
    }

    /**
     * Calculate overall health score
     */
    private function calculateOverallHealthScore($ai, $productivity, $compliance): float
    {
        $scores = [];

        if (! empty($ai) && isset($ai['effectiveness_score'])) {
            $scores[] = $ai['effectiveness_score'] * 0.3; // 30% weight
        }

        if (! empty($productivity) && isset($productivity['avg_productivity'])) {
            $scores[] = $productivity['avg_productivity'] * 0.4; // 40% weight
        }

        if (! empty($compliance) && isset($compliance['compliance_score'])) {
            $scores[] = $compliance['compliance_score'] * 0.3; // 30% weight
        }

        return count($scores) > 0 ? round(array_sum($scores), 2) : 0;
    }

    /**
     * Generate actionable recommendations
     */
    private function generateActionableRecommendations($ai, $productivity, $compliance): array
    {
        $recommendations = [];

        // AI Learning recommendations
        if (! empty($ai) && $ai['effectiveness_score'] < 70) {
            $recommendations[] = [
                'category' => 'AI Learning',
                'priority' => 'high',
                'action' => 'AI öğrenme oturumlarının kalitesini artırın',
                'details' => "Mevcut etkinlik skoru: {$ai['effectiveness_score']}%. Daha detaylı feedback ve örnekler ekleyin.",
            ];
        }

        // Productivity recommendations
        if (! empty($productivity) && isset($productivity['trend']) && $productivity['trend'] === 'declining') {
            $recommendations[] = [
                'category' => 'Productivity',
                'priority' => 'medium',
                'action' => 'Üretkenlik trendini iyileştirin',
                'details' => "Trend: {$productivity['trend']} ({$productivity['trend_percentage']}%). Küçük ve sık commit'ler yapın.",
            ];
        }

        // Compliance recommendations
        if (! empty($compliance) && $compliance['auto_fix_rate'] < 80) {
            $recommendations[] = [
                'category' => 'Code Quality',
                'priority' => 'high',
                'action' => 'Context7 auto-fix oranını artırın',
                'details' => "Mevcut oran: {$compliance['auto_fix_rate']}%. Pre-commit hook'ları ve IDE entegrasyonunu aktifleştirin.",
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'category' => 'General',
                'priority' => 'low',
                'action' => 'Mevcut performansı koruyun',
                'details' => 'Tüm metrikler iyi statusda. Bu kaliteyi sürdürmeye devam edin!',
            ];
        }

        return $recommendations;
    }
}

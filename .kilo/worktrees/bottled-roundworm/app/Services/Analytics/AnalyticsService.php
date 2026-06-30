<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsMetric;
use App\Models\SabGovernanceLog;
use App\Models\ProjectHealthSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ Analytics Service (SAB Version)
 *
 * Tracks project health, architectural compliance, and system metrics.
 */
class AnalyticsService
{
    /**
     * Record a new analytics metric
     */
    public function recordMetric(
        string $type,
        string $name,
        $data,
        ?float $value = null,
        string $source = 'system',
        string $severity = 'info'
    ): AnalyticsMetric {
        return AnalyticsMetric::create([
            'metric_type' => $type,
            'metric_name' => $name,
            'metric_data' => is_array($data) ? $data : ['value' => $data],
            'metric_value' => $value,
            'source' => $source,
            'severity' => $severity,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Log a SAB governance violation
     */
    public function logViolation(
        string $type,
        string $description,
        ?string $filePath = null,
        ?int $lineNumber = null,
        array $context = [],
        bool $autoFixed = false,
        ?string $fixDescription = null,
        string $severity = 'warning',
        string $source = 'sab_enforcement'
    ): SabGovernanceLog {
        return SabGovernanceLog::create([
            'violation_type' => $type,
            'violation_description' => $description,
            'file_path' => $filePath,
            'line_number' => $lineNumber,
            'violation_context' => $context,
            'auto_fixed' => $autoFixed,
            'fix_description' => $fixDescription,
            'severity' => $severity,
            'source' => $source,
            'detected_at' => now(),
            'fixed_at' => $autoFixed ? now() : null,
        ]);
    }

    /**
     * Calculate and store project health snapshot
     */
    public function calculateProjectHealth(): ProjectHealthSnapshot
    {
        $healthData = $this->gatherHealthMetrics();

        return ProjectHealthSnapshot::create([
            'overall_health_score' => $healthData['overall_score'],
            'context7_compliance_score' => $healthData['sab_score'], // DB column mapping
            'code_quality_score' => $healthData['quality_score'],
            'test_coverage_score' => $healthData['coverage_score'],
            'performance_score' => $healthData['performance_score'],
            'active_violations' => $healthData['active_violations'], // context7-ignore
            'critical_issues' => $healthData['critical_issues'],
            'total_files' => $healthData['total_files'],
            'total_lines' => $healthData['total_lines'],
            'health_details' => $healthData['details'],
            'recommendations' => $healthData['recommendations'],
            'snapshot_at' => now(),
        ]);
    }

    /**
     * Get SAB governance summary
     */
    public function getGovernanceSummary(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $violations = SabGovernanceLog::where('detected_at', '>=', $startDate)->get();

        return [
            'total_violations' => $violations->count(),
            'auto_fixed' => $violations->where('auto_fixed', true)->count(),
            'critical_violations' => $violations->where('severity', 'critical')->count(),
            'violations_by_type' => $violations->groupBy('violation_type')->map->count(),
            'violations_by_severity' => $violations->groupBy('severity')->map->count(),
            'average_fix_time' => $violations->whereNotNull('fixed_at')->avg(function ($item) {
                return $item->detected_at->diffInMinutes($item->fixed_at);
            }),
            'fix_rate' => $violations->count() > 0
                ? ($violations->where('is_fixed', true)->count() / $violations->count() * 100)
                : 100,
        ];
    }

    /**
     * Get development velocity metrics
     */
    public function getVelocityMetrics(int $days = 7): array
    {
        $metrics = AnalyticsMetric::byType('git_activity')
            ->where('recorded_at', '>=', now()->subDays($days))
            ->get();

        $commits = $metrics->where('metric_name', 'commit_count')->sum('metric_value');
        $filesChanged = $metrics->where('metric_name', 'files_changed')->sum('metric_value');
        $linesAdded = $metrics->where('metric_name', 'lines_added')->sum('metric_value');
        $linesDeleted = $metrics->where('metric_name', 'lines_deleted')->sum('metric_value');

        return [
            'commits_count' => $commits,
            'files_changed' => $filesChanged,
            'lines_added' => $linesAdded,
            'lines_deleted' => $linesDeleted,
            'net_lines' => $linesAdded - $linesDeleted,
            'average_commit_size' => $commits > 0 ? $filesChanged / $commits : 0,
            'productivity_score' => $this->calculateProductivityScore($commits, $filesChanged, $linesAdded),
        ];
    }

    /**
     * Get real-time dashboard data
     */
    public function getDashboardData(): array
    {
        $latestHealth = ProjectHealthSnapshot::latest('snapshot_at')->first();
        $recentMetrics = AnalyticsMetric::recent(24)->get();
        $activeViolations = SabGovernanceLog::unfixed()->count();

        return [
            'health' => [
                'overall_score' => $latestHealth?->overall_health_score ?? 0,
                'sab_score' => $latestHealth?->context7_compliance_score ?? 0, // Mapping
                'saglik_durumu' => $latestHealth?->health_status ?? 'unknown',
                'trend' => $latestHealth?->health_trend ?? 'stable',
            ],
            'violations' => [
                'active_count' => $activeViolations, // context7-ignore
                'today' => SabGovernanceLog::whereDate('detected_at', today())->count(),
                'auto_fixed_today' => SabGovernanceLog::whereDate('detected_at', today())->where('auto_fixed', true)->count(),
            ],
            'activity' => [
                'commits_today' => $recentMetrics->where('metric_name', 'commit_count')->sum('metric_value'),
                'builds_today' => $recentMetrics->where('metric_name', 'build_count')->sum('metric_value'),
                'tests_run' => $recentMetrics->where('metric_name', 'test_runs')->sum('metric_value'),
            ],
            'ai_learning' => [
                'sessions_today' => $recentMetrics->where('metric_type', 'ai_learning')->count(),
                'patterns_learned' => $recentMetrics->where('metric_name', 'patterns_learned')->sum('metric_value'),
                'ideas_generated' => $recentMetrics->where('metric_name', 'ideas_generated')->sum('metric_value'),
            ],
            // 'velocity_insights' => (new VelocityAnalyzer)->generateVelocityInsights(), // Removed or update if exists
        ];
    }

    /**
     * Gather health metrics from various sources
     */
    private function gatherHealthMetrics(): array
    {
        // SAB governance score
        $recentViolations = SabGovernanceLog::recent(168)->count(); // 7 days
        $sabScore = max(0, 100 - ($recentViolations * 2)); // 2 points per violation

        // Code quality score
        $qualityMetrics = AnalyticsMetric::byType('code_quality')->recent(168)->avg('metric_value');
        $qualityScore = $qualityMetrics ?? 75;

        // Test coverage score
        $coverageMetrics = AnalyticsMetric::where('metric_name', 'test_coverage')->recent(24)->latest()->first();
        $coverageScore = $coverageMetrics?->metric_value ?? null;

        // Performance score
        $performanceMetrics = AnalyticsMetric::byType('performance')->recent(24)->avg('metric_value');
        $performanceScore = $performanceMetrics ?? null;

        // Calculate overall score
        $scores = array_filter([$sabScore, $qualityScore, $coverageScore, $performanceScore]);
        $overallScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        // Active violations and critical issues
        $activeViolations = SabGovernanceLog::unfixed()->count();
        $criticalIssues = SabGovernanceLog::unfixed()->critical()->count();

        // File metrics
        $totalFiles = AnalyticsMetric::where('metric_name', 'total_files')->latest()->first()?->metric_value ?? 0;
        $totalLines = AnalyticsMetric::where('metric_name', 'total_lines')->latest()->first()?->metric_value ?? 0;

        return [
            'overall_score' => round((float)$overallScore, 2),
            'sab_score' => round((float)$sabScore, 2),
            'quality_score' => round((float)$qualityScore, 2),
            'coverage_score' => $coverageScore ? round((float)$coverageScore, 2) : null,
            'performance_score' => $performanceScore ? round((float)$performanceScore, 2) : null,
            'active_violations' => $activeViolations, // context7-ignore
            'critical_issues' => $criticalIssues,
            'total_files' => $totalFiles,
            'total_lines' => $totalLines,
            'details' => [
                'sab_violations_7d' => $recentViolations,
                'auto_fix_rate' => $this->getAutoFixRate(),
                'avg_fix_time' => $this->getAverageFixTime(),
            ],
            'recommendations' => $this->generateRecommendations($sabScore, $qualityScore, $activeViolations),
        ];
    }

    private function calculateProductivityScore(float $commits, float $files, float $lines): float
    {
        if ($commits == 0) return 0;
        $commitScore = min(100, $commits * 10);
        $fileScore = min(100, ($files / $commits) * 20);
        $lineScore = min(100, ($lines / $commits) / 10);
        return $commitScore * 0.4 + $fileScore * 0.3 + $lineScore * 0.3;
    }

    private function getAutoFixRate(): float
    {
        $recent = SabGovernanceLog::recent(168);
        $total = $recent->count();
        $fixed = $recent->where('auto_fixed', true)->count();
        return $total > 0 ? ($fixed / $total * 100) : 0;
    }

    private function getAverageFixTime(): float
    {
        return SabGovernanceLog::recent(168)
            ->whereNotNull('fixed_at')
            ->get()
            ->avg(function ($log) {
                return $log->detected_at?->diffInMinutes($log->fixed_at);
            }) ?? 0;
    }

    private function generateRecommendations(float $sabScore, float $qualityScore, int $activeViolations): array
    {
        $recommendations = [];
        if ($sabScore < 80) {
            $recommendations[] = [
                'type' => 'sab_governance', // context7-ignore
                'priority' => 'high',
                'message' => 'SAB Governance uyumluluğu %80\'in altında.',
                'action' => 'Mimari ihlalleri gözden geçirin.',
            ];
        }
        return $recommendations;
    }
}

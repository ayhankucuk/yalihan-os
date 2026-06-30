<?php

namespace App\Console\Commands;

use App\Models\DevelopmentVelocityMetric;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateVelocityMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:calculate-velocity
                           {--developer= : Specific developer to calculate for}
                           {--days=7 : Number of days to analyze}
                           {--save : Save results to database}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate development velocity metrics for Context7 Analytics';

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $developer = $this->option('developer');
        $save = $this->option('save');

        $this->info("📊 Calculating development velocity metrics for last {$days} days...");

        try {
            $startDate = now()->subDays($days);
            $endDate = now();

            // Get git activity metrics
            $gitMetrics = DB::table('analytics_metrics')
                ->where('metric_type', 'git_activity')
                ->where('recorded_at', '>=', $startDate)
                ->get();

            // Calculate aggregate metrics
            $totalCommits = $gitMetrics->where('metric_name', 'commit_count')->sum('metric_value');
            $totalFiles = $gitMetrics->where('metric_name', 'files_changed')->sum('metric_value');
            $totalLinesAdded = $gitMetrics->where('metric_name', 'lines_added')->sum('metric_value');
            $totalLinesDeleted = $gitMetrics->where('metric_name', 'lines_deleted')->sum('metric_value');
            $netLines = $totalLinesAdded - $totalLinesDeleted;

            // Get Context7 violations
            $violations = DB::table('context7_compliance_logs')
                ->where('detected_at', '>=', $startDate)
                ->count();

            $autoFixes = DB::table('context7_compliance_logs')
                ->where('detected_at', '>=', $startDate)
                ->where('auto_fixed', true)
                ->count();

            // Calculate productivity score
            $productivityScore = $this->calculateProductivityScore(
                $totalCommits,
                $totalFiles,
                $totalLinesAdded,
                $violations,
                $days
            );

            // Calculate code quality score
            $codeQualityScore = $this->calculateCodeQualityScore(
                $violations,
                $autoFixes,
                $totalCommits
            );

            $metrics = [
                'commits_count' => $totalCommits,
                'files_changed' => $totalFiles,
                'lines_added' => $totalLinesAdded,
                'lines_deleted' => $totalLinesDeleted,
                'net_lines' => $netLines,
                'context7_violations' => $violations,
                'auto_fixes_applied' => $autoFixes,
                'productivity_score' => $productivityScore,
                'code_quality_score' => $codeQualityScore,
                'period_days' => $days,
            ];

            // Display results
            $this->displayMetrics($metrics);

            // Save to database if requested
            if ($save) {
                $this->saveVelocityMetrics($metrics, $startDate, $endDate, $developer);
                $this->info('💾 Metrics saved to database');
            }

            // Record analytics metrics
            foreach ($metrics as $key => $value) {
                $this->analyticsService->recordMetric(
                    'dev_velocity',
                    $key,
                    $value,
                    is_numeric($value) ? (float) $value : null,
                    'velocity_calculator'
                );
            }

            $this->info('✅ Velocity metrics calculated successfully!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error calculating velocity metrics: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function calculateProductivityScore($commits, $files, $lines, $violations, $days): float
    {
        if ($commits == 0) {
            return 0;
        }

        // Base metrics (normalized per day)
        $commitsPerDay = $commits / $days;
        $filesPerCommit = $commits > 0 ? $files / $commits : 0;
        $linesPerCommit = $commits > 0 ? $lines / $commits : 0;

        // Productivity factors
        $commitScore = min(100, $commitsPerDay * 20); // Ideal: 5 commits/day
        $fileScore = min(100, $filesPerCommit * 10); // Ideal: 10 files/commit
        $lineScore = min(100, $linesPerCommit / 5); // Ideal: 500 lines/commit

        // Quality penalty
        $qualityPenalty = ($violations / max(1, $commits)) * 10;

        $score = ($commitScore * 0.3 + $fileScore * 0.3 + $lineScore * 0.4) - $qualityPenalty;

        return max(0, min(100, $score));
    }

    private function calculateCodeQualityScore($violations, $autoFixes, $commits): float
    {
        if ($commits == 0) {
            return 100;
        }

        $violationRate = $violations / $commits;
        $autoFixRate = $violations > 0 ? $autoFixes / $violations : 1;

        // Base score starts at 100
        $score = 100;

        // Penalty for violations (max 50 points)
        $score -= min(50, $violationRate * 100);

        // Bonus for auto-fixes (up to 10 points back)
        $score += $autoFixRate * 10;

        return max(0, min(100, $score));
    }

    private function displayMetrics(array $metrics): void
    {
        $this->info("\n📈 Development Velocity Metrics:");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->line('Git Activity:');
        $this->line("  • Commits: {$metrics['commits_count']}");
        $this->line("  • Files Changed: {$metrics['files_changed']}");
        $this->line("  • Lines Added: +{$metrics['lines_added']}");
        $this->line("  • Lines Deleted: -{$metrics['lines_deleted']}");
        $this->line("  • Net Lines: {$metrics['net_lines']}");

        $this->line("\nCode Quality:");
        $this->line("  • Context7 Violations: {$metrics['context7_violations']}");
        $this->line("  • Auto-fixes Applied: {$metrics['auto_fixes_applied']}");
        $this->line("  • Code Quality Score: {$metrics['code_quality_score']}%");

        $this->line("\nProductivity:");
        $this->line("  • Productivity Score: {$metrics['productivity_score']}%");
        $this->line("  • Analysis Period: {$metrics['period_days']} days");

        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
    }

    private function saveVelocityMetrics(array $metrics, $startDate, $endDate, $developer = null): void
    {
        DevelopmentVelocityMetric::create([
            'developer_name' => $developer,
            'branch_name' => null, // Could be enhanced to track branch
            'commits_count' => $metrics['commits_count'],
            'files_changed' => $metrics['files_changed'],
            'lines_added' => $metrics['lines_added'],
            'lines_deleted' => $metrics['lines_deleted'],
            'code_quality_score' => $metrics['code_quality_score'],
            'context7_violations' => $metrics['context7_violations'],
            'auto_fixes_applied' => $metrics['auto_fixes_applied'],
            'test_coverage' => null, // Could be enhanced
            'feature_tags' => null, // Could be enhanced
            'period_start' => $startDate,
            'period_end' => $endDate,
        ]);
    }
}

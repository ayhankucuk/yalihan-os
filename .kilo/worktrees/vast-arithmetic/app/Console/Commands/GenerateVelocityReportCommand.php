<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\VelocityAnalyzer;
use Illuminate\Console\Command;

class GenerateVelocityReportCommand extends Command
{
    protected $signature = 'analytics:velocity-report
                           {--format=table : Output format (table, json)}
                           {--days=30 : Number of days to analyze}';

    protected $description = 'Generate comprehensive development velocity report';

    protected $velocityAnalyzer;

    protected $analyticsService;

    public function __construct(VelocityAnalyzer $velocityAnalyzer, AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->velocityAnalyzer = $velocityAnalyzer;
        $this->analyticsService = $analyticsService;
    }

    public function handle(): int
    {
        $format = $this->option('format');
        $days = (int) $this->option('days');

        $this->info("🚀 Generating {$days}-day velocity report...");

        try {
            $insights = $this->velocityAnalyzer->generateVelocityInsights();
            $healthSnapshot = $this->analyticsService->calculateProjectHealth();

            $healthData = [
                'overall_score' => $healthSnapshot['overall_score'] ?? 0,
                'context7_score' => $healthSnapshot['context7_score'] ?? 0,
                'quality_score' => $healthSnapshot['quality_score'] ?? 0,
                'active_violations' => $healthSnapshot['active_violations'] ?? 0,
            ];

            if ($format === 'json') {
                $this->displayJsonReport($insights, $healthData);
            } else {
                $this->displayTableReport($insights, $healthData);
            }

            $this->info('✅ Velocity report generated successfully!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error generating velocity report: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function displayTableReport(array $insights, array $healthData): void
    {
        $this->info("\n📊 DEVELOPMENT VELOCITY REPORT");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Project Health Summary
        $this->info("\n🏥 PROJECT HEALTH");
        $this->table(
            ['Metric', 'Score', 'Status'],
            [
                ['Overall Health', $healthData['overall_score'].'%', $this->getHealthStatus($healthData['overall_score'])],
                ['Context7 Score', $healthData['context7_score'].'%', $this->getHealthStatus($healthData['context7_score'])],
                ['Code Quality', $healthData['quality_score'].'%', $this->getHealthStatus($healthData['quality_score'])],
                ['Active Violations', $healthData['active_violations'], $healthData['active_violations'] > 10 ? '⚠️ High' : '✅ Good'],
            ]
        );

        // Overall Health Score
        if (isset($insights['overall_health_score'])) {
            $this->info("\n⭐ OVERALL HEALTH SCORE: ".$insights['overall_health_score'].'%');
        }

        // AI Learning Analysis
        if (! empty($insights['ai_learning'])) {
            $ai = $insights['ai_learning'];
            $this->info("\n🤖 AI LEARNING EFFECTIVENESS");

            $aiData = [
                ['Total Sessions', $ai['total_sessions'] ?? 0, ''],
                ['Success Rate', round($ai['success_rate'] ?? 0, 1).'%', $this->getSuccessStatus($ai['success_rate'] ?? 0)],
                ['Avg Confidence', round($ai['avg_confidence'] ?? 0, 1).'%', $this->getConfidenceStatus($ai['avg_confidence'] ?? 0)],
                ['Effectiveness', round($ai['effectiveness_score'] ?? 0, 1).'%', $this->getHealthStatus($ai['effectiveness_score'] ?? 0)],
            ];

            $this->table(['Metric', 'Value', 'Status'], $aiData);

            if (! empty($ai['learning_topics'])) {
                $this->info("\n📚 LEARNING TOPICS:");
                foreach ($ai['learning_topics'] as $topic => $data) {
                    $success = round($data['success_rate'], 1);
                    $this->line("  • {$topic}: {$data['count']} sessions (Success: {$success}%)");
                }
            }
        }

        // Productivity Analysis
        if (! empty($insights['productivity'])) {
            $prod = $insights['productivity'];
            $this->info("\n📈 PRODUCTIVITY PATTERNS");

            $prodData = [
                ['Avg Productivity', round($prod['avg_productivity'] ?? 0, 1).'%', ''],
                ['Total Commits', $prod['total_commits'] ?? 0, ''],
                ['Quality Score', round($prod['avg_quality_score'] ?? 0, 1).'%', ''],
                ['Trend', $prod['trend'] ?? 'stable', $this->getTrendIcon($prod['trend'] ?? 'stable')],
            ];

            $this->table(['Metric', 'Value', 'Trend'], $prodData);

            if (! empty($prod['peak_days'])) {
                $this->info("\n🌟 PEAK PERFORMANCE DAYS:");
                foreach (\array_slice($prod['peak_days'], 0, 3) as $day) {
                    $quality = round($day['quality_score'], 1);
                    $this->line("  • {$day['date']}: Quality {$quality}%, {$day['commits']} commits");
                }
            }

            if (! empty($prod['improvement_suggestions'])) {
                $this->info("\n💡 PRODUCTIVITY SUGGESTIONS:");
                foreach (\array_slice($prod['improvement_suggestions'], 0, 3) as $suggestion) {
                    $this->line("  • {$suggestion}");
                }
            }
        }

        // Compliance Analysis
        if (! empty($insights['compliance'])) {
            $comp = $insights['compliance'];
            $this->info("\n🔍 COMPLIANCE TRENDS");

            $compData = [
                ['Total Violations', $comp['total_violations'] ?? 0, ''],
                ['Auto-fix Rate', round($comp['auto_fix_rate'] ?? 0, 1).'%', $this->getAutoFixStatus($comp['auto_fix_rate'] ?? 0)],
                ['Compliance Score', round($comp['compliance_score'] ?? 0, 1).'%', $this->getHealthStatus($comp['compliance_score'] ?? 0)],
                ['Trend', $comp['improvement_trend'] ?? 'stable', $this->getTrendIcon($comp['improvement_trend'] ?? 'stable')],
            ];

            $this->table(['Metric', 'Value', 'Status'], $compData);

            if (! empty($comp['violation_types'])) {
                $this->info("\n🚨 TOP VIOLATION TYPES:");
                $count = 0;
                foreach ($comp['violation_types'] as $type => $data) {
                    if ($count >= 3) {
                        break;
                    }
                    $autoFix = round($data['auto_fix_rate'], 1);
                    $this->line("  • {$type}: {$data['count']} violations (Auto-fix: {$autoFix}%)");
                    $count++;
                }
            }
        }

        // Actionable Recommendations
        if (! empty($insights['recommendations'])) {
            $this->info("\n💡 ACTIONABLE RECOMMENDATIONS");
            foreach (\array_slice($insights['recommendations'], 0, 5) as $rec) {
                $priority = $this->getPriorityIcon($rec['priority']);
                $this->line("{$priority} [{$rec['category']}] {$rec['action']}");
                $this->line("   └─ {$rec['details']}");
            }
        }

        $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info('📅 Generated: '.now()->format('Y-m-d H:i:s'));
    }

    private function displayJsonReport(array $insights, array $healthData): void
    {
        $report = [
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'version' => '1.0',
                'type' => 'velocity_report',
            ],
            'health' => $healthData,
            'insights' => $insights,
        ];

        $this->line(\json_encode($report, JSON_PRETTY_PRINT));
    }

    // Status helpers
    private function getHealthStatus(float $score): string
    {
        return match (true) {
            $score >= 90 => '🟢 Excellent',
            $score >= 75 => '🟡 Good',
            $score >= 50 => '🟠 Fair',
            default => '🔴 Poor'
        };
    }

    private function getSuccessStatus(float $rate): string
    {
        return match (true) {
            $rate >= 90 => '🟢 Excellent',
            $rate >= 75 => '🟡 Good',
            $rate >= 50 => '🟠 Fair',
            default => '🔴 Needs Work'
        };
    }

    private function getConfidenceStatus(float $confidence): string
    {
        return match (true) {
            $confidence >= 85 => '🟢 High',
            $confidence >= 70 => '🟡 Medium',
            $confidence >= 50 => '🟠 Low',
            default => '🔴 Very Low'
        };
    }

    private function getAutoFixStatus(float $rate): string
    {
        return match (true) {
            $rate >= 80 => '🟢 Excellent',
            $rate >= 60 => '🟡 Good',
            $rate >= 40 => '🟠 Fair',
            default => '🔴 Poor'
        };
    }

    private function getTrendIcon(string $trend): string
    {
        return match ($trend) {
            'improving' => '📈 Improving',
            'declining' => '📉 Declining',
            'stable' => '➡️ Stable',
            default => '❓ Unknown'
        };
    }

    private function getPriorityIcon(string $priority): string
    {
        return match ($priority) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪'
        };
    }
}

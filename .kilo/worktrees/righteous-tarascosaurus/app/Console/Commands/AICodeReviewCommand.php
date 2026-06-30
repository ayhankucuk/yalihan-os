<?php

namespace App\Console\Commands;

use App\Services\AI\CodeReviewService;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class AICodeReviewCommand extends Command
{
    protected $signature = 'ai:code-review
                           {--file= : Specific file to review}
                           {--commit= : Git commit hash to review}
                           {--staged : Review staged changes}
                           {--fix : Apply auto-fixes where possible}
                           {--severity= : Minimum severity (low, medium, high, critical)}
                           {--format=table : Output format (table, json, markdown)}
                           {--save : Save review to file}';

    protected $description = 'AI-powered code review with Context7 compliance and optimization suggestions';

    protected CodeReviewService $codeReviewService;

    protected AnalyticsService $analyticsService;

    public function __construct(
        CodeReviewService $codeReviewService,
        AnalyticsService $analyticsService
    ) {
        parent::__construct();
        $this->codeReviewService = $codeReviewService;
        $this->analyticsService = $analyticsService;
    }

    public function handle(): int
    {
        $this->info('🤖 AI Code Review System');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            // Determine what to review
            $reviewScope = $this->determineReviewScope();

            if (empty($reviewScope['files'])) {
                $this->warn('No files found to review.');

                return self::SUCCESS;
            }

            $this->info("📁 Reviewing {$reviewScope['count']} files...");

            // Perform AI review
            $results = $this->performCodeReview($reviewScope['files']);

            // Filter by severity
            $results = $this->filterBySeverity($results);

            // Display results
            $this->displayResults($results);

            // Apply auto-fixes if requested
            if ($this->option('fix')) {
                $this->applyAutoFixes($results);
            }

            // Save results if requested
            if ($this->option('save')) {
                $this->saveResults($results);
            }

            // Record analytics
            $this->recordAnalytics($results);

            $this->info('✅ Code review completed!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Code review failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function determineReviewScope(): array
    {
        $files = [];

        if ($file = $this->option('file')) {
            // Single file review
            if (File::exists($file)) {
                $files = [$file];
            }
        } elseif ($commit = $this->option('commit')) {
            // Review specific commit
            $files = $this->getCommitFiles($commit);
        } elseif ($this->option('staged')) {
            // Review staged changes
            $files = $this->getStagedFiles();
        } else {
            // Review recent changes (last 24 hours)
            $files = $this->getRecentChanges();
        }

        return [
            'files' => $files,
            'count' => count($files),
        ];
    }

    private function getCommitFiles(string $commit): array
    {
        $result = Process::run("git show --pretty=\"\" --name-only {$commit}");

        if ($result->failed()) {
            return [];
        }

        return array_filter(
            explode("\n", $result->output()),
            fn ($file) => ! empty($file) && str_ends_with($file, '.php')
        );
    }

    private function getStagedFiles(): array
    {
        $result = Process::run('git diff --cached --name-only --diff-filter=ACMR');

        if ($result->failed()) {
            return [];
        }

        return array_filter(
            explode("\n", $result->output()),
            fn ($file) => ! empty($file) && str_ends_with($file, '.php')
        );
    }

    private function getRecentChanges(): array
    {
        $result = Process::run('git log --since="24 hours ago" --pretty="" --name-only');

        if ($result->failed()) {
            return [];
        }

        $files = array_filter(
            explode("\n", $result->output()),
            fn ($file) => ! empty($file) && str_ends_with($file, '.php')
        );

        return array_unique($files);
    }

    private function performCodeReview(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            if (! File::exists($file)) {
                continue;
            }

            $this->line("🔍 Reviewing: {$file}");

            $fileResults = $this->codeReviewService->reviewFile($file);

            if (! empty($fileResults)) {
                $results[$file] = $fileResults;
            }
        }

        return $results;
    }

    private function filterBySeverity(array $results): array
    {
        $minSeverity = $this->option('severity') ?? 'low';
        $severityLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $minLevel = $severityLevels[$minSeverity] ?? 1;

        $filtered = [];

        foreach ($results as $file => $issues) {
            $fileIssues = array_filter($issues, function ($issue) use ($severityLevels, $minLevel) {
                $issueLevel = $severityLevels[$issue['severity']] ?? 1;

                return $issueLevel >= $minLevel;
            });

            if (! empty($fileIssues)) {
                $filtered[$file] = $fileIssues;
            }
        }

        return $filtered;
    }

    private function displayResults(array $results): void
    {
        $format = $this->option('format');

        switch ($format) {
            case 'json':
                $this->displayJsonResults($results);
                break;
            case 'markdown':
                $this->displayMarkdownResults($results);
                break;
            default:
                $this->displayTableResults($results);
        }
    }

    private function displayTableResults(array $results): void
    {
        if (empty($results)) {
            $this->info('✅ No issues found! Code looks great.');

            return;
        }

        $totalIssues = 0;
        $severityCounts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];

        foreach ($results as $file => $issues) {
            $this->info("\n📄 {$file}");
            $this->line(str_repeat('─', 80));

            $tableData = [];
            foreach ($issues as $issue) {
                $severityIcon = match ($issue['severity']) {
                    'critical' => '🚨',
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪'
                };

                $tableData[] = [
                    "L{$issue['line']}",
                    $severityIcon.' '.ucfirst($issue['severity']),
                    $issue['type'],
                    substr($issue['message'], 0, 60).(strlen($issue['message']) > 60 ? '...' : ''),
                ];

                $severityCounts[$issue['severity']]++;
                $totalIssues++;
            }

            $this->table(['Line', 'Severity', 'Type', 'Message'], $tableData);

            // Show suggestions if available
            $suggestions = array_filter($issues, fn ($issue) => ! empty($issue['suggestion']));
            if (! empty($suggestions)) {
                $this->info('💡 Suggestions:');
                foreach ($suggestions as $suggestion) {
                    $this->line("   • {$suggestion['suggestion']}");
                }
            }
        }

        // Summary
        $this->info("\n📊 REVIEW SUMMARY");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(
            ['Severity', 'Count', 'Percentage'],
            [
                ['🚨 Critical', $severityCounts['critical'], $totalIssues > 0 ? round($severityCounts['critical'] / $totalIssues * 100, 1).'%' : '0%'],
                ['🔴 High', $severityCounts['high'], $totalIssues > 0 ? round($severityCounts['high'] / $totalIssues * 100, 1).'%' : '0%'],
                ['🟡 Medium', $severityCounts['medium'], $totalIssues > 0 ? round($severityCounts['medium'] / $totalIssues * 100, 1).'%' : '0%'],
                ['🟢 Low', $severityCounts['low'], $totalIssues > 0 ? round($severityCounts['low'] / $totalIssues * 100, 1).'%' : '0%'],
            ]
        );

        $this->line("Total Issues: {$totalIssues}");

        // Overall score
        $score = $this->calculateOverallScore($severityCounts);
        $this->info("Overall Code Quality Score: {$score}%");
    }

    private function displayJsonResults(array $results): void
    {
        $output = [
            'timestamp' => now()->toISOString(),
            'total_files' => count($results),
            'total_issues' => array_sum(array_map('count', $results)),
            'files' => $results,
            'summary' => $this->generateSummary($results),
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    private function displayMarkdownResults(array $results): void
    {
        $this->line("# AI Code Review Results\n");
        $this->line('**Generated:** '.now()->format('Y-m-d H:i:s')."\n");

        foreach ($results as $file => $issues) {
            $this->line("## 📄 {$file}\n");

            foreach ($issues as $issue) {
                $severityEmoji = match ($issue['severity']) {
                    'critical' => '🚨',
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪'
                };

                $this->line("### {$severityEmoji} Line {$issue['line']} - {$issue['type']}");
                $this->line("**Severity:** {$issue['severity']}");
                $this->line("**Message:** {$issue['message']}");

                if (! empty($issue['suggestion'])) {
                    $this->line("**Suggestion:** {$issue['suggestion']}");
                }

                $this->line('');
            }
        }
    }

    private function applyAutoFixes(array $results): void
    {
        $this->info('🔧 Applying auto-fixes...');

        $fixedCount = 0;

        foreach ($results as $file => $issues) {
            foreach ($issues as $issue) {
                if ($issue['auto_fixable'] && ! empty($issue['fix'])) {
                    if ($this->codeReviewService->applyFix($file, $issue)) {
                        $fixedCount++;
                        $this->line("  ✅ Fixed: {$issue['message']} in {$file}");
                    }
                }
            }
        }

        $this->info("🎉 Applied {$fixedCount} auto-fixes!");
    }

    private function saveResults(array $results): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "code_review_{$timestamp}.json";
        $path = storage_path("app/code-reviews/{$filename}");

        // Ensure directory exists
        $dir = dirname($path);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $data = [
            'timestamp' => now()->toISOString(),
            'total_files' => count($results),
            'total_issues' => array_sum(array_map('count', $results)),
            'results' => $results,
            'summary' => $this->generateSummary($results),
            'options' => [
                'file' => $this->option('file'),
                'commit' => $this->option('commit'),
                'staged' => $this->option('staged'),
                'severity' => $this->option('severity'),
            ],
        ];

        File::put($path, json_encode($data, JSON_PRETTY_PRINT));
        $this->info("💾 Results saved to: {$filename}");
    }

    private function recordAnalytics(array $results): void
    {
        $totalIssues = array_sum(array_map('count', $results));
        $totalFiles = count($results);

        // Record metrics
        $this->analyticsService->recordMetric('code_review', 'files_reviewed', $totalFiles);
        $this->analyticsService->recordMetric('code_review', 'issues_found', $totalIssues);

        // Record by severity
        $severityCounts = $this->getSeverityCounts($results);
        foreach ($severityCounts as $severity => $count) {
            $this->analyticsService->recordMetric('code_review', "issues_{$severity}", $count);
        }

        // Calculate quality score
        $score = $this->calculateOverallScore($severityCounts);
        $this->analyticsService->recordMetric('code_review', 'quality_score', $score, $score);
    }

    private function calculateOverallScore(array $severityCounts): float
    {
        $total = array_sum($severityCounts);

        if ($total === 0) {
            return 100.0;
        }

        // Weight penalties by severity
        $penalty = ($severityCounts['critical'] * 20) +
                  ($severityCounts['high'] * 10) +
                  ($severityCounts['medium'] * 5) +
                  ($severityCounts['low'] * 2);

        $score = max(0, 100 - $penalty);

        return round($score, 1);
    }

    private function getSeverityCounts(array $results): array
    {
        $counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];

        foreach ($results as $issues) {
            foreach ($issues as $issue) {
                $counts[$issue['severity']]++;
            }
        }

        return $counts;
    }

    private function generateSummary(array $results): array
    {
        $severityCounts = $this->getSeverityCounts($results);
        $totalIssues = array_sum($severityCounts);

        return [
            'total_files' => count($results),
            'total_issues' => $totalIssues,
            'severity_breakdown' => $severityCounts,
            'overall_score' => $this->calculateOverallScore($severityCounts),
            'recommendation' => $this->getRecommendation($severityCounts),
        ];
    }

    private function getRecommendation(array $severityCounts): string
    {
        if ($severityCounts['critical'] > 0) {
            return 'Critical issues found! Immediate attention required.';
        } elseif ($severityCounts['high'] > 5) {
            return 'Multiple high-priority issues. Consider refactoring.';
        } elseif ($severityCounts['medium'] > 10) {
            return 'Several medium-priority issues. Schedule cleanup.';
        } elseif ($severityCounts['low'] > 0) {
            return 'Minor issues found. Good overall code quality.';
        }

        return 'Excellent! No issues found.';
    }
}

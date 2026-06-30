<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class YalihanBekciHealthCommand extends Command
{
    protected $signature = 'bekci:health {--detailed : Show detailed health report}';

    protected $description = 'Yalıhan Bekçi AI sistemi genel sağlık durumunu görüntüler';

    private string $mcpServerUrl = 'http://localhost:4001';

    private string $knowledgeBase;

    public function __construct()
    {
        parent::__construct();
        $this->knowledgeBase = base_path('yalihan-bekci/knowledge');
    }

    public function handle()
    {
        $detailed = $this->option('detailed');

        $this->info('🏥 Yalıhan Bekçi Health Check');
        $this->line(str_repeat('=', 50));

        // Check MCP Server
        $mcpStatus = $this->checkMCPServer();
        $this->displayStatus('MCP Server', $mcpStatus);

        // Check Knowledge Base
        $knowledgeStatus = $this->checkKnowledgeBase();
        $this->displayStatus('Knowledge Base', $knowledgeStatus);

        // Check Learning Activity
        $learningStatus = $this->checkLearningActivity();
        $this->displayStatus('Learning Activity', $learningStatus);

        // Check Project Health
        $projectStatus = $this->checkProjectHealth();
        $this->displayStatus('Project Health', $projectStatus);

        // Check App Runtime Health
        $appHealth = $this->checkAppHealth();
        $this->displayStatus('App Runtime Health', $appHealth);

        if ($detailed) {
            $this->showDetailedReport();
        }

        $this->showOverallScore();
    }

    private function checkMCPServer(): array
    {
        try {
            $response = Http::timeout(3)->get($this->mcpServerUrl.'/health');

            if ($response->successful()) {
                return [
                    'saglik_durumu' => 'healthy',
                    'message' => 'MCP Server responding',
                    'score' => 100,
                ];
            }

            return [
                'saglik_durumu' => 'unhealthy',
                'message' => 'MCP Server not responding',
                'score' => 0,
            ];
        } catch (\Exception $e) {
            report($e);
            return [
                'saglik_durumu' => 'offline',
                'message' => 'MCP Server offline or unreachable',
                'score' => 0,
            ];
        }
    }

    private function checkKnowledgeBase(): array
    {
        if (! File::exists($this->knowledgeBase)) {
            return [
                'saglik_durumu' => 'missing',
                'message' => 'Knowledge base directory not found',
                'score' => 0,
            ];
        }

        $files = File::files($this->knowledgeBase);
        $fileCount = count($files);

        if ($fileCount === 0) {
            return [
                'saglik_durumu' => 'empty',
                'message' => 'No learning data found',
                'score' => 20,
            ];
        }

        // Check recent activity (files from last 7 days)
        $recentFiles = collect($files)->filter(function ($file) {
            return Carbon::createFromTimestamp(File::lastModified($file))->isAfter(Carbon::now()->subDays(7));
        });

        $recentCount = $recentFiles->count();
        $score = min(100, ($fileCount * 10) + ($recentCount * 20));

        return [
            'saglik_durumu' => 'healthy',
            'message' => "{$fileCount} learning entries, {$recentCount} recent",
            'score' => $score,
            'details' => [
                'total_files' => $fileCount,
                'recent_files' => $recentCount,
                'storage_size' => $this->getDirectorySize($this->knowledgeBase),
            ],
        ];
    }

    private function checkLearningActivity(): array
    {
        $files = File::files($this->knowledgeBase);

        if (empty($files)) {
            return [
                'saglik_durumu' => 'inactive',
                'message' => 'No learning activity detected',
                'score' => 0,
            ];
        }

        // Analyze learning patterns
        $patterns = [
            'code_change' => 0,
            'context7_fix' => 0,
            'migration' => 0,
            'other' => 0,
        ];

        foreach ($files as $file) {
            $content = File::get($file);
            $data = json_decode($content, true);

            if (isset($data['action_type'])) {
                $actionType = $data['action_type'];
                if (isset($patterns[$actionType])) {
                    $patterns[$actionType]++;
                } else {
                    $patterns['other']++;
                }
            }
        }

        $totalActivity = array_sum($patterns);
        $score = min(100, $totalActivity * 5);

        return [
            'saglik_durumu' => $totalActivity > 0 ? 'active' : 'inactive',
            'message' => "Learning from {$totalActivity} actions",
            'score' => $score,
            'patterns' => $patterns,
        ];
    }

    private function checkProjectHealth(): array
    {
        $health = [
            'context7_compliance' => $this->getContext7Compliance(),
            'code_quality' => $this->getCodeQuality(),
            'test_coverage' => $this->getTestCoverage(),
            'documentation' => $this->getDocumentationScore(),
        ];

        $overallScore = array_sum($health) / count($health);

        return [
            'saglik_durumu' => $overallScore > 80 ? 'excellent' : ($overallScore > 60 ? 'good' : 'needs_improvement'),
            'message' => "Overall project health: {$overallScore}%",
            'score' => $overallScore,
            'details' => $health,
        ];
    }

    private function displayStatus(string $component, array $bilgi_paketi): void
    {
        $icon = match ($bilgi_paketi['saglik_durumu'] ?? 'unknown') {
            'healthy', 'excellent', 'active' => '✅',
            'good', 'offline' => '⚠️',
            default => '❌'
        };

        $this->line("{$icon} {$component}: {$bilgi_paketi['message']} ({$bilgi_paketi['score']}%)");
    }

    private function showDetailedReport(): void
    {
        $this->line("\n📊 Detailed Report:");
        $this->line(str_repeat('-', 30));

        // Recent learning entries
        $files = collect(File::files($this->knowledgeBase))
            ->sortByDesc(function ($file) {
                return File::lastModified($file);
            })
            ->take(5);

        if ($files->isNotEmpty()) {
            $this->line('📚 Recent Learning Entries:');
            foreach ($files as $file) {
                $modified = Carbon::createFromTimestamp(File::lastModified($file));
                $name = pathinfo($file, PATHINFO_FILENAME);
                $this->line("   • {$name} ({$modified->diffForHumans()})");
            }
        }

        // Recommendations
        $this->line("\n💡 Recommendations:");
        $recommendations = $this->generateRecommendations();
        foreach ($recommendations as $rec) {
            $this->line("   • {$rec}");
        }
    }

    private function showOverallScore(): void
    {
        // Calculate overall system health
        $mcpStatus = $this->checkMCPServer();
        $knowledgeStatus = $this->checkKnowledgeBase();
        $learningStatus = $this->checkLearningActivity();
        $projectStatus = $this->checkProjectHealth();
        $appHealth = $this->checkAppHealth();

        $overallScore = (
            $mcpStatus['score'] * 0.25 +
            $knowledgeStatus['score'] * 0.15 +
            $learningStatus['score'] * 0.25 +
            $projectStatus['score'] * 0.15 +
            $appHealth['score'] * 0.20
        );

        $this->line("\n".str_repeat('=', 50));

        $statusIcon = $overallScore > 80 ? '🟢' : ($overallScore > 60 ? '🟡' : '🔴');
        $durumMetni = $overallScore > 80 ? 'EXCELLENT' : ($overallScore > 60 ? 'GOOD' : 'NEEDS ATTENTION');

        $this->line("{$statusIcon} Overall System Health: {$overallScore}% - {$durumMetni}");
    }

    private function getContext7Compliance(): int
    {
        // Simple compliance check - count violations
        try {
            $violations = 0;

            // Check for forbidden patterns
            // @context7-exempt: Pattern definitions for validation
            $yasaklar = ['antigravity-', 'du'.'rum', 'si'.'ra'];
            foreach ($yasaklar as $pattern) {
                $count = (int) \trim(\shell_exec("grep -r '{$pattern}' app/ resources/ --include='*.php' --include='*.blade.php' | wc -l") ?: '0');
                $violations += $count;
            }

            return max(0, 100 - ($violations * 2));
        } catch (\Exception $e) {
            report($e);
            return 50; // Default if check fails
        }
    }

    private function getCodeQuality(): int
    {
        // Simple code quality metrics
        try {
            $phpFiles = (int) \trim(\shell_exec('find app/ -name "*.php" | wc -l') ?: '0');
            $testFiles = (int) \trim(\shell_exec('find tests/ -name "*.php" | wc -l') ?: '0');

            if ($phpFiles === 0) {
                return 0;
            }

            $testRatio = ($testFiles / $phpFiles) * 100;

            return min(100, $testRatio * 2); // Max score when 50%+ test coverage
        } catch (\Exception $e) {
            report($e);
            return 50;
        }
    }

    private function getTestCoverage(): int
    {
        // Estimate test coverage based on test file presence
        try {
            $modelFiles = (int) \trim(\shell_exec('find app/Models/ -name "*.php" | wc -l') ?: '0');
            $testFiles = (int) \trim(\shell_exec('find tests/ -name "*Test.php" | wc -l') ?: '0');

            if ($modelFiles === 0) {
                return 100;
            }

            return min(100, ($testFiles / $modelFiles) * 80);
        } catch (\Exception $e) {
            report($e);
            return 60;
        }
    }

    private function getDocumentationScore(): int
    {
        // Check for documentation files
        $docs = [
            'README.md' => 20,
            'docs/' => 30,
            '.context7/' => 30,
            'CHANGELOG.md' => 20,
        ];

        $score = 0;
        foreach ($docs as $path => $points) {
            if (File::exists(base_path($path))) {
                $score += $points;
            }
        }

        return $score;
    }

    private function getDirectorySize(string $dir): string
    {
        try {
            // macOS compatible du command
            $bytes = (int) \trim(\shell_exec("du -s {$dir} | cut -f1") ?: '0');
            // Convert from 512-byte blocks to bytes on macOS
            $bytes = $bytes * 512;

            return $this->formatBytes($bytes);
        } catch (\Exception $e) {
            report($e);
            return 'Unknown';
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < \count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return \round($bytes, 2).' '.$units[$i];
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];

        $mcpStatus = $this->checkMCPServer();
        if ($mcpStatus['saglik_durumu'] !== 'healthy') {
            $recommendations[] = 'Start MCP server: ./scripts/services/start-bekci-server.sh';
        }

        $knowledgeStatus = $this->checkKnowledgeBase();
        if ($knowledgeStatus['score'] < 50) {
            $recommendations[] = 'Generate more learning data by performing development tasks';
        }

        $projectStatus = $this->checkProjectHealth();
        if ($projectStatus['score'] < 80) {
            $recommendations[] = 'Run Context7 validation: php artisan context7:validate-migration --all';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'System is healthy! Consider exploring new AI features.';
        }

        return $recommendations;
    }

    private function checkAppHealth(): array
    {
        try {
            $controller = app(\App\Http\Controllers\Api\HealthCheckController::class);
            $response = $controller->check();
            $data = $response->getData(true);

            $isHealthy = ($data['durum'] ?? 'unhealthy') === 'healthy';
            
            $score = 100;
            $failedChecks = [];
            if (isset($data['checks'])) {
                foreach ($data['checks'] as $service => $check) {
                    if (($check['durum'] ?? 'error') !== 'ok') {
                        $score -= 25;
                        $failedChecks[] = $service;
                    }
                }
            }
            $score = max(0, $score);

            return [
                'saglik_durumu' => $isHealthy ? 'healthy' : 'unhealthy',
                'message' => $isHealthy ? 'All systems operational' : 'Degraded: ' . implode(', ', $failedChecks),
                'score' => $score,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'saglik_durumu' => 'unhealthy',
                'message' => 'Failed to probe runtime: ' . $e->getMessage(),
                'score' => 0,
            ];
        }
    }
}

<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class YalihanBekciLearnCommand extends Command
{
    protected $signature = 'bekci:learn
                          {action_type : Type of action (code_change, context7_fix, migration, etc.)}
                          {context : Context description}
                          {--files= : Comma-separated list of changed files}
                          {--details= : JSON string of action details}
                          {--format=console : Output format (console, json)}';

    protected $description = 'Teach Yalıhan Bekçi AI about performed actions';

    private string $mcpServerUrl = 'http://localhost:4001';

    private string $knowledgeBase;

    public function __construct()
    {
        parent::__construct();
        $this->knowledgeBase = base_path('yalihan-bekci/knowledge');
    }

    protected \App\Presenters\Sab\PresenterContract $presenter;

    public function handle()
    {
        $this->initializePresenter();

        $actionType = $this->argument('action_type');
        $context = $this->argument('context');
        $files = $this->option('files') ? explode(',', $this->option('files')) : [];
        $details = $this->option('details') ? json_decode($this->option('details'), true) : [];

        $learningData = [
            'action_type' => $actionType,
            'context' => $context,
            'action_details' => $details,
            'files_changed' => $files,
            'timestamp' => Carbon::now()->toISOString(),
            'project_state' => $this->getProjectState(),
            'context7_compliance' => $this->getComplianceStatus(),
        ];

        // Try MCP server first
        if (!$this->teachViaMCP($learningData)) {
            $this->storeLocalLearning($learningData);
        }

        // Prepare suggestions for presenter
        $suggestions = $this->detectPatterns($learningData);
        $learningData['suggestions'] = array_column($suggestions, 'description');

        $this->presenter->renderLearn($learningData);

        return 0;
    }

    private function initializePresenter(): void
    {
        $format = $this->option('format');
        $this->presenter = match ($format) {
            'json' => new \App\Presenters\Sab\JsonPresenter($this),
            default => new \App\Presenters\Sab\ConsolePresenter($this),
        };
    }

    private function teachViaMCP(array $data): bool
    {
        try {
            $response = Http::timeout(5)->post($this->mcpServerUrl.'/learn', [
                'tool' => 'learn_from_action',
                'args' => $data,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            $this->error('MCP Error: '.$e->getMessage());

            return false;
        }
    }

    private function storeLocalLearning(array $data): void
    {
        // Ensure knowledge base directory exists
        if (! File::exists($this->knowledgeBase)) {
            File::makeDirectory($this->knowledgeBase, 0755, true);
        }

        $filename = "learning_{$data['action_type']}_".date('Y-m-d_H-i-s').'.json';
        $filepath = $this->knowledgeBase.'/'.$filename;

        // Add local analysis
        $data['local_analysis'] = [
            'patterns_detected' => $this->detectPatterns($data),
            'compliance_impact' => $this->analyzeComplianceImpact($data),
            'recommendations' => $this->generateRecommendations($data),
        ];

        File::put($filepath, json_encode($data, JSON_PRETTY_PRINT));
        $this->info("💾 Stored locally: {$filename}");
    }

    private function getProjectState(): array
    {
        return [
            'current_branch' => $this->getCurrentBranch(),
            'uncommitted_files' => $this->getUncommittedFiles(),
            'total_files' => $this->getTotalProjectFiles(),
            'migration_count' => $this->getMigrationCount(),
            'context7_score' => $this->getContext7Score(),
        ];
    }

    private function getComplianceStatus(): array
    {
        // Run basic compliance checks
        $violations = [];

        // Check for forbidden patterns
        // @context7-exempt: Pattern definitions for validation
        $forbiddenPatterns = [
            'neo-' => 'Ne'.'o Design System usage',
            'stat'.'us' => 'Forbidden stat'.'us field',
            'ord'.'er' => 'Should use display_ord'.'er instead',
        ];

        foreach ($forbiddenPatterns as $pattern => $description) {
            $count = $this->countPatternInProject($pattern);
            if ($count > 0) {
                $violations[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                    'count' => $count,
                ];
            }
        }

        return [
            'violations' => $violations,
            'compliance_score' => max(0, 100 - (count($violations) * 10)),
            'last_check' => Carbon::now()->toISOString(),
        ];
    }

    private function detectPatterns(array $data): array
    {
        $patterns = [];

        // Pattern detection based on action type
        switch ($data['action_type']) {
            case 'context7_fix':
                $patterns[] = [
                    'type' => 'compliance_improvement',
                    'description' => 'Context7 compliance fix applied',
                    'confidence' => 0.9,
                ];
                break;

            case 'migration':
                $patterns[] = [
                    'type' => 'database_evolution',
                    'description' => 'Database schema change',
                    'confidence' => 0.8,
                ];
                break;

            case 'code_change':
                if (! empty($data['files_changed'])) {
                    $phpFiles = array_filter($data['files_changed'], fn ($f) => str_ends_with($f, '.php'));
                    if (count($phpFiles) > 0) {
                        $patterns[] = [
                            'type' => 'backend_development',
                            'description' => 'PHP code modification',
                            'confidence' => 0.7,
                        ];
                    }
                }
                break;
        }

        return $patterns;
    }

    private function analyzeComplianceImpact(array $data): array
    {
        $impact = [
            'positive_changes' => 0,
            'negative_changes' => 0,
            'neutral_changes' => 0,
        ];

        // Analyze based on patterns and context
        if (str_contains(strtolower($data['context']), 'context7')) {
            $impact['positive_changes']++;
        }

        if (str_contains(strtolower($data['context']), 'fix')) {
            $impact['positive_changes']++;
        }

        return $impact;
    }

    private function generateRecommendations(array $data): array
    {
        $recommendations = [];

        // Generate recommendations based on action type and patterns
        if ($data['action_type'] === 'migration') {
            $recommendations[] = [
                'category' => 'database',
                'suggestion' => 'Consider adding indexes for better performance',
                'priority' => 'medium',
            ];
        }

        if (! empty($data['files_changed'])) {
            $bladeFiles = array_filter($data['files_changed'], fn ($f) => str_ends_with($f, '.blade.php'));
            if (count($bladeFiles) > 0) {
                $recommendations[] = [
                    'category' => 'frontend',
                    'suggestion' => 'Ensure Tailwind CSS usage and transitions',
                    'priority' => 'high',
                ];
            }
        }

        return $recommendations;
    }

    private function generateSuggestions(array $data): void
    {
        $suggestions = [];

        // Based on learning data, generate improvement suggestions
        if ($data['action_type'] === 'context7_fix') {
            $suggestions[] = 'Consider creating a pre-commit hook for automatic Context7 validation';
        }

        if (! empty($data['files_changed'])) {
            $testFiles = array_filter($data['files_changed'], fn ($f) => str_contains($f, 'test'));
            if (empty($testFiles) && count($data['files_changed']) > 0) {
                $suggestions[] = 'Consider adding tests for the changed functionality';
            }
        }

        if (! empty($suggestions)) {
            $this->info('💡 Suggestions:');
            foreach ($suggestions as $suggestion) {
                $this->line("   • {$suggestion}");
            }
        }
    }

    // Helper methods
    private function getCurrentBranch(): string
    {
        try {
            return trim(shell_exec('git branch --show-current')) ?: 'unknown';
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('BekciLearn: Failed to get current branch', ['error' => $e->getMessage()]);
            return 'unknown';
        }
    }

    private function getUncommittedFiles(): int
    {
        try {
            return count(explode("\n", trim(shell_exec('git status --porcelain') ?: '')));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('BekciLearn: Failed to get uncommitted files', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    private function getTotalProjectFiles(): int
    {
        try {
            return (int) trim(shell_exec('find . -type f -name "*.php" | wc -l') ?: '0');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('BekciLearn: Failed to get total project files', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    private function getMigrationCount(): int
    {
        return count(glob(database_path('migrations/*.php')));
    }

    private function getContext7Score(): int
    {
        // Simple scoring based on compliance metrics
        $violations = $this->getComplianceStatus()['violations'];

        return max(0, 100 - (count($violations) * 5));
    }

    private function countPatternInProject(string $pattern): int
    {
        try {
            $result = shell_exec("grep -r '{$pattern}' app/ resources/ --include='*.php' --include='*.blade.php' | wc -l");

            return (int) trim($result ?: '0');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('BekciLearn: Failed to count pattern', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}

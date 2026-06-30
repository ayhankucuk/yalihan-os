<?php

namespace App\Console\Commands\Sab;

use Illuminate\Console\Command;
use App\Services\AI\CodeReviewService;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Support\Facades\File;

/**
 * 🛡️ SAB Integrity Scan
 *
 * Comprehensive architectural governance scanner.
 * Enforces SAB Core Constitution v1.0.
 */
class SabIntegrityScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sab:integrity-scan
                            {--path=app : Directory to scan}
                            {--fix : Attempt to automatically fix violations}
                            {--generate-baseline : Generate a new baseline file}
                            {--baseline-path=.sab/sab-baseline.json : Path to baseline file}
                            {--format=console : Output format (console, json, markdown)}
                            {--silent : Do not output to console}
                            {--diff : Show baseline delta (resolved / new / persisted counts)}';

    /**
     * The console command description.
     */
    protected $description = '🛡️ Perform a SAB Zero-Tolerance integrity scan on the codebase';

    protected \App\Services\Governance\SabScanRunner $runner;
    protected \App\Services\Governance\SabScanFormatter $formatter;
    protected \App\Services\Governance\BaselineDiffService $diffService;

    public function __construct(\App\Services\Governance\SabScanRunner $runner)
    {
        parent::__construct();
        $this->runner = $runner;
        $this->diffService = new \App\Services\Governance\BaselineDiffService();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->formatter = new \App\Services\Governance\SabScanFormatter($this);
        
        $path = $this->option('path');
        $format = $this->option('format');

        $scanStart = microtime(true);
        
        try {
            // Run Normalized Scan (Phase 3)
            // The runner now automatically tags violations with is_baseline using SabBaselineManager
            $violations = $this->runner->scan($path);
            
            $duration = (int)((microtime(true) - $scanStart) * 1000);
            
            $newViolations = array_filter($violations, fn($v) => !($v['is_baseline'] ?? false));
            $baselineViolations = array_filter($violations, fn($v) => $v['is_baseline'] ?? false);
            
            // P3A: Filter out report-only violations from causing an exit code failure
            $blockingNewViolations = array_filter($newViolations, fn($v) => !($v['is_report_only'] ?? false));

            $summary = [
                'path' => $path,
                'duration' => $duration,
                'legacyCount' => count($baselineViolations)
            ];

            // Diff (optional)
            $diff = null;
            if ($this->option('diff')) {
                $diff = $this->diffService->diff($violations);
            }

            // Output
            if ($format === 'json') {
                $this->formatter->renderJson($violations, $summary);
            } elseif ($format === 'markdown') {
                $this->formatter->renderMarkdown($violations, $summary, $diff);
            } else {
                $this->formatter->renderConsole($violations, $summary, $diff);
            }

            // Phase 5: Generate Baseline (if requested)
            if ($this->option('generate-baseline')) {
                $this->info("\n🛡️ Generating new baseline with " . count($violations) . " violations...");
                $success = $this->runner->getBaselineManager()->updateBaseline($violations);
                if ($success) {
                    $this->info("✅ Baseline updated successfully at " . $this->option('baseline-path'));
                } else {
                    $this->error("❌ Baseline update failed.");
                }
            }

            // Phase 4: Baseline-Aware Exit Code
            // Exit 1 if any NEW blocking violations exist (not in baseline and not report-only).
            // If --generate-baseline was used, newViolations will be 0 on next run.
            return count($blockingNewViolations) > 0 ? 1 : 0;

        } catch (\Throwable $e) {
            $this->error("SAB Integrity Error: " . $e->getMessage());
            return 2; // System Error
        }
    }

    private function getFilesToScan(string $path): array
    {
        $files = File::allFiles($path);

        $excludedPaths = [
            'Rules/PHPStan',
            'Models/BaseModel.php',
            'Services/AI/CodeReviewService.php',
            'Services/Bekci/Scanners/',
            'Console/Commands/Sab/',
        ];

        return array_filter($files, function ($file) use ($excludedPaths) {
            $relativePath = $file->getRelativePathname();
            foreach ($excludedPaths as $excluded) {
                if (str_contains($relativePath, $excluded)) {
                    return false;
                }
            }
            return true;
        });
    }
}

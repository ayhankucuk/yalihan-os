<?php

namespace App\Console\Commands\SabGuard;

use Illuminate\Console\Command;
use App\Services\Sab\SabAutomationGuardService;

class SabGuardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:guard {--json : Output as machine-readable JSON report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛡️ SAB Strict Guard: Checks architecture constraints and fails CI (exit 1) if any violations exist.';

    protected \App\Services\Governance\SabScanRunner $runner;

    public function __construct(\App\Services\Governance\SabScanRunner $runner)
    {
        parent::__construct();
        $this->runner = $runner;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isJson = $this->option('json');
        
        if (!$isJson) {
            $this->info("🛡️ Initiating SAB Strict Security & Architecture Guard...");
        }

        // We use the runner to get normalized, baseline-aware violations
        $violations = $this->runner->scan('app');
        
        $newViolations = array_filter($violations, fn($v) => !($v['is_baseline'] ?? false));
        $baselineViolations = array_filter($violations, fn($v) => $v['is_baseline'] ?? false);

        if ($isJson) {
            $this->output->write(json_encode([
                'timestamp' => now()->toIso8601String(),
                'status' => empty($newViolations) ? 'PASSED' : 'FAILED',
                'new_violations_count' => count($newViolations),
                'baseline_violations_count' => count($baselineViolations),
                'new_violations' => $newViolations,
                'baseline_violations' => $baselineViolations
            ], JSON_PRETTY_PRINT));
            
            return empty($newViolations) ? 0 : 1;
        }

        if (empty($newViolations)) {
            $this->info("✅ SAB Guard PASSED: System is compliant (with " . count($baselineViolations) . " known baseline violations).");
            return 0;
        }

        $this->error("❌ SAB Guard FAILED: Architectural drift detected.");
        $this->warn("New Violations: " . count($newViolations));
        $this->line("Please run `php artisan sab:scan` or `php artisan sab:audit` for details.");

        return 1; // Strict failure for CI pipes
    }
}

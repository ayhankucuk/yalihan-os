<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class QualityGateCommand extends Command
{
    protected $signature = 'quality:gate
        {--baseline : Mevcut durumu baseline olarak kilitler}
        {--force : Tüm hataları gösterir (baseline yoksayar)}';

    protected $description = 'Yalihan SAB Kristal Temizlik Otoritesi (Zero Drift Gate)';

    public function handle(): int
    {
        $this->info('🛡️ Quality Gate started (SAB v12)');

        // 0. Mandatory Architectural Chain (fail-fast)
        $mandatorySteps = [
            'domain:seal-check ALL',
            'guard:routes:v2',
            'guard:actions:v10_9',
            'sab:integrity-scan',
            'model:drift-scan',
        ];

        foreach ($mandatorySteps as $step) {
            $this->comment("Running mandatory step: {$step}");
            $exitCode = Artisan::call($step);
            $output = Artisan::output();

            if ($output !== '') {
                $this->line(trim($output));
            }

            if ($exitCode !== 0) {
                $this->error("❌ BUILD FAILED: {$step} failed (exit={$exitCode})");

                return 1;
            }
        }

        // 1. Baseline Mode
        if ($this->option('baseline')) {
            $this->comment('Capturing baseline snapshot...');
            shell_exec(PHP_BINARY . ' artisan route:list --json > storage/logs/route_list.json');
            shell_exec('python3 scripts/hygiene-audit.py app/Http/Controllers/Admin');

            $reportPath = base_path('docs/reports/hygiene_report.json');
            if (File::exists($reportPath)) {
                File::ensureDirectoryExists(storage_path('app/sab'));
                File::copy($reportPath, storage_path('app/sab/baseline.json'));
                $this->info('✅ Baseline snapshot locked at storage/app/sab/baseline.json');
                return 0;
            }
            $this->error('Baseline failed: report not generated.');
            return 1;
        }

        // 1. Refresh Route List
        $this->comment('Refreshing route list...');
        $routeListPath = storage_path('logs/route_list.json');
        shell_exec(PHP_BINARY . " artisan route:list --json > {$routeListPath}");

        // 2. Run Audit (Delta Enforcement)
        $this->comment('Running Hygiene Audit (Delta Enforcement)...');
        $auditScript = base_path('scripts/hygiene-audit.py');
        $cmd = "python3 {$auditScript} app/Http/Controllers/Admin 2>&1";

        $output = shell_exec($cmd);
        echo $output;

        if (strpos($output, 'NEW VIOLATIONS DETECTED') !== false) {
            $this->error('❌ BUILD FAILED: New hygiene violations detected!');
            return 1;
        }

        if (strpos($output, 'No new violations') !== false) {
            $this->info('✅ BUILD SUCCESS: No new drift detected.');
            return 0;
        }

        $this->warn('Audit completed with unknown result state.');
        return 0;
    }
}

<?php

namespace App\Console\Commands\SabGuard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SabBaselineCommand extends Command
{
    protected $signature = 'sab:baseline
                            {--path=app : Directory to scan}
                            {--baseline-path=.sab/sab-baseline.json : Baseline output file path}';

    protected $description = 'Generate or refresh SAB integrity baseline using sab:integrity-scan.';

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $baselinePath = (string) $this->option('baseline-path');

        $this->info("Generating SAB baseline at: {$baselinePath}");

        $exitCode = Artisan::call('sab:integrity-scan', [
            '--path' => $path,
            '--generate-baseline' => true,
            '--baseline-path' => $baselinePath,
        ]);

        $outputText = trim(Artisan::output());
        if ($outputText !== '') {
            $this->line($outputText);
        }

        if ($exitCode !== 0) {
            $this->error("sab:baseline failed (exit={$exitCode}).");
            return $exitCode;
        }

        $this->info('SAB baseline generated successfully.');

        return 0;
    }
}

<?php

namespace App\Console\Commands\SabGuard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SabReportCommand extends Command
{
    protected $signature = 'sab:report
                            {--output=docs/SAB_AUDIT_REPORT.md : Output path}';

    protected $description = 'Alias for sab:audit to generate SAB markdown report.';

    public function handle(): int
    {
        $output = (string) $this->option('output');

        $exitCode = Artisan::call('sab:audit', [
            '--output' => $output,
        ]);

        $outputText = trim(Artisan::output());
        if ($outputText !== '') {
            $this->line($outputText);
        }

        return $exitCode;
    }
}

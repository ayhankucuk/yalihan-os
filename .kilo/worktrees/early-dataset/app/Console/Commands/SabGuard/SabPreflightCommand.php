<?php

namespace App\Console\Commands\SabGuard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SabPreflightCommand extends Command
{
    protected $signature = 'sab:preflight
                            {--profile=fast : fast|full|release}
                            {--path=app : Path for sab:integrity-scan}
                            {--output=docs/SAB_PREFLIGHT_AUDIT_REPORT.md : Output path for audit report}';

    protected $description = 'Run a SAB preflight chain with selectable profile (fast, full, release).';

    public function handle(): int
    {
        $profile = (string) $this->option('profile');
        $path = (string) $this->option('path');
        $output = (string) $this->option('output');

        $pipelines = [
            'fast' => [
                ['sab:scan', []],
                ['sab:integrity-scan', ['--path' => $path]],
            ],
            'full' => [
                ['sab:scan', []],
                ['sab:integrity-scan', ['--path' => $path]],
                ['sab:audit', ['--output' => $output]],
            ],
            'release' => [
                ['sab:scan', []],
                ['sab:integrity-scan', ['--path' => $path]],
                ['sab:audit', ['--output' => $output]],
                ['sab:guard', []],
                ['quality:gate', []],
                // Doc governance gates
                ['sab:authority-map:generate', ['--check' => true]],
            ],
        ];

        if (!array_key_exists($profile, $pipelines)) {
            $this->error("Invalid profile: {$profile}. Allowed: fast, full, release.");
            return 1;
        }

        $this->info("Starting SAB preflight profile: {$profile}");

        foreach ($pipelines[$profile] as [$command, $arguments]) {
            $this->line("Running: {$command}");

            // Yol A: Handle baseline semantics for sab:integrity-scan
            if ($command === 'sab:integrity-scan') {
                $arguments['--format'] = 'json';
                $exitCode = Artisan::call($command, $arguments);
                $outputText = trim(Artisan::output());

                $data = json_decode($outputText, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['data']['summary'])) {
                    $summary = $data['data']['summary'];
                    $newCount = $summary['new_violations'] ?? 0;
                    $baselineCount = $summary['baseline_violations'] ?? 0;
                    $errorsCount = $summary['errors'] ?? 0;

                    if ($newCount === 0 && $baselineCount > 0) {
                        $this->warn("  ⚠ KNOWN BASELINE VIOLATIONS: {$baselineCount} (PASS)");
                        $exitCode = 0; // Enforce successful exit for baseline-only
                    } elseif ($newCount > 0) {
                        $this->error("  🚨 NEW VIOLATIONS DETECTED: {$newCount} (FAIL)");
                        $exitCode = 1; // Enforce failure for new violations
                    } else {
                        $this->info("  ✨ No architectural violations found.");
                        $exitCode = 0;
                    }
                } else {
                    $this->error("sab:integrity-scan execution error or invalid JSON output.");
                    if ($outputText !== '') {
                        $this->line($outputText);
                    }
                    $exitCode = 1;
                }

                if ($exitCode !== 0) {
                    $this->error("Preflight failed at step: {$command} (exit={$exitCode})");
                    return $exitCode;
                }
                
                continue;
            }

            // Normal command handling
            $exitCode = Artisan::call($command, $arguments);
            $outputText = trim(Artisan::output());

            if ($outputText !== '') {
                $this->line($outputText);
            }

            if ($exitCode !== 0) {
                $this->error("Preflight failed at step: {$command} (exit={$exitCode})");
                return $exitCode;
            }
        }

        // Release profile: auto-discover and validate deprecation archives
        if ($profile === 'release') {
            $deprecationResult = $this->runDeprecationValidation();
            if ($deprecationResult !== 0) {
                return $deprecationResult;
            }
        }

        $this->info('SAB preflight completed successfully.');

        return 0;
    }

    /**
     * Auto-discover and validate all deprecation archives with mapping files.
     * Convention: .ai/memory/legacy/*.mapping.json → validates *.md
     *
     * @return int Exit code (0 = all passed or no mappings found)
     */
    private function runDeprecationValidation(): int
    {
        $legacyDir = base_path('.ai/memory/legacy');

        if (!File::isDirectory($legacyDir)) {
            $this->line('  No legacy directory found — skipping deprecation validation.');
            return 0;
        }

        $mappingFiles = File::glob($legacyDir . '/*.mapping.json');

        if (empty($mappingFiles)) {
            $this->line('  No mapping files found — skipping deprecation validation.');
            return 0;
        }

        foreach ($mappingFiles as $mappingFile) {
            $archiveName = str_replace('.mapping.json', '.md', basename($mappingFile));
            $archivePath = '.ai/memory/legacy/' . $archiveName;

            $this->line("Running: sab:deprecation:validate ({$archiveName})");

            $exitCode = Artisan::call('sab:deprecation:validate', [
                'archive' => $archivePath,
                '--json' => true,
            ]);

            $outputText = trim(Artisan::output());
            if ($outputText !== '') {
                $this->line($outputText);
            }

            if ($exitCode !== 0) {
                $this->error("Preflight failed at step: sab:deprecation:validate [{$archiveName}] (exit={$exitCode})");
                return $exitCode;
            }
        }

        return 0;
    }
}

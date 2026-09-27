<?php

namespace App\Console\Commands\SabGuard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class SabDoctorCommand extends Command
{
    protected $signature = 'sab:doctor
                            {--path=app : Path for sab:integrity-scan}
                            {--output=docs/SAB_DOCTOR_AUDIT_REPORT.md : Output path for sab:audit}
                            {--strict : Include strict sab:guard check}';

    protected $description = 'Run SAB diagnostics and print actionable health summary.';

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $output = (string) $this->option('output');

        $steps = [
            ['sab:scan', [], true],
            ['sab:integrity-scan', ['--path' => $path], true],
            ['sab:audit', ['--output' => $output], true],
            ['tests/Feature/Contract/SchedulerSignatureIntegrityTest.php', [], false],
        ];

        if ((bool) $this->option('strict')) {
            $steps[] = ['tests/Feature/Schema/ModelSchemaContractTest.php', [], false];
            $steps[] = ['tests/Feature/Contract/BootstrapLocalCanonicalContractTest.php', [], false];
            $steps[] = ['sab:guard', [], false];
        }

        $results = [];

        foreach ($steps as [$command, $arguments, $advisory]) {
            $this->line("Running: {$command}");

            if ($this->isTestContract($command)) {
                $exitCode = $this->runTestContract($command);
            } else {
                $exitCode = Artisan::call($command, $arguments);
                $outputText = trim(Artisan::output());

                if ($outputText !== '') {
                    $this->line($outputText);
                }
            }

            $results[] = [
                'command' => $command,
                'exit_code' => $exitCode,
                'mode' => $advisory ? 'advisory' : 'blocking',
            ];

            if (! $advisory && $exitCode !== 0) {
                $this->error("Blocking failure at {$command} (exit={$exitCode}).");
                $this->renderSummary($results, $output);

                return $exitCode;
            }
        }

        $this->renderSummary($results, $output);

        $blockingFailures = collect($results)
            ->where('mode', 'blocking')
            ->where('exit_code', '!=', 0)
            ->count();

        return $blockingFailures > 0 ? 1 : 0;
    }

    private function renderSummary(array $results, string $auditPath): void
    {
        $rows = [];

        foreach ($results as $result) {
            $rows[] = [
                $result['command'],
                $result['mode'],
                $result['exit_code'] === 0 ? 'PASS' : 'FAIL',
                $result['exit_code'],
            ];
        }

        $this->newLine();
        $this->table(['Command', 'Mode', 'Result', 'Exit'], $rows);

        $failed = collect($results)->where('exit_code', '!=', 0)->pluck('command')->values();

        if ($failed->isEmpty()) {
            $this->info('SAB doctor result: healthy.');
        } else {
            $this->warn('SAB doctor result: issues detected in -> '.$failed->implode(', '));
        }

        $this->line("Latest audit report path: {$auditPath}");
    }

    private function isTestContract(string $command): bool
    {
        return str_starts_with($command, 'tests/') || str_ends_with($command, '.php');
    }

    private function runTestContract(string $testPath): int
    {
        $process = new Process([PHP_BINARY, 'artisan', 'test', $testPath]);
        $process->setWorkingDirectory(base_path());
        $process->setEnv([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]);
        $process->setTimeout(300);
        $process->run();

        $outputText = trim($process->getOutput());
        if ($outputText === '') {
            $outputText = trim($process->getErrorOutput());
        }

        if ($outputText !== '') {
            $this->line($outputText);
        }

        return $process->getExitCode() ?? ($process->isSuccessful() ? 0 : 1);
    }
}

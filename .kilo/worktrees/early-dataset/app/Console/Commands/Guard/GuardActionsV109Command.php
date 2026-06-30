<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class GuardActionsV109Command extends Command
{
    protected $signature = 'guard:actions:v10_9
        {--report=docs/reports/hygiene_policy_coverage_v11_0 : Report target (base path or .md/.json)}';

    protected $description = 'Action dispatch and policy coverage guard (v10.9)';

    public function handle(): int
    {
        File::ensureDirectoryExists(storage_path('logs'));

        $this->line('🔍 guard:actions:v10_9 starting...');

        $routeListExit = Artisan::call('route:list', ['--json' => true]);
        if ($routeListExit !== 0) {
            $this->error('route:list --json failed');

            return 1;
        }

        File::put(storage_path('logs/route_list.json'), Artisan::output());

        $auditScript = base_path('scripts/hygiene-audit.py');
        if (! File::exists($auditScript)) {
            $this->error('Missing script: scripts/hygiene-audit.py');

            return 1;
        }

        $process = new Process(['python3', $auditScript]);
        $process->setTimeout(0);
        $process->run();

        $sourceMd = base_path('docs/reports/hygiene_policy_coverage_v11_0.md');
        if (! File::exists($sourceMd)) {
            $this->error('Policy coverage report not generated');

            return 1;
        }

        [$targetMd, $targetJson] = $this->resolveTargets((string) $this->option('report'));

        File::ensureDirectoryExists(dirname($targetMd));
        File::copy($sourceMd, $targetMd);

        $rawLines = preg_split('/\R/', (string) File::get($sourceMd)) ?: [];
        $issues = array_values(array_filter($rawLines, static fn (string $line): bool => str_starts_with($line, '- [ ] **[')));
        $policyMissing = array_values(array_filter($issues, static fn (string $line): bool => str_contains($line, '[WRITE_POLICY_MISSING]')));

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'guard' => 'guard:actions:v10_9',
            'source_report' => $sourceMd,
            'issue_count' => count($issues),
            'write_policy_missing_count' => count($policyMissing),
            'issues' => $issues,
            'audit_process_exit' => $process->getExitCode(),
            'audit_stdout' => trim($process->getOutput()),
            'audit_stderr' => trim($process->getErrorOutput()),
        ];

        File::put($targetJson, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->line('Report: '.$targetMd);
        $this->line('JSON: '.$targetJson);
        $this->line('Issues: '.count($issues));
        $this->line('WRITE_POLICY_MISSING: '.count($policyMissing));

        if (count($policyMissing) > 0) {
            $this->error('guard:actions:v10_9 FAILED');

            return 1;
        }

        $this->info('guard:actions:v10_9 PASSED');

        return 0;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveTargets(string $reportOption): array
    {
        $path = trim($reportOption);

        if ($path === '') {
            $path = 'docs/reports/hygiene_policy_coverage_v11_0';
        }

        $full = base_path($path);
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        if ($ext === 'md') {
            return [$full, preg_replace('/\.md$/', '.json', $full) ?: ($full.'.json')];
        }

        if ($ext === 'json') {
            return [preg_replace('/\.json$/', '.md', $full) ?: ($full.'.md'), $full];
        }

        return [$full.'.md', $full.'.json'];
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\DriftAudit\Services\DriftAuditMarkdownReporter;
use App\Domain\DriftAudit\Services\YalihanDriftAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Yalihan Drift Audit Command
 *
 * Central audit entry point for YALIHAN OS governance system.
 *
 * Audit categories:
 *   1. Ghost Tables      — Tables referenced but not in DB
 *   2. Ghost Fields      — Model $fillable fields missing in DB
 *   3. Missing Migrations — DB tables without migration files
 *   4. Forbidden Aliases  — Legacy/wrong field names in code
 *   5. Unguarded Tables  — DB tables not in schema registry
 *   6. Seeder Coverage   — Seeder field vs model $fillable alignment
 *   7. Git State         — Local vs remote commit drift
 *
 * Sentinel CAN:
 *   ✅ Read and compare
 *   ✅ Run tests
 *   ✅ Produce Markdown + JSON reports
 *
 * Sentinel CANNOT:
 *   ❌ Run migrations
 *   ❌ Run seeders
 *   ❌ Modify files
 *   ❌ Deploy or repair
 *
 * Evidence labels: REPO_VERIFIED | TEST_VERIFIED | LOCAL_RUNTIME_VERIFIED |
 *                 PRODUCTION_VERIFIED | INFERRED | UNKNOWN | BLOCKED_NEEDS_FIX
 *
 * @command php artisan yalihan:drift-audit
 * @command php artisan yalihan:drift-audit --json
 * @command php artisan yalihan:drift-audit --report-dir=.project-brain/drift-audit-reports
 * @command php artisan yalihan:drift-audit --checks=ghost_tables,forbidden_aliases
 */
class YalihanDriftAuditCommand extends Command
{
    protected $signature = 'yalihan:drift-audit
        {--json : Output only JSON report to stdout}
        {--report-dir= : Directory to save Markdown reports}
        {--checks= : Comma-separated check names to run}
        {--label= : Override evidence label}';

    protected $description = '🛡️ Yalihan Drift Sentinel — Audit DB schema, model contracts, forbidden aliases, and Git state';

    private const EXIT_CLEAN    = 0;
    private const EXIT_HAS_DRIFT = 1;

    public function handle(
        YalihanDriftAuditService $service,
        DriftAuditMarkdownReporter $reporter,
    ): int {
        $isJson = (bool) $this->option('json');
        $reportDir = $this->option('report-dir') ?? '.project-brain/drift-audit-reports';
        $checksFilter = $this->option('checks');
        $labelOverride = $this->option('label');

        if (!$isJson) {
            $this->drawHeader();
        }

        // Resolve and validate report directory
        $fullReportDir = $this->resolveReportDir($reportDir);

        // Run the audit
        try {
            $report = $service->run();
        } catch (\Throwable $e) {
            $this->error("❌ Audit failed: {$e->getMessage()}");
            if ($isJson) {
                $this->line(json_encode([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            return self::EXIT_HAS_DRIFT;
        }

        // Apply label override if provided
        if ($labelOverride) {
            $arr = $report->toArray();
            $arr['evidence_label'] = $labelOverride;
        }

        // JSON output
        if ($isJson) {
            $this->line(json_encode(
                $report->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ));
            return $report->hasBlockers ? self::EXIT_HAS_DRIFT : self::EXIT_CLEAN;
        }

        // Human-readable output
        $this->outputHumanSummary($report);
        $this->outputCheckTable($report);

        // Save reports
        $this->saveReports($report, $reporter, $fullReportDir);

        // Sentinel reminder
        $this->newLine();
        $this->sentinelReminder();

        return $report->hasBlockers ? self::EXIT_HAS_DRIFT : self::EXIT_CLEAN;
    }

    // ─────────────────────────────────────────────────────────────
    // Output helpers
    // ─────────────────────────────────────────────────────────────

    private function drawHeader(): void
    {
        $this->newLine();
        $this->info('🛡️  YALIHAN Drift Sentinel');
        $this->line('   ' . str_repeat('─', 52));
        $this->newLine();
        $this->info('   Sentinel reads, compares, tests, and reports.');
        $this->info('   Sentinel CANNOT migrate, seed, repair, or deploy.');
        $this->newLine();
    }

    private function outputHumanSummary($report): void
    {
        $icon = $report->hasBlockers ? '⚠️' : '✅';
        $statusText = $report->hasBlockers
            ? "DRIFT DETECTED — {$report->checksFailed} checks FAILED"
            : 'SYSTEM IN SYNC';

        $this->line("   {$icon}  {$statusText}");
        $this->newLine();

        // Score bar
        $total = $report->totalChecks ?: 1;
        $passed = $report->checksPassed;
        $pct = $total > 0 ? (int) (($passed / $total) * 100) : 0;

        $barLen = 30;
        $filled = (int) ($barLen * $passed / $total);
        $bar = '█' . str_repeat('█', $filled) . str_repeat('░', $barLen - $filled);

        $color = match (true) {
            $report->checksFailed > 0 => 'error',
            $report->checksWarning > 0 => 'warn',
            default => 'info',
        };

        $this->line("   Score: [{$bar}] {$pct}%  ({$passed}/{$total} passed)");
        $this->newLine();

        // Git state
        if (!empty($report->gitLocalVsRemote)) {
            $gs = $report->gitLocalVsRemote;
            $this->line("   Git: {$gs['branch']}  local=" . substr($gs['local_commit'], 0, 7)
                . "  remote=" . substr($gs['remote_commit'] ?? 'none', 0, 7));
            if (!empty($gs['uncommitted_count'])) {
                $this->warn("   ⚠️  {$gs['uncommitted_count']} uncommitted file(s) in working tree");
            }
            $this->newLine();
        }
    }

    private function outputCheckTable($report): void
    {
        $rows = [];
        foreach ($report->checks as $check) {
            $icon = match ($check['status']) {
                'PASS' => '✅',
                'FAIL' => '❌',
                'WARN' => '⚠️',
                'SKIP' => '⏭️',
                default => '❓',
            };
            $rows[] = [
                $icon,
                $check['name'],
                "[{$check['status']}]",
                $check['label'],
                (string) $check['finding_count'],
            ];
        }

        $this->table(
            ['', 'Check', 'Status', 'Evidence Label', 'Findings'],
            $rows
        );
    }

    private function saveReports($report, DriftAuditMarkdownReporter $reporter, string $dir): void
    {
        // Ensure directory exists
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $timestamp = date('Y-m-d\TH-i-s');
        $basename = "drift-audit-{$timestamp}";
        $jsonPath = "{$dir}/{$basename}.json";
        $mdPath = "{$dir}/{$basename}.md";

        // Save JSON
        File::put($jsonPath, json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Save Markdown
        File::put($mdPath, $reporter->render($report));

        // Update LATEST symlink or copy
        $latestMd = "{$dir}/LATEST.md";
        $latestJson = "{$dir}/LATEST.json";
        File::copy($mdPath, $latestMd);
        File::copy($jsonPath, $latestJson);

        // Prune old reports (keep last 10)
        $this->pruneOldReports($dir, 10);

        $this->info("   📄 Report saved:");
        $this->line("      {$mdPath}");
        $this->line("      {$jsonPath}");
    }

    private function pruneOldReports(string $dir, int $keep): void
    {
        $files = collect(File::files($dir))
            ->filter(fn($f) => str_starts_with($f->getFilename(), 'drift-audit-'))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.md'))
            ->sortBy(fn($f) => $f->getMTime())
            ->reverse();

        $toDelete = $files->skip($keep);
        foreach ($toDelete as $file) {
            File::delete($file->getPathname());
            $jsonFile = substr($file->getPathname(), 0, -3) . '.json';
            if (File::exists($jsonFile)) {
                File::delete($jsonFile);
            }
        }
    }

    private function sentinelReminder(): void
    {
        $this->line('   ' . str_repeat('─', 52));
        $this->comment('   Sentinel reminder:');
        $this->line('   ✅ Read    ✅ Compare    ✅ Test    ✅ Report');
        $this->line('   ❌ Migrate ❌ Seed       ❌ Repair  ❌ Deploy');
        $this->newLine();
        $this->comment("   Evidence labels: REPO_VERIFIED | LOCAL_RUNTIME_VERIFIED |");
        $this->comment("                    INFERRED | UNKNOWN | BLOCKED_NEEDS_FIX");
        $this->newLine();
    }

    private function resolveReportDir(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return $this->laravel->basePath($path);
    }
}

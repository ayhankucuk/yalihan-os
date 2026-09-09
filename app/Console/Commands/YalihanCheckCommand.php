<?php

namespace App\Console\Commands;

use App\Support\Governance\Audit\DTO\AuditReport;
use App\Support\Governance\Audit\DTO\AuditCheckResult;
use App\Support\Governance\Audit\Enums\AuditResult;
use App\Support\Governance\Audit\Enums\AuditSeverity;
use App\Support\Governance\Audit\Enums\EvidenceLabel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Yalıhan OS Layered Audit System - Main Orchestrator.
 *
 * Usage:
 *   php artisan yalihan:check                    # Run all checks
 *   php artisan yalihan:check drift              # Run drift layer
 *   php artisan yalihan:check drift schema       # Run schema drift checks
 *   php artisan yalihan:check architecture        # Run SAB architecture checks
 *   php artisan yalihan:check runtime            # Run runtime/bekçi checks
 *   php artisan yalihan:check domain              # Run domain-specific checks
 *   php artisan yalihan:check --json             # JSON output for CI
 *   php artisan yalihan:check --strict           # Treat warnings as failures
 *
 * Exit codes:
 *   0 = pass (no blockers)
 *   1 = fail (blockers found or --strict with warnings)
 *   2 = system error
 *
 * Layer structure:
 *   ├── drift (Schema/Migration/Git/Config drift)
 *   │   ├── schema  → AuditSchemaAlignment + GhostModelDriftScan
 *   │   ├── migration → yalihan:drift-audit --checks=missing_migrations
 *   │   ├── git     → yalihan:drift-audit --checks=git_state
 *   │   └── config  → EnvDriftGuard
 *   ├── architecture (SAB guards)
 *   │   └── sab     → sab:guard
 *   ├── runtime (Bekçi health/audit)
 *   │   ├── health  → bekci:health (if exists) or governance:health-check
 *   │   └── audit   → bekci:audit
 *   └── domain (Domain-specific checks)
 *       ├── security → guard:security
 *       ├── tenant   → TenantIsolationSafetyTest concepts
 *       ├── api      → Route integrity checks
 *       └── crm      → crm:drift-scan
 *
 * NOT included (per design decision):
 *   - Repair/mutation commands (system:repair-schema-drift)
 *   - Production database writes
 *   - Migration/seed execution
 */
class YalihanCheckCommand extends Command
{
    protected $signature = 'yalihan:check
                            {layer? : Audit layer (drift|architecture|runtime|domain|all)}
                            {check? : Specific check within layer (e.g., drift:schema, drift:migration)}
                            {--json : Output as machine-readable JSON}
                            {--strict : Treat warnings as failures (CI fail-fast)}
                            {--report= : Save report to file}
                            {--label= : Override evidence label}
                            {--no-cache : Skip cache for sub-command results}
                            {--parallel : Run independent checks in parallel}
                            {--checks= : Comma-separated check names to run}';

    protected $description = 'Yalıhan OS Layered Audit System - Orchestrator for all audit commands';

    private const VERSION = '1.0.0';

    private array $results = [];
    private int $startTime = 0;

    public function handle(): int
    {
        $this->startTime = microtime(true);

        $layer = $this->argument('layer') ?? 'all';
        $check = $this->argument('check');
        $json = $this->option('json');
        $strict = $this->option('strict');

        $this->info("🛡️  Yalıhan OS Audit System v" . self::VERSION);

        // Normalize layer/check arguments
        $normalizedCheck = $this->normalizeCheck($layer, $check);
        $layersToRun = $this->resolveLayers($layer, $normalizedCheck);

        if (empty($layersToRun)) {
            $this->error("Unknown layer or check: {$layer}" . ($check ? " {$check}" : ''));
            $this->line("Available layers: drift, architecture, runtime, domain, all");
            return 2;
        }

        $this->line("Running: " . implode(', ', $layersToRun));
        $this->line("---");

        // Execute checks for each layer
        foreach ($layersToRun as $targetLayer) {
            $this->runLayer($targetLayer, $normalizedCheck);
        }

        // Generate final report
        $report = $this->generateReport($strict, $layer, $normalizedCheck);

        // Output
        if ($json) {
            $this->outputJson($report);
        } else {
            $this->outputConsole($report);
        }

        // Save report if requested
        if ($reportPath = $this->option('report')) {
            $this->saveReport($report, $reportPath);
        }

        // Return appropriate exit code
        return $this->determineExitCode($report, $strict);
    }

    /**
     * Normalize layer and check arguments.
     */
    private function normalizeCheck(string $layer, ?string $check): ?string
    {
        // If check is provided directly as "layer:check" format
        if (str_contains($layer, ':')) {
            [$layerPart, $checkPart] = explode(':', $layer, 2);
            return $checkPart;
        }

        return $check;
    }

    /**
     * Resolve which layers to run based on arguments.
     */
    private function resolveLayers(string $layer, ?string $check): array
    {
        // Check-specific checks
        $checkMap = [
            'drift:schema' => ['drift'],
            'drift:migration' => ['drift'],
            'drift:git' => ['drift'],
            'drift:config' => ['drift'],
            'architecture:sab' => ['architecture'],
            'runtime:health' => ['runtime'],
            'runtime:audit' => ['runtime'],
            'domain:security' => ['domain'],
            'domain:tenant' => ['domain'],
            'domain:api' => ['domain'],
            'domain:crm' => ['domain'],
        ];

        if ($check && isset($checkMap[$check])) {
            return $checkMap[$check];
        }

        // Layer-level checks
        return match($layer) {
            'all', '' => ['drift', 'architecture', 'runtime', 'domain'],
            'drift' => ['drift'],
            'architecture', 'sab' => ['architecture'],
            'runtime', 'bekci' => ['runtime'],
            'domain' => ['domain'],
            default => [],
        };
    }

    /**
     * Run all checks for a specific layer.
     */
    private function runLayer(string $layer, ?string $specificCheck): void
    {
        $this->line("\n📦 Layer: " . strtoupper($layer));

        $checks = match($layer) {
            'drift' => $this->getDriftChecks($specificCheck),
            'architecture' => $this->getArchitectureChecks($specificCheck),
            'runtime' => $this->getRuntimeChecks($specificCheck),
            'domain' => $this->getDomainChecks($specificCheck),
            default => [],
        };

        foreach ($checks as $checkName => $command) {
            $this->runSubCommand($checkName, $command);
        }
    }

    /**
     * Get drift layer checks.
     */
    private function getDriftChecks(?string $specificCheck): array
    {
        $checks = [
            'drift:schema' => 'system:audit-schema-alignment --json',
            'drift:ghost' => 'model:drift-scan --json',
            'drift:migration' => 'yalihan:drift-audit --json --checks=missing_migrations',
            'drift:git' => 'yalihan:drift-audit --json --checks=git_state',
            'drift:config' => 'system:env-drift-guard --json',
        ];

        if ($specificCheck && isset($checks[$specificCheck])) {
            return [$specificCheck => $checks[$specificCheck]];
        }

        // Default: run all schema-related drift checks
        if (!$specificCheck || str_starts_with($specificCheck, 'drift')) {
            return array_filter($checks, fn($_, $k) => str_starts_with($k, 'drift'), ARRAY_FILTER_USE_BOTH);
        }

        return [];
    }

    /**
     * Get architecture layer checks.
     */
    private function getArchitectureChecks(?string $specificCheck): array
    {
        if ($specificCheck === 'architecture:sab' || !$specificCheck) {
            return ['architecture:sab' => 'sab:guard --json'];
        }
        return [];
    }

    /**
     * Get runtime layer checks.
     */
    private function getRuntimeChecks(?string $specificCheck): array
    {
        $checks = [
            'runtime:health' => 'governance:health-check --json',
            'runtime:audit' => 'bekci:audit --silent-catch --naming --secret-scan',
        ];

        if ($specificCheck && isset($checks[$specificCheck])) {
            return [$specificCheck => $checks[$specificCheck]];
        }

        return $checks;
    }

    /**
     * Get domain layer checks.
     */
    private function getDomainChecks(?string $specificCheck): array
    {
        $checks = [
            'domain:security' => 'guard:security',
            'domain:crm' => 'crm:drift-scan',
            'domain:api' => 'guard:routes:v2',
        ];

        if ($specificCheck && isset($checks[$specificCheck])) {
            return [$specificCheck => $checks[$specificCheck]];
        }

        return $checks;
    }

    /**
     * Run a sub-command and capture results.
     */
    private function runSubCommand(string $checkName, string $command): void
    {
        $this->line("  🔍 {$checkName}...");

        $startTime = microtime(true);

        try {
            // Run the command and capture output
            ob_start();
            $exitCode = Artisan::call($command);
            $output = ob_get_clean();
            $duration = (int)((microtime(true) - $startTime) * 1000);

            // Parse output to determine result
            $result = $this->parseSubCommandOutput($checkName, $output, $exitCode, $duration);
            $this->results[$checkName] = $result;

            // Output status
            $icon = $result['status'] === 'pass' ? '✅' : ($result['status'] === 'fail' ? '❌' : '⚠️');
            $this->line("     {$icon} {$result['summary']} ({$duration}ms)");

        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            $this->results[$checkName] = [
                'status' => 'fail',
                'summary' => "Error: " . $e->getMessage(),
                'exit_code' => 2,
                'duration_ms' => $duration,
                'findings' => [],
            ];
            $this->line("     ❌ Error: {$e->getMessage()}");
        }
    }

    /**
     * Parse sub-command output to extract results.
     */
    private function parseSubCommandOutput(string $checkName, string $output, int $exitCode, int $duration): array
    {
        // Try to parse as JSON
        if ($json = $this->extractJson($output)) {
            return $this->parseJsonOutput($checkName, $json, $exitCode, $duration);
        }

        // Fallback to exit code analysis
        return [
            'status' => $exitCode === 0 ? 'pass' : 'fail',
            'summary' => $exitCode === 0 ? 'Passed' : 'Failed',
            'exit_code' => $exitCode,
            'duration_ms' => $duration,
            'findings' => [],
            'raw_output' => substr($output, 0, 500),
        ];
    }

    /**
     * Extract JSON from command output.
     */
    private function extractJson(string $output): ?array
    {
        // Try to find JSON at the end of output
        $lines = array_filter(explode("\n", trim($output)));
        foreach (array_reverse($lines) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '{') && $json = json_decode($line, true)) {
                return $json;
            }
        }
        return null;
    }

    /**
     * Parse JSON output from sub-commands.
     */
    private function parseJsonOutput(string $checkName, array $json, int $exitCode, int $duration): array
    {
        // Pattern A: {drift_detected: bool, ...}
        if (isset($json['drift_detected'])) {
            return [
                'status' => $json['drift_detected'] ? 'fail' : 'pass',
                'summary' => $json['drift_detected'] ? 'Drift detected' : 'No drift',
                'exit_code' => $exitCode,
                'duration_ms' => $duration,
                'findings' => $json['violations'] ?? [],
            ];
        }

        // Pattern B: {basarili: bool, ...}
        if (isset($json['basarili'])) {
            return [
                'status' => $json['basarili'] ? 'pass' : 'fail',
                'summary' => $json['ozet'] ?? ($json['basarili'] ? 'All checks passed' : 'Checks failed'),
                'exit_code' => $exitCode,
                'duration_ms' => $duration,
                'findings' => $json['checks'] ?? [],
            ];
        }

        // Pattern C: {status: 'pass'|'fail', ...}
        if (isset($json['status'])) {
            return [
                'status' => $json['status'],
                'summary' => $json['summary'] ?? $json['status'],
                'exit_code' => $exitCode,
                'duration_ms' => $duration,
                'findings' => $json['checks'] ?? $json['findings'] ?? [],
            ];
        }

        // Pattern D: {has_blockers: bool, ...}
        if (isset($json['has_blockers'])) {
            return [
                'status' => $json['has_blockers'] ? 'fail' : 'pass',
                'summary' => $json['has_blockers'] ? 'Blockers found' : 'No blockers',
                'exit_code' => $exitCode,
                'duration_ms' => $duration,
                'findings' => $json['checks'] ?? [],
            ];
        }

        // Pattern E: SabGuard with new_violations
        if (isset($json['new_violations_count']) || isset($json['baseline_violations_count'])) {
            return [
                'status' => $json['status'] ?? ($json['new_violations_count'] > 0 ? 'fail' : 'pass'),
                'summary' => "Violations: {$json['new_violations_count']} new, {$json['baseline_violations_count']} baseline",
                'exit_code' => $exitCode,
                'duration_ms' => $duration,
                'findings' => array_merge($json['new_violations'] ?? [], $json['baseline_violations'] ?? []),
            ];
        }

        // Default fallback
        return [
            'status' => $exitCode === 0 ? 'pass' : 'fail',
            'summary' => 'Completed',
            'exit_code' => $exitCode,
            'duration_ms' => $duration,
            'findings' => [],
            'raw_json' => $json,
        ];
    }

    /**
     * Generate final audit report.
     */
    private function generateReport(bool $strict, string $layer, ?string $check): AuditReport
    {
        $checks = [];
        $allFindings = [];

        foreach ($this->results as $name => $result) {
            $status = AuditResult::fromLegacy($result['status']);
            $severity = $result['status'] === 'fail' ? AuditSeverity::HIGH : AuditSeverity::INFO;

            $checks[] = AuditCheckResult::fromArray([
                'name' => $name,
                'status' => $status,
                'label' => EvidenceLabel::REPO_VERIFIED,
                'severity' => $severity,
                'summary' => $result['summary'],
                'findings' => $result['findings'] ?? [],
            ]);

            if (!empty($result['findings'])) {
                $allFindings = array_merge($allFindings, $result['findings']);
            }
        }

        $totalDuration = (int)((microtime(true) - $this->startTime) * 1000);

        $report = new AuditReport(
            command: "yalihan:check {$layer}" . ($check ? " {$check}" : ''),
            version: self::VERSION,
            checks: $checks,
            findings: $allFindings,
            evidenceLabel: $this->option('label') ?? EvidenceLabel::REPO_VERIFIED->value,
            generatedAt: date('c'),
            gitCommit: $this->getGitCommit(),
            durationMs: $totalDuration,
        );

        return $report;
    }

    /**
     * Output report as JSON.
     */
    private function outputJson(AuditReport $report): void
    {
        $this->line($report->toJson());
    }

    /**
     * Output report to console.
     */
    private function outputConsole(AuditReport $report): void
    {
        $this->line("\n" . str_repeat('═', 60));
        $this->line("AUDIT SUMMARY");
        $this->line(str_repeat('═', 60));

        $statusColor = match($report->getStatus()) {
            'pass' => 'green',
            'warn' => 'yellow',
            default => 'red',
        };

        $this->info("Status: <{$statusColor}>{$report->getStatus()}</{$statusColor}>");
        $this->line("Total checks: {$report->getTotalChecks()}");
        $this->line("  ✅ Passed: {$report->getPassed()}");
        $this->line("  ⚠️  Warnings: {$report->getWarnings()}");
        $this->line("  ❌ Failures: {$report->getFailures()}");
        $this->line("Duration: {$report->getDurationMs()}ms");
        $this->line("Evidence: {$report->getEvidenceLabel()}");
        $this->line("Git: " . ($report->getGitCommit() ?? 'N/A'));

        if (!empty($report->getFindings())) {
            $this->line("\n" . str_repeat('─', 60));
            $this->warn("FINDINGS (" . count($report->getFindings()) . ")");

            foreach (array_slice($report->getFindings(), 0, 20) as $finding) {
                $file = $finding['file'] ?? $finding['path'] ?? 'unknown';
                $line = $finding['line'] ?? '?';
                $this->line("  • {$file}:{$line}");
                if (isset($finding['message'])) {
                    $this->line("    {$finding['message']}");
                }
            }

            if (count($report->getFindings()) > 20) {
                $this->line("  ... and " . (count($report->getFindings()) - 20) . " more");
            }
        }

        $this->line("\n" . str_repeat('═', 60));
    }

    /**
     * Save report to file.
     */
    private function saveReport(AuditReport $report, string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $report->toJson());
        $this->info("Report saved: {$path}");
    }

    /**
     * Determine exit code based on report and options.
     */
    private function determineExitCode(AuditReport $report, bool $strict): int
    {
        // System error
        if ($report->getFailures() === 0 && $report->getPassed() === 0 && $report->getTotalChecks() === 0) {
            return 2;
        }

        // Strict mode: warnings become failures
        if ($strict && $report->getWarnings() > 0) {
            return 1;
        }

        // Failures always return 1
        if ($report->getFailures() > 0) {
            return 1;
        }

        // Pass
        return 0;
    }

    /**
     * Get current Git commit hash.
     */
    private function getGitCommit(): ?string
    {
        try {
            return trim(shell_exec('git rev-parse HEAD 2>/dev/null')) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

namespace App\Services\Governance;

use Illuminate\Console\Command;

/**
 * SAB Scan Formatter - Responsible for projection of governance results.
 */
class SabScanFormatter
{
    protected Command $command;
    protected string $contractVersion = '1.1.0';

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Render the human-readable console output (Phase 2).
     */
    public function renderConsole(array $allViolations, array $summary, ?array $diff = null): void
    {
        $this->command->info("🚀 Starting SAB Integrity Scan in: " . ($summary['path'] ?? 'app'));
        $this->command->info("Standard: SAB Core Constitution [ONE AUTHORITY]");

        $newViolations = array_filter($allViolations, fn($v) => !($v['is_baseline'] ?? false));
        $baselineViolations = array_filter($allViolations, fn($v) => $v['is_baseline'] ?? false);
        $blockingNewViolations = array_filter($newViolations, fn($v) => !($v['is_report_only'] ?? false));

        if (empty($newViolations) && empty($baselineViolations)) {
            $this->command->info("✨ No architectural violations found. System is SAB Compliant.");
        } else {
            if (!empty($newViolations)) {
                $this->command->error("🚨 NEW VIOLATIONS DETECTED:");
                $this->renderTable($newViolations);
            }

            if (!empty($baselineViolations)) {
                $this->command->warn("⚠ KNOWN BASELINE VIOLATIONS:");
                $this->renderTable($baselineViolations);
            }
        }

        // Delta section
        if ($diff !== null) {
            $s = $diff['summary'];
            $this->command->line("");
            $this->command->info("📊 Baseline Delta:");
            $this->command->line("  ✅ Resolved  : " . $s['resolved_count']);
            $this->command->line("  🆕 New       : " . $s['new_count']);
            $this->command->line("  🔁 Persisted : " . $s['persisted_count']);
            $this->command->line("  📦 Baseline  : " . $s['baseline_total']);
        }

        $this->command->line("");
        if (count($blockingNewViolations) > 0) {
            $this->command->error("FAIL: " . count($blockingNewViolations) . " new blocking violation(s) found.");
        } else {
            $reportOnlyCount = count($newViolations) - count($blockingNewViolations);
            $reportOnlyText = $reportOnlyCount > 0 ? " (+{$reportOnlyCount} report-only)" : "";
            $this->command->info("PASS: System compliant (with " . count($baselineViolations) . " known baseline violations){$reportOnlyText}.");
        }
    }

    /**
     * Render the machine-readable JSON envelope (Phase 1).
     */
    public function renderJson(array $allViolations, array $summary): void
    {
        $newViolations = array_filter($allViolations, fn($v) => !($v['is_baseline'] ?? false));
        $baselineViolations = array_filter($allViolations, fn($v) => $v['is_baseline'] ?? false);

        $response = [
            'ok' => count($newViolations) === 0,
            'tool' => 'bekci.scan',
            'contractVersion' => $this->contractVersion,
            'coreVersion' => 'bekci-core-v3.3.0',
            'durationMs' => $summary['duration'] ?? 0,
            'data' => [
                'summary' => [
                    'violations' => count($allViolations),
                    'new_violations' => count($newViolations),
                    'baseline_violations' => count($baselineViolations),
                    'errors' => count(array_filter($newViolations, fn($v) => in_array($v['severity'], ['HIGH', 'CRITICAL']))),
                    'warnings' => count(array_filter($newViolations, fn($v) => !in_array($v['severity'], ['HIGH', 'CRITICAL']))),
                ],
                'violations' => $allViolations
            ],
            'errors' => []
        ];

        $this->command->getOutput()->write(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Render as Markdown (Phase 4).
     */
    public function renderMarkdown(array $allViolations, array $summary, ?array $diff = null): void
    {
        $newViolations = array_filter($allViolations, fn($v) => !($v['is_baseline'] ?? false));
        $baselineViolations = array_filter($allViolations, fn($v) => $v['is_baseline'] ?? false);
        $blockingNewViolations = array_filter($newViolations, fn($v) => !($v['is_report_only'] ?? false));

        $out = "# 🛡️ SAB Integrity Scan Report\n\n";
        $out .= "- **Target Path:** `" . ($summary['path'] ?? 'app') . "`\n";
        $out .= "- **Duration:** `" . ($summary['duration'] ?? 0) . " ms`\n";

        // Delta section
        if ($diff !== null) {
            $s = $diff['summary'];
            $out .= "\n## 📊 Baseline Delta\n\n";
            $out .= "| Metric | Count |\n|---|---|\n";
            $out .= "| ✅ Resolved | {$s['resolved_count']} |\n";
            $out .= "| 🆕 New | {$s['new_count']} |\n";
            $out .= "| 🔁 Persisted | {$s['persisted_count']} |\n";
            $out .= "| 📦 Baseline Total | {$s['baseline_total']} |\n";
        }

        if (empty($newViolations) && empty($baselineViolations)) {
            $out .= "\n✨ **No architectural violations found. System is SAB Compliant.**\n";
        }

        if (!empty($newViolations)) {
            $out .= "\n## 🚨 NEW VIOLATIONS\n\n";
            $out .= "| File | Line | Rule | Severity | Message |\n";
            $out .= "|---|---|---|---|---|\n";
            foreach ($newViolations as $v) {
                $isReportOnly = ($v['is_report_only'] ?? false) ? " *(Report-Only)*" : "";
                $out .= sprintf("| `%s` | %d | %s | %s | %s |\n", $v['file'], $v['line'], $v['rule'], $v['severity'], $v['message'] . $isReportOnly);
            }
        }

        if (!empty($baselineViolations)) {
            $out .= "\n## ⚠ KNOWN BASELINE VIOLATIONS\n\n";
            $out .= "<details><summary>Click to view " . count($baselineViolations) . " baseline violations</summary>\n\n";
            $out .= "| File | Line | Rule | Severity | Message |\n";
            $out .= "|---|---|---|---|---|\n";
            foreach ($baselineViolations as $v) {
                $out .= sprintf("| `%s` | %d | %s | %s | %s |\n", $v['file'], $v['line'], $v['rule'], $v['severity'], $v['message']);
            }
            $out .= "\n</details>\n";
        }

        $out .= "\n---\n";
        if (count($blockingNewViolations) > 0) {
            $out .= "**FAIL:** " . count($blockingNewViolations) . " new blocking violation(s) found.\n";
        } else {
            $reportOnlyCount = count($newViolations) - count($blockingNewViolations);
            $reportOnlyText = $reportOnlyCount > 0 ? " (+{$reportOnlyCount} report-only)" : "";
            $out .= "**PASS:** System compliant (with " . count($baselineViolations) . " known baseline violations){$reportOnlyText}.\n";
        }

        $this->command->line($out);
    }

    private function renderTable(array $violations): void
    {
        $headers = ['File', 'Line', 'Rule', 'Severity', 'Message'];
        $rows = array_map(fn($v) => [
            $v['file'],
            $v['line'],
            $v['rule'],
            $this->formatSeverity($v['severity']),
            $v['message']
        ], $violations);

        $this->command->table($headers, $rows);
    }

    private function formatSeverity(string $severity): string
    {
        return match (strtoupper($severity)) {
            'CRITICAL' => '<error>CRITICAL</error>',
            'HIGH' => '<fg=red>HIGH</fg=red>',
            'MEDIUM' => '<comment>MEDIUM</comment>',
            default => '<info>LOW</info>',
        };
    }
}

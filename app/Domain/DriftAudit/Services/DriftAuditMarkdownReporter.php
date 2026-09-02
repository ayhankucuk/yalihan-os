<?php

declare(strict_types=1);

namespace App\Domain\DriftAudit\Services;

use App\Domain\DriftAudit\DTO\DriftAuditReport;

/**
 * Formats DriftAuditReport as Markdown for .project-brain/ storage.
 */
class DriftAuditMarkdownReporter
{
    public function render(DriftAuditReport $report): string
    {
        $lines = [];

        $lines[] = '# YALIHAN Drift Audit Report';
        $lines[] = '';
        $lines[] = '| Field | Value |';
        $lines[] = '|-------|-------|';
        $lines[] = '| **Generated** | ' . $report->generatedAt . ' |';
        $lines[] = '| **Source** | ' . $report->source . ' |';
        $lines[] = '| **Git Commit** | `' . substr($report->gitCommit, 0, 12) . '` |';
        $lines[] = '| **Evidence Label** | `' . $report->evidenceLabel . '` |';
        $lines[] = '| **Total Checks** | ' . $report->totalChecks . ' |';
        $lines[] = '| **Passed** | ' . $report->checksPassed . ' |';
        $lines[] = '| **Failed** | ' . $report->checksFailed . ' |';
        $lines[] = '| **Warnings** | ' . $report->checksWarning . ' |';
        $lines[] = '| **Has Blockers** | ' . ($report->hasBlockers ? 'YES ⚠️' : 'NO ✅') . ' |';
        $lines[] = '| **Dry Run** | ' . ($report->dryRun ? 'Yes (no writes)' : 'No') . ' |';
        $lines[] = '';
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = $report->summary;
        $lines[] = '';

        // ── Check Results ──────────────────────────────────────────
        $lines[] = '## Check Results';
        $lines[] = '';

        foreach ($report->checks as $check) {
            $icon = match ($check['status']) {
                'PASS' => '✅',
                'FAIL' => '❌',
                'WARN' => '⚠️',
                'SKIP' => '⏭️',
                default => '❓',
            };

            $lines[] = "### {$icon} {$check['name']}  `[{$check['status']}]`";
            $lines[] = '';
            $lines[] = "- **Label**: `{$check['label']}`";
            $lines[] = "- **Findings**: `{$check['finding_count']}`";
            $lines[] = "- **Summary**: {$check['summary']}";
            $lines[] = '';

            if (!empty($check['findings'])) {
                $lines[] = '| # | Severity | Subject | Description | File |';
                $lines[] = '|---|----------|---------|-------------|------|';

                foreach ($check['findings'] as $i => $f) {
                    $file = isset($f['file'])
                        ? ($f['file'] . (isset($f['line']) ? ":{$f['line']}" : ''))
                        : '—';
                    $desc = mb_substr($f['description'], 0, 80) . (mb_strlen($f['description']) > 80 ? '…' : '');
                    $lines[] = "| {$i}+1 | {$f['severity']} | `{$f['subject']}` | {$desc} | `{$file}` |";
                }
                $lines[] = '';
            }
        }

        // ── Git State ─────────────────────────────────────────────
        if (!empty($report->gitLocalVsRemote)) {
            $gs = $report->gitLocalVsRemote;
            $lines[] = '## Git State';
            $lines[] = '';
            $lines[] = '| Field | Value |';
            $lines[] = '|-------|-------|';
            $lines[] = '| **Branch** | ' . ($gs['branch'] ?: 'unknown') . ' |';
            $lines[] = '| **Local Commit** | `' . substr($gs['local_commit'] ?? '', 0, 12) . '` |';
            $lines[] = '| **Remote Commit** | `' . substr($gs['remote_commit'] ?? 'not-found', 0, 12) . '` |';
            $lines[] = '| **Ahead** | ' . ($gs['ahead'] ?: 0) . ' |';
            $lines[] = '| **Behind** | ' . ($gs['behind'] ?: 0) . ' |';
            $lines[] = '| **Uncommitted Files** | ' . ($gs['uncommitted_count'] ?: 0) . ' |';

            if (!empty($gs['uncommitted_files'])) {
                $lines[] = '';
                $lines[] = '**Uncommitted files:**';
                foreach (array_slice($gs['uncommitted_files'], 0, 10) as $f) {
                    $lines[] = '- `' . $f . '`';
                }
            }
            $lines[] = '';
        }

        // ── Ghost Tables ───────────────────────────────────────────
        if (!empty($report->ghostTables)) {
            $lines[] = '## Ghost Tables (HIGH)';
            $lines[] = '';
            $lines[] = '> Tables referenced in schema_guard/config but not found in database.';
            $lines[] = '';
            foreach ($report->ghostTables as $t) {
                $lines[] = "- `{$t}` — REPO_VERIFIED";
            }
            $lines[] = '';
        }

        // ── Missing Migrations ─────────────────────────────────────
        if (!empty($report->missingMigrations)) {
            $lines[] = '## Missing Migrations (MEDIUM)';
            $lines[] = '';
            $lines[] = '> Tables exist in DB but no migration file found.';
            $lines[] = '';
            foreach (array_slice($report->missingMigrations, 0, 20) as $t) {
                $lines[] = "- `{$t}` — REPO_VERIFIED";
            }
            if (count($report->missingMigrations) > 20) {
                $lines[] = '- … and ' . (count($report->missingMigrations) - 20) . ' more.';
            }
            $lines[] = '';
        }

        // ── Evidence Labels ─────────────────────────────────────────
        $lines[] = '## Evidence Label Reference';
        $lines[] = '';
        $lines[] = '| Label | Meaning |';
        $lines[] = '|-------|---------|';
        $lines[] = '| `REPO_VERIFIED` | Code/repo audit passed |';
        $lines[] = '| `TEST_VERIFIED` | Automated tests provide evidence |';
        $lines[] = '| `LOCAL_RUNTIME_VERIFIED` | Local DB/schema verified |';
        $lines[] = '| `PRODUCTION_VERIFIED` | Live production evidence captured |';
        $lines[] = '| `INFERRED` | Conclusion from indirect evidence |';
        $lines[] = '| `UNKNOWN` | Unable to determine |';
        $lines[] = '| `BLOCKED_NEEDS_FIX` | Blocker found; cannot proceed |';
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '> ⚠️ **One test passing does NOT prove production is correct.**';
        $lines[] = '> Sentinel CAN read, compare, test, report. Sentinel CANNOT migrate, seed, repair, or deploy.';

        return implode("\n", $lines);
    }
}

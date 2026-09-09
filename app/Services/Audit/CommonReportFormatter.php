<?php

namespace App\Services\Audit;

use App\Support\Governance\Audit\DTO\AuditReport;
use App\Support\Governance\Audit\DTO\AuditCheckResult;
use App\Support\Governance\Audit\Enums\AuditResult;
use App\Support\Governance\Audit\Enums\AuditSeverity;
use App\Support\Governance\Audit\Enums\EvidenceLabel;

/**
 * Common audit report formatter.
 *
 * Provides standardized output formatting for all audit commands.
 * Ensures consistent terminal output and JSON format across the layered audit system.
 */
class CommonReportFormatter
{
    /**
     * Format report as JSON string.
     */
    public function toJson(AuditReport $report): string
    {
        return $report->toJson();
    }

    /**
     * Format report as terminal output with colors.
     */
    public function toConsole(AuditReport $report): string
    {
        $lines = [];

        $lines[] = '';
        $lines[] = $this->divider('═');
        $lines[] = "  YALIHAN OS AUDIT REPORT";
        $lines[] = $this->divider('═');
        $lines[] = '';

        // Summary
        $lines[] = "Command:   {$report->getCommand()}";
        $lines[] = "Version:   {$report->getVersion()}";
        $lines[] = "Status:    " . $this->colorStatus($report->getStatus());
        $lines[] = "Evidence:  {$report->getEvidenceLabel()}";
        $lines[] = "Generated: {$report->getGeneratedAt()}";
        $lines[] = "Git:       " . ($report->getGitCommit() ?? 'N/A');
        $lines[] = "Duration:  {$report->getDurationMs()}ms";
        $lines[] = '';

        // Summary counts
        $lines[] = $this->divider('─');
        $lines[] = '  SUMMARY';
        $lines[] = $this->divider('─');
        $lines[] = "  Total Checks:  {$report->getTotalChecks()}";
        $lines[] = "  " . $this->iconForStatus('pass') . " Passed:     {$report->getPassed()}";
        $lines[] = "  " . $this->iconForStatus('warn') . " Warnings:   {$report->getWarnings()}";
        $lines[] = "  " . $this->iconForStatus('fail') . " Failures:   {$report->getFailures()}";
        $lines[] = '';

        // Check details
        if (!empty($report->getChecks())) {
            $lines[] = $this->divider('─');
            $lines[] = '  CHECKS';
            $lines[] = $this->divider('─');

            foreach ($report->getChecks() as $check) {
                $icon = $this->iconForStatus($check['status']);
                $severity = $check['severity'] ?? 'info';
                $lines[] = "  {$icon} [{$severity}] {$check['check']}";
                $lines[] = "      {$check['message']}";
                if ($check['finding_count'] > 0) {
                    $lines[] = "      Findings: {$check['finding_count']}";
                }
            }
            $lines[] = '';
        }

        // Findings
        if (!empty($report->getFindings())) {
            $lines[] = $this->divider('─');
            $lines[] = '  TOP FINDINGS (max 20)';
            $lines[] = $this->divider('─');

            foreach (array_slice($report->getFindings(), 0, 20) as $finding) {
                $file = $finding['file'] ?? $finding['path'] ?? 'unknown';
                $line = $finding['line'] ?? '?';
                $msg = $finding['message'] ?? '';
                $lines[] = "  • {$file}:{$line}";
                if ($msg) {
                    $lines[] = "    {$msg}";
                }
            }

            if (count($report->getFindings()) > 20) {
                $lines[] = '  ... and ' . (count($report->getFindings()) - 20) . ' more findings';
            }
            $lines[] = '';
        }

        $lines[] = $this->divider('═');

        return implode("\n", $lines);
    }

    /**
     * Format a single check result.
     */
    public function formatCheck(AuditCheckResult $check): string
    {
        $icon = $check->status->icon();
        $name = str_pad($check->name, 30);
        $severity = str_pad($check->severity->value, 10);

        return "{$icon} [{$severity}] {$name} - {$check->summary}";
    }

    /**
     * Format findings as a list.
     */
    public function formatFindings(array $findings, int $limit = 20): string
    {
        if (empty($findings)) {
            return "  (no findings)";
        }

        $lines = [];
        foreach (array_slice($findings, 0, $limit) as $finding) {
            $file = $finding['file'] ?? $finding['path'] ?? 'unknown';
            $line = $finding['line'] ?? '?';
            $msg = $finding['message'] ?? '';

            $lines[] = "  • {$file}:{$line}";
            if ($msg) {
                $lines[] = "    {$msg}";
            }
        }

        if (count($findings) > $limit) {
            $lines[] = "  ... and " . (count($findings) - $limit) . " more";
        }

        return implode("\n", $lines);
    }

    /**
     * Format GitHub Actions annotation.
     */
    public function toGithubAnnotation(array $finding): string
    {
        $file = $finding['file'] ?? $finding['path'] ?? 'unknown';
        $line = $finding['line'] ?? 1;
        $severity = $finding['severity'] ?? 'warning';
        $msg = $finding['message'] ?? 'Audit finding';

        // Map severity to GitHub annotation level
        $level = match($severity) {
            'critical', 'high' => 'error',
            'medium' => 'warning',
            default => 'notice',
        };

        return "::{$level} file={$file},line={$line}::{$msg}";
    }

    /**
     * Format findings as GitHub Actions annotations.
     */
    public function toGithubAnnotations(array $findings): string
    {
        if (empty($findings)) {
            return '';
        }

        $lines = [];
        foreach ($findings as $finding) {
            $lines[] = $this->toGithubAnnotation($finding);
        }

        return implode("\n", $lines);
    }

    /**
     * Get divider line.
     */
    private function divider(string $char): string
    {
        return str_repeat($char, 60);
    }

    /**
     * Color status for terminal.
     */
    private function colorStatus(string $status): string
    {
        return match($status) {
            'pass' => "\033[32m{$status}\033[0m",     // green
            'warn' => "\033[33m{$status}\033[0m",    // yellow
            'fail' => "\033[31m{$status}\033[0m",    // red
            default => $status,
        };
    }

    /**
     * Get icon for status.
     */
    private function iconForStatus(string $status): string
    {
        return match($status) {
            'pass' => '✅',
            'fail' => '❌',
            'warn' => '⚠️',
            'skip' => '⏭️',
            default => '🔵',
        };
    }
}

<?php

namespace App\Services\Governance\DeprecationValidator;

use Illuminate\Console\Command;

/**
 * Formats validation reports for console, JSON, or Markdown output.
 *
 * Responsible for:
 * - Console table output with color coding
 * - JSON output for CI/CD pipeline consumption
 * - Markdown report file generation
 * - PASS/FAIL icons and status formatting
 */
class ValidationReportFormatter
{
    private Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Render the report to console with formatted tables.
     *
     * @param array $report
     * @return void
     */
    public function renderConsole(array $report): void
    {
        $decision = $report['final_decision']['decision'];
        $summary = $report['summary'];

        // Header
        $this->command->newLine();
        $this->command->line('╔══════════════════════════════════════════════════════════╗');
        $this->command->line('║       🛡️  SAB Deprecation Coverage Validation Report     ║');
        $this->command->line('╚══════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        // Subject
        $this->command->line("  <fg=cyan>Subject:</> {$report['subject']}");
        $this->command->line("  <fg=cyan>Validated:</> {$report['validated_at']}");
        $this->command->line("  <fg=cyan>Strict Mode:</> " . ($report['strict_mode'] ? 'YES' : 'NO'));
        $this->command->newLine();

        // Summary
        $this->command->line('  ─── Summary ───');
        $this->command->line("  Total Sections:    <fg=white;options=bold>{$summary['total']}</>");
        $this->command->line("  Moved Full:        <fg=green>{$summary['moved_full']}</>");
        $this->command->line("  Moved Partial:     " . ($summary['moved_partial'] > 0 ? "<fg=yellow>{$summary['moved_partial']}</>" : "<fg=green>{$summary['moved_partial']}</>"));
        $this->command->line("  Archived Only:     <fg=blue>{$summary['archived_only']}</>");
        $this->command->line("  Dropped Approved:  <fg=gray>{$summary['dropped_approved']}</>");
        $this->command->line("  Missing:           " . ($summary['missing'] > 0 ? "<fg=red;options=bold>{$summary['missing']}</>" : "<fg=green>{$summary['missing']}</>"));
        $this->command->newLine();

        // Section Mapping Table
        $this->command->line('  ─── Section Mapping ───');
        $this->command->table(
            ['ID', 'Title', 'Decision', 'Status', 'Role Valid', 'Notes'],
            array_map(function ($section) {
                return [
                    $section['id'],
                    mb_substr($section['title'], 0, 35),
                    $section['decision'],
                    $this->statusIcon($section['coverage_status']),
                    $section['target_role_valid'] ? '✅' : '❌',
                    mb_substr($section['notes'], 0, 30),
                ];
            }, $report['section_mapping'])
        );

        // Archive Validation
        $this->command->newLine();
        $this->command->line('  ─── Archive Validation ───');
        $archiveVal = $report['archive_validation'];
        foreach ($archiveVal as $key => $value) {
            if ($key === 'overall') {
                continue;
            }
            $label = str_replace('_', ' ', ucfirst($key));
            $this->command->line("  {$label}: {$this->resultIcon($value)}");
        }

        // Context Isolation
        $this->command->newLine();
        $this->command->line('  ─── AI Context Isolation ───');
        $ctxIso = $report['context_isolation'];
        foreach ($ctxIso as $key => $value) {
            if ($key === 'overall') {
                continue;
            }
            $label = str_replace('_', ' ', ucfirst($key));
            $this->command->line("  {$label}: {$this->resultIcon($value)}");
        }

        // Final Decision
        $this->command->newLine();
        $this->command->line('  ═══════════════════════════════════════');
        $decisionLine = match ($decision) {
            'PASS' => "  <fg=green;options=bold>  ✅ FINAL DECISION: PASS</>",
            'PARTIAL' => "  <fg=yellow;options=bold>  ⚠️  FINAL DECISION: PARTIAL</>",
            'FAIL' => "  <fg=red;options=bold>  ❌ FINAL DECISION: FAIL</>",
            default => "  <fg=gray>  UNKNOWN</>",
        };
        $this->command->line($decisionLine);
        $this->command->line("  <fg=gray>{$report['final_decision']['reason']}</>");
        $this->command->line('  ═══════════════════════════════════════');
        $this->command->newLine();

        // Closure Criteria
        $criteria = $report['final_decision']['criteria'];
        $this->command->line('  Closure Criteria:');
        $this->command->line("    MISSING = 0:        {$this->resultIcon($criteria['missing_zero'])}");
        $this->command->line("    Archive isolation:   {$this->resultIcon($criteria['archive_isolation'])}");
        $this->command->line("    Context isolation:   {$this->resultIcon($criteria['context_isolation'])}");
        $this->command->line("    No partial moves:    {$this->resultIcon($criteria['no_partial'])}");
        $this->command->newLine();
    }

    /**
     * Render the report as JSON.
     *
     * @param array $report
     * @return void
     */
    public function renderJson(array $report): void
    {
        $this->command->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Generate a Markdown report string.
     *
     * @param array $report
     * @return string
     */
    public function generateMarkdown(array $report): string
    {
        $summary = $report['summary'];
        $decision = $report['final_decision'];
        $archiveVal = $report['archive_validation'];
        $ctxIso = $report['context_isolation'];

        $md = "# Coverage Validation Report\n\n";
        $md .= "**Subject:** `{$report['subject']}`\n";
        $md .= "**Validated:** {$report['validated_at']}\n";
        $md .= "**Strict Mode:** " . ($report['strict_mode'] ? 'YES' : 'NO') . "\n\n";
        $md .= "---\n\n";

        // Summary
        $md .= "## Summary\n\n";
        $md .= "| Metric | Value |\n|--------|-------|\n";
        $md .= "| Total Sections | {$summary['total']} |\n";
        $md .= "| Moved Full | {$summary['moved_full']} |\n";
        $md .= "| Moved Partial | {$summary['moved_partial']} |\n";
        $md .= "| Archived Only | {$summary['archived_only']} |\n";
        $md .= "| Dropped Approved | {$summary['dropped_approved']} |\n";
        $md .= "| Missing | {$summary['missing']} |\n";
        $md .= "| **Result** | **{$decision['decision']}** |\n\n";

        // Section Mapping
        $md .= "## Section Mapping\n\n";
        $md .= "| ID | Title | Decision | Status | Role Valid | Notes |\n";
        $md .= "|-----|-------|----------|--------|-----------|-------|\n";
        foreach ($report['section_mapping'] as $section) {
            $roleIcon = $section['target_role_valid'] ? '✅' : '❌';
            $md .= "| {$section['id']} | {$section['title']} | {$section['decision']} | {$section['coverage_status']} | {$roleIcon} | {$section['notes']} |\n";
        }
        $md .= "\n";

        // Archive Validation
        $md .= "## Archive Validation\n\n";
        $md .= "| Check | Status |\n|-------|--------|\n";
        foreach ($archiveVal as $key => $value) {
            if ($key === 'overall') {
                continue;
            }
            $label = str_replace('_', ' ', ucfirst($key));
            $md .= "| {$label} | {$value} |\n";
        }
        $md .= "\n";

        // Context Isolation
        $md .= "## AI Context Isolation\n\n";
        $md .= "| Check | Status |\n|-------|--------|\n";
        foreach ($ctxIso as $key => $value) {
            if ($key === 'overall') {
                continue;
            }
            $label = str_replace('_', ' ', ucfirst($key));
            $md .= "| {$label} | {$value} |\n";
        }
        $md .= "\n";

        // Final Decision
        $md .= "## Final Decision\n\n";
        $md .= "**{$decision['decision']}** — {$decision['reason']}\n\n";
        $md .= "| Criterion | Status |\n|-----------|--------|\n";
        foreach ($decision['criteria'] as $key => $value) {
            $label = str_replace('_', ' ', ucfirst($key));
            $md .= "| {$label} | {$value} |\n";
        }

        return $md;
    }

    /**
     * Get status icon for coverage status.
     */
    private function statusIcon(string $status): string
    {
        return match ($status) {
            TargetMappingValidator::STATUS_MOVED_FULL => '✅ FULL',
            TargetMappingValidator::STATUS_MOVED_PARTIAL => '⚠️ PARTIAL',
            TargetMappingValidator::STATUS_ARCHIVED_ONLY => '📦 ARCHIVE',
            TargetMappingValidator::STATUS_DROPPED_APPROVED => '🗑️ DROPPED',
            TargetMappingValidator::STATUS_MISSING => '❌ MISSING',
            default => '❓ UNKNOWN',
        };
    }

    /**
     * Get icon for PASS/FAIL/WARN result.
     */
    private function resultIcon(string $result): string
    {
        return match ($result) {
            'PASS' => '✅ PASS',
            'FAIL' => '❌ FAIL',
            'WARN' => '⚠️  WARN',
            default => '❓ ' . $result,
        };
    }
}

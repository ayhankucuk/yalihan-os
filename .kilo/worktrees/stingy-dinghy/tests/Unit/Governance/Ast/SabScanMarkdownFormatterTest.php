<?php

namespace Tests\Unit\Governance\Ast;

use PHPUnit\Framework\TestCase;
use App\Services\Governance\SabScanFormatter;

/**
 * Unit tests for SabScanFormatter::renderMarkdown().
 * Tests output shape directly — no artisan, no DB, no global scan.
 *
 * NOTE: sab:integrity-scan --format=markdown uses this formatter.
 * These tests verify the formatter contract in isolation, independent of
 * whether any global guard violations exist.
 */
class SabScanMarkdownFormatterTest extends TestCase
{
    // ──────────────────────────────────────────────────
    // renderMarkdown — basic structure
    // ──────────────────────────────────────────────────

    /** @test */
    public function it_renders_header_and_target_path(): void
    {
        $output = $this->renderMarkdown([], []);

        $this->assertStringContainsString('# 🛡️ SAB Integrity Scan Report', $output);
        $this->assertStringContainsString('**Target Path:**', $output);
    }

    /** @test */
    public function it_renders_pass_when_no_blocking_violations(): void
    {
        $violations = [$this->makeViolation(isBaseline: false, isReportOnly: true)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringContainsString('**PASS:**', $output);
    }

    /** @test */
    public function it_renders_fail_when_blocking_violations_exist(): void
    {
        $violations = [$this->makeViolation(isBaseline: false, isReportOnly: false)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringContainsString('**FAIL:**', $output);
        $this->assertStringContainsString('blocking violation', $output);
    }

    /** @test */
    public function it_renders_new_violations_table(): void
    {
        $violations = [$this->makeViolation(isBaseline: false, isReportOnly: true)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringContainsString('## 🚨 NEW VIOLATIONS', $output);
        $this->assertStringContainsString('| File | Line | Rule | Severity | Message |', $output);
        $this->assertStringContainsString('LanguageHardcodeAST', $output);
    }

    /** @test */
    public function it_marks_report_only_violations_in_table(): void
    {
        $violations = [$this->makeViolation(isBaseline: false, isReportOnly: true)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringContainsString('*(Report-Only)*', $output);
    }

    /** @test */
    public function it_does_not_mark_blocking_violations_as_report_only(): void
    {
        $violations = [$this->makeViolation(isBaseline: false, isReportOnly: false)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringNotContainsString('*(Report-Only)*', $output);
    }

    /** @test */
    public function it_renders_baseline_section_in_collapsible_details(): void
    {
        $violations = [$this->makeViolation(isBaseline: true, isReportOnly: false)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringContainsString('## ⚠ KNOWN BASELINE VIOLATIONS', $output);
        $this->assertStringContainsString('<details>', $output);
        $this->assertStringContainsString('</details>', $output);
    }

    /** @test */
    public function it_shows_compliant_when_all_violations_are_baseline(): void
    {
        $violations = [$this->makeViolation(isBaseline: true, isReportOnly: false)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringContainsString('**PASS:**', $output);
    }

    /** @test */
    public function it_includes_report_only_count_suffix_in_pass(): void
    {
        $violations = [$this->makeViolation(isBaseline: false, isReportOnly: true)];

        $output = $this->renderMarkdown($violations);

        $this->assertStringContainsString('+1 report-only', $output);
    }

    /** @test */
    public function it_renders_compliant_with_empty_violations(): void
    {
        $output = $this->renderMarkdown([]);

        $this->assertStringContainsString('No architectural violations found', $output);
    }

    // ──────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────

    private function renderMarkdown(array $violations, array $summary = []): string
    {
        $buf = '';
        $summary = array_merge(['path' => 'app', 'duration' => 0, 'legacyCount' => 0], $summary);

        $command = new class($buf) extends \Illuminate\Console\Command {
            public function __construct(private string &$buf) { parent::__construct(); }
            public function line($string, $style = null, $verbosity = null): void { $this->buf .= $string; }
            public function info($string, $verbosity = null): void { $this->buf .= $string; }
            public function error($string, $verbosity = null): void { $this->buf .= $string; }
            public function warn($string, $verbosity = null): void { $this->buf .= $string; }
        };

        (new SabScanFormatter($command))->renderMarkdown($violations, $summary);

        return $buf;
    }

    private function makeViolation(bool $isBaseline, bool $isReportOnly): array
    {
        return [
            'file'          => 'app/SomeService.php',
            'line'          => 10,
            'rule'          => 'LanguageHardcodeAST',
            'type'          => 'LanguageHardcodeAST',
            'severity'      => 'HIGH',
            'message'       => 'Hardcoded language array found.',
            'suggestion'    => '',
            'source'        => 'yalihan-bekci',
            'origin'        => 'ast_analyzer',
            'fingerprint'   => 'test_fp_' . uniqid(),
            'is_baseline'   => $isBaseline,
            'is_report_only' => $isReportOnly,
        ];
    }
}

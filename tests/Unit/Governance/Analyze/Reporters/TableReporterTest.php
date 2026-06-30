<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze\Reporters;

use App\Support\Governance\Analyze\AnalysisResult;
use App\Support\Governance\Analyze\Reporters\TableReporter;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Governance\Analyze\Support\AnalyzeTestFactory;

class TableReporterTest extends TestCase
{
    public function test_table_reporter_contains_critical_headings_and_lines(): void
    {
        $result = new AnalysisResult(
            findings: [AnalyzeTestFactory::finding('TABLE-1')],
            generatedAt: '2026-04-21T12:00:00+00:00',
        );

        $text = (new TableReporter())->render($result);

        // Keep assertions resilient against cosmetic formatting changes.
        $this->assertStringContainsString('Governance Analyze Report', $text);
        $this->assertStringContainsString('Findings: total=1', $text);
        $this->assertStringContainsString('[HIGH] TABLE-1', $text);
        $this->assertStringContainsString('summary:', $text);
        $this->assertStringContainsString('safe_action:', $text);
        $this->assertStringContainsString('detector:', $text);
    }

    public function test_table_reporter_handles_empty_result_set(): void
    {
        $text = (new TableReporter())->render(new AnalysisResult(findings: []));

        $this->assertStringContainsString('Governance Analyze Report', $text);
        $this->assertStringContainsString('(no findings)', $text);
    }

    public function test_table_reporter_renders_evidence_lines(): void
    {
        $result = new AnalysisResult(findings: [AnalyzeTestFactory::finding('TABLE-EV')]);

        $text = (new TableReporter())->render($result);
        $this->assertStringContainsString('evidence: tests/fixture.php:12', $text);
    }
}

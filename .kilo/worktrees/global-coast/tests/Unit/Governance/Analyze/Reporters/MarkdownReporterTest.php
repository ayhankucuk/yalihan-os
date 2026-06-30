<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze\Reporters;

use App\Support\Governance\Analyze\AnalysisResult;
use App\Support\Governance\Analyze\Enums\Confidence;
use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use App\Support\Governance\Analyze\Evidence;
use App\Support\Governance\Analyze\Finding;
use App\Support\Governance\Analyze\Reporters\MarkdownReporter;
use Tests\TestCase;

/**
 * @covers \App\Support\Governance\Analyze\Reporters\MarkdownReporter
 */
final class MarkdownReporterTest extends TestCase
{
    private MarkdownReporter $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new MarkdownReporter();
    }

    public function test_render_empty_result(): void
    {
        $result = new AnalysisResult([]);

        $output = $this->reporter->render($result);

        $this->assertStringContainsString('# Governance Analysis Report', $output);
        $this->assertStringContainsString('**Total Findings:** 0', $output);
        $this->assertStringContainsString('✅ No issues detected.', $output);
    }

    public function test_render_with_findings(): void
    {
        $findings = [
            new Finding(
                id: 'test-1',
                title: 'Test Finding',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'model',
                summary: 'This is a test finding',
                evidence: [
                    new Evidence(
                        file: 'app/Models/User.php',
                        line: 42,
                        snippet: '$table->string("ghost_column");'
                    ),
                ],
                safeAction: 'Remove ghost column',
                detector: 'App\Support\Governance\Analyze\Detectors\TestDetector'
            ),
        ];

        $result = new AnalysisResult($findings);

        $output = $this->reporter->render($result);

        $this->assertStringContainsString('# Governance Analysis Report', $output);
        $this->assertStringContainsString('**Total Findings:** 1', $output);
        $this->assertStringContainsString('## Summary', $output);
        $this->assertStringContainsString('| Risk Level | Count |', $output);
        $this->assertStringContainsString('🔴 HIGH', $output);
        $this->assertStringContainsString('## Findings', $output);
        $this->assertStringContainsString('### Test Detector', $output);
        $this->assertStringContainsString('Test Finding', $output);
        $this->assertStringContainsString('This is a test finding', $output);
        $this->assertStringContainsString('app/Models/User.php:42', $output);
        $this->assertStringContainsString('```php', $output);
    }

    public function test_render_groups_by_detector(): void
    {
        $findings = [
            new Finding(
                id: 'a1',
                title: 'Finding A1',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'model',
                summary: 'Message A1',
                evidence: [],
                safeAction: 'Fix A1',
                detector: 'App\Support\Governance\Analyze\Detectors\DetectorA'
            ),
            new Finding(
                id: 'b1',
                title: 'Finding B1',
                tur: FindingType::AUTHORITY_CONFLICT,
                risk: RiskLevel::MEDIUM,
                confidence: Confidence::MEDIUM,
                layer: 'service',
                summary: 'Message B1',
                evidence: [],
                safeAction: 'Fix B1',
                detector: 'App\Support\Governance\Analyze\Detectors\DetectorB'
            ),
            new Finding(
                id: 'a2',
                title: 'Finding A2',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::LOW,
                confidence: Confidence::HIGH,
                layer: 'model',
                summary: 'Message A2',
                evidence: [],
                safeAction: 'Fix A2',
                detector: 'App\Support\Governance\Analyze\Detectors\DetectorA'
            ),
        ];

        $result = new AnalysisResult($findings);

        $output = $this->reporter->render($result);

        // Should have both detector sections
        $this->assertStringContainsString('### Detector A', $output);
        $this->assertStringContainsString('### Detector B', $output);

        // Should have all findings
        $this->assertStringContainsString('Finding A1', $output);
        $this->assertStringContainsString('Finding A2', $output);
        $this->assertStringContainsString('Finding B1', $output);
    }

    public function test_render_summary_counts_by_risk(): void
    {
        $findings = [
            new Finding(
                id: 'h1',
                title: 'High 1',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'model',
                summary: 'msg',
                evidence: [],
                safeAction: 'fix',
                detector: 'Test'
            ),
            new Finding(
                id: 'h2',
                title: 'High 2',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'model',
                summary: 'msg',
                evidence: [],
                safeAction: 'fix',
                detector: 'Test'
            ),
            new Finding(
                id: 'm1',
                title: 'Medium 1',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::MEDIUM,
                confidence: Confidence::MEDIUM,
                layer: 'model',
                summary: 'msg',
                evidence: [],
                safeAction: 'fix',
                detector: 'Test'
            ),
            new Finding(
                id: 'l1',
                title: 'Low 1',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::LOW,
                confidence: Confidence::LOW,
                layer: 'model',
                summary: 'msg',
                evidence: [],
                safeAction: 'fix',
                detector: 'Test'
            ),
        ];

        $result = new AnalysisResult($findings);

        $output = $this->reporter->render($result);

        // Check summary table has correct counts
        $this->assertMatchesRegularExpression('/🔴 HIGH\s+\|\s+2/', $output);
        $this->assertMatchesRegularExpression('/🟡 MEDIUM\s+\|\s+1/', $output);
        $this->assertMatchesRegularExpression('/🟢 LOW\s+\|\s+1/', $output);
    }

    public function test_render_is_deterministic(): void
    {
        $findings = [
            new Finding(
                id: 'b1',
                title: 'Finding B',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'model',
                summary: 'Message B',
                evidence: [],
                safeAction: 'Fix B',
                detector: 'DetectorB'
            ),
            new Finding(
                id: 'a1',
                title: 'Finding A',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::MEDIUM,
                confidence: Confidence::MEDIUM,
                layer: 'model',
                summary: 'Message A',
                evidence: [],
                safeAction: 'Fix A',
                detector: 'DetectorA'
            ),
        ];

        $result = new AnalysisResult($findings);

        $output1 = $this->reporter->render($result);
        $output2 = $this->reporter->render($result);
        $output3 = $this->reporter->render($result);

        // Remove timestamp line for comparison
        $normalized1 = preg_replace('/\*\*Generated:\*\* .+/', '', $output1);
        $normalized2 = preg_replace('/\*\*Generated:\*\* .+/', '', $output2);
        $normalized3 = preg_replace('/\*\*Generated:\*\* .+/', '', $output3);

        $this->assertEquals($normalized1, $normalized2);
        $this->assertEquals($normalized1, $normalized3);
    }

    public function test_render_formats_detector_names(): void
    {
        $findings = [
            new Finding(
                id: 't1',
                title: 'Test',
                tur: FindingType::CONTEXT7_VIOLATION,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'model',
                summary: 'msg',
                evidence: [],
                safeAction: 'fix',
                detector: 'App\Support\Governance\Analyze\Detectors\Context7ForbiddenFieldDetector'
            ),
        ];

        $result = new AnalysisResult($findings);

        $output = $this->reporter->render($result);

        // CamelCase should be split with spaces
        $this->assertStringContainsString('### Context7 Forbidden Field Detector', $output);
    }
}

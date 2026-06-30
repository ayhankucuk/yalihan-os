<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze\Reporters;

use App\Support\Governance\Analyze\AnalysisResult;
use App\Support\Governance\Analyze\Reporters\JsonReporter;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Governance\Analyze\Support\AnalyzeTestFactory;

class JsonReporterTest extends TestCase
{
    public function test_json_reporter_outputs_required_top_level_fields(): void
    {
        $result = new AnalysisResult(
            findings: [AnalyzeTestFactory::finding('JSON-1')],
            repoState: ['repo_root' => '/tmp/repo'],
            generatedAt: '2026-04-21T12:00:00+00:00',
        );

        $decoded = json_decode((new JsonReporter())->render($result), true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('tool', $decoded);
        $this->assertArrayHasKey('version', $decoded);
        $this->assertArrayHasKey('generated_at', $decoded);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('findings', $decoded);
        $this->assertSame('governance:analyze', $decoded['tool']);
    }

    public function test_json_reporter_includes_required_finding_shape(): void
    {
        $result = new AnalysisResult(findings: [AnalyzeTestFactory::finding('JSON-F')]);
        $finding = json_decode((new JsonReporter())->render($result), true)['findings'][0];

        foreach (['id', 'tur', 'risk', 'durum', 'summary', 'safe_action', 'autofix'] as $key) {
            $this->assertArrayHasKey($key, $finding);
        }
        $this->assertFalse($finding['autofix']);
    }

    public function test_json_reporter_handles_empty_findings_set(): void
    {
        $decoded = json_decode((new JsonReporter())->render(new AnalysisResult(findings: [])), true);

        $this->assertSame(0, $decoded['summary']['findings_total']);
        $this->assertSame([], $decoded['findings']);
    }
}

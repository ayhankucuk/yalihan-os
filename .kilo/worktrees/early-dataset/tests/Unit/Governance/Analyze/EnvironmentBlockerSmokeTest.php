<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use Tests\TestCase;

/**
 * Pack-T4.INT — Integration / smoke tests for governance:analyze.
 *
 * These tests run the REAL artisan command against the REAL codebase.
 * They validate shape and contract invariants — NOT specific finding counts,
 * since the real codebase evolves over time.
 *
 * @group smoke
 */
class EnvironmentBlockerSmokeTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = sys_get_temp_dir() . '/h7-smoke-' . uniqid('', true) . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // T4.INT.1 — Command exits 0 (advisory-only, never fails on findings)
    // ------------------------------------------------------------------

    public function test_smoke_command_exits_zero(): void
    {
        $this->artisan('governance:analyze', [
            '--format' => 'json',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);
    }

    // ------------------------------------------------------------------
    // T4.INT.2 — JSON output has the correct top-level contract keys
    // ------------------------------------------------------------------

    public function test_smoke_json_output_matches_contract_shape(): void
    {
        $this->artisan('governance:analyze', [
            '--format' => 'json',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $this->assertFileExists($this->outputPath);

        $raw = file_get_contents($this->outputPath);
        $this->assertNotFalse($raw, 'Output file must be readable');
        $this->assertJson($raw, 'Output must be valid JSON');

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded);

        // Top-level contract keys (from AnalysisResult::toArray())
        foreach (['tool', 'version', 'generated_at', 'summary', 'repo_state', 'findings'] as $key) {
            $this->assertArrayHasKey($key, $decoded, "Top-level key '{$key}' must be present");
        }

        // Summary sub-keys
        $summary = $decoded['summary'];
        foreach (['findings_total', 'high', 'medium', 'low', 'env_blockers'] as $sKey) {
            $this->assertArrayHasKey($sKey, $summary, "summary.{$sKey} must be present");
        }

        // findings is an array
        $this->assertIsArray($decoded['findings']);
    }

    // ------------------------------------------------------------------
    // T4.INT.3 — Every finding has required fields and autofix=false
    // ------------------------------------------------------------------

    public function test_smoke_all_findings_have_required_fields_and_no_autofix(): void
    {
        $this->artisan('governance:analyze', [
            '--format' => 'json',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $decoded = json_decode((string) file_get_contents($this->outputPath), true);
        $findings = $decoded['findings'] ?? [];

        // Skip if no findings (valid state — codebase may be clean)
        if (count($findings) === 0) {
            $this->markTestSkipped('No findings in real codebase — shape assertions skipped');
        }

        foreach ($findings as $i => $finding) {
            $ctx = "findings[{$i}]";
            $this->assertArrayHasKey('id', $finding, "{$ctx}.id must exist");
            $this->assertArrayHasKey('detector', $finding, "{$ctx}.detector must exist");
            $this->assertArrayHasKey('autofix', $finding, "{$ctx}.autofix must exist");
            // Advisory-only invariant: no finding may ever be autofixable
            $this->assertFalse($finding['autofix'], "{$ctx}.autofix must always be false (advisory-only)");
        }
    }

    // ------------------------------------------------------------------
    // T4.INT.4 — env_blockers summary counter matches env-blocker findings
    // ------------------------------------------------------------------

    public function test_smoke_env_blockers_summary_matches_finding_count(): void
    {
        $this->artisan('governance:analyze', [
            '--format' => 'json',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $decoded = json_decode((string) file_get_contents($this->outputPath), true);

        $envBlockerFindings = array_filter(
            $decoded['findings'] ?? [],
            static fn (array $f): bool => ($f['detector'] ?? '') === 'env-blocker'
        );

        $this->assertSame(
            count($envBlockerFindings),
            $decoded['summary']['env_blockers'],
            'summary.env_blockers must equal the number of env-blocker findings'
        );
    }
}

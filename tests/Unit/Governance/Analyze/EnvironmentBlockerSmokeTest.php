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
 * PERFORMANCE FIX (R002): Single command execution shared across all 4 tests.
 * Before: 4 × ~19s = ~76s. After: 1 × ~19s = ~19s.
 *
 * @group smoke
 */
class EnvironmentBlockerSmokeTest extends TestCase
{
    /** @var string|null Shared output path — single file for all tests in this class */
    private static ?string $sharedOutputPath = null;

    /** @var array|null Cached decoded JSON result */
    private static ?array $cachedResult = null;

    /** @var bool Flag to track if command already ran */
    private static bool $commandRan = false;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Run governance:analyze ONCE, share result across all tests.
     * Thread-safe: uses static properties so only one process executes the command.
     */
    private function runGovernanceAnalyzeOnce(): array
    {
        if (self::$commandRan) {
            // Command already ran in this process — return cached result
            return self::$cachedResult ?? [];
        }

        // First test — run the command once
        $outputPath = sys_get_temp_dir() . '/h7-smoke-shared-' . getmypid() . '.json';

        $this->artisan('governance:analyze', [
            '--format' => 'json',
            '--output' => $outputPath,
        ])->assertExitCode(0);

        self::$sharedOutputPath = $outputPath;
        self::$commandRan = true;

        $raw = file_get_contents($outputPath);
        self::$cachedResult = json_decode($raw, true) ?? [];

        return self::$cachedResult;
    }

    // ------------------------------------------------------------------
    // T4.INT.1 — Command exits 0 (advisory-only, never fails on findings)
    // ------------------------------------------------------------------

    public function test_smoke_command_exits_zero(): void
    {
        // T4.INT.1: Command must exit with code 0 (advisory-only, never fails)
        $outputPath = sys_get_temp_dir() . '/h7-smoke-shared-' . getmypid() . '.json';

        $this->artisan('governance:analyze', [
            '--format' => 'json',
            '--output' => $outputPath,
        ])->assertExitCode(0);

        self::$sharedOutputPath = $outputPath;
        self::$commandRan = true;

        $raw = file_get_contents($outputPath);
        self::$cachedResult = json_decode($raw, true) ?? [];
    }

    // ------------------------------------------------------------------
    // T4.INT.2 — JSON output has the correct top-level contract keys
    // ------------------------------------------------------------------

    public function test_smoke_json_output_matches_contract_shape(): void
    {
        $decoded = $this->runGovernanceAnalyzeOnce();

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
        $decoded = $this->runGovernanceAnalyzeOnce();
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
        $decoded = $this->runGovernanceAnalyzeOnce();

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

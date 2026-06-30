<?php

namespace Tests\Unit\Governance;

use Tests\TestCase;
use App\Services\Governance\BaselineDiffService;

/**
 * Unit tests for BaselineDiffService.
 * Uses real baseline file for integration confidence, but core logic
 * is also tested with synthetic fixtures for determinism.
 */
class BaselineDiffServiceTest extends TestCase
{
    // ──────────────────────────────────────────────────────
    // Core diff logic tests (synthetic — no file I/O)
    // ──────────────────────────────────────────────────────

    /** @test */
    public function diff_identifies_new_violations(): void
    {
        $service = $this->makeServiceWithFingerprints(['fp_a', 'fp_b']);

        $current = [
            $this->makeViolation('fp_c'), // not in baseline → new
            $this->makeViolation('fp_a'), // in baseline → persisted
        ];

        $result = $service->diff($current);

        $this->assertCount(1, $result['new']);
        $this->assertEquals('fp_c', $result['new'][0]['fingerprint']);
    }

    /** @test */
    public function diff_identifies_resolved_violations(): void
    {
        $service = $this->makeServiceWithFingerprints(['fp_a', 'fp_b', 'fp_c']);

        $current = [
            $this->makeViolation('fp_a'), // still there
            // fp_b and fp_c are missing → resolved
        ];

        $result = $service->diff($current);

        $this->assertCount(2, $result['resolved']);
        $resolvedFps = array_column($result['resolved'], 'fingerprint');
        $this->assertContains('fp_b', $resolvedFps);
        $this->assertContains('fp_c', $resolvedFps);
    }

    /** @test */
    public function diff_identifies_persisted_violations(): void
    {
        $service = $this->makeServiceWithFingerprints(['fp_a', 'fp_b']);

        $current = [
            $this->makeViolation('fp_a'),
            $this->makeViolation('fp_b'),
        ];

        $result = $service->diff($current);

        $this->assertCount(2, $result['persisted']);
        $this->assertCount(0, $result['new']);
        $this->assertCount(0, $result['resolved']);
    }

    /** @test */
    public function diff_returns_correct_summary_counts(): void
    {
        $service = $this->makeServiceWithFingerprints(['fp_a', 'fp_b', 'fp_c']);

        $current = [
            $this->makeViolation('fp_a'),   // persisted
            $this->makeViolation('fp_new'), // new
            // fp_b, fp_c → resolved
        ];

        $result = $service->diff($current);
        $summary = $result['summary'];

        $this->assertEquals(2, $summary['resolved_count']);
        $this->assertEquals(1, $summary['new_count']);
        $this->assertEquals(1, $summary['persisted_count']);
        $this->assertEquals(3, $summary['baseline_total']);
    }

    /** @test */
    public function diff_with_empty_current_marks_all_resolved(): void
    {
        $service = $this->makeServiceWithFingerprints(['fp_a', 'fp_b']);

        $result = $service->diff([]);

        $this->assertEquals(2, $result['summary']['resolved_count']);
        $this->assertEquals(0, $result['summary']['new_count']);
    }

    /** @test */
    public function diff_with_empty_baseline_marks_all_new(): void
    {
        $service = $this->makeServiceWithFingerprints([]);

        $current = [
            $this->makeViolation('fp_x'),
            $this->makeViolation('fp_y'),
        ];

        $result = $service->diff($current);

        $this->assertEquals(0, $result['summary']['resolved_count']);
        $this->assertEquals(2, $result['summary']['new_count']);
    }

    /** @test */
    public function diff_handles_duplicate_fingerprints_correctly(): void
    {
        // 3 occurrences of fp_dup in baseline
        $service = $this->makeServiceWithFingerprints(['fp_dup', 'fp_dup', 'fp_dup']);

        // 2 occurrences of fp_dup in current scan (1 resolved)
        $current = [
            $this->makeViolation('fp_dup'),
            $this->makeViolation('fp_dup'),
        ];

        $result = $service->diff($current);
        $summary = $result['summary'];

        $this->assertEquals(1, $summary['resolved_count']);
        $this->assertEquals(0, $summary['new_count']);
        $this->assertEquals(2, $summary['persisted_count']);
        $this->assertEquals(3, $summary['baseline_total']);
    }

    /** @test */
    public function is_in_baseline_returns_correct_result(): void
    {
        $service = $this->makeServiceWithFingerprints(['fp_exists']);

        $this->assertTrue($service->isInBaseline('fp_exists'));
        $this->assertFalse($service->isInBaseline('fp_missing'));
    }

    /** @test */
    public function get_baseline_fingerprint_count_returns_correct_total(): void
    {
        $service = $this->makeServiceWithFingerprints(['fp_1', 'fp_2', 'fp_3']);

        $this->assertEquals(3, $service->getBaselineFingerprintCount());
    }

    // ──────────────────────────────────────────────────────
    // Real baseline integration test
    // ──────────────────────────────────────────────────────

    /** @test */
    public function it_loads_real_baseline_without_errors(): void
    {
        // Verifies the actual .sab/sab-baseline.json can be loaded
        $service = new BaselineDiffService();

        $this->assertGreaterThan(0, $service->getBaselineFingerprintCount());
    }

    // ──────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────

    /**
     * Create a BaselineDiffService pre-loaded with specific fingerprints (no file I/O).
     */
    private function makeServiceWithFingerprints(array $fingerprints): BaselineDiffService
    {
        $service = new class extends BaselineDiffService {
            public function __construct() {} // skip file loading

            public function seedFingerprints(array $fps): void
            {
                foreach ($fps as $fp) {
                    $this->baselineFingerprints[$fp][] = [
                        'file'    => 'Services/Test.php',
                        'line'    => 1,
                        'type'    => 'test',
                        'message' => 'test',
                    ];
                }
            }

            // Expose protected property for testing
            protected array $baselineFingerprints = [];
        };

        $service->seedFingerprints($fingerprints);

        return $service;
    }

    private function makeViolation(string $fingerprint): array
    {
        return [
            'file'          => 'app/Services/Test.php',
            'line'          => 1,
            'rule'          => 'TestRule',
            'type'          => 'TestRule',
            'severity'      => 'HIGH',
            'message'       => 'Test violation',
            'fingerprint'   => $fingerprint,
            'is_baseline'   => false,
            'is_report_only' => false,
        ];
    }
}

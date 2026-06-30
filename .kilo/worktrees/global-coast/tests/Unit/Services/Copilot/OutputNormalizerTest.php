<?php

namespace Tests\Unit\Services\Copilot;

use Tests\TestCase;
use App\Services\AI\Copilot\Support\OutputNormalizer;

class OutputNormalizerTest extends TestCase
{
    private OutputNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new OutputNormalizer();
    }

    // ─── Legacy decision-level status mapping ────────────────────

    public function test_it_maps_decision_status_safe_to_proceed(): void
    {
        $input = [
            'stage' => 'verify',
            'status' => 'safe', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertArrayNotHasKey('status', $result); // context7-ignore: asserting field removal
        $this->assertSame('proceed', $result['decision']['action']);
        $this->assertSame('Mapped from legacy status field', $result['decision']['reason']);
    }

    public function test_it_maps_decision_status_warning_to_caution(): void
    {
        $input = [
            'stage' => 'audit',
            'status' => 'warning', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame('proceed_with_caution', $result['decision']['action']);
    }

    public function test_it_maps_decision_status_unsafe_to_block(): void
    {
        $input = [
            'stage' => 'audit',
            'status' => 'unsafe', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame('block', $result['decision']['action']);
    }

    public function test_it_maps_decision_status_blocked_to_block(): void
    {
        $input = [
            'stage' => 'audit',
            'status' => 'blocked', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame('block', $result['decision']['action']);
    }

    // ─── Operational status → meta (NOT decision) ───────────────

    public function test_it_moves_operational_success_to_meta(): void
    {
        $input = [
            'stage' => 'verify',
            'status' => 'success', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => ['action' => 'proceed', 'reason' => 'explicit'],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertArrayNotHasKey('status', $result); // context7-ignore: asserting removal
        $this->assertSame('success', $result['meta']['original_status']); // context7-ignore: asserting meta field
        // Decision preserved, not overwritten
        $this->assertSame('proceed', $result['decision']['action']);
        $this->assertSame('explicit', $result['decision']['reason']);
    }

    public function test_it_moves_operational_ok_to_meta(): void
    {
        $input = [
            'stage' => 'verify',
            'status' => 'ok', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => ['action' => 'proceed', 'reason' => 'explicit'],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame('ok', $result['meta']['original_status']); // context7-ignore: asserting meta field
    }

    public function test_it_moves_operational_failed_to_meta(): void
    {
        $input = [
            'stage' => 'verify',
            'status' => 'failed', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => ['action' => 'block', 'reason' => 'explicit'],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame('failed', $result['meta']['original_status']); // context7-ignore: asserting meta field
    }

    public function test_operational_status_without_decision_goes_to_meta(): void
    {
        $input = [
            'stage' => 'verify',
            'status' => 'passed', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
        ];

        $result = $this->normalizer->normalize($input);

        // Operational status → meta only, no decision created
        $this->assertSame('passed', $result['meta']['original_status']); // context7-ignore: asserting meta field
        $this->assertArrayNotHasKey('decision', $result);
    }

    // ─── Unknown status → block ─────────────────────────────────

    public function test_it_maps_unknown_status_to_block(): void
    {
        $input = [
            'stage' => 'audit',
            'status' => 'something_random', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame('block', $result['decision']['action']);
        $this->assertStringContainsString('Unknown', $result['decision']['reason']);
    }

    // ─── Existing decision never overwritten ─────────────────────

    public function test_it_does_not_overwrite_existing_decision_with_status(): void
    {
        $input = [
            'stage' => 'verify',
            'status' => 'safe', // context7-ignore: intentional legacy field for test
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => [
                'action' => 'block',
                'reason' => 'Explicit block from agent.',
            ],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertArrayNotHasKey('status', $result); // context7-ignore: asserting removal
        $this->assertSame('block', $result['decision']['action']);
        $this->assertSame('Explicit block from agent.', $result['decision']['reason']);
    }

    // ─── Stage normalization ─────────────────────────────────────

    public function test_it_normalizes_stage_aliases(): void
    {
        $cases = [
            'analysis' => 'audit',
            'analyze' => 'audit',
            'review' => 'audit',
            'patch' => 'fix',
            'apply' => 'execution',
            'check' => 'verify',
            'validate' => 'verify',
            'governance' => 'govern',
        ];

        foreach ($cases as $alias => $canonical) {
            $normalizer = new OutputNormalizer();
            $input = [
                'stage' => $alias,
                'findings' => [],
                'fixes' => [],
                'execution' => [],
                'verification' => [],
                'decision' => ['action' => 'proceed', 'reason' => 'test'],
            ];

            $result = $normalizer->normalize($input);

            $this->assertSame($canonical, $result['stage'], "Stage alias '{$alias}' should normalize to '{$canonical}'");
        }
    }

    public function test_it_preserves_canonical_stages(): void
    {
        foreach (['audit', 'fix', 'execution', 'verify', 'govern'] as $stage) {
            $normalizer = new OutputNormalizer();
            $input = [
                'stage' => $stage,
                'findings' => [],
                'fixes' => [],
                'execution' => [],
                'verification' => [],
                'decision' => ['action' => 'proceed', 'reason' => 'test'],
            ];

            $result = $normalizer->normalize($input);

            $this->assertSame($stage, $result['stage']);
        }
    }

    public function test_unknown_stage_falls_back_to_govern(): void
    {
        $input = [
            'stage' => 'banana',
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => ['action' => 'proceed', 'reason' => 'test'],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame('govern', $result['stage']);
        $this->assertContains("Unknown stage 'banana' normalized to 'govern'", $result['warnings']);
    }

    // ─── Strict mode ─────────────────────────────────────────────

    public function test_strict_mode_does_not_auto_fill_arrays(): void
    {
        $normalizer = new OutputNormalizer(OutputNormalizer::MODE_STRICT);

        $input = [
            'stage' => 'audit',
            'decision' => ['action' => 'proceed', 'reason' => 'test'],
        ];

        $result = $normalizer->normalize($input);

        $this->assertArrayNotHasKey('findings', $result);
        $this->assertArrayNotHasKey('fixes', $result);
        $this->assertArrayNotHasKey('execution', $result);
        $this->assertArrayNotHasKey('verification', $result);
    }

    public function test_tolerant_mode_fills_missing_arrays(): void
    {
        $normalizer = new OutputNormalizer(OutputNormalizer::MODE_TOLERANT);

        $input = [
            'stage' => 'audit',
            'decision' => ['action' => 'proceed', 'reason' => 'test'],
        ];

        $result = $normalizer->normalize($input);

        $this->assertSame([], $result['findings']);
        $this->assertSame([], $result['fixes']);
        $this->assertSame([], $result['execution']);
        $this->assertSame([], $result['verification']);
    }

    public function test_tolerant_does_not_overwrite_existing_arrays(): void
    {
        $finding = [
            'id' => 'F1',
            'title' => 'Test',
            'classification' => 'HIGH',
            'type' => 'data_mismatch',
            'evidence' => ['DB check'],
        ];

        $input = [
            'stage' => 'audit',
            'findings' => [$finding],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => ['action' => 'proceed', 'reason' => 'test'],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertCount(1, $result['findings']);
        $this->assertSame('F1', $result['findings'][0]['id']);
    }

    // ─── Normalization meta tracking ─────────────────────────────

    public function test_it_tracks_normalization_changes_in_meta(): void
    {
        $input = [
            'stage' => 'review',
            'status' => 'safe', // context7-ignore: intentional legacy field for test
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertTrue($result['meta']['normalization']['applied']);
        $this->assertSame('tolerant', $result['meta']['normalization']['mode']);
        $this->assertNotEmpty($result['meta']['normalization']['changes']);
    }

    public function test_clean_payload_has_no_normalization_meta(): void
    {
        $input = [
            'stage' => 'verify',
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => ['action' => 'proceed', 'reason' => 'No issues.'],
        ];

        $result = $this->normalizer->normalize($input);

        // No changes applied → no normalization meta injected
        $this->assertArrayNotHasKey('meta', $result);
    }

    public function test_get_last_changes_returns_applied_changes(): void
    {
        $input = [
            'stage' => 'analysis',
            'status' => 'unsafe', // context7-ignore: intentional legacy field for test
        ];

        $this->normalizer->normalize($input);
        $changes = $this->normalizer->getLastChanges();

        $this->assertNotEmpty($changes);
        $this->assertTrue(
            collect($changes)->contains(fn ($c) => str_contains($c, 'stage:analysis'))
        );
    }

    // ─── No-op passthrough ───────────────────────────────────────

    public function test_it_passes_through_clean_payload(): void
    {
        $input = [
            'stage' => 'verify',
            'summary' => 'All good.',
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => ['action' => 'proceed', 'reason' => 'No issues.'],
            'warnings' => [],
            'meta' => ['debug' => ['agent' => 'test', 'confidence' => 0.95]],
        ];

        $result = $this->normalizer->normalize($input);

        $this->assertSame($input, $result);
    }
}

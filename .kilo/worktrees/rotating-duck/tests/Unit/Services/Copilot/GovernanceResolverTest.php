<?php

namespace Tests\Unit\Services\Copilot;

use App\Services\AI\Copilot\Pipeline\GovernanceResolver;
use App\Services\AI\Copilot\Pipeline\PipelineResultAggregator;
use Tests\TestCase;

class GovernanceResolverTest extends TestCase
{
    public function test_resolve_blocks_on_failed_steps_with_high_confidence(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(2);
        $aggregator->method('collectWarnings')->willReturn([]);
        // Enough findings to drive confidence above 60
        $aggregator->method('getAuditFindings')->willReturn([
            ['severity' => 'high', 'title' => 'Issue 1'],
            ['severity' => 'high', 'title' => 'Issue 2'],
            ['severity' => 'medium', 'title' => 'Issue 3'],
        ]);
        $aggregator->method('getVerificationResults')->willReturn([
            ['check' => 'schema', 'passed' => true],
        ]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        $this->assertEquals('block', $decision['action']);
        $this->assertStringContainsString('2 step(s) failed', $decision['reason']);
        $this->assertGreaterThanOrEqual(60, $decision['confidence']);
        $this->assertArrayHasKey('signals', $decision);
        $this->assertNotEmpty($decision['signals']);
    }

    public function test_resolve_caution_on_failed_steps_with_low_confidence(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(1);
        $aggregator->method('collectWarnings')->willReturn([
            'w1', 'w2', 'w3', 'w4', // 4+ warnings reduce confidence
        ]);
        $aggregator->method('getAuditFindings')->willReturn([]); // zero findings → low confidence
        $aggregator->method('getVerificationResults')->willReturn([]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        // Low confidence + failed step → caution, not block
        $this->assertEquals('proceed_with_caution', $decision['action']);
        $this->assertLessThan(60, $decision['confidence']);
        $this->assertStringContainsString('low confidence', $decision['reason']);
    }

    public function test_resolve_blocks_on_critical_findings(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(0);
        $aggregator->method('collectWarnings')->willReturn([]);
        $aggregator->method('getAuditFindings')->willReturn([
            ['severity' => 'critical', 'title' => 'DB mismatch'],
            ['severity' => 'critical', 'title' => 'Missing FK'],
        ]);
        $aggregator->method('getVerificationResults')->willReturn([]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        $this->assertEquals('block', $decision['action']);
        $this->assertStringContainsString('2 critical', $decision['reason']);
        $this->assertGreaterThanOrEqual(85, $decision['confidence']);
    }

    public function test_resolve_caution_on_verification_failures(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(0);
        $aggregator->method('collectWarnings')->willReturn([]);
        $aggregator->method('getAuditFindings')->willReturn([]);
        $aggregator->method('getVerificationResults')->willReturn([
            ['check' => 'schema', 'passed' => true],
            ['check' => 'endpoint', 'passed' => false],
        ]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        $this->assertEquals('proceed_with_caution', $decision['action']);
        $this->assertArrayHasKey('signals', $decision);
    }

    public function test_resolve_caution_on_warnings(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(0);
        $aggregator->method('collectWarnings')->willReturn(['[audit] Stale data']);
        $aggregator->method('getAuditFindings')->willReturn([]);
        $aggregator->method('getVerificationResults')->willReturn([]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        $this->assertEquals('proceed_with_caution', $decision['action']);
    }

    public function test_resolve_proceeds_when_all_clear(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(0);
        $aggregator->method('collectWarnings')->willReturn([]);
        $aggregator->method('getAuditFindings')->willReturn([]);
        $aggregator->method('getVerificationResults')->willReturn([]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        $this->assertEquals('proceed', $decision['action']);
        $this->assertGreaterThanOrEqual(90, $decision['confidence']);
        $this->assertEmpty($decision['signals']);
    }

    public function test_failed_steps_priority_over_warnings(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(1);
        $aggregator->method('collectWarnings')->willReturn(['some warning']);
        $aggregator->method('getAuditFindings')->willReturn([
            ['severity' => 'high', 'title' => 'Not critical'],
            ['severity' => 'high', 'title' => 'Another issue'],
            ['severity' => 'medium', 'title' => 'Third issue'],
        ]);
        $aggregator->method('getVerificationResults')->willReturn([
            ['check' => 'test', 'passed' => false],
        ]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        // Failed steps with enough evidence → block
        $this->assertEquals('block', $decision['action']);
    }

    public function test_critical_findings_priority_over_verification(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(0);
        $aggregator->method('collectWarnings')->willReturn([]);
        $aggregator->method('getAuditFindings')->willReturn([
            ['severity' => 'critical', 'title' => 'Critical issue'],
        ]);
        $aggregator->method('getVerificationResults')->willReturn([
            ['check' => 'test', 'passed' => false],
        ]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        // Critical findings override verification failures → block
        $this->assertEquals('block', $decision['action']);
    }

    public function test_confidence_computation_increases_with_evidence(): void
    {
        // Few findings, no verification
        $aggregator1 = $this->createMock(PipelineResultAggregator::class);
        $aggregator1->method('countFailedSteps')->willReturn(0);
        $aggregator1->method('collectWarnings')->willReturn([]);
        $aggregator1->method('getAuditFindings')->willReturn([]);
        $aggregator1->method('getVerificationResults')->willReturn([]);

        $resolver1 = new GovernanceResolver($aggregator1);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $low = $resolver1->resolve($run);

        // Many findings + passing verification
        $aggregator2 = $this->createMock(PipelineResultAggregator::class);
        $aggregator2->method('countFailedSteps')->willReturn(0);
        $aggregator2->method('collectWarnings')->willReturn([]);
        $aggregator2->method('getAuditFindings')->willReturn([
            ['severity' => 'medium', 'title' => 'A'],
            ['severity' => 'medium', 'title' => 'B'],
            ['severity' => 'medium', 'title' => 'C'],
            ['severity' => 'medium', 'title' => 'D'],
        ]);
        $aggregator2->method('getVerificationResults')->willReturn([
            ['check' => 'a', 'passed' => true],
            ['check' => 'b', 'passed' => true],
        ]);

        $resolver2 = new GovernanceResolver($aggregator2);
        $high = $resolver2->resolve($run);

        // Both proceed, but confidence should differ
        $this->assertEquals('proceed', $low['action']);
        $this->assertEquals('proceed', $high['action']);
        // The run with more evidence should have higher or equal confidence
        $this->assertGreaterThanOrEqual($low['confidence'], $high['confidence']);
    }

    public function test_decision_structure_has_signals(): void
    {
        $aggregator = $this->createMock(PipelineResultAggregator::class);
        $aggregator->method('countFailedSteps')->willReturn(0);
        $aggregator->method('collectWarnings')->willReturn(['w1']);
        $aggregator->method('getAuditFindings')->willReturn([
            ['severity' => 'high', 'title' => 'Issue'],
        ]);
        $aggregator->method('getVerificationResults')->willReturn([]);

        $resolver = new GovernanceResolver($aggregator);
        $run = $this->createMock(\App\Models\PipelineRun::class);
        $decision = $resolver->resolve($run);

        $this->assertArrayHasKey('action', $decision);
        $this->assertArrayHasKey('reason', $decision);
        $this->assertArrayHasKey('confidence', $decision);
        $this->assertArrayHasKey('signals', $decision);
        $this->assertIsArray($decision['signals']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\AnalysisRunner;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Unit\Governance\Analyze\Support\AnalyzeTestFactory;

class AnalysisRunnerTest extends TestCase
{
    public function test_runner_filters_by_requested_detector(): void
    {
        $a = AnalyzeTestFactory::detector('alpha', RiskLevel::HIGH);
        $b = AnalyzeTestFactory::detector('beta', RiskLevel::MEDIUM);

        $runner = new AnalysisRunner([$a, $b]);
        $context = new AnalysisContext(
            repoRoot: '/tmp',
            detectorsRequested: ['alpha'],
        );

        $result = $runner->run($context);

        $this->assertCount(1, $result->findings);
        $this->assertStringStartsWith('ALPHA_', $result->findings[0]->id);
    }

    public function test_runner_filters_by_min_risk(): void
    {
        $a = AnalyzeTestFactory::detector('alpha', RiskLevel::LOW);
        $b = AnalyzeTestFactory::detector('beta', RiskLevel::HIGH);

        $runner = new AnalysisRunner([$a, $b]);
        $context = new AnalysisContext(
            repoRoot: '/tmp',
            minRisk: RiskLevel::HIGH,
        );

        $result = $runner->run($context);

        $this->assertCount(1, $result->findings);
        $this->assertSame(RiskLevel::HIGH, $result->findings[0]->risk);
    }

    public function test_runner_keeps_detector_order_with_same_risk_findings(): void
    {
        $first = AnalyzeTestFactory::detector('first', RiskLevel::HIGH);
        $second = AnalyzeTestFactory::detector('second', RiskLevel::HIGH);

        $runner = new AnalysisRunner([$first, $second]);
        $result = $runner->run(new AnalysisContext(repoRoot: '/tmp'));

        $this->assertSame('FIRST_F1', $result->findings[0]->id);
        $this->assertSame('SECOND_F1', $result->findings[1]->id);
    }

    public function test_runner_bubbles_detector_exception(): void
    {
        $runner = new AnalysisRunner([
            AnalyzeTestFactory::detector(
                slug: 'broken',
                risk: RiskLevel::HIGH,
                onDetect: static fn (AnalysisContext $ctx): array => throw new RuntimeException('detector failed')
            ),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('detector failed');

        $runner->run(new AnalysisContext(repoRoot: '/tmp'));
    }

    public function test_runner_sets_repo_state_in_result(): void
    {
        $runner = new AnalysisRunner([AnalyzeTestFactory::detector('alpha', RiskLevel::HIGH)]);
        $context = new AnalysisContext(
            repoRoot: '/repo',
            detectorsRequested: ['alpha'],
            includeEnv: true,
            baseline: true,
        );

        $result = $runner->run($context)->toArray()['repo_state'];

        $this->assertSame('/repo', $result['repo_root']);
        $this->assertSame(['alpha'], $result['detectors_requested']);
        $this->assertTrue($result['include_env']);
        $this->assertTrue($result['baseline']);
    }
}

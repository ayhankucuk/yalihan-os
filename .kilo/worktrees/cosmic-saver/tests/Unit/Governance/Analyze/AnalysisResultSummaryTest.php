<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\AnalysisResult;
use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Governance\Analyze\Support\AnalyzeTestFactory;

class AnalysisResultSummaryTest extends TestCase
{
    public function test_counts_by_risk_are_computed_correctly(): void
    {
        $result = new AnalysisResult([
            AnalyzeTestFactory::finding('A', RiskLevel::HIGH),
            AnalyzeTestFactory::finding('B', RiskLevel::HIGH),
            AnalyzeTestFactory::finding('C', RiskLevel::MEDIUM),
            AnalyzeTestFactory::finding('D', RiskLevel::LOW),
            AnalyzeTestFactory::finding('E', RiskLevel::SKIP),
        ]);

        $counts = $result->countsByRisk();

        $this->assertSame(2, $counts['high']);
        $this->assertSame(1, $counts['medium']);
        $this->assertSame(1, $counts['low']);
        $this->assertSame(1, $counts['skip']);
    }

    public function test_summary_contains_total_and_env_blockers(): void
    {
        $result = new AnalysisResult([
            AnalyzeTestFactory::finding('F-H', RiskLevel::HIGH, FindingType::RUNTIME_RISK),
            AnalyzeTestFactory::finding('F-E', RiskLevel::HIGH, FindingType::ENVIRONMENT_BLOCKER),
        ]);

        $summary = $result->toArray()['summary'];

        $this->assertSame(2, $summary['findings_total']);
        $this->assertSame(2, $summary['high']);
        $this->assertSame(1, $summary['env_blockers']);
    }

    public function test_ranked_findings_sorts_by_risk_rank_desc(): void
    {
        $result = new AnalysisResult([
            AnalyzeTestFactory::finding('LOW', RiskLevel::LOW),
            AnalyzeTestFactory::finding('HIGH', RiskLevel::HIGH),
            AnalyzeTestFactory::finding('MEDIUM', RiskLevel::MEDIUM),
        ]);

        $ranked = $result->rankedFindings();

        $this->assertSame('HIGH', $ranked[0]->id);
        $this->assertSame('MEDIUM', $ranked[1]->id);
        $this->assertSame('LOW', $ranked[2]->id);
    }
}

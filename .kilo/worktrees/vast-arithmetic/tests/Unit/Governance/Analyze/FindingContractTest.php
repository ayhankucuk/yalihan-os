<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Governance\Analyze\Support\AnalyzeTestFactory;

class FindingContractTest extends TestCase
{
    public function test_to_array_contains_required_fields(): void
    {
        $finding = AnalyzeTestFactory::finding(
            id: 'F-CONTRACT-1',
            risk: RiskLevel::HIGH,
            tur: FindingType::AUTHORITY_CONFLICT,
            title: 'Duplicate route authority',
            summary: 'Duplicate route name detected',
        );

        $arr = $finding->toArray();

        $required = [
            'id',
            'title',
            'tur',
            'risk',
            'confidence',
            'layer',
            'durum',
            'summary',
            'evidence',
            'impact',
            'safe_action',
            'autofix',
            'tags',
            'detector',
            // Canonical additions
            'slug',
            'message',
            'severity',
            'rule',
            'metadata',
        ];

        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $arr);
        }

        $this->assertSame('F-CONTRACT-1', $arr['id']);
        $this->assertSame('authority_conflict', $arr['tur']);
        $this->assertSame('high', $arr['risk']);
    }

    public function test_autofix_is_always_false_and_durum_is_open(): void
    {
        $arr = AnalyzeTestFactory::finding()->toArray();

        $this->assertFalse($arr['autofix']);
        $this->assertSame('open', $arr['durum']);
    }

    public function test_evidence_is_normalized_into_arrays(): void
    {
        $arr = AnalyzeTestFactory::finding()->toArray();

        $this->assertIsArray($arr['evidence']);
        $this->assertNotEmpty($arr['evidence']);
        $this->assertArrayHasKey('file', $arr['evidence'][0]);
        $this->assertArrayHasKey('line', $arr['evidence'][0]);
        $this->assertArrayHasKey('snippet', $arr['evidence'][0]);
    }
}

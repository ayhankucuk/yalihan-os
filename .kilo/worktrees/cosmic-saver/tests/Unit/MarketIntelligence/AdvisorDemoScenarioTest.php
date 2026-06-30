<?php

namespace Tests\Unit\MarketIntelligence;

use App\DTOs\MarketIntelligence\AdvisorInsightDTO;
use App\Services\AIService;
use App\Services\MarketIntelligence\AdvisorAssistantService;
use PHPUnit\Framework\TestCase;

/**
 * Advisor Validation Test — Demo Senaryo
 *
 * 3 farklı ilan senaryosu ile danışman çıktısını doğrular:
 * - FIRSAT: underpriced + HOT demand + high location → BUY / HIGH urgency
 * - NÖTR:   fair price + ACTIVE demand + mid location → WAIT / MEDIUM urgency
 * - RİSKLİ: overpriced + WEAK demand + low location → SELL / LOW-HIGH urgency + risk note
 *
 * AI çağrısı yapılmaz — deterministic fallback test edilir.
 */
class AdvisorDemoScenarioTest extends TestCase
{
    private AdvisorAssistantService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Force deterministic fallback by returning invalid AI response
        $mockAI = $this->createMock(AIService::class);
        $mockAI->method('generate')->willReturn('invalid-response');

        $this->service = new AdvisorAssistantService($mockAI);
    }

    // ═══════════════════════════════════════
    // SENARYO 1: FIRSAT (Opportunity)
    // Yalıkavak villa — underpriced, HOT demand, strong location
    // ═══════════════════════════════════════

    public function test_firsat_buy_signal_returns_high_urgency(): void
    {
        $result = $this->service->generate($this->firsatPayload());

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
        $this->assertSame('HIGH', $result->urgency);
    }

    public function test_firsat_summary_mentions_underpriced(): void
    {
        $result = $this->service->generate($this->firsatPayload());

        $this->assertStringContainsString('altında', mb_strtolower($result->summary));
    }

    public function test_firsat_reasoning_mentions_location_services(): void
    {
        $result = $this->service->generate($this->firsatPayload());

        // Location score 85 + top groups should appear in reasoning
        $this->assertStringContainsString('erişim güçlü', mb_strtolower($result->reasoning));
    }

    public function test_firsat_action_recommends_followup(): void
    {
        $result = $this->service->generate($this->firsatPayload());

        $this->assertStringContainsString('takip', mb_strtolower($result->recommended_action));
    }

    public function test_firsat_has_all_required_fields(): void
    {
        $result = $this->service->generate($this->firsatPayload());
        $arr = $result->toArray();

        foreach (['summary', 'reasoning', 'recommended_action', 'urgency', 'risk_note'] as $field) {
            $this->assertArrayHasKey($field, $arr, "FIRSAT: missing {$field}");
        }
    }

    // ═══════════════════════════════════════
    // SENARYO 2: NÖTR (Neutral)
    // Konacık daire — fair price, ACTIVE demand, mid location
    // ═══════════════════════════════════════

    public function test_notr_wait_signal_returns_medium_urgency(): void
    {
        $result = $this->service->generate($this->notrPayload());

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
        $this->assertSame('MEDIUM', $result->urgency);
    }

    public function test_notr_summary_mentions_fair_price(): void
    {
        $result = $this->service->generate($this->notrPayload());

        $this->assertStringContainsString('uyumlu', mb_strtolower($result->summary));
    }

    public function test_notr_action_recommends_monitoring(): void
    {
        $result = $this->service->generate($this->notrPayload());

        $this->assertStringContainsString('izleme', mb_strtolower($result->recommended_action));
    }

    public function test_notr_reasoning_mentions_location_context(): void
    {
        $result = $this->service->generate($this->notrPayload());

        // Location score 60 with top groups should appear
        $this->assertStringContainsString('erişim güçlü', mb_strtolower($result->reasoning));
    }

    // ═══════════════════════════════════════
    // SENARYO 3: RİSKLİ (Risky)
    // Mumcular daire — overpriced, WEAK demand, low location, 120 days
    // ═══════════════════════════════════════

    public function test_riskli_sell_signal_returns_valid_urgency(): void
    {
        $result = $this->service->generate($this->riskliPayload());

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
        $this->assertContains($result->urgency, ['HIGH', 'MEDIUM', 'LOW']);
    }

    public function test_riskli_summary_mentions_overpriced(): void
    {
        $result = $this->service->generate($this->riskliPayload());

        $this->assertStringContainsString('üzerinde', mb_strtolower($result->summary));
    }

    public function test_riskli_reasoning_mentions_limited_services(): void
    {
        $result = $this->service->generate($this->riskliPayload());

        // Location score 22 → "erişimi sınırlı"
        $this->assertStringContainsString('sınırlı', mb_strtolower($result->reasoning));
    }

    public function test_riskli_risk_note_mentions_long_days(): void
    {
        $result = $this->service->generate($this->riskliPayload());

        $this->assertNotEmpty($result->risk_note);
        $this->assertStringContainsString('120', $result->risk_note);
    }

    public function test_riskli_risk_note_mentions_low_confidence(): void
    {
        $result = $this->service->generate($this->riskliPayload());

        $this->assertStringContainsString('düşük', mb_strtolower($result->risk_note));
    }

    public function test_riskli_action_recommends_price_review(): void
    {
        $result = $this->service->generate($this->riskliPayload());

        $this->assertStringContainsString('revizyon', mb_strtolower($result->recommended_action));
    }

    // ═══════════════════════════════════════
    // CROSS-SCENARIO
    // ═══════════════════════════════════════

    public function test_all_three_scenarios_produce_different_summaries(): void
    {
        $firsat = $this->service->generate($this->firsatPayload());
        $notr = $this->service->generate($this->notrPayload());
        $riskli = $this->service->generate($this->riskliPayload());

        $summaries = [
            $firsat->summary,
            $notr->summary,
            $riskli->summary,
        ];

        // All 3 should be distinct
        $this->assertCount(3, array_unique($summaries), 'All 3 scenarios should produce different summaries');
    }

    public function test_all_three_scenarios_have_valid_urgency(): void
    {
        $scenarios = [
            'firsat' => $this->firsatPayload(),
            'notr' => $this->notrPayload(),
            'riskli' => $this->riskliPayload(),
        ];

        foreach ($scenarios as $name => $payload) {
            $result = $this->service->generate($payload);
            $this->assertContains(
                $result->urgency,
                ['LOW', 'MEDIUM', 'HIGH'],
                "Scenario '{$name}' has invalid urgency: {$result->urgency}"
            );
        }
    }

    public function test_firsat_urgency_higher_than_notr(): void
    {
        $urgencyMap = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3];

        $firsat = $this->service->generate($this->firsatPayload());
        $notr = $this->service->generate($this->notrPayload());

        $this->assertGreaterThanOrEqual(
            $urgencyMap[$notr->urgency],
            $urgencyMap[$firsat->urgency],
            'FIRSAT urgency should be >= NÖTR urgency'
        );
    }

    public function test_dto_serialization_consistent_across_scenarios(): void
    {
        $scenarios = [
            $this->firsatPayload(),
            $this->notrPayload(),
            $this->riskliPayload(),
        ];

        foreach ($scenarios as $payload) {
            $result = $this->service->generate($payload);
            $arr = $result->toArray();
            $json = json_encode($arr);

            $this->assertNotFalse($json, 'DTO should serialize to valid JSON');
            $decoded = json_decode($json, true);
            $this->assertSame($arr, $decoded, 'JSON round-trip should be identical');
        }
    }

    // ═══════════════════════════════════════
    // Payload Helpers
    // ═══════════════════════════════════════

    /**
     * FIRSAT: Yalıkavak villa — underpriced with strong location
     * Expected: BUY, HIGH urgency, takip recommendation
     */
    private function firsatPayload(): array
    {
        return [
            'pricing_position' => 'underpriced',
            'pricing_score' => 82,
            'confidence_label' => 'HIGH',
            'confidence_score' => 78,
            'demand_label' => 'HOT',
            'opportunity_action' => 'BUY',
            'priority_label' => 'CRITICAL',
            'queue_type' => 'OPPORTUNITY_FOLLOWUP',
            'days_on_market' => 10,
            'price' => 28500000,
            'benchmark_price' => 35000000,
            // MIE v4: Location Intelligence
            'location_signal_score' => 85,
            'location_confidence_label' => 'HIGH',
            'location_demand_modifier' => 12,
            'location_top_groups' => ['education', 'health', 'shopping'],
        ];
    }

    /**
     * NÖTR: Konacık daire — fair price with mid location
     * Expected: WAIT, MEDIUM urgency, izleme recommendation
     */
    private function notrPayload(): array
    {
        return [
            'pricing_position' => 'fair',
            'pricing_score' => 55,
            'confidence_label' => 'HIGH',
            'confidence_score' => 65,
            'demand_label' => 'ACTIVE',
            'opportunity_action' => 'WAIT',
            'priority_label' => 'MEDIUM',
            'queue_type' => 'MONITORING',
            'days_on_market' => 45,
            'price' => 6200000,
            'benchmark_price' => 6000000,
            // MIE v4: Location
            'location_signal_score' => 60,
            'location_confidence_label' => 'MEDIUM',
            'location_demand_modifier' => 5,
            'location_top_groups' => ['transport', 'shopping'],
        ];
    }

    /**
     * RİSKLİ: Mumcular daire — overpriced, weak demand, poor location, stale
     * Expected: SELL, risk_note mentions days + confidence, sınırlı erişim
     */
    private function riskliPayload(): array
    {
        return [
            'pricing_position' => 'overpriced',
            'pricing_score' => 25,
            'confidence_label' => 'LOW',
            'confidence_score' => 30,
            'demand_label' => 'WEAK',
            'opportunity_action' => 'SELL',
            'priority_label' => 'HIGH',
            'queue_type' => 'PRICE_REVIEW',
            'days_on_market' => 120,
            'price' => 1800000,
            'benchmark_price' => 1200000,
            // MIE v4: Location — poor services
            'location_signal_score' => 22,
            'location_confidence_label' => 'LOW',
            'location_demand_modifier' => -5,
            'location_top_groups' => [],
        ];
    }
}

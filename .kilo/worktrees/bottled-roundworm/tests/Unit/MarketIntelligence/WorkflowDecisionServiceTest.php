<?php

namespace Tests\Unit\MarketIntelligence;

use App\Services\MarketIntelligence\WorkflowDecisionService;
use PHPUnit\Framework\TestCase;

class WorkflowDecisionServiceTest extends TestCase
{
    private WorkflowDecisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WorkflowDecisionService();
    }

    // ── Rule 1: MANUAL_REVIEW — confidence VERY_LOW ──

    public function test_very_low_confidence_returns_manual_review(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'BUY',
            'confidence_label' => 'VERY_LOW',
            'priority_label' => 'HIGH',
            'pricing_position' => 'underpriced',
            'demand_label' => 'HOT',
        ]);

        $this->assertSame('MANUAL_REVIEW', $result['queue_type']);
        $this->assertSame('NEW', $result['workflow_state']);
        $this->assertNotEmpty($result['reason']);
    }

    public function test_insufficient_data_action_returns_manual_review(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'INSUFFICIENT_DATA',
            'confidence_label' => 'HIGH',
            'priority_label' => 'HIGH',
            'pricing_position' => 'fair',
            'demand_label' => 'ACTIVE',
        ]);

        $this->assertSame('MANUAL_REVIEW', $result['queue_type']);
        $this->assertSame('NEW', $result['workflow_state']);
    }

    // ── Rule 2: PRICE_REVIEW — SELL + medium/high confidence ──

    public function test_sell_high_confidence_returns_price_review(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'SELL',
            'confidence_label' => 'HIGH',
            'priority_label' => 'HIGH',
            'pricing_position' => 'overpriced',
            'demand_label' => 'SLOW',
        ]);

        $this->assertSame('PRICE_REVIEW', $result['queue_type']);
        $this->assertSame('NEW', $result['workflow_state']);
        $this->assertStringContainsString('revizyon', $result['reason']);
    }

    public function test_sell_medium_confidence_returns_price_review(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'SELL',
            'confidence_label' => 'MEDIUM',
            'pricing_position' => 'aggressively_overpriced',
            'demand_label' => 'WEAK',
        ]);

        $this->assertSame('PRICE_REVIEW', $result['queue_type']);
    }

    // ── Rule 3: OPPORTUNITY_FOLLOWUP — BUY + medium/high confidence ──

    public function test_buy_high_confidence_returns_opportunity_followup(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'BUY',
            'confidence_label' => 'HIGH',
            'priority_label' => 'CRITICAL',
            'pricing_position' => 'underpriced',
            'demand_label' => 'HOT',
        ]);

        $this->assertSame('OPPORTUNITY_FOLLOWUP', $result['queue_type']);
        $this->assertSame('NEW', $result['workflow_state']);
        $this->assertStringContainsString('fırsat', $result['reason']);
    }

    public function test_buy_medium_confidence_returns_opportunity_followup(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'BUY',
            'confidence_label' => 'MEDIUM',
            'pricing_position' => 'underpriced',
            'demand_label' => 'ACTIVE',
        ]);

        $this->assertSame('OPPORTUNITY_FOLLOWUP', $result['queue_type']);
    }

    // ── Rule 4: WATCHLIST — WAIT + medium/high/critical priority ──

    public function test_wait_medium_priority_returns_watchlist(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'WAIT',
            'confidence_label' => 'HIGH',
            'priority_label' => 'MEDIUM',
            'pricing_position' => 'fair',
            'demand_label' => 'SLOW',
        ]);

        $this->assertSame('WATCHLIST', $result['queue_type']);
        $this->assertSame('NEW', $result['workflow_state']);
        $this->assertStringContainsString('izleme', $result['reason']);
    }

    public function test_wait_critical_priority_returns_watchlist(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'WAIT',
            'confidence_label' => 'MEDIUM',
            'priority_label' => 'CRITICAL',
            'pricing_position' => 'overpriced',
            'demand_label' => 'ACTIVE',
        ]);

        $this->assertSame('WATCHLIST', $result['queue_type']);
    }

    // ── Rule 5: NO_ACTION — fallback ──

    public function test_stable_fair_listing_returns_no_action(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'WAIT',
            'confidence_label' => 'HIGH',
            'priority_label' => 'LOW',
            'pricing_position' => 'fair',
            'demand_label' => 'SLOW',
        ]);

        $this->assertSame('NO_ACTION', $result['queue_type']);
        $this->assertSame('NEW', $result['workflow_state']);
        $this->assertStringContainsString('aksiyon gerekmiyor', $result['reason']);
    }

    // ── Deterministic & Idempotent ──

    public function test_same_input_produces_same_output_always(): void
    {
        $payload = [
            'opportunity_action' => 'BUY',
            'confidence_label' => 'HIGH',
            'priority_label' => 'HIGH',
            'pricing_position' => 'underpriced',
            'demand_label' => 'HOT',
        ];

        $result1 = $this->service->decide($payload);
        $result2 = $this->service->decide($payload);
        $result3 = $this->service->decide($payload);

        $this->assertSame($result1, $result2);
        $this->assertSame($result2, $result3);
    }

    // ── Reason always non-empty Turkish string ──

    public function test_reason_always_non_empty(): void
    {
        $scenarios = [
            ['opportunity_action' => 'SELL', 'confidence_label' => 'HIGH', 'pricing_position' => 'overpriced'],
            ['opportunity_action' => 'BUY', 'confidence_label' => 'MEDIUM', 'pricing_position' => 'underpriced'],
            ['opportunity_action' => 'INSUFFICIENT_DATA'],
            ['opportunity_action' => 'WAIT', 'confidence_label' => 'HIGH', 'priority_label' => 'MEDIUM'],
            ['opportunity_action' => 'WAIT', 'confidence_label' => 'HIGH', 'priority_label' => 'LOW'],
        ];

        foreach ($scenarios as $payload) {
            $result = $this->service->decide($payload);
            $this->assertNotEmpty($result['reason'], 'Reason should never be empty');
            $this->assertIsString($result['reason']);
        }
    }

    // ── Queue importance ranking ──

    public function test_queue_importance_order(): void
    {
        $this->assertSame(1, $this->service->getQueueImportance('PRICE_REVIEW'));
        $this->assertSame(2, $this->service->getQueueImportance('OPPORTUNITY_FOLLOWUP'));
        $this->assertSame(3, $this->service->getQueueImportance('MANUAL_REVIEW'));
        $this->assertSame(4, $this->service->getQueueImportance('WATCHLIST'));
        $this->assertSame(6, $this->service->getQueueImportance('NO_ACTION'));
    }

    public function test_unknown_queue_type_returns_high_number(): void
    {
        $this->assertSame(99, $this->service->getQueueImportance('UNKNOWN'));
    }

    // ── Edge: empty payload ──

    public function test_empty_payload_returns_manual_review(): void
    {
        $result = $this->service->decide([]);

        $this->assertSame('MANUAL_REVIEW', $result['queue_type']);
        $this->assertSame('NEW', $result['workflow_state']);
    }

    // ── Priority: MANUAL_REVIEW overrides even BUY+VERY_LOW ──

    public function test_buy_with_very_low_confidence_still_manual_review(): void
    {
        $result = $this->service->decide([
            'opportunity_action' => 'BUY',
            'confidence_label' => 'VERY_LOW',
            'priority_label' => 'CRITICAL',
            'pricing_position' => 'underpriced',
            'demand_label' => 'HOT',
        ]);

        $this->assertSame('MANUAL_REVIEW', $result['queue_type']);
    }
}

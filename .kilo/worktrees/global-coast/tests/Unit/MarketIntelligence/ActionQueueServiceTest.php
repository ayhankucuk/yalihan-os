<?php

namespace Tests\Unit\MarketIntelligence;

use App\DTOs\MarketIntelligence\ActionQueueItemDTO;
use App\Services\MarketIntelligence\ActionQueueService;
use PHPUnit\Framework\TestCase;

class ActionQueueServiceTest extends TestCase
{
    private ActionQueueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActionQueueService();
    }

    // ── buildQueue sorting ──

    public function test_queue_sorted_by_priority_score_desc(): void
    {
        $payloads = [
            $this->makePayload(1, 'SELL', 'HIGH', 'HIGH', 30),
            $this->makePayload(2, 'SELL', 'HIGH', 'CRITICAL', 90),
            $this->makePayload(3, 'SELL', 'HIGH', 'MEDIUM', 60),
        ];

        $queue = $this->service->buildQueue($payloads);

        $this->assertCount(3, $queue);
        $this->assertSame(2, $queue[0]->listing_id);
        $this->assertSame(3, $queue[1]->listing_id);
        $this->assertSame(1, $queue[2]->listing_id);
    }

    public function test_same_priority_sorted_by_queue_importance(): void
    {
        // Same priority score, different queue types
        $payloads = [
            $this->makePayload(1, 'WAIT', 'HIGH', 'LOW', 50),        // → NO_ACTION (6)
            $this->makePayload(2, 'SELL', 'HIGH', 'HIGH', 50),       // → PRICE_REVIEW (1)
            $this->makePayload(3, 'BUY', 'HIGH', 'HIGH', 50),        // → OPPORTUNITY_FOLLOWUP (2)
        ];

        $queue = $this->service->buildQueue($payloads);

        $this->assertSame(2, $queue[0]->listing_id); // PRICE_REVIEW importance=1
        $this->assertSame(3, $queue[1]->listing_id); // OPPORTUNITY_FOLLOWUP importance=2
        $this->assertSame(1, $queue[2]->listing_id); // NO_ACTION importance=6
    }

    public function test_same_priority_same_queue_sorted_by_listing_id(): void
    {
        $payloads = [
            $this->makePayload(5, 'SELL', 'HIGH', 'HIGH', 70),
            $this->makePayload(2, 'SELL', 'HIGH', 'HIGH', 70),
            $this->makePayload(8, 'SELL', 'HIGH', 'HIGH', 70),
        ];

        $queue = $this->service->buildQueue($payloads);

        $this->assertSame(2, $queue[0]->listing_id);
        $this->assertSame(5, $queue[1]->listing_id);
        $this->assertSame(8, $queue[2]->listing_id);
    }

    // ── buildItem ──

    public function test_build_item_returns_dto_with_correct_fields(): void
    {
        $payload = $this->makePayload(42, 'SELL', 'HIGH', 'CRITICAL', 85, [
            'title' => 'Test İlan',
            'days_on_market' => 45.0,
            'current_price' => 5000000.0,
            'benchmark_price' => 4000000.0,
        ]);

        $item = $this->service->buildItem($payload);

        $this->assertInstanceOf(ActionQueueItemDTO::class, $item);
        $this->assertSame(42, $item->listing_id);
        $this->assertSame('Test İlan', $item->title);
        $this->assertSame('PRICE_REVIEW', $item->queue_type);
        $this->assertSame('NEW', $item->workflow_state);
        $this->assertSame(85, $item->priority_score);
        $this->assertSame('CRITICAL', $item->priority_label);
        $this->assertSame('SELL', $item->opportunity_action);
        $this->assertSame('HIGH', $item->confidence_label);
        $this->assertNotEmpty($item->reason);
        $this->assertSame(45.0, $item->days_on_market);
        $this->assertSame(5000000.0, $item->current_price);
        $this->assertSame(4000000.0, $item->benchmark_price);
    }

    // ── filterByQueueType ──

    public function test_filter_by_queue_type_returns_matching_only(): void
    {
        $payloads = [
            $this->makePayload(1, 'SELL', 'HIGH', 'HIGH', 80),       // → PRICE_REVIEW
            $this->makePayload(2, 'BUY', 'HIGH', 'HIGH', 70),        // → OPPORTUNITY_FOLLOWUP
            $this->makePayload(3, 'SELL', 'MEDIUM', 'MEDIUM', 60),   // → PRICE_REVIEW
            $this->makePayload(4, 'INSUFFICIENT_DATA', 'LOW', 'LOW', 20), // → MANUAL_REVIEW
        ];

        $queue = $this->service->buildQueue($payloads);
        $priceReviews = $this->service->filterByQueueType($queue, 'PRICE_REVIEW');

        $this->assertCount(2, $priceReviews);
        foreach ($priceReviews as $item) {
            $this->assertSame('PRICE_REVIEW', $item->queue_type);
        }
    }

    public function test_filter_by_nonexistent_type_returns_empty(): void
    {
        $payloads = [
            $this->makePayload(1, 'SELL', 'HIGH', 'HIGH', 80),
        ];

        $queue = $this->service->buildQueue($payloads);
        $result = $this->service->filterByQueueType($queue, 'NONEXISTENT');

        $this->assertCount(0, $result);
    }

    // ── countByQueueType ──

    public function test_count_by_queue_type(): void
    {
        $payloads = [
            $this->makePayload(1, 'SELL', 'HIGH', 'HIGH', 80),
            $this->makePayload(2, 'SELL', 'MEDIUM', 'MEDIUM', 60),
            $this->makePayload(3, 'BUY', 'HIGH', 'HIGH', 70),
            $this->makePayload(4, 'INSUFFICIENT_DATA', 'LOW', 'LOW', 20),
        ];

        $queue = $this->service->buildQueue($payloads);
        $counts = $this->service->countByQueueType($queue);

        $this->assertSame(2, $counts['PRICE_REVIEW']);
        $this->assertSame(1, $counts['OPPORTUNITY_FOLLOWUP']);
        $this->assertSame(1, $counts['MANUAL_REVIEW']);
    }

    // ── Deterministic ──

    public function test_deterministic_same_input_same_output(): void
    {
        $payloads = [
            $this->makePayload(1, 'SELL', 'HIGH', 'HIGH', 80),
            $this->makePayload(2, 'BUY', 'MEDIUM', 'CRITICAL', 90),
            $this->makePayload(3, 'WAIT', 'HIGH', 'MEDIUM', 45),
        ];

        $queue1 = $this->service->buildQueue($payloads);
        $queue2 = $this->service->buildQueue($payloads);

        $this->assertCount(count($queue1), $queue2);
        foreach ($queue1 as $i => $item) {
            $this->assertSame($item->listing_id, $queue2[$i]->listing_id);
            $this->assertSame($item->queue_type, $queue2[$i]->queue_type);
            $this->assertSame($item->reason, $queue2[$i]->reason);
        }
    }

    // ── Empty input ──

    public function test_empty_input_returns_empty_queue(): void
    {
        $queue = $this->service->buildQueue([]);
        $this->assertCount(0, $queue);
    }

    // ── All workflow states are NEW ──

    public function test_all_items_have_new_workflow_state(): void
    {
        $payloads = [
            $this->makePayload(1, 'SELL', 'HIGH', 'HIGH', 80),
            $this->makePayload(2, 'BUY', 'MEDIUM', 'HIGH', 70),
            $this->makePayload(3, 'INSUFFICIENT_DATA', 'LOW', 'LOW', 20),
            $this->makePayload(4, 'WAIT', 'HIGH', 'MEDIUM', 50),
            $this->makePayload(5, 'WAIT', 'HIGH', 'LOW', 30),
        ];

        $queue = $this->service->buildQueue($payloads);

        foreach ($queue as $item) {
            $this->assertSame('NEW', $item->workflow_state);
        }
    }

    // ── toArray on DTO ──

    public function test_dto_to_array_contains_all_keys(): void
    {
        $payload = $this->makePayload(1, 'SELL', 'HIGH', 'HIGH', 80);
        $item = $this->service->buildItem($payload);
        $arr = $item->toArray();

        $expectedKeys = [
            'listing_id', 'title', 'queue_type', 'workflow_state',
            'priority_score', 'priority_label', 'opportunity_action',
            'confidence_label', 'reason', 'days_on_market',
            'current_price', 'benchmark_price',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $arr);
        }
    }

    // ── Helper ──

    private function makePayload(
        int $listingId,
        string $action,
        string $confidence,
        string $priority,
        int $priorityScore,
        array $extra = [],
    ): array {
        return array_merge([
            'listing_id' => $listingId,
            'opportunity_action' => $action,
            'confidence_label' => $confidence,
            'priority_label' => $priority,
            'priority_score' => $priorityScore,
            'pricing_position' => 'fair',
            'demand_label' => 'ACTIVE',
        ], $extra);
    }
}

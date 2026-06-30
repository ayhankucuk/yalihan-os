<?php

namespace App\Services\MarketIntelligence;

use App\DTOs\MarketIntelligence\ActionQueueItemDTO;

/**
 * Action Queue Service — MIE v1.5
 *
 * Intelligence payload listesinden action queue oluşturur.
 * WorkflowDecisionService ile queue kararı alır, DTO üretir, sıralar.
 *
 * Tamamen deterministik — AI sıfır, rand() sıfır, background job sıfır.
 *
 * Sıralama: priority_score DESC → queue importance ASC → listing_id ASC
 */
class ActionQueueService
{
    private WorkflowDecisionService $decisionService;

    public function __construct(?WorkflowDecisionService $decisionService = null)
    {
        $this->decisionService = $decisionService ?? new WorkflowDecisionService();
    }

    /**
     * Intelligence payload listesinden action queue oluştur.
     *
     * @param iterable<array> $intelligencePayloads Her biri listing intelligence verisi
     * @return ActionQueueItemDTO[]
     */
    public function buildQueue(iterable $intelligencePayloads): array
    {
        $items = [];

        foreach ($intelligencePayloads as $payload) {
            $items[] = $this->buildItem($payload);
        }

        usort($items, function (ActionQueueItemDTO $a, ActionQueueItemDTO $b) {
            // 1. priority_score DESC
            if ($a->priority_score !== $b->priority_score) {
                return $b->priority_score <=> $a->priority_score;
            }
            // 2. queue importance ASC (küçük = daha önemli)
            $impA = $this->decisionService->getQueueImportance($a->queue_type);
            $impB = $this->decisionService->getQueueImportance($b->queue_type);
            if ($impA !== $impB) {
                return $impA <=> $impB;
            }
            // 3. listing_id ASC
            return $a->listing_id <=> $b->listing_id;
        });

        return $items;
    }

    /**
     * Tek payload'dan ActionQueueItemDTO üret.
     */
    public function buildItem(array $payload): ActionQueueItemDTO
    {
        $decision = $this->decisionService->decide($payload);

        return new ActionQueueItemDTO(
            listing_id: $payload['listing_id'] ?? 0,
            title: $payload['title'] ?? null,
            queue_type: $decision['queue_type'],
            workflow_state: $decision['workflow_state'],
            priority_score: $payload['priority_score'] ?? 0,
            priority_label: $payload['priority_label'] ?? 'LOW',
            opportunity_action: $payload['opportunity_action'] ?? 'INSUFFICIENT_DATA',
            confidence_label: $payload['confidence_label'] ?? 'VERY_LOW',
            reason: $decision['reason'],
            days_on_market: $payload['days_on_market'] ?? null,
            current_price: $payload['current_price'] ?? null,
            benchmark_price: $payload['benchmark_price'] ?? null,
        );
    }

    /**
     * Queue'yu type'a göre filtrele.
     *
     * @param ActionQueueItemDTO[] $queue
     * @return ActionQueueItemDTO[]
     */
    public function filterByQueueType(array $queue, string $queueType): array
    {
        return array_values(array_filter($queue, fn (ActionQueueItemDTO $item) => $item->queue_type === $queueType));
    }

    /**
     * Queue'daki type dağılımını say.
     *
     * @param ActionQueueItemDTO[] $queue
     * @return array<string, int>
     */
    public function countByQueueType(array $queue): array
    {
        $counts = [];
        foreach ($queue as $item) {
            $counts[$item->queue_type] = ($counts[$item->queue_type] ?? 0) + 1;
        }
        return $counts;
    }
}

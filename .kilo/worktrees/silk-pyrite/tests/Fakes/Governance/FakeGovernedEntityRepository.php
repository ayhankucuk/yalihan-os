<?php

namespace Tests\Fakes\Governance;

use App\Contracts\Governance\GovernedEntityRepositoryInterface;
use App\Enums\Governance\GovernanceState;
use RuntimeException;

final class FakeGovernedEntityRepository implements GovernedEntityRepositoryInterface
{
    /** @var array<string, array<int|string, array<string, mixed>>> */
    private array $store = [];
    private int $idCounter = 1;

    public function seed(
        string $entityType,
        int|string $entityId,
        string $state,
        array $attributes = [],
    ): void {
        $this->store[$entityType][$entityId] = array_merge($attributes, [
            'id' => $entityId,
            'entity_type' => $entityType,
            'governance_state' => $state,
            'payload' => []
        ]);
        if (is_int($entityId) && $entityId >= $this->idCounter) {
            $this->idCounter = $entityId + 1;
        }
    }

    public function findOrFail(string $entityType, int|string $entityId): object
    {
        $record = $this->store[$entityType][$entityId] ?? null;

        if ($record === null) {
            throw new RuntimeException("Governed entity [{$entityType}:{$entityId}] not found.");
        }

        return (object) $record;
    }

    public function updateState(
        string $entityType,
        int|string $entityId,
        string $newState,
    ): void {
        if (!isset($this->store[$entityType][$entityId])) {
            throw new RuntimeException("Governed entity [{$entityType}:{$entityId}] not found.");
        }

        $this->store[$entityType][$entityId]['governance_state'] = $newState;
    }

    public function createDraft(string $entityType, array $payload): int|string
    {
        $newId = $this->idCounter++;
        $this->store[$entityType][$newId] = [
            'id' => $newId,
            'entity_type' => $entityType,
            'governance_state' => GovernanceState::DRAFT->value,
            'payload' => $payload
        ];
        return $newId;
    }

    public function updatePayload(string $entityType, int|string $entityId, array $payload): void
    {
        if (!isset($this->store[$entityType][$entityId])) {
            throw new RuntimeException("Governed entity [{$entityType}:{$entityId}] not found.");
        }
        $this->store[$entityType][$entityId]['payload'] = $payload;
    }

    public function currentState(string $entityType, int|string $entityId): string
    {
        return $this->store[$entityType][$entityId]['governance_state']
            ?? throw new RuntimeException("Governed entity [{$entityType}:{$entityId}] not found.");
    }
}

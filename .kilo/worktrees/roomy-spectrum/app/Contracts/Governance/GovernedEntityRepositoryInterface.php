<?php

namespace App\Contracts\Governance;

interface GovernedEntityRepositoryInterface
{
    public function findOrFail(string $entityType, int|string $entityId): object;

    public function updateState(
        string $entityType,
        int|string $entityId,
        string $newState,
    ): void;

    public function createDraft(string $entityType, array $payload): int|string;

    public function updatePayload(string $entityType, int|string $entityId, array $payload): void;
}

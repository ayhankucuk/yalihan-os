<?php

namespace App\Services\Governance\Diff;

use App\Contracts\Governance\GovernanceReadServiceInterface;
use App\Contracts\Governance\GovernedEntityRepositoryInterface;
use App\DataTransferObjects\Governance\DiffProjection;
use App\Services\Governance\GovernanceTransitionGuard;
use App\Enums\Governance\GovernanceState;

/**
 * 🛡️ SAB SEALED
 * Salt-Okunur (Read Mode) Governance Servisi.
 * CQRS ilkesi gereği Mutation Barındırmaz.
 */
final class GovernanceReadService implements GovernanceReadServiceInterface
{
    public function __construct(
        private readonly GovernedEntityRepositoryInterface $repository,
        private readonly PayloadDiffCalculator $diffCalculator,
        private readonly GovernanceTransitionGuard $transitionGuard,
    ) {
    }

    public function getDiff(string $entityType, int|string $entityId): DiffProjection
    {
        $entity = $this->repository->findOrFail($entityType, $entityId);
        
        $currentState = GovernanceState::from($entity->governance_state ?? GovernanceState::DRAFT->value);
        
        // Guard katmanından bağımsız olarak Diff Model UI'a "Yayına Çıkılabilir mi?" flagini hesaplar
        $canPublish = $this->transitionGuard->canPublish($currentState);

        // Fetch payloads (Eğer DB Modelinde yoksa array default verilir)
        $originalPayload = (array) ($entity->published_payload ?? []);
        $draftPayload = (array) ($entity->payload ?? []);

        // Snapshot Engine Calculation
        $changes = $this->diffCalculator->calculate($originalPayload, $draftPayload);

        return new DiffProjection(
            entityType: $entityType,
            entityId: $entityId,
            currentState: $currentState,
            changes: $changes,
            canPublish: $canPublish,
        );
    }
}

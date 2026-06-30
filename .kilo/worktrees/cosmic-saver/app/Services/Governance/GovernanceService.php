<?php

namespace App\Services\Governance;

use App\Contracts\Governance\AuditLoggerInterface;
use App\Contracts\Governance\GovernanceServiceInterface;
use App\Contracts\Governance\GovernedEntityRepositoryInterface;
use App\Contracts\Governance\TelemetryPublisherInterface;
use App\Enums\Governance\GovernanceTelemetryEvent;
use App\Jobs\Governance\GovernancePublishShadowJob;
use App\DataTransferObjects\Governance\CreateDraftCommand;
use App\DataTransferObjects\Governance\UpdateDraftCommand;
use App\DataTransferObjects\Governance\PromoteDraftCommand;
use App\DataTransferObjects\Governance\PublishPromotedCommand;
use App\DataTransferObjects\Governance\ArchiveGovernedEntityCommand;
use App\DataTransferObjects\Governance\GovernanceAuditContext;
use App\Enums\Governance\GovernanceActionType;
use App\Enums\Governance\GovernanceState;
use DomainException;

final class GovernanceService implements GovernanceServiceInterface
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
        private readonly GovernanceTransitionGuard $transitionGuard,
        private readonly GovernedEntityRepositoryInterface $repository,
        private readonly TelemetryPublisherInterface $telemetry,
    ) {
    }

    private function rejectTransition(string $transitionName, string|int $entityType, string|int $entityId, ?int $actorId, string $correlationId, ?string $reason, GovernanceState $currentState, GovernanceState $targetState): void
    {
        $this->auditLogger->logTransition(
            GovernanceActionType::TRANSITION_REJECTED,
            new GovernanceAuditContext(
                entityType: (string) $entityType,
                entityId: $entityId,
                actorId: $actorId,
                correlationId: $correlationId,
                reason: $reason ?? "Rejected transition due to invalid state",
                payloadSnapshot: [
                    'attempted_transition' => $transitionName,
                    'current_state' => $currentState->value,
                ]
            ),
            $currentState,
            $targetState
        );

        if ($transitionName === 'publish') {
             $this->telemetry->publish(
                 GovernanceTelemetryEvent::PUBLISH_REJECTED,
                 $correlationId,
                 (string) $entityType,
                 $entityId,
                 0,
                 ['reason' => 'transition_guard_blocked', 'state' => $currentState->value]
             );
        }

        throw new DomainException("Invalid governance transition from {$currentState->value} to {$targetState->value}");
    }

    public function promote(PromoteDraftCommand $command): void
    {
        $entity = $this->repository->findOrFail($command->entityType, $command->entityId);
        $currentState = GovernanceState::from($entity->governance_state ?? GovernanceState::DRAFT->value);

        if (!$this->transitionGuard->canPromote($currentState)) {
            $this->rejectTransition('promote', $command->entityType, $command->entityId, $command->actorId, $command->correlationId, $command->reason, $currentState, GovernanceState::PROMOTED);
        }

        $this->repository->updateState($command->entityType, $command->entityId, GovernanceState::PROMOTED->value);

        $this->auditLogger->logTransition(
            GovernanceActionType::PROMOTED,
            new GovernanceAuditContext(
                entityType: $command->entityType,
                entityId: $command->entityId,
                actorId: $command->actorId,
                correlationId: $command->correlationId,
                reason: $command->reason,
                payloadSnapshot: ['transition' => 'promote']
            ),
            $currentState,
            GovernanceState::PROMOTED
        );
    }

    public function publish(PublishPromotedCommand $command): void
    {
        $startTime = microtime(true);
        $this->telemetry->publish(
            GovernanceTelemetryEvent::PUBLISH_ATTEMPTED,
            $command->correlationId,
            $command->entityType,
            $command->entityId,
            0
        );

        $entity = $this->repository->findOrFail($command->entityType, $command->entityId);
        $currentState = GovernanceState::from($entity->governance_state);
        // Draft payload is passed to shadow evaluator
        $draftPayload = (array) ($entity->payload ?? []);

        if (!$this->transitionGuard->canPublish($currentState)) {
            $this->rejectTransition('publish', $command->entityType, $command->entityId, $command->actorId, $command->correlationId, $command->reason, $currentState, GovernanceState::PUBLISHED);
        }

        $this->repository->updateState($command->entityType, $command->entityId, GovernanceState::PUBLISHED->value);

        $this->auditLogger->logTransition(
            GovernanceActionType::PUBLISHED,
            new GovernanceAuditContext(
                entityType: $command->entityType,
                entityId: $command->entityId,
                actorId: $command->actorId,
                correlationId: $command->correlationId,
                reason: $command->reason,
                payloadSnapshot: ['transition' => 'publish']
            ),
            $currentState,
            GovernanceState::PUBLISHED
        );

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->telemetry->publish(
            GovernanceTelemetryEvent::PUBLISH_SUCCEEDED,
            $command->correlationId,
            $command->entityType,
            $command->entityId,
            $durationMs
        );

         GovernancePublishShadowJob::dispatch(
            $command->correlationId,
            $command->entityType,
            $command->entityId,
            $draftPayload
        );
    }

    public function archive(ArchiveGovernedEntityCommand $command): void
    {
        $entity = $this->repository->findOrFail($command->entityType, $command->entityId);
        $currentState = GovernanceState::from($entity->governance_state);

        if (!$this->transitionGuard->canArchive($currentState)) {
             $this->rejectTransition('archive', $command->entityType, $command->entityId, $command->actorId, $command->correlationId, $command->reason, $currentState, GovernanceState::ARCHIVED);
        }

        $this->repository->updateState($command->entityType, $command->entityId, GovernanceState::ARCHIVED->value);

        $this->auditLogger->logTransition(
            GovernanceActionType::ARCHIVED,
            new GovernanceAuditContext(
                entityType: $command->entityType,
                entityId: $command->entityId,
                actorId: $command->actorId,
                correlationId: $command->correlationId,
                reason: $command->reason,
                payloadSnapshot: ['transition' => 'archive']
            ),
            $currentState,
            GovernanceState::ARCHIVED
        );
    }

    public function createDraft(CreateDraftCommand $command): void
    {
        $entityId = $this->repository->createDraft($command->entityType, $command->payload);

        $this->auditLogger->logTransition(
            GovernanceActionType::DRAFT_CREATED,
            new GovernanceAuditContext(
                entityType: $command->entityType,
                entityId: $entityId,
                actorId: $command->actorId,
                correlationId: $command->correlationId,
                reason: 'Draft created from command',
                payloadSnapshot: $command->payload,
            ),
            null,
            GovernanceState::DRAFT
        );
    }

    public function updateDraft(UpdateDraftCommand $command): void
    {
        $entity = $this->repository->findOrFail($command->entityType, $command->entityId);
        $currentState = GovernanceState::from($entity->governance_state);

        // Mutability Rule: Only DRAFT can be updated.
        if ($currentState !== GovernanceState::DRAFT) {
            $this->auditLogger->logTransition(
                GovernanceActionType::TRANSITION_REJECTED,
                new GovernanceAuditContext(
                    entityType: $command->entityType,
                    entityId: $command->entityId,
                    actorId: $command->actorId,
                    correlationId: $command->correlationId,
                    reason: "Update rejected due to immutable state (must be DRAFT)",
                    payloadSnapshot: [
                        'attempted_mutation' => 'updateDraft',
                        'current_state' => $currentState->value,
                    ]
                ),
                $currentState,
                $currentState
            );

            throw new DomainException("Immutable state violation: Cannot mutate entity in {$currentState->value} state.");
        }

        $this->repository->updatePayload($command->entityType, $command->entityId, $command->payload);

        $this->auditLogger->logTransition(
            GovernanceActionType::DRAFT_UPDATED,
            new GovernanceAuditContext(
                entityType: $command->entityType,
                entityId: $command->entityId,
                actorId: $command->actorId,
                correlationId: $command->correlationId,
                reason: 'Draft updated from command',
                payloadSnapshot: $command->payload,
            ),
            $currentState,
            $currentState
        );
    }
}

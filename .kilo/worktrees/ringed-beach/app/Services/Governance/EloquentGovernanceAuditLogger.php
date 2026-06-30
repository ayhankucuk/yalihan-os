<?php

namespace App\Services\Governance;

use App\Contracts\Governance\AuditLoggerInterface;
use App\DataTransferObjects\Governance\GovernanceAuditContext;
use App\Enums\Governance\GovernanceActionType;
use App\Enums\Governance\GovernanceState;
use App\Models\GovernanceAuditLog;

final class EloquentGovernanceAuditLogger implements AuditLoggerInterface
{
    public function logTransition(
        GovernanceActionType $actionType,
        GovernanceAuditContext $context,
        ?GovernanceState $fromState = null,
        ?GovernanceState $toState = null,
    ): void {
        GovernanceAuditLog::query()->create([
            'entity_type' => $context->entityType,
            'entity_id' => $context->entityId,
            'action_type' => $actionType->value,
            'from_state' => $fromState?->value,
            'to_state' => $toState?->value,
            'actor_id' => $context->actorId,
            'correlation_id' => $context->correlationId,
            'reason' => $context->reason,
            'payload_snapshot' => $this->normalizeSnapshot($context->payloadSnapshot),
        ]);
    }

    private function normalizeSnapshot(array $payloadSnapshot): array
    {
        ksort($payloadSnapshot);

        return $payloadSnapshot;
    }
}

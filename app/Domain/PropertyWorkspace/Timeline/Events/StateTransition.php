<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline\Events;

use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;

/**
 * Class StateTransition
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when workspace state transitions.
 *
 * @package App\Domain\PropertyWorkspace\Timeline\Events
 */
final class StateTransition extends WorkspaceEvent
{
    /**
     * Create a new StateTransition event
     *
     * @param string $workspaceId Workspace UUID
     * @param int $tenantId Tenant ID
     * @param string $fromState Previous state
     * @param string $toState New state
     * @param string|null $triggeredBy User or system that triggered the transition
     */
    public function __construct(
        string $workspaceId,
        int $tenantId,
        public readonly string $fromState,
        public readonly string $toState,
        public readonly ?string $triggeredBy = null
    ) {
        parent::__construct($workspaceId, $tenantId, [
            'workspace_id' => $workspaceId,
            'tenant_id' => $tenantId,
            'from_state' => $fromState,
            'to_state' => $toState,
            'triggered_by' => $triggeredBy,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Create from stored payload array
     *
     * @param array<string, mixed> $payload
     * @return self
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            workspaceId: $payload['workspace_id'],
            tenantId: (int) $payload['tenant_id'],
            fromState: $payload['from_state'],
            toState: $payload['to_state'],
            triggeredBy: $payload['triggered_by'] ?? null
        );
    }
}

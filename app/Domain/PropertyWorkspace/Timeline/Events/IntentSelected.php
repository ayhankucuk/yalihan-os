<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline\Events;

use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;

/**
 * Class IntentSelected
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when workspace intent is selected or changed.
 *
 * @package App\Domain\PropertyWorkspace\Timeline\Events
 */
final class IntentSelected extends WorkspaceEvent
{
    /**
     * Create a new IntentSelected event
     *
     * @param string $workspaceId Workspace UUID
     * @param int $tenantId Tenant ID
     * @param string $intent Selected intent
     * @param string|null $previousIntent Previous intent (for change tracking)
     */
    public function __construct(
        string $workspaceId,
        int $tenantId,
        public readonly string $intent,
        public readonly ?string $previousIntent = null
    ) {
        parent::__construct($workspaceId, $tenantId, [
            'workspace_id' => $workspaceId,
            'tenant_id' => $tenantId,
            'intent' => $intent,
            'previous_intent' => $previousIntent,
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
            intent: $payload['intent'],
            previousIntent: $payload['previous_intent'] ?? null
        );
    }
}

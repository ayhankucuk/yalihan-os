<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline\Events;

use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;

/**
 * Class CapabilityExecuted
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when a workspace capability is successfully executed.
 *
 * @package App\Domain\PropertyWorkspace\Timeline\Events
 */
final class CapabilityExecuted extends WorkspaceEvent
{
    /**
     * Create a new CapabilityExecuted event
     *
     * @param string $workspaceId Workspace UUID
     * @param int $tenantId Tenant ID
     * @param string $capability Capability identifier
     * @param array<string, mixed> $result Execution result
     * @param int|null $durationMs Execution duration in milliseconds
     */
    public function __construct(
        string $workspaceId,
        int $tenantId,
        public readonly string $capability,
        public readonly ?array $result = null,
        public readonly ?int $durationMs = null
    ) {
        parent::__construct($workspaceId, $tenantId, [
            'workspace_id' => $workspaceId,
            'tenant_id' => $tenantId,
            'capability' => $capability,
            'result' => $result,
            'duration_ms' => $durationMs,
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
            capability: $payload['capability'],
            result: $payload['result'] ?? null,
            durationMs: $payload['duration_ms'] ?? null
        );
    }
}

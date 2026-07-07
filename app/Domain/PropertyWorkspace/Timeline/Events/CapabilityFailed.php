<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline\Events;

use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;

/**
 * Class CapabilityFailed
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when a workspace capability execution fails.
 *
 * @package App\Domain\PropertyWorkspace\Timeline\Events
 */
final class CapabilityFailed extends WorkspaceEvent
{
    /**
     * Create a new CapabilityFailed event
     *
     * @param string $workspaceId Workspace UUID
     * @param int $tenantId Tenant ID
     * @param string $capability Capability identifier that failed
     * @param string $error Error message
     * @param array<string, mixed> $context Additional failure context
     * @param int|null $durationMs Time spent before failure in milliseconds
     */
    public function __construct(
        string $workspaceId,
        int $tenantId,
        public readonly string $capability,
        public readonly string $error,
        public readonly ?array $context = null,
        public readonly ?int $durationMs = null
    ) {
        parent::__construct($workspaceId, $tenantId, [
            'workspace_id' => $workspaceId,
            'tenant_id' => $tenantId,
            'capability' => $capability,
            'error' => $error,
            'context' => $context,
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
            error: $payload['error'],
            context: $payload['context'] ?? null,
            durationMs: $payload['duration_ms'] ?? null
        );
    }
}

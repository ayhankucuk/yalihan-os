<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline\Events;

use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;

/**
 * Class WorkspaceInitiated
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when a new property workspace is initiated/created.
 *
 * This is the first event in a workspace lifecycle - it marks the birth of the workspace.
 *
 * @package App\Domain\PropertyWorkspace\Timeline\Events
 */
final class WorkspaceInitiated extends WorkspaceEvent
{
    /**
     * Create a new WorkspaceInitiated event
     *
     * @param string $workspaceId Workspace UUID
     * @param int $tenantId Tenant ID
     * @param int $ilanId Listing ID
     * @param string $intent Workspace intent
     * @param string|null $templateId Optional template ID
     */
    public function __construct(
        string $workspaceId,
        int $tenantId,
        public readonly int $ilanId,
        public readonly string $intent,
        public readonly ?string $templateId = null
    ) {
        parent::__construct($workspaceId, $tenantId, [
            'workspace_id' => $workspaceId,
            'tenant_id' => $tenantId,
            'ilan_id' => $ilanId,
            'intent' => $intent,
            'template_id' => $templateId,
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
            ilanId: (int) $payload['ilan_id'],
            intent: $payload['intent'],
            templateId: $payload['template_id'] ?? null
        );
    }
}

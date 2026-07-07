<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline\Events;

use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;

/**
 * Class TemplateApplied
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when a template is applied to the workspace.
 *
 * @package App\Domain\PropertyWorkspace\Timeline\Events
 */
final class TemplateApplied extends WorkspaceEvent
{
    /**
     * Create a new TemplateApplied event
     *
     * @param string $workspaceId Workspace UUID
     * @param int $tenantId Tenant ID
     * @param string $templateId Applied template ID
     * @param array<string, mixed> $templateData Optional template configuration data
     */
    public function __construct(
        string $workspaceId,
        int $tenantId,
        public readonly string $templateId,
        public readonly ?array $templateData = null
    ) {
        parent::__construct($workspaceId, $tenantId, [
            'workspace_id' => $workspaceId,
            'tenant_id' => $tenantId,
            'template_id' => $templateId,
            'template_data' => $templateData,
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
            templateId: $payload['template_id'],
            templateData: $payload['template_data'] ?? null
        );
    }
}

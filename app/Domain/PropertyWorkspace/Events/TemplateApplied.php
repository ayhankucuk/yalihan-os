<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Events;

use JsonSerializable;

/**
 * Class TemplateApplied
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when a template is applied to the workspace.
 *
 * @package App\Domain\PropertyWorkspace\Events
 */
final class TemplateApplied implements JsonSerializable
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $templateId,
        public readonly string $timestamp,
    ) {}

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'template_id' => $this->templateId,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Create from array payload
     */
    public static function fromArray(array $data): self
    {
        return new self(
            workspaceId: $data['workspace_id'],
            templateId: $data['template_id'],
            timestamp: $data['timestamp'],
        );
    }
}

<?php

namespace App\Events\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Events\Hermes\HermesEventTrait;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyWorkspaceCreated Event
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 *
 * Emitted by DriveAgent after a Google Drive workspace is successfully created
 * for a portfolio. Downstream agents can subscribe to this event to process
 * the workspace (e.g., upload initial files, share with team, etc.).
 *
 * Implements HermesEventContract for Team Hermes event-driven foundation.
 */
class PropertyWorkspaceCreated implements HermesEventContract
{
    use Dispatchable, SerializesModels, HermesEventTrait;

    /**
     * The Drive workspace that was created
     */
    public PortfolioDriveWorkspace $workspace;

    /**
     * Additional metadata about the workspace creation
     */
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(PortfolioDriveWorkspace $workspace, array $metadata = [])
    {
        $this->workspace = $workspace;
        $this->metadata = $metadata;
    }

    /**
     * @inheritDoc
     */
    public function eventName(): string
    {
        return 'workforce.workspace.created';
    }

    /**
     * @inheritDoc
     */
    public function tenantId(): ?int
    {
        return $this->workspace->tenant_id ?? null;
    }

    /**
     * @inheritDoc
     */
    public function toPayload(): array
    {
        return [
            'ilan_id' => $this->workspace->ilan_id,
            'tenant_id' => $this->tenantId(),
            'workspace_id' => $this->workspace->getKey(),
            'drive_folder_id' => $this->workspace->drive_folder_id,
            'drive_folder_url' => $this->workspace->drive_folder_url,
            'workspace_status' => $this->workspace->workspace_status,
            'root_folder_name' => $this->workspace->root_folder_name,
            'portfolio_no' => $this->workspace->portfolio_no,
            'subfolders_count' => $this->workspace->getSubfolderCount(),
            'metadata' => $this->metadata,
        ];
    }
}

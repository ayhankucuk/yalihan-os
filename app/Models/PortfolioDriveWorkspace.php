<?php

namespace App\Models;

use App\Domain\Workspace\Enums\WorkspaceState;
use App\Scopes\TenantScope;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PortfolioDriveWorkspace Model
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 * Sprint 4.5 — Digital Property Intelligence Platform
 *
 * Property Digital Twin: central workspace entity for all portfolio operations.
 * Stores Google Drive workspace metadata + lifecycle state machine + AI completion.
 *
 * State transitions are event-driven (see WorkspaceState enum).
 *
 * @sab-context7-table portfolio_drive_workspaces
 */
class PortfolioDriveWorkspace extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'portfolio_drive_workspaces';

    protected $fillable = [
        'ilan_id',
        'tenant_id',
        'drive_folder_id',
        'drive_folder_url',
        'workspace_status',
        'lifecycle_state',
        'state_changed_at',
        'workspace_created_at',
        'ai_completion_percent',
        'ai_completion_flags',
        'root_folder_name',
        'portfolio_no',
        'subfolders_json',
    ];

    protected $casts = [
        'ilan_id' => 'integer',
        'tenant_id' => 'integer',
        'subfolders_json' => 'array',
        'ai_completion_flags' => 'array',
        'ai_completion_percent' => 'integer',
        'lifecycle_state' => WorkspaceState::class,
    ];

    // ─── Drive Workspace Status (Google Drive API state) ───────────────
    public const STATUS_CREATING = 'creating';
    public const STATUS_READY = 'ready';
    public const STATUS_ERROR = 'error';

    // ─── Global Scopes ─────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeForPortfolio($query, int $ilanId)
    {
        return $query->where('ilan_id', $ilanId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('workspace_status', $status);
    }

    public function scopeReady($query)
    {
        return $query->where('workspace_status', self::STATUS_READY);
    }

    public function scopeWithError($query)
    {
        return $query->where('workspace_status', self::STATUS_ERROR);
    }

    public function scopeByLifecycleState($query, WorkspaceState $state)
    {
        return $query->where('lifecycle_state', $state->value);
    }

    public function scopePrePublishing($query)
    {
        return $query->whereIn('lifecycle_state', [
            WorkspaceState::DRAFT->value,
            WorkspaceState::WORKSPACE_CREATED->value,
            WorkspaceState::MEDIA_READY->value,
            WorkspaceState::DESCRIPTION_READY->value,
            WorkspaceState::QUALITY_CHECKED->value,
            WorkspaceState::READY_FOR_PUBLISH->value,
        ]);
    }

    public function scopeLive($query)
    {
        return $query->whereIn('lifecycle_state', [
            WorkspaceState::PUBLISHED->value,
            WorkspaceState::ACTIVE->value,
        ]);
    }

    // ─── Drive Workspace Status Helpers ─────────────────────────────────

    public function markReady(): self
    {
        $this->update(['workspace_status' => self::STATUS_READY]);
        return $this;
    }

    public function markError(): self
    {
        $this->update(['workspace_status' => self::STATUS_ERROR]);
        return $this;
    }

    public function isReady(): bool
    {
        return $this->workspace_status === self::STATUS_READY;
    }

    public function hasError(): bool
    {
        return $this->workspace_status === self::STATUS_ERROR;
    }

    // ─── Lifecycle State Machine ─────────────────────────────────────────

    /**
     * Transition to a new lifecycle state (event-driven)
     *
     * @throws \InvalidArgumentException if transition is not allowed
     */
    public function transitionTo(WorkspaceState $newState): self
    {
        $currentState = $this->lifecycle_state ?? WorkspaceState::DRAFT;

        if (!$currentState->canTransitionTo($newState)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid state transition from %s to %s. Allowed: %s',
                $currentState->value,
                $newState->value,
                implode(', ', array_map(fn ($s) => $s->value, $currentState->allowedTransitions()))
            ));
        }

        $this->update([
            'lifecycle_state' => $newState->value,
            'state_changed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Advance to next state if valid (silent no-op if already at terminal)
     */
    public function advanceState(): self
    {
        $currentState = $this->lifecycle_state ?? WorkspaceState::DRAFT;
        $allowed = $currentState->allowedTransitions();

        if (!empty($allowed)) {
            $this->transitionTo($allowed[0]);
        }

        return $this;
    }

    /**
     * Mark workspace as created (initial state after DriveAgent)
     */
    public function markWorkspaceCreated(): self
    {
        $this->update([
            'lifecycle_state' => WorkspaceState::WORKSPACE_CREATED->value,
            'workspace_created_at' => now(),
            'state_changed_at' => now(),
            'workspace_status' => self::STATUS_READY,
        ]);
        return $this;
    }

    // ─── AI Completion Tracking ────────────────────────────────────────

    /**
     * Mark an AI agent as completed for this workspace
     */
    public function markAiAgentComplete(string $agentName, array $result = []): self
    {
        $flags = $this->ai_completion_flags ?? [];
        $flags[$agentName] = [
            'complete' => true,
            'completed_at' => now()->toIso8601String(),
            'result' => $result,
        ];

        $completedCount = count(array_filter($flags, fn ($f) => ($f['complete'] ?? false) === true));
        $totalAgents = 4; // photo, description, property_score, publish_decision
        $percent = min(100, (int) round(($completedCount / $totalAgents) * 100));

        $this->update([
            'ai_completion_flags' => $flags,
            'ai_completion_percent' => $percent,
        ]);

        // Auto-advance state based on completion
        $this->autoAdvanceLifecycle($agentName);

        return $this;
    }

    /**
     * Auto-advance lifecycle based on completed agents
     */
    private function autoAdvanceLifecycle(string $completedAgent): void
    {
        $flags = $this->ai_completion_flags ?? [];
        $state = $this->lifecycle_state ?? WorkspaceState::WORKSPACE_CREATED;

        // State advancement rules
        if ($state === WorkspaceState::WORKSPACE_CREATED
            && ($flags['photo_agent']['complete'] ?? false)) {
            $this->transitionTo(WorkspaceState::MEDIA_READY);
            return;
        }

        if ($state === WorkspaceState::MEDIA_READY
            && ($flags['description_agent']['complete'] ?? false)) {
            $this->transitionTo(WorkspaceState::DESCRIPTION_READY);
            return;
        }

        if ($state === WorkspaceState::DESCRIPTION_READY
            && ($flags['property_score_agent']['complete'] ?? false)) {
            $this->transitionTo(WorkspaceState::QUALITY_CHECKED);
            return;
        }

        if ($state === WorkspaceState::QUALITY_CHECKED
            && ($flags['publish_decision_agent']['complete'] ?? false)) {
            $this->transitionTo(WorkspaceState::READY_FOR_PUBLISH);
            return;
        }
    }

    /**
     * Check if a specific agent has completed
     */
    public function isAgentComplete(string $agentName): bool
    {
        $flags = $this->ai_completion_flags ?? [];
        return ($flags[$agentName]['complete'] ?? false) === true;
    }

    /**
     * Get completion summary for dashboard
     */
    public function getAiCompletionSummary(): array
    {
        $flags = $this->ai_completion_flags ?? [];
        $agents = ['photo_agent', 'description_agent', 'property_score_agent', 'publish_decision_agent'];

        $summary = [];
        foreach ($agents as $agent) {
            $summary[$agent] = [
                'complete' => ($flags[$agent]['complete'] ?? false),
                'completed_at' => $flags[$agent]['completed_at'] ?? null,
            ];
        }

        return [
            'percent' => $this->ai_completion_percent ?? 0,
            'agents' => $summary,
            'all_complete' => $this->ai_completion_percent >= 100,
        ];
    }

    // ─── Subfolder Helpers ───────────────────────────────────────────────

    public function getSubfolderId(string $name): ?string
    {
        $subfolders = $this->subfolders_json ?? [];
        foreach ($subfolders as $folder) {
            if (($folder['name'] ?? '') === $name) {
                return $folder['id'] ?? null;
            }
        }
        return null;
    }

    public function getSubfolderMap(): array
    {
        $map = [];
        foreach (($this->subfolders_json ?? []) as $folder) {
            if (isset($folder['name'], $folder['id'])) {
                $map[$folder['name']] = $folder['id'];
            }
        }
        return $map;
    }

    public function getSubfolderCount(): int
    {
        return count($this->subfolders_json ?? []);
    }
}

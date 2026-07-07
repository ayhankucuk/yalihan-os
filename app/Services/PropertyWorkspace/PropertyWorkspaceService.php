<?php

declare(strict_types=1);

namespace App\Services\PropertyWorkspace;

use App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate;
use App\Domain\PropertyWorkspace\Exceptions\InvalidStateTransitionException;
use App\Models\PropertyWorkspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Traits\GuardsAgentWrites;

/**
 * Class PropertyWorkspaceService
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Service layer for property workspace operations.
 *
 * @package App\Services\PropertyWorkspace
 */
class PropertyWorkspaceService
{
    use GuardsAgentWrites;
    /**
     * Get tenant ID from auth context
     *
     * @return int
     */
    protected function getTenantId(): int
    {
        return (int) Auth::user()?->tenant_id ?? 0;
    }

    /**
     * Create a new workspace
     *
     * @param int $ilanId
     * @param string $intent
     * @param string|null $templateId
     * @return PropertyWorkspace
     */
    public function createWorkspace(int $ilanId, string $intent, ?string $templateId = null): PropertyWorkspace
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($ilanId, $intent, $templateId) {
            $tenantId = $this->getTenantId();
            $workspaceUuid = (string) Str::uuid();

            // Create workspace record FIRST to get DB ID
            $workspace = PropertyWorkspace::create([
                'tenant_id' => $tenantId,
                'ilan_id' => $ilanId,
                'workspace_uuid' => $workspaceUuid,
                'intent' => $intent,
                'template_id' => $templateId,
                'state' => PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED,
            ]);

            // Now create aggregate with correct ID
            $aggregate = new PropertyWorkspaceAggregate($workspace->id, $tenantId);
            $aggregate->initializeWorkspace($workspaceUuid, $ilanId, $intent, $templateId);

            // Commit events to event store
            $aggregate->commit();

            return $workspace->fresh();
        });
    }

    /**
     * Select workspace intent
     *
     * @param string $workspaceId
     * @param string $intent
     * @return PropertyWorkspace
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function selectIntent(string $workspaceId, string $intent): PropertyWorkspace
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($workspaceId, $intent) {
            $tenantId = $this->getTenantId();

            $workspace = PropertyWorkspace::tenantScope($tenantId)
                ->byUuid($workspaceId)
                ->firstOrFail();

            $aggregate = PropertyWorkspaceAggregate::fromModel($workspace);
            $aggregate->selectIntent($intent);
            $aggregate->commit();

            $workspace->refresh();
            return $workspace;
        });
    }

    /**
     * Apply template to workspace
     *
     * @param string $workspaceId
     * @param string $templateId
     * @return PropertyWorkspace
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function applyTemplate(string $workspaceId, string $templateId): PropertyWorkspace
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($workspaceId, $templateId) {
            $tenantId = $this->getTenantId();

            $workspace = PropertyWorkspace::tenantScope($tenantId)
                ->byUuid($workspaceId)
                ->firstOrFail();

            $aggregate = PropertyWorkspaceAggregate::fromModel($workspace);
            $aggregate->applyTemplate($templateId);
            $aggregate->commit();

            $workspace->refresh();
            return $workspace;
        });
    }

    /**
     * Transition workspace state
     *
     * @param string $workspaceId
     * @param string $newState
     * @param bool $isAdmin
     * @return PropertyWorkspace
     * @throws InvalidStateTransitionException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function transitionState(string $workspaceId, string $newState, bool $isAdmin = false): PropertyWorkspace
    {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($workspaceId, $newState, $isAdmin) {
            $tenantId = $this->getTenantId();

            $workspace = PropertyWorkspace::tenantScope($tenantId)
                ->byUuid($workspaceId)
                ->firstOrFail();

            $aggregate = PropertyWorkspaceAggregate::fromModel($workspace);
            $aggregate->transitionTo($newState, $isAdmin);
            $aggregate->commit();

            // Update model with new state
            $workspace->update(['state' => $aggregate->getWorkspaceState()]);

            $workspace->refresh();
            return $workspace;
        });
    }

    /**
     * Get workspace by UUID
     *
     * @param string $workspaceId
     * @return PropertyWorkspace
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getWorkspace(string $workspaceId): PropertyWorkspace
    {
        $tenantId = $this->getTenantId();

        return PropertyWorkspace::tenantScope($tenantId)
            ->byUuid($workspaceId)
            ->firstOrFail();
    }

    /**
     * Get workspaces by ilan ID
     *
     * @param int $ilanId
     * @return \Illuminate\Database\Eloquent\Collection<int, PropertyWorkspace>
     */
    public function getWorkspacesByIlan(int $ilanId)
    {
        $tenantId = $this->getTenantId();

        return PropertyWorkspace::tenantScope($tenantId)
            ->byIlan($ilanId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get workspaces by state
     *
     * @param string $state
     * @return \Illuminate\Database\Eloquent\Collection<int, PropertyWorkspace>
     */
    public function getWorkspacesByState(string $state)
    {
        $tenantId = $this->getTenantId();

        return PropertyWorkspace::tenantScope($tenantId)
            ->byState($state)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Transition workspace to draft state
     *
     * @param string $workspaceId
     * @return PropertyWorkspace
     */
    public function transitionToDraft(string $workspaceId): PropertyWorkspace
    {
        return $this->transitionState($workspaceId, PropertyWorkspaceAggregate::STATE_DRAFT);
    }

    /**
     * Transition workspace to ready_for_review state
     *
     * @param string $workspaceId
     * @return PropertyWorkspace
     */
    public function transitionToReadyForReview(string $workspaceId): PropertyWorkspace
    {
        return $this->transitionState($workspaceId, PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);
    }

    /**
     * Transition workspace to published state
     *
     * @param string $workspaceId
     * @return PropertyWorkspace
     */
    public function transitionToPublished(string $workspaceId): PropertyWorkspace
    {
        return $this->transitionState($workspaceId, PropertyWorkspaceAggregate::STATE_PUBLISHED);
    }

    /**
     * Archive workspace
     *
     * @param string $workspaceId
     * @param bool $isAdmin
     * @return PropertyWorkspace
     */
    public function archiveWorkspace(string $workspaceId, bool $isAdmin = false): PropertyWorkspace
    {
        return $this->transitionState($workspaceId, PropertyWorkspaceAggregate::STATE_ARCHIVED, $isAdmin);
    }
}

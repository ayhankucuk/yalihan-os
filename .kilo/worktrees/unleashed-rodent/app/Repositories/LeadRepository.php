<?php

namespace App\Repositories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 🛰️ Lead Repository Pattern
 *
 * Centralizes all queries for Lead model:
 * - Fail-Safe Ownership Scoping (Phase 2.2)
 * - Filtering and Stats (Aggregation Isolation)
 * - Authority delegation for mutations
 */
class LeadRepository
{
    protected Lead $model;

    public function __construct(Lead $model)
    {
        $this->model = $model;
    }

    /**
     * Apply ownership filter based on user role
     *
     * Automatically enforces tenant isolation for non-admin users.
     * Replicates the "Fail-Safe Kernel" pattern from IlanRepository.
     */
    protected function applyOwnershipScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        // Null user: Enforce deterministic fail for unauthenticated paths
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Admin bypass: Full access
        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            return $query;
        }

        // Danışman: Only their assigned leads
        // Note: Lead SSOT ownership field is assigned_agent_id
        return $query->where('assigned_agent_id', $user->id);
    }

    /**
     * Find a single Lead by ID safely (Write/Mutation Path Isolation)
     */
    public function findById(int $id): ?Lead
    {
        return $this->applyOwnershipScope($this->model->newQuery())->find($id);
    }

    /**
     * Find a single Lead by ID or fail (Write/Mutation Path Isolation)
     */
    public function findOrFail(int $id): Lead
    {
        return $this->applyOwnershipScope($this->model->newQuery())->findOrFail($id);
    }

    /**
     * Get paginated and filtered list of Leads.
     */
    public function getLeads(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->applyOwnershipScope($this->model->newQuery());

        // Search Filter
        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('platform', 'like', "%{$search}%");
            });
        }

        // Sentiment Filter (Context7: Based on tags/AI metadata)
        if (!empty($filters['sentiment'])) {
            if ($filters['sentiment'] === 'positive') {
                $query->where('tags', 'like', '%Mutlu%');
            }
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get summary statistics for the Lead domain (Aggregation Isolation)
     */
    public function getSummaryStats(): array
    {
        $query = $this->applyOwnershipScope($this->model->newQuery());

        return [
            'toplam'     => (clone $query)->count(),
            'yeni'       => (clone $query)->where('crm_durumu', Lead::CRM_NEW)->count(),
            'ulasildi'   => (clone $query)->where('crm_durumu', Lead::CRM_REACHED)->count(),
            'nitelikli'  => (clone $query)->where('crm_durumu', Lead::CRM_QUALIFIED)->count(),
            'kazanildi'  => (clone $query)->where('crm_durumu', Lead::CRM_WON)->count(),
            'kayip'      => (clone $query)->where('crm_durumu', Lead::CRM_LOST)->count(),
        ];
    }

    /**
     * Delegate: Update Status
     * Enforces write path ownership isolation via scoped findOrFail().
     */
    public function updateStatus(int $id, int|string $newStatus, string $trigger = 'manual'): void
    {
        $lead = $this->findOrFail($id);

        /** @var \App\Services\CRM\LeadAuthorityService $service */
        $service = app(\App\Services\CRM\LeadAuthorityService::class);
        $service->updateStatus($lead, $newStatus, $trigger);
    }

    /**
     * Delegate: Assign Lead to Agent
     * Enforces write path ownership isolation via scoped findOrFail().
     */
    public function assignLeadToAgent(int $id, int $agentId, string $trigger = 'manual'): void
    {
        $lead = $this->findOrFail($id);

        /** @var \App\Services\CRM\LeadAuthorityService $service */
        $service = app(\App\Services\CRM\LeadAuthorityService::class);
        $service->assignLeadToAgent($lead, $agentId, $trigger);
    }

    /**
     * Get hot leads for proactive outreach
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function getHotLeads(int $hotThreshold = 80, int $limit = 10, ?User $user = null)
    {
        $query = $this->model->newQuery()
            ->where('quality_score', '>=', $hotThreshold)
            ->where('temperature', 'hot')
            ->where('crm_durumu', '!=', Lead::CRM_WON)
            ->where('aktif', true)
            ->orderByDesc('quality_score')
            ->orderBy('last_contacted_at', 'asc')
            ->limit($limit);

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }

    /**
     * Get warm leads for follow-up
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function getWarmLeads(int $warmThreshold = 50, int $hotThreshold = 80, int $limit = 20, ?User $user = null)
    {
        $query = $this->model->newQuery()
            ->whereBetween('quality_score', [$warmThreshold, $hotThreshold - 1])
            ->where('temperature', 'warm')
            ->where('crm_durumu', '!=', Lead::CRM_WON)
            ->where('aktif', true)
            ->orderByDesc('quality_score')
            ->limit($limit);

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }
}

<?php

namespace App\Repositories;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * 🛰️ Gorev Repository Pattern
 *
 * Centralizes all queries for Gorev (Task) model:
 * - Fail-Safe Ownership Scoping (Phase 4B)
 * - Scoped Delete Guard (Production Safety)
 * - Task lifecycle management
 * - Agent-specific task filtering
 *
 * @governance PHASE4B_SERVICE_GOVERNANCE
 * @created 2026-05-12
 * @reason Task management requires strict tenant isolation and scoped delete protection
 */
class GorevRepository
{
    protected Gorev $model;

    public function __construct(Gorev $model)
    {
        $this->model = $model;
    }

    /**
     * Apply ownership filter based on user role
     *
     * Automatically enforces tenant isolation for non-admin users.
     * Replicates the "Fail-Safe Kernel" pattern.
     *
     * ⚠️ CRITICAL: Task queries MUST be scoped to prevent cross-tenant access
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

        // Agent: Only their assigned tasks
        return $query->where('atanan_user_id', $user->id);
    }

    /**
     * Find pending tasks by lead ID
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function findPendingByLeadId(int $leadId, ?User $user = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('lead_id', $leadId)
            ->where('gorev_durumu', 'beklemede');

        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }

    /**
     * Delete pending tasks by lead ID (SCOPED)
     *
     * ⚠️ CRITICAL: Scoped delete guard - prevents cross-tenant data destruction
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     *
     * @governance SCOPED_DELETE_GUARD
     */
    public function deletePendingByLeadId(int $leadId, ?User $user = null): int
    {
        $query = $this->model->newQuery()
            ->where('lead_id', $leadId)
            ->where('gorev_durumu', 'beklemede');

        // ✅ ENFORCEMENT: Apply ownership scope BEFORE delete
        $query = $this->applyOwnershipScope($query, $user);

        return $query->delete();
    }

    /**
     * Get overdue tasks for an agent
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getOverdueTasksForAgent(int $agentId, ?User $user = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('atanan_user_id', $agentId)
            ->where('gorev_durumu', 'beklemede')
            ->where('bitis_tarihi', '<', now())
            ->orderByDesc('oncelik');

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }

    /**
     * Get today's tasks for an agent
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getTodayTasksForAgent(int $agentId, ?User $user = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('atanan_user_id', $agentId)
            ->where('gorev_durumu', 'beklemede')
            ->whereDate('bitis_tarihi', today())
            ->orderByDesc('oncelik');

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }

    /**
     * Get overdue tasks for auto-escalation
     *
     * ⚠️ ADMIN ONLY: Cross-agent aggregation
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getOverdueTasksForEscalation(int $daysOverdue = 3, ?User $user = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('gorev_durumu', 'beklemede')
            ->where('bitis_tarihi', '<', now()->subDays($daysOverdue));

        // ✅ ENFORCEMENT: Apply ownership scope (admin sees all, agent sees own)
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }

    /**
     * Get upcoming tasks for reminders
     *
     * ⚠️ ADMIN ONLY: Cross-agent aggregation
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getUpcomingTasksForReminders(int $hoursAhead = 2, ?User $user = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('gorev_durumu', 'beklemede')
            ->whereBetween('bitis_tarihi', [
                now(),
                now()->addHours($hoursAhead),
            ])
            ->with('danisman');

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }

    /**
     * Create a new task
     *
     * ✅ ENFORCEMENT: Ownership assigned at creation
     */
    public function create(array $data): Gorev
    {
        // Ensure ownership is set
        if (empty($data['atanan_user_id'])) {
            $data['atanan_user_id'] = auth()->id();
        }

        return $this->model->create($data);
    }

    /**
     * Update task
     *
     * ✅ ENFORCEMENT: Scoped update via findOrFail
     */
    public function update(int $id, array $data, ?User $user = null): Gorev
    {
        $task = $this->findOrFail($id, $user);
        $task->update($data);

        return $task->refresh();
    }

    /**
     * Find task by ID with ownership scope
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function findOrFail(int $id, ?User $user = null): Gorev
    {
        $query = $this->model->newQuery()->where('id', $id);
        $query = $this->applyOwnershipScope($query, $user);

        return $query->firstOrFail();
    }

    /**
     * Get task statistics (scoped)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     */
    public function getStatistics(?User $user = null): array
    {
        $baseQuery = $this->model->newQuery();
        $baseQuery = $this->applyOwnershipScope($baseQuery, $user);

        $pendingQuery = clone $baseQuery;
        $completedQuery = clone $baseQuery;
        $overdueQuery = clone $baseQuery;

        $pending = $pendingQuery->where('gorev_durumu', 'beklemede')->count();
        $completed = $completedQuery->where('gorev_durumu', 'tamamlandi')->count();
        $overdue = $overdueQuery
            ->where('gorev_durumu', 'beklemede')
            ->where('bitis_tarihi', '<', now())
            ->count();

        return [
            'pending_tasks' => $pending,
            'completed_tasks' => $completed,
            'overdue_tasks' => $overdue,
            'completion_rate' => $pending + $completed > 0
                ? number_format(($completed / ($pending + $completed)) * 100, 2)
                : 0,
        ];
    }
}

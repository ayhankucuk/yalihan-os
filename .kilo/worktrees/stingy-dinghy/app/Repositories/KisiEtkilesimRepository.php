<?php

namespace App\Repositories;

use App\Models\KisiEtkilesim;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * KisiEtkilesim Repository Pattern
 *
 * Centralizes all queries for KisiEtkilesim (Customer Interaction) model
 *
 * @governance PHASE4B_SERVICE_GOVERNANCE
 * @created 2026-05-12
 * @reason Customer interaction tracking requires tenant isolation
 */
class KisiEtkilesimRepository
{
    protected KisiEtkilesim $model;

    public function __construct(KisiEtkilesim $model)
    {
        $this->model = $model;
    }

    /**
     * Apply ownership filter based on user role
     */
    protected function applyOwnershipScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            return $query;
        }

        // Agent: Only interactions for their customers
        return $query->whereHas('kisi', function ($q) use ($user) {
            $q->where('danisman_id', $user->id);
        });
    }

    /**
     * Get pending followups count (scoped)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     */
    public function getPendingFollowupsCount(int $days = 30, ?User $user = null): int
    {
        $query = $this->model->newQuery()
            ->where('aktiflik_durumu', 1)
            ->where('created_at', '>=', now()->subDays($days));

        $query = $this->applyOwnershipScope($query, $user);

        return $query->count();
    }

    /**
     * Get today's activities count (scoped)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     */
    public function getTodayActivitiesCount(?User $user = null): int
    {
        $query = $this->model->newQuery()
            ->whereDate('created_at', today());

        $query = $this->applyOwnershipScope($query, $user);

        return $query->count();
    }

    /**
     * Get high priority followups count (scoped)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     */
    public function getHighPriorityFollowupsCount(int $days = 7, ?User $user = null): int
    {
        $query = $this->model->newQuery()
            ->where('aktiflik_durumu', 1)
            ->where('created_at', '<=', now()->subDays($days));

        $query = $this->applyOwnershipScope($query, $user);

        return $query->count();
    }

    /**
     * Get recent activities (scoped)
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getRecentActivities(int $limit = 15, ?User $user = null): Collection
    {
        $query = $this->model->newQuery()
            ->with(['kisi', 'kullanici'])
            ->where('aktiflik_durumu', 1)
            ->orderByDesc('etkilesim_tarihi')
            ->limit($limit);

        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }
}

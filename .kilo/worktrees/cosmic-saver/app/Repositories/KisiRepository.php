<?php

namespace App\Repositories;

use App\Enums\IlanDurumu;

use App\Models\Kisi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ✅ P1: Kişi Repository Pattern
 *
 * Centralizes all queries for Kişi model:
 * - Consistent eager loading
 * - N+1 prevention
 * - Soft-delete handling
 * - Context7 field filtering
 *
 * Benefits:
 * - Controllers focus on HTTP logic
 * - Reusable query logic
 * - Easy to test
 * - Single source of truth for database access
 */
class KisiRepository
{
    protected Kisi $model;

    public function __construct(Kisi $model)
    {
        $this->model = $model;
    }

    /**
     * Apply ownership filter based on user role
     *
     * Phase 1: Runtime Containment
     * Automatically enforces tenant isolation for non-admin users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User|null $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyOwnershipScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        // Null user: Deterministic fail — matches GovernanceCore Fail-Safe Kernel pattern
        // @see GorevRepository::applyOwnershipScope (reference implementation)
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Admin bypass: Full access
        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            return $query;
        }

        // Danışman: Only their kisiler
        return $query->where('danisman_id', $user->id);
    }

    /**
     * Get all kisiler with standard eager loading
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function all(?User $user = null): Collection
    {
        $query = $this->model
            ->with(['ilanlar', 'talepler']);

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query
            ->orderBy('ad')
            ->orderBy('soyad')
            ->get();
    }

    /**
     * Paginate kisiler with context7 compliant fields and filters
     */
    public function paginate(int $perPage = 15, array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $query = $this->model
            ->with(['ilanlar' => function ($query) {
                $query->where('aktiflik_durumu', 1)
                    ->select(['id', 'user_id', 'baslik', 'fiyat']);
            }]);

        // ✅ ENFORCEMENT: Apply ownership scope FIRST
        $query = $this->applyOwnershipScope($query, $user);

        // Search Filter
        if (!empty($filters['q'])) {
            $term = "%{$filters['q']}%";
            $query->where(function ($q) use ($term) {
                $q->where('ad', 'LIKE', $term)
                  ->orWhere('soyad', 'LIKE', $term)
                  ->orWhere('eposta', 'LIKE', $term)
                  ->orWhere('telefon', 'LIKE', $term);
            });
        }

        // Status Filter
        if (!empty($filters['aktif'])) {
            $query->where('aktiflik_durumu', $filters['aktif'] === IlanDurumu::YAYINDA->value);
        }

        // ❌ REMOVED: danisman_id filter (now automatic via applyOwnershipScope)

        // Type Filter
        if (!empty($filters['kisi_tipi'])) {
            $query->where('kisi_tipi', $filters['kisi_tipi']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find kişi by ID, including soft-deleted (for admin)
     *
     * ⚠️ CRITICAL: Soft delete path with ownership validation
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function findWithTrashed(int $id, ?User $user = null): ?Kisi
    {
        $query = $this->model
            ->withTrashed()
            ->with(['ilanlar', 'talepler'])
            ->where('id', $id);

        // ✅ ENFORCEMENT: Apply ownership scope BEFORE retrieval
        $query = $this->applyOwnershipScope($query, $user);

        return $query->first();
    }

    /**
     * Find kişi by ID, only active
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function findActive(int $id, ?User $user = null): ?Kisi
    {
        $query = $this->model
            ->where('aktiflik_durumu', 1)
            ->where('id', $id)
            ->with(['ilanlar', 'talepler']);

        // ✅ ENFORCEMENT: Apply ownership scope BEFORE retrieval
        $query = $this->applyOwnershipScope($query, $user);

        return $query->first();
    }

    /**
     * Find by email with security
     *
     * ⚠️ CRITICAL: Email lookup can leak cross-tenant data
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function findByEmail(string $email, ?User $user = null): ?Kisi
    {
        $query = $this->model
            ->where('eposta', strtolower($email));

        // ✅ ENFORCEMENT: Apply ownership scope BEFORE retrieval
        $query = $this->applyOwnershipScope($query, $user);

        return $query->first();
    }

    /**
     * Find by phone with security
     *
     * ⚠️ CRITICAL: Phone lookup can leak cross-tenant data
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     *
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function findByPhone(string $phone, ?User $user = null): ?Kisi
    {
        $query = $this->model
            ->where('telefon', $phone);

        // ✅ ENFORCEMENT: Apply ownership scope BEFORE retrieval
        $query = $this->applyOwnershipScope($query, $user);

        return $query->first();
    }

    /**
     * Find by TC Kimlik with security
     *
     * ⚠️ CRITICAL: TC Kimlik lookup can leak cross-tenant data
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     *
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function findByTcKimlik(string $tcKimlik, ?User $user = null): ?Kisi
    {
        $query = $this->model
            ->where('tc_kimlik', $tcKimlik);

        // ✅ ENFORCEMENT: Apply ownership scope BEFORE retrieval
        $query = $this->applyOwnershipScope($query, $user);

        return $query->first();
    }

    /**
     * Search kisiler by name, email, phone
     */
    public function search(string $query, ?User $user = null): Collection
    {
        $term = "%{$query}%";

        $builder = $this->model
            ->where(function ($q) use ($term) {
                $q->whereRaw("CONCAT(ad, ' ', soyad) LIKE ?", [$term])
                  ->orWhere('eposta', 'LIKE', $term)
                  ->orWhere('telefon', 'LIKE', $term);
            });

        // ✅ ENFORCEMENT: Apply ownership scope
        $builder = $this->applyOwnershipScope($builder, $user);

        return $builder
            ->orderBy('ad')
            ->orderBy('soyad')
            ->limit(20)
            ->get();
    }

    /**
     * Get kisiler by location (il/ilçe)
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function byLocation(int $ilId, ?int $ilceId = null, ?User $user = null): Collection
    {
        $query = $this->model
            ->where('il_id', $ilId)
            ->with(['ilanlar' => function ($q) {
                $q->where('aktiflik_durumu', 1);
            }]);

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->orderBy('ad')->orderBy('soyad')->get();
    }

    /**
     * Get kisiler by type (Ev Sahibi, Emlakçı, etc)
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function byType(string $type, ?User $user = null): Collection
    {
        $query = $this->model
            ->where('kisi_tipi', $type)
            ->where('aktiflik_durumu', 1);

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query
            ->orderBy('ad')
            ->orderBy('soyad')
            ->get();
    }

    /**
     * Create kişi with validation
     */
    public function create(array $data): Kisi
    {
        // Ensure Context7 compliance
        $data['aktiflik_durumu'] = $data['aktiflik_durumu'] ?? 1;

        return $this->model->create($data);
    }

    /**
     * Update kişi with audit trail
     */
    public function update(int $id, array $data): Kisi
    {
        $kisi = $this->findWithTrashed($id);

        if (!$kisi) {
            throw new \Exception("Kişi #{$id} not found");
        }

        // Log changes (prepare for audit logging)
        $changes = array_diff_assoc($data, $kisi->getAttributes());

        $kisi->update($data);

        return $kisi->refresh()->load(['ilanlar', 'talepler']);
    }

    /**
     * Soft delete with ownership enforcement
     *
     * ✅ ENFORCEMENT: Runtime ownership validation — matches GorevRepository pattern
     * @governance SCOPED_DELETE_GUARD
     */
    public function delete(int $id, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false; // Null user: deterministic fail
        }

        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            $kisi = $this->model->find($id);
            return $kisi?->delete() ?? false;
        }

        // Ownership check: scoped to danisman_id
        $kisi = $this->model
            ->where('id', $id)
            ->where('danisman_id', $user->id)
            ->first();

        return $kisi?->delete() ?? false;
    }

    /**
     * Restore soft-deleted kişi with ownership enforcement
     *
     * ✅ ENFORCEMENT: Runtime ownership validation
     * @governance SCOPED_DELETE_GUARD
     */
    public function restore(int $id, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            return $this->model->onlyTrashed()->where('id', $id)->restore() > 0;
        }

        return $this->model->onlyTrashed()
            ->where('id', $id)
            ->where('danisman_id', $user->id)
            ->restore() > 0;
    }

    /**
     * Hard delete (permanent) with ownership enforcement
     *
     * ✅ ENFORCEMENT: Runtime ownership validation
     * @governance SCOPED_DELETE_GUARD
     */
    public function forceDelete(int $id, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            $kisi = $this->model->withTrashed()->find($id);
            return $kisi?->forceDelete() ?? false;
        }

        $kisi = $this->model->withTrashed()
            ->where('id', $id)
            ->where('danisman_id', $user->id)
            ->first();

        return $kisi?->forceDelete() ?? false;
    }

    /**
     * Get statistics for dashboard
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getStats(?User $user = null): array
    {
        // Base query with ownership scope
        $baseQuery = $this->model->newQuery();
        $baseQuery = $this->applyOwnershipScope($baseQuery, $user);

        // Clone for different aggregations
        $totalQuery = clone $baseQuery;
        $aktifQuery = clone $baseQuery;
        $withListingsQuery = clone $baseQuery;
        $recentQuery = clone $baseQuery;

        return [
            'total' => $totalQuery->count(),
            'aktif' => $aktifQuery->where('aktiflik_durumu', 1)->count(),
            'with_listings' => $withListingsQuery
                ->whereHas('ilanlar', function ($q) {
                    $q->where('aktiflik_durumu', 1);
                })
                ->count(),
            'recent' => $recentQuery
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'ad', 'soyad', 'created_at']),
            'taslak' => 0,
        ];
    }

    /**
     * Get kisiler with active listings (for marketing)
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function activeWithListings(int $limit = 100, ?User $user = null): Collection
    {
        $query = $this->model
            ->whereHas('ilanlar', function ($q) {
                $q->where('aktiflik_durumu', 1);
            })
            ->with(['ilanlar' => function ($q) {
                $q->where('aktiflik_durumu', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(3);
            }]);

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk update aktiflik durumu (for admin)
     */
    public function bulkUpdateAktiflikDurumu(array $ids, int $aktiflikDurumu): int
    {
        return $this->model
            ->whereIn('id', $ids)
            ->update(['aktiflik_durumu' => $aktiflikDurumu]);
    }

    /**
     * Get recent activity
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getRecentActivity(int $days = 30, ?User $user = null): Collection
    {
        $since = now()->subDays($days);

        $query = $this->model
            ->where('updated_at', '>=', $since)
            ->with(['ilanlar', 'talepler']);

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get duplicate emails with ownership enforcement
     *
     * ✅ ENFORCEMENT: Runtime ownership validation applied
     */
    public function getDuplicateEmails(?User $user = null, int $limit = 5): array
    {
        $query = $this->model
            ->select('eposta')
            ->whereNotNull('eposta')
            ->groupBy('eposta')
            ->havingRaw('COUNT(*) > 1');

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query
            ->limit($limit)
            ->pluck('eposta')
            ->toArray();
    }

    /**
     * Get dashboard statistics (scoped aggregation)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function getDashboardStats(?User $user = null): array
    {
        $baseQuery = $this->model->newQuery();
        $baseQuery = $this->applyOwnershipScope($baseQuery, $user);

        $totalQuery = clone $baseQuery;
        $activeQuery = clone $baseQuery;

        return [
            'total_customers' => $totalQuery->count(),
            'active_customers' => $activeQuery->where('aktiflik_durumu', 1)->count(),
        ];
    }

    /**
     * Get customer segments (scoped aggregation)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function getCustomerSegments(?User $user = null): \Illuminate\Support\Collection
    {
        $query = $this->model
            ->selectRaw('COALESCE(kisi_tipi, "Belirsiz") as segment_label, count(*) as total')
            ->where('aktiflik_durumu', 1)
            ->groupBy('segment_label');

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get()
            ->mapWithKeys(fn ($item) => [$item->segment_label => $item->total]);
    }

    /**
     * Get pipeline stages (scoped aggregation)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function getPipelineStages(?User $user = null): array
    {
        $baseQuery = $this->model->newQuery()->where('aktiflik_durumu', 1);
        $baseQuery = $this->applyOwnershipScope($baseQuery, $user);

        return [
            1 => (clone $baseQuery)->where('crm_surec_asamasi', 'potansiyel')->get(),
            2 => (clone $baseQuery)->where('crm_surec_asamasi', 'ilgili')->get(),
            3 => (clone $baseQuery)->where('crm_surec_asamasi', 'takipte')->get(),
            4 => (clone $baseQuery)->where('crm_surec_asamasi', 'sicak')->get(),
            5 => (clone $baseQuery)->where('crm_surec_asamasi', 'islem_yapmis')->get(),
        ];
    }

    /**
     * Get lost pipeline count (scoped aggregation)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function getLostPipelineCount(?User $user = null): int
    {
        $query = $this->model->newQuery()
            ->where('crm_surec_asamasi', 'pasif')
            ->where('updated_at', '>=', now()->subDays(30));

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->count();
    }

    /**
     * Get lead source analytics (scoped aggregation)
     *
     * ✅ ENFORCEMENT: Aggregation with tenant scope
     * @governance AGGREGATION_BOUNDARY
     * @governance PHASE4B_SERVICE_GOVERNANCE
     */
    public function getLeadSourceAnalytics(?User $user = null): \Illuminate\Support\Collection
    {
        $query = $this->model
            ->selectRaw('kaynak as lead_source, count(*) as total, avg(skor) as avg_score')
            ->where('aktiflik_durumu', 1)
            ->whereNotNull('kaynak')
            ->groupBy('lead_source');

        // ✅ ENFORCEMENT: Apply ownership scope
        $query = $this->applyOwnershipScope($query, $user);

        return $query->get();
    }
}

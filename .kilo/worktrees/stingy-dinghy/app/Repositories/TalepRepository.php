<?php

namespace App\Repositories;

use App\Models\Talep;
use App\Models\User;
use App\Enums\TalepDurumu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * 🛰️ Talep Repository Pattern
 *
 * Centralizes all queries for Talep (Demand) model:
 * - Fail-Safe Ownership Scoping (Phase 2.1)
 * - Filtering and Stats (Aggregation Isolation)
 * - Search operations
 */
class TalepRepository
{
    protected Talep $model;

    public function __construct(Talep $model)
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

        // Danışman: Only their assigned demands
        return $query->where('danisman_id', $user->id);
    }

    /**
     * Find a single Talep by ID safely (Write/Mutation Path Isolation)
     */
    public function findById(int $id): ?Talep
    {
        return $this->applyOwnershipScope($this->model->newQuery())->find($id);
    }

    /**
     * Find a single Talep by ID or fail (Write/Mutation Path Isolation)
     */
    public function findOrFail(int $id): Talep
    {
        return $this->applyOwnershipScope($this->model->newQuery())->findOrFail($id);
    }

    /**
     * Get paginated and filtered list of Talepler.
     */
    public function getTalepler(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->applyOwnershipScope($this->model->newQuery());
        
        $query->with([
            'kisi:id,ad,soyad,telefon,email',
            'danisman:id,name,email',
        ])->select([
            'id', 'baslik', 'tip', 'talep_durumu', 'kisi_id', 'danisman_id',
            'alt_kategori_id', 'il_id', 'ilce_id', 'min_fiyat', 'max_fiyat',
            'created_at', 'updated_at',
        ]);

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('baslik', 'like', "%{$search}%")
                    ->orWhereHas('kisi', function ($k) use ($search) {
                        $k->where('ad', 'like', "%{$search}%")
                            ->orWhere('soyad', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['talep_durumu'])) {
            $query->where('talep_durumu', $filters['talep_durumu']);
        }

        if (!empty($filters['tip'])) {
            $query->where('tip', $filters['tip']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get summary statistics for the Talep domain (Aggregation Isolation)
     */
    public function getSummaryStats(): array
    {
        $query = $this->applyOwnershipScope($this->model->newQuery());

        return [
            'toplam'    => (clone $query)->count(),
            'aktif'     => (clone $query)->where('talep_durumu', TalepDurumu::AKTIF->value)->count(),
            'beklemede' => (clone $query)->where('talep_durumu', TalepDurumu::BEKLEMEDE->value)->count(),
            'eslesen'   => (clone $query)->where('talep_durumu', TalepDurumu::KARSIILANDI->value)->count(),
        ];
    }

    /**
     * Get unique statuses available in the system.
     */
    public function getAvailableStatuses(): \Illuminate\Support\Collection
    {
        $query = $this->applyOwnershipScope($this->model->newQuery());
        return $query->select('talep_durumu')->distinct()->pluck('talep_durumu');
    }

    /**
     * Search talepler (used for AJAX endpoints)
     */
    public function search(string $searchQuery, int $limit = 20): Collection
    {
        $query = $this->applyOwnershipScope($this->model->newQuery());

        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('baslik', 'like', "%{$searchQuery}%")
                  ->orWhere('aciklama', 'like', "%{$searchQuery}%")
                  ->orWhereHas('kisi', function ($kq) use ($searchQuery) {
                      $kq->where('ad', 'like', "%{$searchQuery}%")
                         ->orWhere('soyad', 'like', "%{$searchQuery}%");
                  });
            });
        }

        return $query->with(['kisi', 'kategori'])
                     ->limit($limit)
                     ->get();
    }

    /**
     * Create a new Talep
     * Delegates to AuthorityService.
     */
    public function create(array $data, ?User $actor = null): Talep
    {
        // Authority service handles the actual creation
        /** @var \App\Services\CRM\TalepAuthorityService $service */
        $service = app(\App\Services\CRM\TalepAuthorityService::class);
        return $service->createTalep($data, $actor);
    }

    /**
     * Update existing Talep
     * Enforces write path ownership isolation via scoped findOrFail().
     */
    public function update(int $id, array $data, ?User $actor = null): Talep
    {
        $talep = $this->findOrFail($id);
        
        /** @var \App\Services\CRM\TalepAuthorityService $service */
        $service = app(\App\Services\CRM\TalepAuthorityService::class);
        return $service->updateTalep($talep, $data, $actor);
    }

    /**
     * Delete existing Talep
     * Enforces write path ownership isolation via scoped findOrFail().
     */
    public function delete(int $id, ?User $actor = null): bool
    {
        $talep = $this->findOrFail($id);
        
        /** @var \App\Services\CRM\TalepAuthorityService $service */
        $service = app(\App\Services\CRM\TalepAuthorityService::class);
        return $service->deleteTalep($talep, $actor);
    }

    /**
     * Set One Cikan status
     * Enforces write path ownership isolation via scoped findOrFail().
     */
    public function setOneCikan(int $id, bool $value, ?User $actor = null): Talep
    {
        $talep = $this->findOrFail($id);
        
        /** @var \App\Services\CRM\TalepAuthorityService $service */
        $service = app(\App\Services\CRM\TalepAuthorityService::class);
        return $service->setOneCikan($talep, $value, $actor);
    }
}

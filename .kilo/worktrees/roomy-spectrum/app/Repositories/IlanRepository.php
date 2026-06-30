<?php

namespace App\Repositories;

use App\Governance\Instrumentation\RepositoryInstrumentation;
use App\Models\Ilan;
use App\Models\User;
use App\Enums\IlanDurumu;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * ✅ P1: İlan Repository Pattern
 *
 * Centralizes all queries for İlan model:
 * - Publication state handling (yayin_durumu)
 * - Location filtering (Context7)
 * - Featured listing logic
 * - SEO optimization queries
 * - Fail-Safe Ownership Scoping (Phase 2)
 *
 * Phase 4C: RepositoryInstrumentation trait ile GovernanceMetrics'e
 * tüm yazma operasyonları raporlanır. Mevcut iş mantığı değişmez.
 */
class IlanRepository
{
    use RepositoryInstrumentation;
    protected Ilan $model;
    protected \App\Services\Listing\YalihanLifecycle $lifecycle;
    protected IlanCrudService $crudService;

    public function __construct(Ilan $model, \App\Services\Listing\YalihanLifecycle $lifecycle, IlanCrudService $crudService)
    {
        $this->model = $model;
        $this->lifecycle = $lifecycle;
        $this->crudService = $crudService;
    }

    /**
     * Apply ownership filter based on user role
     *
     * Phase 2: Runtime Containment
     * Automatically enforces tenant isolation for non-admin users.
     */
    protected function applyOwnershipScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        // Null user: Enforce deterministic fail for unauthenticated paths within CRM logic
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Admin bypass: Full access
        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            return $query;
        }

        // Danışman: Only their ilanlar
        return $query->where('danisman_id', $user->id);
    }

    /**
     * Find a single Ilan by ID with ownership scope (Read/Mutation Path Isolation)
     *
     * Layer 2 Fail-Safe: Returns null if resource does not belong to current user.
     */
    public function findById(int $id): ?Ilan
    {
        return $this->applyOwnershipScope($this->model->newQuery())->find($id);
    }

    /**
     * Find a single Ilan by ID or throw 404 (Write/Mutation Path Isolation)
     *
     * Layer 2 Fail-Safe Kernel: Cross-tenant access returns deterministic 404.
     * Scoped BEFORE exception — existence is never revealed to unauthorized callers.
     */
    public function findOrFail(int $id): Ilan
    {
        return $this->applyOwnershipScope($this->model->newQuery())->findOrFail($id);
    }

    /**
     * Get admin listings with ownership scope (Aggregation Isolation)
     *
     * Replaces IlanService::getAdminListingsWithStats for tenant-isolated listing.
     * Admin users see all ilanlar; danışman users see only their own.
     */
    public function getAdminListings(array $filters = [], int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->applyOwnershipScope($this->model->newQuery())
            ->with(['kategori', 'il', 'danisman'])
            ->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('baslik', 'like', "%{$search}%")
                  ->orWhere('ilan_no', 'like', "%{$search}%")
                  ->orWhere('referans_no', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['yayin_durumu'])) {
            $durum = \App\Enums\IlanDurumu::normalize($filters['yayin_durumu']);
            if ($durum) {
                $query->where('yayin_durumu', $durum->value);
            }
        }

        // danisman_id filtresi YALNIZCA admin kullanıcılara açıktır.
        // Danışman kullanıcılar bu filtreyi bypass olarak kullanamaz —
        // applyOwnershipScope zaten tenant izolasyonunu garantiler.
        $currentUser = auth()->user();
        $isAdmin = $currentUser && (
            (method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin()) ||
            (method_exists($currentUser, 'hasRole') && $currentUser->hasRole(['admin', 'super-admin']))
        );
        if ($isAdmin && !empty($filters['danisman_id'])) {
            $query->where('danisman_id', $filters['danisman_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get active listings with location context
     */
    public function active(): Collection

    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->with(['kisi', 'il', 'ilce', 'mahalle', 'fotograflar'])
            ->orderBy('created_at', 'desc');
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Paginate active listings
     */
    public function activePaginated(int $perPage = 20): Paginator
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('aktiflik_durumu', 1)
            ->with(['kisi:id,ad_soyad,telefon', 'il', 'ilce', 'fotograflar'])
            ->orderBy('created_at', 'desc');
            
        return $this->applyOwnershipScope($query)->paginate($perPage);
    }

    /**
     * Featured listings (recently updated, one_cikan = 1)
     */
    public function featured(int $limit = 12): Collection
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('one_cikan', 1)
            ->with(['kisi', 'il', 'ilce', 'fotograflar'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit);
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Filter by location (il/ilçe/mahalle)
     */
    public function byLocation(int $ilId, ?int $ilceId = null, ?int $mahalleId = null): Collection
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('il_id', $ilId);

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        if ($mahalleId) {
            $query->where('mahalle_id', $mahalleId);
        }

        $query->with(['kisi', 'il', 'ilce', 'mahalle'])
              ->orderBy('created_at', 'desc');

        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Filter by property type (Konut, Ticari, etc)
     */
    public function byType(string $type): Collection
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('islem_turu', $type) // islem_turu column Context7
            ->with(['kisi', 'il', 'ilce'])
            ->orderBy('created_at', 'desc');
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Price range filter
     */
    public function priceRange(int $min, int $max): Collection
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereBetween('fiyat', [$min, $max])
            ->with(['kisi', 'il', 'ilce'])
            ->orderBy('fiyat');
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Search listings by title/description
     */
    public function search(string $queryStr): Collection
    {
        $term = "%{$queryStr}%";

        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where(function ($q) use ($term) {
                $q->where('baslik', 'LIKE', $term)
                    ->orWhere('aciklama', 'LIKE', $term);
            })
            ->with(['kisi', 'il', 'ilce'])
            ->orderBy('created_at', 'desc')
            ->limit(50);
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Get listings by contact
     */
    public function byContact(int $kisiId): Collection
    {
        $query = $this->model
            ->where('kisi_id', $kisiId)
            ->orderBy('created_at', 'desc');
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Recent listings (last 7 days)
     */
    public function recent(int $days = 7, int $limit = 50): Collection
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['kisi', 'il', 'ilce'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Create new listing
     *
     * Delegates to IlanCrudService to enforce write authority.
     */
    public function create(array $data): Ilan
    {
        return $this->crudService->store($data);
    }

    /**
     * Update listing
     *
     * Ensures write path ownership isolation via findOrFail() on scoped query.
     */
    public function update(int $id, array $data): Ilan
    {
        $ilan = $this->applyOwnershipScope($this->model->newQuery())->findOrFail($id);
        return $this->crudService->update($ilan, $data);
    }

    /**
     * Publish listing
     */
    public function publish(int $id): Ilan
    {
        $ilan = $this->applyOwnershipScope($this->model->newQuery())->findOrFail($id);
        return $this->lifecycle->transition($ilan, IlanDurumu::YAYINDA, null, ['source' => 'repo_publish']);
    }

    /**
     * Archive listing
     */
    public function archive(int $id): Ilan
    {
        $ilan = $this->applyOwnershipScope($this->model->newQuery())->findOrFail($id);
        return $this->lifecycle->transition($ilan, IlanDurumu::ARSIV, null, ['source' => 'repo_archive']);
    }

    /**
     * Delete listing
     */
    public function delete(int $id): bool
    {
        $ilan = $this->applyOwnershipScope($this->model->newQuery())->find($id);
        return $ilan?->delete() ?? false;
    }

    /**
     * Toggle featured state
     */
    public function toggleFeatured(int $id, bool $featured = true): Ilan
    {
        $ilan = $this->applyOwnershipScope($this->model->newQuery())->findOrFail($id);
        $ilan->update(['one_cikan' => $featured ? 1 : 0]);

        return $ilan;
    }

    /**
     * Get statistics for dashboard (Aggregation Leakage Prevention)
     */
    public function getStats(): array
    {
        $query = $this->applyOwnershipScope($this->model->newQuery());
        
        // We use clones so we don't mutate the base query for each stat calculation.
        return [
            'total' => (clone $query)->count(),
            'aktif' => (clone $query)->where('yayin_durumu', IlanDurumu::YAYINDA->value)->count(),
            'draft' => (clone $query)->where('yayin_durumu', IlanDurumu::TASLAK->value)->count(),
            'archived' => (clone $query)->where('yayin_durumu', IlanDurumu::ARSIV->value)->count(),
            'featured' => (clone $query)->where('one_cikan', 1)->count(),
            'avg_price' => (clone $query)->where('yayin_durumu', IlanDurumu::YAYINDA->value)->avg('fiyat'),
        ];
    }

    /**
     * Get listings with coordinates for map
     */
    public function withCoordinates(int $limit = 100): Collection
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->select(['id', 'baslik', 'fiyat', 'lat', 'lng', 'kapak_resmi', 'kisi_id', 'danisman_id'])
            ->with(['kisi:id,ad_soyad'])
            ->limit($limit);
            
        return $this->applyOwnershipScope($query)->get();
    }

    /**
     * Bulk state update (toplu_durum_guncelle)
     */
    public function topluDurumGuncelle(array $ids, string $durum): int
    {
        $count = 0;
        $targetEnum = IlanDurumu::normalize($durum);
        if (!$targetEnum) return 0;

        // Apply ownership scope so users can only bulk-update their own listings
        $ilanlar = $this->applyOwnershipScope($this->model->newQuery())->whereIn('id', $ids)->get();
        
        foreach ($ilanlar as $ilan) {
            try {
                $this->lifecycle->transition($ilan, $targetEnum, null, ['source' => 'repo_bulk']);
                $count++;
            } catch (\Exception $e) {
                // Log and continue bulk operation
                \Illuminate\Support\Facades\Log::error('Bulk state transition failed', [
                    'ilan_id' => $ilan->id,
                    'target' => $durum,
                    'error' => $e->getMessage()
                ]);
            }
        }
        return $count;
    }

    /**
     * Get expired listings (not updated in 30 days)
     */
    public function expired(int $days = 30): Collection
    {
        $query = $this->model
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('updated_at', '<', now()->subDays($days))
            ->with(['kisi']);
            
        return $this->applyOwnershipScope($query)->get();
    }
}

<?php

namespace App\Services;

use App\Models\Ilan;
use Illuminate\Support\Collection;

/**
 * Ilan Service (READ-ONLY)
 *
 * Phase3-WA: Write methods SEALED. All writes go through IlanCrudService.
 * Read methods (find, findOrFail, getAktifIlanlar, search, etc.) remain valid.
 *
 * @see \App\Services\Ilan\IlanCrudService for write authority
 */
class IlanService
{
    public function __construct(
        private CacheManager $cache
    ) {}

    /**
     * @throws \RuntimeException Always — write authority moved to IlanCrudService
     */
    private function writeGuard(string $method): never
    {
        throw new \RuntimeException(
            "IlanService::{$method}() is sealed. See SAB v24.0 §6 (Write Authority). " .
            "Use IlanCrudService for single writes or IlanBulkService (WIP) for batch operations."
        );
    }

    /**
     * @deprecated SEALED: Use IlanCrudService::store() instead.
     * @throws \RuntimeException Always — write authority moved to IlanCrudService
     */
    public function create(array $data): never
    {
        $this->writeGuard(__FUNCTION__);
    }

    /**
     * @deprecated SEALED: Use IlanCrudService::update() instead.
     * @throws \RuntimeException Always — write authority moved to IlanCrudService
     */
    public function update(Ilan $ilan, array $data): never
    {
        $this->writeGuard(__FUNCTION__);
    }

    /**
     * @deprecated SEALED: Use IlanCrudService::destroy() instead.
     * @throws \RuntimeException Always — write authority moved to IlanCrudService
     */
    public function delete(Ilan $ilan): never
    {
        $this->writeGuard(__FUNCTION__);
    }

    /**
     * Find ilan by ID with cache read-through
     * Eager loads relations to prevent N+1
     */
    public function find(int $id): ?Ilan
    {
        return $this->cache->remember(
            (string)$id,
            'ilan',
            fn() => Ilan::with(['kategori', 'danisman', 'ozellikler', 'il', 'ilce', 'mahalle'])->find($id)
        );
    }

    /**
     * Get ilan by ID or fail with cache
     */
    public function findOrFail(int $id): Ilan
    {
        return $this->cache->remember(
            (string)$id,
            'ilan',
            fn() => Ilan::with(['kategori', 'danisman', 'ozellikler', 'il', 'ilce', 'mahalle'])->findOrFail($id)
        );
    }

    /**
     * Get all active ilanlar with cache
     */
    public function getAktifIlanlar(int $perPage = 20): Collection
    {
        return $this->cache->remember(
            "aktif:page:{$perPage}",
            'ilan',
            function() use ($perPage) {
                return Ilan::with(['kategori', 'danisman', 'il', 'ilce', 'mahalle'])
                    ->where('yayin_durumu', \App\Enums\IlanDurumu::YAYINDA->value)
                    ->where('aktiflik_durumu', 1)
                    ->orderBy('created_at', 'desc') // context7-ignore
                    ->limit($perPage)
                    ->get();
            }
        );
    }

    /**
     * Search ilanlar (no cache - dynamic results)
     */
    public function search(array $filters): Collection
    {
        $query = Ilan::with(['kategori', 'danisman', 'il', 'ilce', 'mahalle']);

        if (isset($filters['baslik'])) {
            $query->where('baslik', 'LIKE', "%{$filters['baslik']}%");
        }

        if (isset($filters['min_fiyat'])) {
            $query->where('fiyat', '>=', $filters['min_fiyat']);
        }

        if (isset($filters['max_fiyat'])) {
            $query->where('fiyat', '<=', $filters['max_fiyat']);
        }

        if (isset($filters['kategori_id'])) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        return $query->get();
    }

    /**
     * Get base query for danisman listings (for pagination and filtering)
     */
    public function getDanismanIlanlar(int $danismanId)
    {
        return Ilan::where('danisman_id', $danismanId);
    }

    /**
     * Calculate dashboard statistics for a danisman
     */
    public function calculateDanismanIlanStats(int $danismanId): array
    {
        $activeDurum = \App\Enums\IlanDurumu::YAYINDA->value;
        $pendingDurum = \App\Enums\IlanDurumu::BEKLEMEDE->value;

        // ✅ OPTIMIZED: Calculate statistics in single query (no N+1)
        $statsRaw = Ilan::where('danisman_id', $danismanId)
            ->selectRaw(
                'COUNT(*) as total_listings,
                 SUM(CASE WHEN yayin_durumu = ? THEN 1 ELSE 0 END) as active_listings,
                 SUM(CASE WHEN yayin_durumu = ? THEN 1 ELSE 0 END) as pending_listings,
                 COALESCE(SUM(goruntulenme), 0) as total_views',
                [$activeDurum, $pendingDurum]
            )
            ->first();

        return [
            'total_listings' => (int) $statsRaw->total_listings,
            'active_listings' => (int) $statsRaw->active_listings, // context7-ignore
            'pending_listings' => (int) $statsRaw->pending_listings,
            'total_views' => (int) $statsRaw->total_views,
        ];
    }

    /**
     * Bulk change status for Listings owned by a specific Danışman.
     * 🛡️ GOVERNANCE: Use status instead of durum for Business Layer API.
     */
    public function bulkChangeStatusForDanisman(array $ilanIds, int $danismanId, string $statusValue): void
    {
        Ilan::whereIn('id', $ilanIds)
            ->where('danisman_id', $danismanId)
            ->update(['yayin_durumu' => $statusValue]);
    }

    /**
     * @deprecated SEALED: Write authority moved to IlanCrudService/BulkService.
     * @throws \RuntimeException Always
     */
    public function bulkDeleteForDanisman(array $ilanIds, int $danismanId): never
    {
        $this->writeGuard(__FUNCTION__);
    }

    /**
     * Get raw statistics for danisman (used in AJAX calls)
     */
    public function getDanismanRawStats(int $danismanId): array
    {
        return [
            'total_listings' => Ilan::where('danisman_id', $danismanId)->count(),
            'active_listings' => Ilan::where('danisman_id', $danismanId) // context7-ignore
                ->where('yayin_durumu', \App\Enums\IlanDurumu::YAYINDA->value)
                ->count(),
            'pending_listings' => Ilan::where('danisman_id', $danismanId)
                ->where('yayin_durumu', \App\Enums\IlanDurumu::BEKLEMEDE->value)
                ->count(),
            'draft_listings' => Ilan::where('danisman_id', $danismanId)
                ->where('yayin_durumu', \App\Enums\IlanDurumu::TASLAK->value)
                ->count(),
            'inactive_listings' => Ilan::where('danisman_id', $danismanId)
                ->where('yayin_durumu', \App\Enums\IlanDurumu::PASIF->value)
                ->count(),
            'total_views' => Ilan::where('danisman_id', $danismanId)
                ->sum('goruntulenme') ?? 0,
            'this_month' => Ilan::where('danisman_id', $danismanId)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];
    }
}

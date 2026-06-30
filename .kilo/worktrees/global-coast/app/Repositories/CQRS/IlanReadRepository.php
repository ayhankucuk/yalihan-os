<?php

namespace App\Repositories\CQRS;

use App\Models\Projections\IlanReadModel;
use App\Services\Cache\TenantCacheService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class IlanReadRepository
 *
 * Query-optimized, cached repository for IlanReadModel projections.
 * Enforces strict multi-tenant isolation and fail-safe ownership scoping.
 *
 * @package App\Repositories\CQRS
 */
class IlanReadRepository
{
    /**
     * @var IlanReadModel
     */
    protected IlanReadModel $model;

    /**
     * @var TenantCacheService
     */
    protected TenantCacheService $cache;

    /**
     * IlanReadRepository constructor.
     *
     * @param IlanReadModel $model
     * @param TenantCacheService $cache
     */
    public function __construct(IlanReadModel $model, TenantCacheService $cache)
    {
        $this->model = $model;
        $this->cache = $cache;
    }

    /**
     * Find listing projection by ID with multi-tenant caching
     *
     * @param int $id
     * @return IlanReadModel|null
     */
    public function findById(int $id): ?IlanReadModel
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('ilanlar_read', "id:{$id}");

        return $this->cache->remember($cacheKey, 'medium', function () use ($id, $tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->find($id);
        });
    }

    /**
     * Find listing projection by aggregate ID (ilan_id) with multi-tenant caching
     *
     * @param int $ilanId
     * @return IlanReadModel|null
     */
    public function findByIlanId(int $ilanId): ?IlanReadModel
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('ilanlar_read', "ilan_id:{$ilanId}");

        return $this->cache->remember($cacheKey, 'medium', function () use ($ilanId, $tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('ilan_id', $ilanId)
                ->orderBy('id')
                ->first();
        });
    }

    /**
     * Get active listing projections for the current tenant context
     *
     * @return Collection
     */
    public function getActiveListings(): Collection
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('ilanlar_read', "tenant:{$tenantId}:active");

        return $this->cache->remember($cacheKey, 'medium', function () use ($tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('aktiflik_durumu', 1)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
        });
    }

    /**
     * Get active listings as array for the specified tenant
     *
     * @param int $tenantId
     * @return array
     */
    public function getActiveList(int $tenantId): array
    {
        $cacheKey = $this->cache->key('ilanlar_read', "tenant:{$tenantId}:active_list");

        return $this->cache->remember($cacheKey, 'medium', function () use ($tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('aktiflik_durumu', 1)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
                ->all();
        });
    }
}

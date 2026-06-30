<?php

namespace App\Repositories\CQRS;

use App\Models\Projections\LeadReadModel;
use App\Services\Cache\TenantCacheService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class LeadReadRepository
 *
 * Query-optimized, cached repository for LeadReadModel projections.
 * Enforces strict multi-tenant isolation and fail-safe ownership scoping.
 *
 * @package App\Repositories\CQRS
 */
class LeadReadRepository
{
    /**
     * @var LeadReadModel
     */
    protected LeadReadModel $model;

    /**
     * @var TenantCacheService
     */
    protected TenantCacheService $cache;

    /**
     * LeadReadRepository constructor.
     *
     * @param LeadReadModel $model
     * @param TenantCacheService $cache
     */
    public function __construct(LeadReadModel $model, TenantCacheService $cache)
    {
        $this->model = $model;
        $this->cache = $cache;
    }

    /**
     * Find lead projection by ID with multi-tenant caching
     *
     * @param int $id
     * @return LeadReadModel|null
     */
    public function findById(int $id): ?LeadReadModel
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('leads_read', "id:{$id}");

        return $this->cache->remember($cacheKey, 'medium', function () use ($id, $tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->find($id);
        });
    }

    /**
     * Find lead projection by UUID with multi-tenant caching
     *
     * @param string $uuid
     * @return LeadReadModel|null
     */
    public function findByUuid(string $uuid): ?LeadReadModel
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('leads_read', "uuid:{$uuid}");

        return $this->cache->remember($cacheKey, 'medium', function () use ($uuid, $tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('uuid', $uuid)
                ->orderBy('id')
                ->first();
        });
    }

    /**
     * Get all active leads for the current tenant context
     *
     * @return Collection
     */
    public function getAllActive(): Collection
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('leads_read', "tenant:{$tenantId}:active");

        return $this->cache->remember($cacheKey, 'medium', function () use ($tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('aktiflik_durumu', 1)
                ->orderBy('id')
                ->get();
        });
    }
}

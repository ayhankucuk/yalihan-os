<?php

namespace App\Repositories\CQRS;

use App\Models\Projections\KisiReadModel;
use App\Services\Cache\TenantCacheService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class KisiReadRepository
 *
 * Query-optimized, cached repository for KisiReadModel projections.
 * Enforces strict multi-tenant isolation and fail-safe ownership scoping.
 *
 * @package App\Repositories\CQRS
 */
class KisiReadRepository
{
    /**
     * @var KisiReadModel
     */
    protected KisiReadModel $model;

    /**
     * @var TenantCacheService
     */
    protected TenantCacheService $cache;

    /**
     * KisiReadRepository constructor.
     *
     * @param KisiReadModel $model
     * @param TenantCacheService $cache
     */
    public function __construct(KisiReadModel $model, TenantCacheService $cache)
    {
        $this->model = $model;
        $this->cache = $cache;
    }

    /**
     * Find person projection by ID with multi-tenant caching
     *
     * @param int $id
     * @return KisiReadModel|null
     */
    public function findById(int $id): ?KisiReadModel
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('kisiler_read', "id:{$id}");

        return $this->cache->remember($cacheKey, 'medium', function () use ($id, $tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->find($id);
        });
    }

    /**
     * Find person projection by UUID with multi-tenant caching
     *
     * @param string $uuid
     * @return KisiReadModel|null
     */
    public function findByUuid(string $uuid): ?KisiReadModel
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('kisiler_read', "uuid:{$uuid}");

        return $this->cache->remember($cacheKey, 'medium', function () use ($uuid, $tenantId) {
            return $this->model->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('uuid', $uuid)
                ->orderBy('id')
                ->first();
        });
    }

    /**
     * Get all active person projections for the current tenant context
     *
     * @return Collection
     */
    public function getAllActive(): Collection
    {
        $tenantId = $this->cache->resolveTenantId();
        $cacheKey = $this->cache->key('kisiler_read', "tenant:{$tenantId}:active");

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
     * Find an active contact by UUID under strict tenant confinement.
     *
     * @param int $tenantId Canonical Tenant Database Authority Identifier
     * @param string $uuid Event-Sourced Aggregate root identifier (UUID v4)
     * @return KisiReadModel|null
     *
     * @throws \App\Domain\CQRS\Exceptions\CrossTenantAccessException If malicious cross-tenant mapping is detected
     */
    public function bulCeresel(int $tenantId, string $uuid): ?KisiReadModel
    {
        if ($tenantId === 0) {
            return null;
        }

        // PhpRedis Tagged Caching Engine Confinement with Redis Cluster slot-pinning (<1ms latency budget)
        return \Illuminate\Support\Facades\Cache::tags(["{kiraci_{$tenantId}}", "{kiraci_{$tenantId}}:kisi_{$uuid}"])
            ->remember("kisi_okuma_modeli:{$uuid}", now()->addHours(2), function () use ($tenantId, $uuid) {
                /** @var KisiReadModel|null $model */
                $model = KisiReadModel::where('tenant_id', $tenantId)
                    ->where('uuid', $uuid)
                    ->where('aktiflik_durumu', true)
                    ->first();

                // Defensive Runtime Security Guard
                if ($model && (int)$model->tenant_id !== $tenantId) {
                    throw new \App\Domain\CQRS\Exceptions\CrossTenantAccessException(
                        "SAB SECURITY VIOLATION: Cross-tenant data access attempt intercepted."
                    );
                }

                return $model;
            });
    }
}

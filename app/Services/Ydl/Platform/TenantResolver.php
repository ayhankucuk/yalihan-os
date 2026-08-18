<?php

namespace App\Services\Ydl\Platform;

use App\Models\Ilan;
use App\Models\PropertyReservation;

/**
 * TenantResolver — Domain implementation of model-specific tenant resolution.
 *
 * Kural (Charter §3.4):
 *   "Model-specific query'ler hardcoded olmaz.
 *    Merkezi TenantResolver üzerinden çözümlenir."
 *
 * All model-specific queries for tenant_id resolution live here.
 * TenantBoundaryGuard (platform) delegates to this class to get
 * the actual tenant_id of a record — it never writes its own queries.
 *
 * Model-specific queries (NOT in TenantBoundaryGuard):
 *   - Ilan::withoutGlobalScopes()->findOrFail($id)->tenant_id
 *   - PropertyReservation::withoutGlobalScopes()->findOrFail($id)->tenant_id
 *
 * Responsibilities:
 *   - Resolve tenant_id for Ilan records
 *   - Resolve tenant_id for PropertyReservation records
 *   - Throw ModelNotFoundException if record does not exist
 *   - Use withoutGlobalScopes() to bypass tenant global scopes
 *
 * Does NOT include:
 *   - Business rules (publish, cancel, override)
 *   - State transitions
 *   - Evidence production
 */
class TenantResolver implements TenantResolverInterface
{
    /**
     * Resolve the tenant ID for a given model record.
     *
     * Delegates to model-specific methods.
     *
     * @param string $modelClass
     * @param int $recordId
     * @return int
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolve(string $modelClass, int $recordId): int
    {
        return match ($modelClass) {
            Ilan::class => $this->resolveIlan($recordId),
            PropertyReservation::class => $this->resolveReservation($recordId),
            default => throw new \DomainException(
                "TenantResolver: unknown model class {$modelClass}"
            ),
        };
    }

    /**
     * Resolve the tenant ID for an Ilan.
     *
     * @param int $ilanId
     * @return int
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveIlan(int $ilanId): int
    {
        return Ilan::withoutGlobalScopes()->findOrFail($ilanId)->tenant_id;
    }

    /**
     * Resolve the tenant ID for a PropertyReservation.
     *
     * @param int $reservationId
     * @return int
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveReservation(int $reservationId): int
    {
        return PropertyReservation::withoutGlobalScopes()->findOrFail($reservationId)->tenant_id;
    }
}

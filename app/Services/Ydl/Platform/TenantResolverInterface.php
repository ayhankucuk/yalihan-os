<?php

namespace App\Services\Ydl\Platform;

/**
 * TenantResolverInterface — Domain implementation of model-specific tenant resolution.
 *
 * Kural (Charter §3.4):
 *   "Model-specific query'ler hardcoded olmaz.
 *    Merkezi TenantResolver üzerinden çözümlenir."
 *
 * TenantBoundaryGuard (platform) delegates to this interface
 * to get the actual tenant_id of a record.
 * Each domain model has its own TenantResolver implementation.
 *
 * Responsibilities:
 *   - Resolve tenant_id for a given model + record ID
 *   - Use withoutGlobalScopes() for bypass of tenant global scopes
 *   - Throw ModelNotFoundException if record does not exist
 *
 * Does NOT include:
 *   - Business rules (publish, cancel, override)
 *   - State transitions
 *   - Evidence production
 */
interface TenantResolverInterface
{
    /**
     * Resolve the tenant ID for a given model record.
     *
     * READ ONLY — queries DB to find the record's tenant.
     *
     * @param string $modelClass Fully-qualified model class name
     * @param int $recordId Primary key of the record
     * @return int The tenant_id of the record
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolve(string $modelClass, int $recordId): int;

    /**
     * Resolve the tenant ID for an Ilan.
     *
     * @param int $ilanId
     * @return int
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveIlan(int $ilanId): int;

    /**
     * Resolve the tenant ID for a PropertyReservation.
     *
     * @param int $reservationId
     * @return int
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveReservation(int $reservationId): int;
}

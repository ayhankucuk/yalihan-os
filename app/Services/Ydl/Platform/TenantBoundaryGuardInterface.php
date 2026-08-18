<?php

namespace App\Services\Ydl\Platform;

/**
 * TenantBoundaryGuardInterface — Platform-level cross-tenant access enforcement.
 *
 * PILOT-001 + PILOT-002 common tenant isolation primitive.
 *
 * CRITICAL CONSTRAINT (Charter §3.4):
 *   TenantBoundaryGuard has ZERO domain knowledge.
 *   It does NOT know about: publish, reservation, cancel, override,
 *   Ilan, PropertyReservation, IlanCrudService, ReservationService,
 *   or any domain-specific semantics.
 *
 *   It knows ONLY:
 *     - A record of some model type exists
 *     - What tenant that record belongs to
 *     - Whether that matches the expected tenant
 *
 * READ-ONLY enforcement boundary:
 *   - Guard only answers: "Does this record belong to this tenant?"
 *   - Guard does NOT write to DB
 *   - Guard does NOT produce evidence
 *   - Guard does NOT resolve tenant IDs (TenantResolver does)
 *
 * Pipeline ownership:
 *   Domain Orchestrator   → ne yapılacağına karar verir
 *           ↓
 *   TenantBoundaryGuard   → tenant doğrulaması (platform primitive)
 *           ↓
 *   TenantResolver        → model-specific query ile tenant ID'yi alır
 *           ↓
 *   DB                    → kaydı döner
 *
 * Domain-agnostic: all parameters are primitive strings.
 * No App\Models\... imports.
 */
interface TenantBoundaryGuardInterface
{
    /**
     * Verify a record belongs to the expected tenant.
     *
     * READ ONLY — does not modify any state.
     *
     * @param string $modelClass Fully-qualified model class name (e.g. 'App\Models\Ilan')
     * @param int $recordId Primary key of the record
     * @param int $expectedTenantId The tenant ID that should own this record
     * @throws \DomainException if the record belongs to a different tenant
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the record does not exist
     */
    public function verify(string $modelClass, int $recordId, int $expectedTenantId): void;

    /**
     * Verify an Ilan record belongs to the expected tenant.
     *
     * Convenience method — delegates to verify() with Ilan model.
     *
     * @param int $ilanId
     * @param int $expectedTenantId
     * @throws \DomainException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function verifyIlan(int $ilanId, int $expectedTenantId): void;

    /**
     * Verify a PropertyReservation record belongs to the expected tenant.
     *
     * Convenience method — delegates to verify() with PropertyReservation model.
     *
     * @param int $reservationId
     * @param int $expectedTenantId
     * @throws \DomainException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function verifyReservation(int $reservationId, int $expectedTenantId): void;
}

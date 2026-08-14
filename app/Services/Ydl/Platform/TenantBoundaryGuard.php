<?php

namespace App\Services\Ydl\Platform;

/**
 * TenantBoundaryGuard — Platform-level cross-tenant access enforcement.
 *
 * PILOT-001 + PILOT-002 common tenant isolation primitive.
 *
 * Provides ONLY tenant ID comparison — zero domain knowledge.
 * Does NOT know about: publish, reservation, cancel, override,
 * Ilan, PropertyReservation, or any domain-specific semantics.
 *
 * READ-ONLY boundary (Charter §3.3):
 *   - Guard only answers: "Does this record belong to this tenant?"
 *   - Guard does NOT write to event log
 *   - Guard does NOT produce evidence
 *   - Guard does NOT resolve tenant IDs (TenantResolver does)
 *
 * Dependency injection:
 *   TenantResolver is injected — not hardcoded.
 *   This keeps the guard platform-level while allowing
 *   model-specific queries to stay in domain implementations.
 *
 * Domain-agnostic: all parameters are primitive strings.
 * No App\Models\... or App\Services\... imports in verify().
 */
class TenantBoundaryGuard implements TenantBoundaryGuardInterface
{
    private TenantResolverInterface $resolver;

    public function __construct(TenantResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Verify a record belongs to the expected tenant.
     *
     * @param string $modelClass Fully-qualified model class name
     * @param int $recordId
     * @param int $expectedTenantId
     * @throws \DomainException if cross-tenant access is detected
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if record not found
     */
    public function verify(string $modelClass, int $recordId, int $expectedTenantId): void
    {
        $actualTenantId = $this->resolver->resolve($modelClass, $recordId);

        if ($actualTenantId !== $expectedTenantId) {
            throw new \DomainException(
                "Cross-tenant access rejected: record {$modelClass}#{$recordId} " .
                "belongs to tenant {$actualTenantId}, expected {$expectedTenantId}"
            );
        }
    }

    /**
     * Verify an Ilan record belongs to the expected tenant.
     *
     * @param int $ilanId
     * @param int $expectedTenantId
     * @throws \DomainException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function verifyIlan(int $ilanId, int $expectedTenantId): void
    {
        $this->verify(\App\Models\Ilan::class, $ilanId, $expectedTenantId);
    }

    /**
     * Verify a PropertyReservation record belongs to the expected tenant.
     *
     * @param int $reservationId
     * @param int $expectedTenantId
     * @throws \DomainException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function verifyReservation(int $reservationId, int $expectedTenantId): void
    {
        $this->verify(\App\Models\PropertyReservation::class, $reservationId, $expectedTenantId);
    }
}

<?php

namespace App\Contracts\Property;

use App\DTOs\Property\OverrideAuditRecord;
use App\DTOs\Property\OverrideResult;

/**
 * ConflictOverrideContract
 *
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * Override allows authorized actors (Admin, Super-Admin) to force-create
 * a reservation despite a detected conflict.
 *
 * MANDATORY REQUIREMENTS (ADR-003):
 * 1. Authorized actor only (admin, super-admin)
 * 2. Explicit reason is required
 * 3. Audit record must be created
 * 4. ConflictOverriddenEvent must be dispatched
 * 5. Override does NOT bypass ConflictDetectionService — detection still runs
 *
 * Detection ≠ Rejection ≠ Override
 * Override is an EXPLICIT authorized decision, not a detection bypass.
 */
interface ConflictOverrideContract
{
    /**
     * Check if an actor is authorized to override a conflict.
     *
     * @param int $actorUserId The user attempting the override
     * @return bool
     */
    public function canOverride(int $actorUserId): bool;

    /**
     * Execute a conflict override.
     *
     * Validates actor authorization, records audit trail, dispatches event.
     * Does NOT create the reservation — that remains the application layer's responsibility.
     *
     * @param int    $actorUserId  The authorized user performing the override
     * @param int    $tenantId     Tenant context
     * @param int    $propertyId   Target property
     * @param string $startDate    Requested start date (inclusive)
     * @param string $endDate      Requested end date (exclusive)
     * @param string $reason       Mandatory justification for the override
     * @param array  $conflictData Original ConflictResult data being overridden
     *
     * @return OverrideResult
     *
     * @throws \App\Exceptions\Reservation\OverrideNotAuthorizedException When actor not authorized
     * @throws \App\Exceptions\Reservation\OverrideReasonRequiredException When reason is empty
     */
    public function override(
        int    $actorUserId,
        int    $tenantId,
        int    $propertyId,
        string $startDate,
        string $endDate,
        string $reason,
        array  $conflictData
    ): OverrideResult;

    /**
     * Get override audit trail for a property.
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @return OverrideAuditRecord[]
     */
    public function getAuditTrail(int $tenantId, int $propertyId): array;
}

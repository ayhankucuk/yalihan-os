<?php

namespace App\Services\Property;

use App\Contracts\Property\ConflictOverrideContract;
use App\DTOs\Property\OverrideAuditRecord;
use App\DTOs\Property\OverrideResult;
use App\Events\Reservation\ConflictOverriddenEvent;
use App\Exceptions\Reservation\OverrideNotAuthorizedException;
use App\Exceptions\Reservation\OverrideReasonRequiredException;
use App\Models\Ilan;
use App\Models\User;

/**
 * ConflictOverrideService
 *
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * Allows authorized actors (Admin, Super-Admin) to explicitly override
 * a conflict block with a mandatory justification reason and full audit trail.
 *
 * RULES (ADR-003):
 * 1. Only admin and super-admin may override
 * 2. Reason is mandatory (non-empty string)
 * 3. Audit record created on every override
 * 4. ConflictOverriddenEvent dispatched
 * 5. Does NOT bypass ConflictDetectionService — detection still runs
 * 6. Override does NOT write availability rows — that is the application layer's job
 *
 * Detection ≠ Rejection ≠ Override (ADR-003)
 */
class ConflictOverrideService implements ConflictOverrideContract
{
    /**
     * Authorized roles for conflict override.
     */
    private const AUTHORIZED_ROLES = ['admin', 'super-admin'];

    /**
     * In-memory audit trail (for test environment).
     * In production, this would be persisted to a DB table.
     */
    private array $auditTrail = [];

    /**
     * Check if an actor is authorized to override a conflict.
     */
    public function canOverride(int $actorUserId): bool
    {
        $user = User::find($actorUserId);

        if (!$user) {
            return false;
        }

        foreach (self::AUTHORIZED_ROLES as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute a conflict override.
     *
     * @throws OverrideNotAuthorizedException When actor not authorized
     * @throws OverrideReasonRequiredException When reason is empty
     * @throws \Exception When property does not belong to the given tenant (cross-tenant violation)
     */
    public function override(
        int    $actorUserId,
        int    $tenantId,
        int    $propertyId,
        string $startDate,
        string $endDate,
        string $reason,
        array  $conflictData
    ): OverrideResult {
        // 1. Validate authorization
        if (!$this->canOverride($actorUserId)) {
            throw new OverrideNotAuthorizedException($actorUserId);
        }

        // 2. Validate reason is non-empty
        if (empty(trim($reason))) {
            throw new OverrideReasonRequiredException();
        }

        // 3. Tenant isolation: verify property belongs to the given tenant.
        // withoutGlobalScopes bypasses TenantScope so we can check ownership
        // regardless of active tenant context (same pattern as AvailabilityProjectionService).
        $ilan = Ilan::withoutGlobalScopes()->find($propertyId);
        if (!$ilan) {
            throw new \Exception("Override rejected: property {$propertyId} not found.");
        }
        if ($ilan->tenant_id !== null && (int) $ilan->tenant_id !== $tenantId) {
            throw new \Exception(
                "Override rejected: cross-tenant violation — " .
                "property {$propertyId} belongs to tenant {$ilan->tenant_id}, not {$tenantId}."
            );
        }

        // 3. Build audit record
        $overrideId    = uniqid('override_', true);
        $correlationId = uniqid('corr_', true);
        $now           = new \DateTimeImmutable();

        $auditRecord = new OverrideAuditRecord(
            overrideId:      $overrideId,
            actorUserId:     $actorUserId,
            tenantId:        $tenantId,
            propertyId:      $propertyId,
            startDate:       $startDate,
            endDate:         $endDate,
            reason:          trim($reason),
            conflictDates:   $conflictData['conflict_dates'] ?? [],
            blockingSources: $conflictData['blocking_sources'] ?? [],
            correlationId:   $correlationId,
            overriddenAt:    $now,
        );

        // 4. Persist audit record (in-memory for Phase 3C; replace with DB in Phase 3D+)
        $this->auditTrail[] = $auditRecord;

        // 5. Dispatch domain event
        event(new ConflictOverriddenEvent(
            overrideId:     $overrideId,
            actorUserId:    $actorUserId,
            tenantId:       $tenantId,
            propertyId:     $propertyId,
            startDate:      $startDate,
            endDate:        $endDate,
            reason:         trim($reason),
            conflictDates:  $conflictData['conflict_dates'] ?? [],
            correlationId:  $correlationId,
            overriddenAt:   $now,
        ));

        return OverrideResult::granted($actorUserId, $auditRecord);
    }

    /**
     * Get override audit trail for a property.
     *
     * @return OverrideAuditRecord[]
     */
    public function getAuditTrail(int $tenantId, int $propertyId): array
    {
        return array_values(array_filter(
            $this->auditTrail,
            fn(OverrideAuditRecord $r) => $r->tenantId === $tenantId && $r->propertyId === $propertyId
        ));
    }
}

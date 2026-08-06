<?php

namespace App\DTOs\Property;

/**
 * OverrideAuditRecord
 *
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * Immutable record of a conflict override action.
 * Stored for audit trail — who, when, why, what was overridden.
 *
 * No personal data, no financial data.
 */
final class OverrideAuditRecord
{
    public function __construct(
        public readonly string $overrideId,       // Unique ID for this override action
        public readonly int    $actorUserId,      // Who performed the override
        public readonly int    $tenantId,
        public readonly int    $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $reason,           // Mandatory justification
        public readonly array  $conflictDates,    // What was being overridden
        public readonly array  $blockingSources,  // Blocking sources that were overridden
        public readonly string $correlationId,    // Links to ConflictDetectedEvent
        public readonly \DateTimeImmutable $overriddenAt,
    ) {}

    public function toArray(): array
    {
        return [
            'override_id'      => $this->overrideId,
            'actor_user_id'    => $this->actorUserId,
            'tenant_id'        => $this->tenantId,
            'property_id'      => $this->propertyId,
            'start_date'       => $this->startDate,
            'end_date'         => $this->endDate,
            'reason'           => $this->reason,
            'conflict_dates'   => $this->conflictDates,
            'blocking_sources' => $this->blockingSources,
            'correlation_id'   => $this->correlationId,
            'overridden_at'    => $this->overriddenAt->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

namespace App\DTOs\Property;

/**
 * OverrideResult
 *
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * Result of a conflict override operation.
 */
final class OverrideResult
{
    public function __construct(
        public readonly bool               $authorized,
        public readonly int                $actorUserId,
        public readonly OverrideAuditRecord $auditRecord,
        public readonly string             $summary,
    ) {}

    public static function granted(int $actorUserId, OverrideAuditRecord $record): self
    {
        return new self(
            authorized:   true,
            actorUserId:  $actorUserId,
            auditRecord:  $record,
            summary:      sprintf(
                'Override GRANTED by user %d for %s — %s. Reason: %s',
                $actorUserId,
                $record->startDate,
                $record->endDate,
                $record->reason
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'authorized'    => $this->authorized,
            'actor_user_id' => $this->actorUserId,
            'audit_record'  => $this->auditRecord->toArray(),
            'summary'       => $this->summary,
        ];
    }
}

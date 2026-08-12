<?php

namespace App\DTOs\Ydl\Events;

use DateTimeImmutable;

/**
 * YdlEvent — Immutable certification event record.
 *
 * YDL v1 Phase 2A
 *
 * Represents a single certification event that was processed by the YDL pipeline.
 * Used for idempotency: same event_id = same event, no duplicate processing.
 *
 * @readonly
 */
final class YdlEvent
{
    public const TYPE_CERTIFICATION = 'CERTIFICATION';
    public const TYPE_BLOCKER_ADDED = 'BLOCKER_ADDED';
    public const TYPE_BLOCKER_RESOLVED = 'BLOCKER_RESOLVED';
    public const TYPE_SPRINT_CLOSED = 'SPRINT_CLOSED';
    public const TYPE_SPRINT_STARTED = 'SPRINT_STARTED';
    public const TYPE_MEMORY_UPDATED = 'MEMORY_UPDATED';

    public function __construct(
        public readonly string $eventId,
        public readonly string $type,
        public readonly string $sprint,
        public readonly string $snapshotId,
        public readonly string $commit,
        public readonly string $action,
        public readonly string $target,
        public readonly string $rationale,
        public readonly string $confidence,
        public readonly bool   $parallelWorkAllowed,
        public readonly int    $gatesPass,
        public readonly int    $gatesFail,
        public readonly int    $gatesBlockedExternal,
        public readonly int    $gatesBlockedInternal,
        public readonly int    $sabViolationsNew,
        public readonly int    $sabViolationsBlocking,
        public readonly string $gitStatus,
        public readonly array  $blockerChanges,
        public readonly string $occurredAt,
    ) {}

    /**
     * Generate a deterministic event ID from sprint + commit + action.
     *
     * Same inputs → same event_id. This is what makes the log idempotent.
     * Multiple agents processing the same commit produce the same event_id.
     */
    public static function generateEventId(string $sprint, string $commit, string $action): string
    {
        $payload = "{$sprint}|{$commit}|{$action}";
        return substr(hash('sha256', $payload), 0, 16);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventId:             $data['event_id'],
            type:               $data['type'],
            sprint:             $data['sprint'],
            snapshotId:         $data['snapshot_id'],
            commit:             $data['commit'],
            action:             $data['action'],
            target:             $data['target'],
            rationale:          $data['rationale'],
            confidence:         $data['confidence'],
            parallelWorkAllowed: $data['parallel_work_allowed'],
            gatesPass:          (int) ($data['gates_pass'] ?? 0),
            gatesFail:          (int) ($data['gates_fail'] ?? 0),
            gatesBlockedExternal: (int) ($data['gates_blocked_external'] ?? 0),
            gatesBlockedInternal: (int) ($data['gates_blocked_internal'] ?? 0),
            sabViolationsNew:    (int) ($data['sab_violations_new'] ?? 0),
            sabViolationsBlocking: (int) ($data['sab_violations_blocking'] ?? 0),
            gitStatus:          $data['git_status'] ?? 'clean',
            blockerChanges:     $data['blocker_changes'] ?? [],
            occurredAt:         $data['occurred_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'event_id'               => $this->eventId,
            'type'                  => $this->type,
            'sprint'                => $this->sprint,
            'snapshot_id'            => $this->snapshotId,
            'commit'                => $this->commit,
            'action'                => $this->action,
            'target'                => $this->target,
            'rationale'             => $this->rationale,
            'confidence'            => $this->confidence,
            'parallel_work_allowed'  => $this->parallelWorkAllowed,
            'gates_pass'            => $this->gatesPass,
            'gates_fail'            => $this->gatesFail,
            'gates_blocked_external' => $this->gatesBlockedExternal,
            'gates_blocked_internal' => $this->gatesBlockedInternal,
            'sab_violations_new'    => $this->sabViolationsNew,
            'sab_violations_blocking' => $this->sabViolationsBlocking,
            'git_status'            => $this->gitStatus,
            'blocker_changes'       => $this->blockerChanges,
            'occurred_at'           => $this->occurredAt,
        ];
    }
}

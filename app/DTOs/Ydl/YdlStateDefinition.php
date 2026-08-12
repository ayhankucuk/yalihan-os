<?php

namespace App\DTOs\Ydl;

/**
 * YdlStateDefinition — Immutable DTO for the current system state snapshot.
 *
 * YDL v1 Phase 1 — Repository State Intelligence
 *
 * Produced by: YdlStateCollector
 * Consumed by: YdlSnapshotValidator, YdlNextBestActionEngine
 *
 * @readonly
 */
final class YdlStateDefinition
{
    public const STATUS_ACTIVE            = 'ACTIVE';
    public const STATUS_BLOCKED          = 'BLOCKED';
    public const STATUS_COMPLETE         = 'COMPLETE';
    public const STATUS_AWAITING         = 'AWAITING_EXTERNAL';

    public const GATE_RESULT_PASS       = 'PASS';
    public const GATE_RESULT_FAIL       = 'FAIL';
    public const GATE_RESULT_BLOCKED    = 'BLOCKED_EXTERNAL';
    public const GATE_RESULT_BLOCKED_INTERNAL = 'BLOCKED_INTERNAL';
    public const GATE_RESULT_NA         = 'N/A';

    public const ACTION_CONTINUE         = 'CONTINUE';
    public const ACTION_FIX             = 'FIX';
    public const ACTION_START           = 'START';
    public const ACTION_STOP            = 'STOP';
    public const ACTION_STATE_DRIFT      = 'STATE_DRIFT';
    public const ACTION_NO_OP            = 'NO_OP';
    public const ACTION_REVIEW           = 'REVIEW';

    public function __construct(
        public readonly string            $snapshotId,
        public readonly string            $sprint,
        public readonly string            $sprintStatus,
        public readonly int              $gatesTotal,
        public readonly int              $gatesPass,
        public readonly int              $gatesFail,
        public readonly int              $gatesBlockedExternal,
        public readonly int              $gatesBlockedInternal,
        public readonly int              $gatesNa,
        public readonly int              $testsPassed,
        public readonly int              $testsFailed,
        public readonly int              $sabViolationsNew,
        public readonly int              $sabViolationsBlocking,
        public readonly string           $branch,
        public readonly string           $commit,
        public readonly string           $generatedAt,
        public readonly string           $nextRecommendedAction,
        public readonly string           $rationale,
        public readonly bool             $parallelWorkAllowed,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            snapshotId:             $data['snapshot_id'] ?? '',
            sprint:                 $data['sprint'] ?? '',
            sprintStatus:           $data['sprint_status'] ?? self::STATUS_ACTIVE,
            gatesTotal:             (int) ($data['gates_total'] ?? 0),
            gatesPass:              (int) ($data['gates_pass'] ?? 0),
            gatesFail:              (int) ($data['gates_fail'] ?? 0),
            gatesBlockedExternal:    (int) ($data['gates_blocked_external'] ?? 0),
            gatesBlockedInternal:   (int) ($data['gates_blocked_internal'] ?? 0),
            gatesNa:               (int) ($data['gates_na'] ?? 0),
            testsPassed:            (int) ($data['tests_passed'] ?? 0),
            testsFailed:            (int) ($data['tests_failed'] ?? 0),
            sabViolationsNew:       (int) ($data['sab_violations_new'] ?? 0),
            sabViolationsBlocking:   (int) ($data['sab_violations_blocking'] ?? 0),
            branch:                 $data['branch'] ?? '',
            commit:                $data['commit'] ?? '',
            generatedAt:            $data['generated_at'] ?? now()->toIso8601String(),
            nextRecommendedAction:  $data['next_recommended_action'] ?? self::ACTION_NO_OP,
            rationale:             $data['rationale'] ?? '',
            parallelWorkAllowed:    (bool) ($data['parallel_work_allowed'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'snapshot_id'            => $this->snapshotId,
            'sprint'                => $this->sprint,
            'sprint_status'          => $this->sprintStatus,
            'gates_total'            => $this->gatesTotal,
            'gates_pass'            => $this->gatesPass,
            'gates_fail'            => $this->gatesFail,
            'gates_blocked_external' => $this->gatesBlockedExternal,
            'gates_blocked_internal' => $this->gatesBlockedInternal,
            'gates_na'              => $this->gatesNa,
            'tests_passed'          => $this->testsPassed,
            'tests_failed'          => $this->testsFailed,
            'sab_violations_new'    => $this->sabViolationsNew,
            'sab_violations_blocking' => $this->sabViolationsBlocking,
            'branch'                => $this->branch,
            'commit'                => $this->commit,
            'generated_at'           => $this->generatedAt,
            'next_recommended_action' => $this->nextRecommendedAction,
            'rationale'             => $this->rationale,
            'parallel_work_allowed'  => $this->parallelWorkAllowed,
        ];
    }

    public function certificationScore(): string
    {
        return "{$this->gatesPass}/{$this->gatesTotal} PASS";
    }

    public function hasFailingGates(): bool
    {
        return $this->gatesFail > 0;
    }

    public function hasBlockingViolations(): bool
    {
        return $this->sabViolationsBlocking > 0;
    }

    public function isEngineeringComplete(): bool
    {
        return $this->gatesFail === 0
            && $this->gatesBlockedInternal === 0
            && $this->sabViolationsBlocking === 0;
    }

    public function isAllGatesComplete(): bool
    {
        return ($this->gatesPass + $this->gatesFail + $this->gatesBlockedExternal
            + $this->gatesBlockedInternal + $this->gatesNa) === $this->gatesTotal;
    }
}

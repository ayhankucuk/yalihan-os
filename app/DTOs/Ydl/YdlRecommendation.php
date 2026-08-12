<?php

namespace App\DTOs\Ydl;

/**
 * YdlRecommendation — Immutable DTO for the next action recommendation.
 *
 * YDL v1 Phase 1
 *
 * Produced by: YdlNextBestActionEngine
 * Consumed by: Artisan command output, LLM rationale generation
 *
 * @readonly
 */
final class YdlRecommendation
{
    public const ACTION_CONTINUE         = 'CONTINUE';
    public const ACTION_START           = 'START';
    public const ACTION_FIX             = 'FIX';
    public const ACTION_STOP            = 'STOP';
    public const ACTION_NO_OP            = 'NO_OP';
    public const ACTION_STATE_DRIFT     = 'STATE_DRIFT';
    public const ACTION_REVIEW          = 'REVIEW';

    public function __construct(
        public readonly string   $action,
        public readonly string   $target,
        public readonly string   $rationale,
        public readonly string   $confidence,
        public readonly bool    $parallelWorkAllowed,
        public readonly string   $snapshotId,
        public readonly array   $details,
    ) {}

    public static function fromState(YdlStateDefinition $state, string $action, string $target, string $rationale, bool $parallelWorkAllowed, array $details = []): self
    {
        return new self(
            action:             $action,
            target:             $target,
            rationale:          $rationale,
            confidence:          $rationale !== '' ? 'HIGH' : 'MEDIUM',
            parallelWorkAllowed: $parallelWorkAllowed,
            snapshotId:          $state->snapshotId,
            details:             $details,
        );
    }

    public static function noOp(string $reason): self
    {
        return new self(
            action:             self::ACTION_NO_OP,
            target:             '',
            rationale:          $reason,
            confidence:         'HIGH',
            parallelWorkAllowed: false,
            snapshotId:         '',
            details:            [],
        );
    }

    public static function stateDrift(string $conflict): self
    {
        return new self(
            action:             self::ACTION_STATE_DRIFT,
            target:             'memory/ydl/state/current.json',
            rationale:          "Snapshot and blocker registry conflict: {$conflict}",
            confidence:         'HIGH',
            parallelWorkAllowed: false,
            snapshotId:         '',
            details:            ['conflict' => $conflict],
        );
    }

    public function toArray(): array
    {
        return [
            'action'              => $this->action,
            'target'              => $this->target,
            'rationale'           => $this->rationale,
            'confidence'          => $this->confidence,
            'parallel_work_allowed' => $this->parallelWorkAllowed,
            'snapshot_id'         => $this->snapshotId,
            'details'            => $this->details,
        ];
    }

    public function isStop(): bool
    {
        return $this->action === self::ACTION_STOP;
    }

    public function isFixRequired(): bool
    {
        return $this->action === self::ACTION_FIX;
    }

    public function isStart(): bool
    {
        return $this->action === self::ACTION_START;
    }

    public function isNoOp(): bool
    {
        return $this->action === self::ACTION_NO_OP;
    }

    public function isStateDrift(): bool
    {
        return $this->action === self::ACTION_STATE_DRIFT;
    }
}

<?php

namespace App\Services\Ydl\Platform;

/**
 * AuthorityDecision — Immutable result of authority evaluation.
 *
 * PILOT-001 + PILOT-002
 *
 * Produced by AuthorityEvaluator::evaluate().
 * Consumed by domain orchestrators to determine pipeline flow.
 *
 * Domain-agnostic: uses only primitive strings for authority levels.
 * No imports from App\DTOs\Ydl\... or App\Models\...
 *
 * @readonly
 */
final class AuthorityDecision
{
    public const STATUS_BLOCKED = 'BLOCKED';
    public const STATUS_PROCEED = 'PROCEED';

    public function __construct(
        public readonly string $authority,
        public readonly string $decisionStatus,
        public readonly string $reason,
        public readonly bool $isStop,
        public readonly bool $isLimited,
        public readonly bool $isFull,
        public readonly bool $blockedByScopeIntersection,
        public readonly ?string $taskScope,
    ) {}

    public static function blocked(
        string $authority,
        string $reason,
        bool $byScopeIntersection = false,
        ?string $taskScope = null,
    ): self {
        return new self(
            authority: $authority,
            decisionStatus: self::STATUS_BLOCKED,
            reason: $reason,
            isStop: $authority === AuthorityEvaluator::STOP,
            isLimited: $authority === AuthorityEvaluator::LIMITED_BY_BLOCKER
                || $authority === AuthorityEvaluator::LIMITED,
            isFull: $authority === AuthorityEvaluator::FULL,
            blockedByScopeIntersection: $byScopeIntersection,
            taskScope: $taskScope,
        );
    }

    public static function proceed(
        string $authority,
        ?string $taskScope = null,
    ): self {
        return new self(
            authority: $authority,
            decisionStatus: self::STATUS_PROCEED,
            reason: 'Authority level allows operation to proceed',
            isStop: false,
            isLimited: $authority === AuthorityEvaluator::LIMITED_BY_BLOCKER
                || $authority === AuthorityEvaluator::LIMITED,
            isFull: $authority === AuthorityEvaluator::FULL,
            blockedByScopeIntersection: false,
            taskScope: $taskScope,
        );
    }

    public function canProceed(): bool
    {
        return $this->decisionStatus === self::STATUS_PROCEED;
    }

    public function isBlocked(): bool
    {
        return $this->decisionStatus === self::STATUS_BLOCKED;
    }
}

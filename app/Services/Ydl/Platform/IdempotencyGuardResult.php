<?php

namespace App\Services\Ydl\Platform;

/**
 * IdempotencyGuardResult — Result of an idempotency check.
 *
 * Platform-level value object. Contains no domain knowledge.
 *
 * @readonly
 */
final class IdempotencyGuardResult
{
    public const STATUS_NEW = 'new';
    public const STATUS_DUPLICATE = 'duplicate';

    private function __construct(
        public readonly string $status,
        public readonly string $eventId,
        public readonly ?string $occurredAt,
    ) {}

    /**
     * Mark the event as newly processed (first time seen).
     */
    public static function new(string $eventId): self
    {
        return new self(
            status: self::STATUS_NEW,
            eventId: $eventId,
            occurredAt: null,
        );
    }

    /**
     * Mark the event as a duplicate (already processed).
     */
    public static function duplicate(string $eventId, ?string $occurredAt = null): self
    {
        return new self(
            status: self::STATUS_DUPLICATE,
            eventId: $eventId,
            occurredAt: $occurredAt,
        );
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isDuplicate(): bool
    {
        return $this->status === self::STATUS_DUPLICATE;
    }
}

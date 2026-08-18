<?php

namespace App\Services\Ydl\Phase2B;

use App\DTOs\Ydl\Events\YdlEvent;

/**
 * YdlIdempotencyGuard — Phase 2B Certification Gate 1/4.
 *
 * Blocks duplicate patch generation for the same certification event.
 * Uses YdlEventLog as the source of truth for processed event_ids.
 *
 * Certification Gate:
 *   GIVEN a YdlEvent with eventId
 *   WHEN  YdlIdempotencyGuard::check() is called
 *   THEN  it returns ALLOWED if event_id is new
 *         BLOCKS with reason if event_id already exists
 */
class YdlIdempotencyGuard
{
    private array $processedEventIds = [];

    public function __construct(array $processedEventIds = [])
    {
        $this->processedEventIds = $processedEventIds;
    }

    /**
     * @return array{allowed: bool, reason: string|null}
     */
    public function check(YdlEvent $event): array
    {
        if (in_array($event->eventId, $this->processedEventIds, true)) {
            return [
                'allowed' => false,
                'reason'  => "DUPLICATE_EVENT: {$event->eventId} already processed — idempotent skip",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Record an event as processed (after successful write).
     */
    public function markProcessed(string $eventId): void
    {
        $this->processedEventIds[] = $eventId;
    }

    public function isProcessed(string $eventId): bool
    {
        return in_array($eventId, $this->processedEventIds, true);
    }
}

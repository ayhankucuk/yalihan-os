<?php

namespace App\Services\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\Events\ReservationEvent;
use Illuminate\Support\Facades\File;

/**
 * ReservationEventLog — Idempotent certification event store for reservations.
 *
 * PILOT-002 Wave 1
 *
 * Stores one record per reservation event (identified by event_id).
 * Re-running the same event does NOT create duplicate entries.
 * Every CREATE / BLOCKED / CONFLICT / OVERRIDE is logged.
 *
 * Event log path: memory/ydl/reservation-event-log.jsonl
 */
class ReservationEventLog
{
    private string $logPath;
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
        $this->logPath = $this->basePath . '/memory/ydl/reservation-event-log.jsonl';
    }

    /**
     * Append a new event to the log.
     *
     * Idempotent: if event_id already exists, returns false (no-op).
     * BLOCKED dahil her sonuç loglanır.
     */
    public function append(ReservationEvent $event): bool
    {
        $this->ensureDirectoryExists();

        if ($this->eventExists($event->eventId)) {
            return false; // Already processed — idempotent no-op
        }

        $line = json_encode($event->toArray(), JSON_UNESCAPED_SLASHES) . "\n";
        File::append($this->logPath, $line);

        return true;
    }

    /**
     * Check if an event_id has already been processed.
     */
    public function eventExists(string $eventId): bool
    {
        if (! File::exists($this->logPath)) {
            return false;
        }

        $handle = fopen($this->logPath, 'r');
        if ($handle === false) {
            return false;
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $data = json_decode($line, true);
            if (($data['event_id'] ?? '') === $eventId) {
                fclose($handle);
                return true;
            }
        }

        fclose($handle);
        return false;
    }

    /**
     * Get all events for a given ilan.
     *
     * @return ReservationEvent[]
     */
    public function eventsForIlan(int $ilanId): array
    {
        if (! File::exists($this->logPath)) {
            return [];
        }

        $events = [];
        $handle = fopen($this->logPath, 'r');
        if ($handle === false) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $data = json_decode($line, true);
            if (($data['ilan_id'] ?? '') == $ilanId) {
                $events[] = ReservationEvent::fromArray($data);
            }
        }

        fclose($handle);
        return $events;
    }

    /**
     * Get the latest event for a given ilan.
     */
    public function latestEventForIlan(int $ilanId): ?ReservationEvent
    {
        $events = $this->eventsForIlan($ilanId);
        if (empty($events)) {
            return null;
        }
        usort($events, fn(ReservationEvent $a, ReservationEvent $b) =>
            ($a->occurredAt <=> $b->occurredAt) ?: ($b->eventId <=> $a->eventId)
        );
        return $events[count($events) - 1];
    }

    /**
     * Get all events (for audit/debug).
     *
     * @return ReservationEvent[]
     */
    public function allEvents(): array
    {
        if (! File::exists($this->logPath)) {
            return [];
        }

        $events = [];
        $handle = fopen($this->logPath, 'r');
        if ($handle === false) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $data = json_decode($line, true);
            if ($data !== null) {
                $events[] = ReservationEvent::fromArray($data);
            }
        }

        fclose($handle);
        return $events;
    }

    public function count(): int
    {
        if (! File::exists($this->logPath)) {
            return 0;
        }

        $count = 0;
        $handle = fopen($this->logPath, 'r');
        if ($handle === false) {
            return 0;
        }
        while (($line = fgets($handle)) !== false) {
            if (trim($line) !== '') {
                $count++;
            }
        }
        fclose($handle);
        return $count;
    }

    private function ensureDirectoryExists(): void
    {
        $dir = dirname($this->logPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }
}

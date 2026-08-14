<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\Events\YdlEvent;
use App\Services\Ydl\YdlStateOrchestrator;
use Illuminate\Support\Facades\File;

/**
 * YdlEventLog — Idempotent certification event store.
 *
 * YDL v1 Phase 2A
 *
 * Stores one record per certification event (identified by event_id).
 * Re-running the same event (same sprint + commit + action) does NOT
 * create duplicate entries — this is what makes Phase 2 idempotent.
 *
 * Event log path: memory/ydl/event-log.jsonl (one JSON per line)
 *
 * NON-GOALS:
 *   - Does NOT write to memory/progress files (that's YdlStatePatcher)
 *   - Does NOT make any git commits
 *   - Does NOT modify business code
 */
class YdlEventLog
{
    private string $logPath;
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
        $this->logPath = $this->basePath . '/memory/ydl/event-log.jsonl';
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }

    /**
     * Append a new event to the log.
     *
     * Idempotent: if event_id already exists in the log, returns false.
     * Otherwise appends the event and returns true.
     */
    public function append(YdlEvent $event): bool
    {
        $this->ensureDirectoryExists();

        // Idempotency check: scan existing events for this event_id
        if ($this->eventExists($event->eventId)) {
            return false; // Already processed — idempotent no-op
        }

        $line = json_encode($event->toArray(), JSON_UNESCAPED_SLASHES) . "\n";
        File::append($this->logPath, $line);

        return true; // New event written
    }

    /**
     * Check if an event_id has already been processed.
     */
    public function eventExists(string $eventId): bool
    {
        if (!File::exists($this->logPath)) {
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
     * Get all events for a given sprint (file order = append order).
     *
     * @return YdlEvent[]
     */
    public function eventsForSprint(string $sprint): array
    {
        if (!File::exists($this->logPath)) {
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
            if (($data['sprint'] ?? '') === $sprint) {
                $events[] = YdlEvent::fromArray($data);
            }
        }

        fclose($handle);
        return $events;
    }

    /**
     * Get the latest event for a given sprint (by occurred_at timestamp, descending).
     */
    public function latestEventForSprint(string $sprint): ?YdlEvent
    {
        $events = $this->eventsForSprint($sprint);
        if (count($events) === 0) {
            return null;
        }
        // Sort ascending, then by eventId descending (tiebreaker — later eventId = later in append order)
        usort($events, fn(YdlEvent $a, YdlEvent $b) => ($a->occurredAt <=> $b->occurredAt) ?: ($b->eventId <=> $a->eventId));
        return $events[count($events) - 1];
    }

    /**
     * Get all events (for audit/debug).
     *
     * @return YdlEvent[]
     */
    public function allEvents(): array
    {
        if (!File::exists($this->logPath)) {
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
                $events[] = YdlEvent::fromArray($data);
            }
        }

        fclose($handle);
        return $events;
    }

    /**
     * Get the total event count.
     */
    public function count(): int
    {
        if (!File::exists($this->logPath)) {
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
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }
}

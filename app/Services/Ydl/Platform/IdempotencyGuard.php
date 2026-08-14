<?php

namespace App\Services\Ydl\Platform;

use Illuminate\Support\Facades\File;

/**
 * IdempotencyGuard — Platform-level event deduplication (read-only).
 *
 * PILOT-001 + PILOT-002 common idempotency primitive.
 *
 * Provides ONLY eventId deduplication — zero domain knowledge.
 * Does NOT know about: publish, reservation, cancel, override,
 * Ilan, PropertyReservation, or any domain-specific semantics.
 *
 * READ-ONLY boundary (Charter §3.3):
 *   - Guard only answers: "Has this eventId been processed?"
 *   - Guard does NOT write to event log
 *   - Guard does NOT produce evidence
 *   - Guard does NOT generate eventIds
 *
 * Pipeline ownership:
 *   P5 EventIdentityPolicy  → eventId üretir
 *           ↓
 *   P3 IdempotencyGuard   → daha önce işlendi mi? (READ ONLY)
 *           ↓
 *   Domain Orchestrator     → ne yapılacağına karar verir
 *           ↓
 *   Domain EventLog         → evidence yazar (domain'in sorumluluğu)
 *
 * Domain-agnostic: all parameters are primitive strings.
 * No App\Models\... or App\Services\... imports.
 */
class IdempotencyGuard implements IdempotencyGuardInterface
{
    private string $logPath;
    private string $basePath;

    /**
     * @param string|null $logPath  Absolute path to the idempotency log file.
     *                               e.g. memory/ydl/event-log.jsonl
     *                               Null = use default platform log path.
     * @param string|null $basePath Override base path (for testing).
     */
    public function __construct(?string $logPath = null, ?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
        $this->logPath = $logPath
            ?? $this->basePath . '/memory/ydl/idempotency-guard-log.jsonl';
    }

    /**
     * Check whether an eventId has already been processed.
     *
     * READ ONLY — does not modify any state.
     *
     * @param string $eventId Unique event identifier
     * @return IdempotencyGuardResult
     */
    public function check(string $eventId): IdempotencyGuardResult
    {
        $existed = $this->exists($eventId);

        if ($existed) {
            return IdempotencyGuardResult::duplicate($eventId);
        }

        return IdempotencyGuardResult::new($eventId);
    }

    /**
     * Check if an event_id already exists in the log.
     *
     * READ ONLY.
     *
     * @param string $eventId
     * @return bool
     */
    public function exists(string $eventId): bool
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
}

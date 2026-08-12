<?php

namespace App\Services\Ydl\Phase2B;

use App\DTOs\Ydl\Events\YdlEvent;
use App\Services\Ydl\Patchers\YdlPatch;
use App\Services\Ydl\YdlEventLog;
use App\Services\Ydl\YdlStateOrchestrator;

/**
 * YdlWriteGuard — Phase 2B Certification Gate orchestration.
 *
 * Runs all four certification gates BEFORE any write operation.
 * All four must pass before YdlControlledWriter is allowed to proceed.
 *
 * Certification Gates:
 *   G1 — Idempotency:   duplicate event detection
 *   G2 — Target Whitelist: patch targets are only institutional-memory files
 *   G3 — State Drift:   no dirty Git, no state drift since certification
 *   G4 — File Hash:     pre-patch file hashes match planned hashes
 *
 * PHASE 2B WHITELIST (closed list — no app/, database/, production files):
 *   - memory/ydl/blockers.json
 *   - memory/ydl/state/current.json
 *   - docs/BEKCI_CHANGELOG.md
 *   - memory/SESSION_NOTES.md
 *   - memory/CHANGELOG_AGENT.md
 *   - docs/PROGRESS-TRACKER.md
 *
 * NON-GOALS (enforced by G2):
 *   - app/ (all files)     — NEVER
 *   - database/             — NEVER
 *   - resources/views/      — NEVER
 *   - config/              — NEVER
 */
class YdlWriteGuard
{
    /** Whitelist of allowed write targets (relative to repo root). */
    private const ALLOWED_TARGETS = [
        'memory/ydl/blockers.json',
        'memory/ydl/state/current.json',
        'docs/BEKCI_CHANGELOG.md',
        'memory/SESSION_NOTES.md',
        'memory/CHANGELOG_AGENT.md',
        'docs/PROGRESS-TRACKER.md',
    ];

    /** Patterns that are ALWAYS forbidden, regardless of whitelist. */
    private const FORBIDDEN_PATTERNS = [
        'app/',
        'database/',
        'resources/views/',
        'config/',
        'bootstrap/',
        'vendor/',
        'routes/',
        'tests/',
        '.env',
        '.phpunit',
        'node_modules/',
        'storage/logs/',
        'storage/framework/',
    ];

    private YdlIdempotencyGuard $idempotencyGuard;
    private YdlEventLog $eventLog;
    private FileHashStore $hashStore;
    private YdlStateOrchestrator $orchestrator;
    private string $basePath;

    public function __construct(
        ?YdlIdempotencyGuard $idempotencyGuard = null,
        ?YdlEventLog $eventLog = null,
        ?FileHashStore $hashStore = null,
        ?YdlStateOrchestrator $orchestrator = null,
        ?string $basePath = null
    ) {
        $this->idempotencyGuard = $idempotencyGuard ?? new YdlIdempotencyGuard();
        $this->eventLog = $eventLog ?? new YdlEventLog();
        $this->hashStore = $hashStore ?? new FileHashStore();
        $this->orchestrator = $orchestrator ?? new YdlStateOrchestrator();
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Run all four certification gates.
     *
     * @param YdlPatch[] $patches
     * @return array{pass: bool, gates: array, blocked_patches: array, event_id: string|null}
     */
    public function authorize(array $patches, ?YdlEvent $event = null): array
    {
        $gates = [];
        $blockedPatches = [];

        // ── G1: Idempotency ────────────────────────────────────────────
        $g1Result = $this->runGate1_Idempotency($event);
        $gates['G1_Idempotency'] = $g1Result;
        if (!$g1Result['pass']) {
            return ['pass' => false, 'gates' => $gates, 'blocked_patches' => [], 'event_id' => $event?->eventId];
        }

        // ── G2: Target Whitelist ───────────────────────────────────────
        $g2Result = $this->runGate2_Whitelist($patches);
        $gates['G2_TargetWhitelist'] = $g2Result;
        if (!$g2Result['pass']) {
            return ['pass' => false, 'gates' => $gates, 'blocked_patches' => $g2Result['blocked'], 'event_id' => $event?->eventId];
        }

        // ── G3: State Drift ───────────────────────────────────────────
        $g3Result = $this->runGate3_StateDrift($event);
        $gates['G3_StateDrift'] = $g3Result;
        if (!$g3Result['pass']) {
            return ['pass' => false, 'gates' => $gates, 'blocked_patches' => [], 'event_id' => $event?->eventId];
        }

        // ── G4: File Hash Verification ────────────────────────────────
        $g4Result = $this->runGate4_FileHash($patches);
        $gates['G4_FileHash'] = $g4Result;
        if (!$g4Result['pass']) {
            return ['pass' => false, 'gates' => $gates, 'blocked_patches' => $g4Result['mismatches'], 'event_id' => $event?->eventId];
        }

        return ['pass' => true, 'gates' => $gates, 'blocked_patches' => [], 'event_id' => $event?->eventId];
    }

    // ─────────────────────────────────────────────────────────────────
    // G1: Duplicate event detection
    // ─────────────────────────────────────────────────────────────────

    protected function runGate1_Idempotency(?YdlEvent $event): array
    {
        if ($event === null) {
            return ['pass' => false, 'reason' => 'G1: No event provided'];
        }

        // Load already-processed event IDs from event log
        $processedIds = array_map(
            fn(YdlEvent $e) => $e->eventId,
            $this->eventLog->allEvents()
        );

        $guard = new YdlIdempotencyGuard($processedIds);
        $result = $guard->check($event);

        return [
            'pass' => $result['allowed'],
            'reason' => $result['allowed'] ? 'G1: Event is new — proceed' : "G1: {$result['reason']}",
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // G2: Target whitelist enforcement
    // ─────────────────────────────────────────────────────────────────

    protected function runGate2_Whitelist(array $patches): array
    {
        $blocked = [];

        foreach ($patches as $patch) {
            if (!$this->isTargetAllowed($patch->target)) {
                $blocked[] = [
                    'target' => $patch->target,
                    'operation' => $patch->operation,
                    'rationale' => $patch->rationale,
                    'blocked_reason' => $this->forbiddenReason($patch->target),
                ];
            }
        }

        if (count($blocked) > 0) {
            $blockedTargets = implode(', ', array_column($blocked, 'target'));
            // Use the first blocked item's reason for the top-level reason
            $firstReason = $blocked[0]['blocked_reason'] ?? "not in institutional-memory whitelist";
            return [
                'pass' => false,
                'reason' => "G2: {$blockedTargets} not in institutional-memory whitelist — {$firstReason}",
                'blocked' => $blocked,
            ];
        }

        return ['pass' => true, 'reason' => 'G2: All targets are whitelisted'];
    }

    private function isTargetAllowed(string $target): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $target), '/');

        // Must be in explicit whitelist
        if (in_array($normalized, self::ALLOWED_TARGETS, true)) {
            return true;
        }

        // Must not match any forbidden pattern
        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (str_starts_with($normalized, $pattern)) {
                return false;
            }
        }

        return false;
    }

    private function forbiddenReason(string $target): string
    {
        $normalized = ltrim(str_replace('\\', '/', $target), '/');

        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (str_starts_with($normalized, $pattern)) {
                return "FORBIDDEN_PATTERN: matches '{$pattern}' — production code cannot be auto-written by YDL";
            }
        }

        return "NOT_IN_WHITELIST: {$target} is not in the Phase 2B allowed-targets list";
    }

    // ─────────────────────────────────────────────────────────────────
    // G3: Dirty Git + State Drift detection
    // ─────────────────────────────────────────────────────────────────

    protected function runGate3_StateDrift(?YdlEvent $event): array
    {
        // Re-run orchestrator to get current state
        try {
            $result = $this->orchestrator->run();
        } catch (\Throwable $e) {
            return [
                'pass' => false,
                'reason' => "G3: YDL pipeline failed — {$e->getMessage()}",
            ];
        }

        $state = $result['state'];
        $drift = $result['drift'];

        // Gate 3a: Git must be clean
        if (!$state->gitClean) {
            return [
                'pass' => false,
                'reason' => 'G3a: Dirty Git — commit or stash changes before applying patch',
            ];
        }

        // Gate 3b: No state drift
        if ($drift !== null) {
            return [
                'pass' => false,
                'reason' => "G3b: STATE_DRIFT detected — {$drift}",
            ];
        }

        // Gate 3c: If event has a commit, current HEAD must match
        if ($event !== null && $event->commit !== '') {
            $currentCommit = $state->commit;
            if ($currentCommit !== $event->commit) {
                return [
                    'pass' => false,
                    'reason' => "G3c: COMMIT_DRIFT — event commit {$event->commit} != current HEAD {$currentCommit}",
                ];
            }
        }

        return ['pass' => true, 'reason' => 'G3: Git clean, no state drift, commit matches'];
    }

    // ─────────────────────────────────────────────────────────────────
    // G4: File hash verification before write
    // ─────────────────────────────────────────────────────────────────

    protected function runGate4_FileHash(array $patches): array
    {
        $mismatches = [];

        foreach ($patches as $patch) {
            if ($patch->isNoOp()) {
                continue; // no-op patches don't write
            }

            $result = $this->hashStore->verifyPreApply($patch->target, $patch->currentHash);

            if (!$result['valid']) {
                $mismatches[] = [
                    'target' => $patch->target,
                    'reason' => $result['reason'],
                    'stored_hash' => $patch->currentHash,
                ];
            } else {
                // Snapshot current hash for post-write verification
                $this->hashStore->snapshot($patch->target);
            }
        }

        if (count($mismatches) > 0) {
            $files = implode(', ', array_column($mismatches, 'target'));
            return [
                'pass' => false,
                'reason' => "G4: File hash mismatch — files changed since patch was planned: {$files}",
                'mismatches' => $mismatches,
            ];
        }

        return ['pass' => true, 'reason' => 'G4: All file hashes verified'];
    }

    /**
     * Returns the list of ALLOWED_TARGETS for documentation/display.
     */
    public static function allowedTargets(): array
    {
        return self::ALLOWED_TARGETS;
    }
}

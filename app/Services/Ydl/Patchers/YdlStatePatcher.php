<?php

namespace App\Services\Ydl\Patchers;

use App\DTOs\Ydl\Events\YdlEvent;

/**
 * YdlStatePatcher — Generates planned patches for memory/progress files.
 *
 * YDL v1 Phase 2A — Generate Only Mode
 *
 * Produces diffs/plans for 6 memory targets:
 *   1. memory/ydl/blockers.json
 *   2. memory/ydl/state/current.json
 *   3. docs/BEKCI_CHANGELOG.md
 *   4. memory/SESSION_NOTES.md
 *   5. memory/CHANGELOG_AGENT.md
 *   6. docs/PROGRESS-TRACKER.md
 *
 * PHASE 2A: Does NOT write files. Only generates diff plans.
 * Phase 2B: ydl:patch --confirm applies confirmed plans.
 *
 * All operations are IDEMPOTENT.
 *
 * NON-GOALS (Phase 2):
 *   - Does NOT modify business code (PHP files under app/)
 *   - Does NOT run migrations
 *   - Does NOT make git commits
 *   - Does NOT write to database tables
 */
class YdlStatePatcher
{
    /** @var YdlPatch[] */
    private array $patches = [];

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Generate patch plans for a certification event.
     *
     * @return YdlPatch[]
     */
    public function generate(YdlEvent $event): array
    {
        $this->patches = [];

        $this->addBlockerPatch($event);
        $this->addStatePatch($event);
        $this->addBekciChangelogPatch($event);
        $this->addSessionNotesPatch($event);
        $this->addChangelogAgentPatch($event);
        $this->addProgressTrackerPatch($event);

        return $this->patches;
    }

    // ─────────────────────────────────────────────────────────────────
    // Target 1: blockers.json
    // ─────────────────────────────────────────────────────────────────

    private function addBlockerPatch(YdlEvent $event): void
    {
        $path = $this->basePath . '/memory/ydl/blockers.json';
        $existing = $this->readJson($path, [
            'version' => '1.0',
            'blockers' => [],
            'resolved' => [],
        ]);

        $blockers = $existing['blockers'] ?? [];

        // Apply blocker changes from event
        foreach ($event->blockerChanges as $change) {
            $op = $change['op'] ?? '';
            match ($op) {
                'add' => $this->blockerAdd($blockers, $change),
                'resolve' => $this->blockerResolve($blockers, $existing, $change),
                'update' => $this->blockerUpdate($blockers, $change),
                default => null,
            };
        }

        $newContent = json_encode([
            'version' => '1.0',
            'updated' => $event->occurredAt,
            'blockers' => $blockers,
            'resolved' => $existing['resolved'] ?? [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $currentHash = $this->fileExists($path) ? md5((string) file_get_contents($path)) : '';

        $this->patches[] = new YdlPatch(
            target: 'memory/ydl/blockers.json',
            operation: 'update_blockers',
            currentHash: $currentHash,
            plannedHash: md5($newContent),
            rationale: "Update blocker registry for {$event->sprint}",
            changes: $event->blockerChanges,
            newContent: $newContent,
        );
    }

    private function blockerAdd(array &$blockers, array $change): void
    {
        $blocker = $change['blocker'] ?? [];
        $id = $blocker['id'] ?? '';
        if ($id === '') {
            return;
        }
        foreach ($blockers as $existing) {
            if (($existing['id'] ?? '') === $id) {
                return; // Already exists — idempotent skip
            }
        }
        $blockers[] = $blocker;
    }

    private function blockerResolve(array &$blockers, array &$existing, array $change): void
    {
        $id = $change['id'] ?? '';
        if ($id === '') {
            return;
        }

        $resolvedBlocker = null;
        $filtered = [];
        foreach ($blockers as $b) {
            if (($b['id'] ?? '') === $id) {
                $resolvedBlocker = $b;
            } else {
                $filtered[] = $b;
            }
        }

        // Re-index array keys for pass-by-reference
        $blockers = array_values($filtered);

        if ($resolvedBlocker !== null) {
            $resolvedBlocker['resolved_at'] = $change['resolved_at'] ?? now()->toIso8601String();
            $resolvedBlocker['resolution_note'] = $change['resolution_note'] ?? '';
            $existing['resolved'][] = $resolvedBlocker;
        }
    }

    private function blockerUpdate(array &$blockers, array $change): void
    {
        $id = $change['id'] ?? '';
        foreach ($blockers as &$b) {
            if (($b['id'] ?? '') === $id) {
                $b = array_merge($b, $change['fields'] ?? []);
                break;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Target 2: current.json
    // ─────────────────────────────────────────────────────────────────

    private function addStatePatch(YdlEvent $event): void
    {
        $path = $this->basePath . '/memory/ydl/state/current.json';
        $existing = $this->readJson($path, [
            'version' => '1.0',
            'active_sprint' => [],
            'sab' => ['new_violations' => 0, 'blocking_violations' => 0],
            'git' => ['branch' => '', 'commit' => ''],
            'recommendation' => [],
        ]);

        $existing['active_sprint'] = array_merge($existing['active_sprint'] ?? [], [
            'id' => $event->sprint,
            'status' => $event->action === 'CERTIFIED'
                ? 'CERTIFIED'
                : ($event->action === 'START' ? 'AWAITING_EXTERNAL' : 'ACTIVE'),
            'gates_pass' => $event->gatesPass,
            'gates_fail' => $event->gatesFail,
            'gates_blocked_external' => $event->gatesBlockedExternal,
            'gates_blocked_internal' => $event->gatesBlockedInternal,
        ]);

        $existing['sab'] = [
            'new_violations' => $event->sabViolationsNew,
            'blocking_violations' => $event->sabViolationsBlocking,
        ];

        $existing['git'] = [
            'branch' => '',
            'commit' => $event->commit,
        ];

        $existing['recommendation'] = [
            'action' => $event->action,
            'target' => $event->target,
            'rationale' => $event->rationale,
            'confidence' => $event->confidence,
            'parallel_work_allowed' => $event->parallelWorkAllowed,
            'updated' => $event->occurredAt,
        ];

        $existing['updated'] = $event->occurredAt;
        $existing['last_event_id'] = $event->eventId;

        $newContent = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $currentHash = $this->fileExists($path) ? md5((string) file_get_contents($path)) : '';

        $this->patches[] = new YdlPatch(
            target: 'memory/ydl/state/current.json',
            operation: 'update_state',
            currentHash: $currentHash,
            plannedHash: md5($newContent),
            rationale: "Update current state for {$event->sprint} certification event",
            changes: [],
            newContent: $newContent,
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Target 3: BEKCI_CHANGELOG.md
    // ─────────────────────────────────────────────────────────────────

    private function addBekciChangelogPatch(YdlEvent $event): void
    {
        $path = $this->basePath . '/docs/BEKCI_CHANGELOG.md';
        $existing = $this->fileExists($path) ? (string) file_get_contents($path) : '';

        $newContent = $this->prependSection($existing, $event, 'BEKCI_CHANGELOG.md');

        $this->patches[] = new YdlPatch(
            target: 'docs/BEKCI_CHANGELOG.md',
            operation: $this->isIdempotentNoOp($existing, $event) ? 'noop_idempotent' : 'prepend_section',
            currentHash: md5($existing),
            plannedHash: md5($newContent),
            rationale: $this->isIdempotentNoOp($existing, $event)
                ? "Sprint {$event->sprint} section already exists (idempotent no-op)"
                : "Add Oturum entry for {$event->sprint} — {$event->action}",
            changes: $this->isIdempotentNoOp($existing, $event) ? [] : ['section' => $event->sprint],
            newContent: $newContent,
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Target 4: SESSION_NOTES.md
    // ─────────────────────────────────────────────────────────────────

    private function addSessionNotesPatch(YdlEvent $event): void
    {
        $path = $this->basePath . '/memory/SESSION_NOTES.md';
        $existing = $this->fileExists($path) ? (string) file_get_contents($path) : '';

        $newContent = $this->prependSection($existing, $event, 'SESSION_NOTES.md');

        $this->patches[] = new YdlPatch(
            target: 'memory/SESSION_NOTES.md',
            operation: $this->isIdempotentNoOp($existing, $event) ? 'noop_idempotent' : 'prepend_section',
            currentHash: md5($existing),
            plannedHash: md5($newContent),
            rationale: $this->isIdempotentNoOp($existing, $event)
                ? "Sprint {$event->sprint} section already exists (idempotent no-op)"
                : "Add session notes for {$event->sprint}",
            changes: $this->isIdempotentNoOp($existing, $event) ? [] : ['section' => $event->sprint],
            newContent: $newContent,
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Target 5: CHANGELOG_AGENT.md
    // ─────────────────────────────────────────────────────────────────

    private function addChangelogAgentPatch(YdlEvent $event): void
    {
        $path = $this->basePath . '/memory/CHANGELOG_AGENT.md';
        $existing = $this->fileExists($path) ? (string) file_get_contents($path) : '';

        // Idempotency: if event_id already in file, skip
        if (str_contains($existing, $event->eventId)) {
            $this->patches[] = new YdlPatch(
                target: 'memory/CHANGELOG_AGENT.md',
                operation: 'noop_idempotent',
                currentHash: md5($existing),
                plannedHash: md5($existing),
                rationale: "Event {$event->eventId} already logged (idempotent no-op)",
                changes: [],
                newContent: $existing,
            );
            return;
        }

        $entry = $this->buildAgentChangelogEntry($event);
        $newContent = $entry . "\n" . $existing;

        $this->patches[] = new YdlPatch(
            target: 'memory/CHANGELOG_AGENT.md',
            operation: 'prepend_entry',
            currentHash: md5($existing),
            plannedHash: md5($newContent),
            rationale: "Log certification event {$event->eventId} for {$event->sprint}",
            changes: ['event_id' => $event->eventId],
            newContent: $newContent,
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Target 6: PROGRESS-TRACKER.md
    // ─────────────────────────────────────────────────────────────────

    private function addProgressTrackerPatch(YdlEvent $event): void
    {
        $path = $this->basePath . '/docs/PROGRESS-TRACKER.md';
        $existing = $this->fileExists($path) ? (string) file_get_contents($path) : '';

        // Update header timestamp
        $newContent = preg_replace(
            '/^\*\*Son Güncelleme:\*\* .+$/m',
            '**Son Güncelleme:** ' . now()->format('Y-m-d') . " (YDL Phase 2 — {$event->sprint})",
            $existing
        ) ?: $existing;

        // Upgrade YDL Phase 1 badge to Phase 2
        if (str_contains($newContent, 'YDL v1 Phase 1')) {
            $newContent = str_replace('YDL v1 Phase 1', 'YDL v1 Phase 2', $newContent);
        }

        $this->patches[] = new YdlPatch(
            target: 'docs/PROGRESS-TRACKER.md',
            operation: 'update_progress',
            currentHash: md5($existing),
            plannedHash: md5($newContent),
            rationale: "Update PROGRESS-TRACKER for {$event->sprint} — {$event->action}",
            changes: ['sprint' => $event->sprint, 'action' => $event->action],
            newContent: $newContent,
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function readJson(string $path, array $defaults): array
    {
        if (!file_exists($path)) {
            return $defaults;
        }
        $content = (string) file_get_contents($path);
        $decoded = json_decode($content, true);
        return is_array($decoded) ? array_merge($defaults, $decoded) : $defaults;
    }

    private function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    private function isIdempotentNoOp(string $content, YdlEvent $event): bool
    {
        // Simple string search: if "## OTURUM YDL-Phase2 |" + sprint name exists, skip
        // This avoids regex issues with ISO timestamps containing "+" and special chars
        if ($event->sprint !== '' && str_contains($content, $event->sprint)) {
            // Further verify it's a YDL Phase 2 section header
            if (str_contains($content, '## OTURUM YDL-Phase2')) {
                return true;
            }
        }
        // Also check by event_id if present
        if ($event->eventId !== '' && str_contains($content, $event->eventId)) {
            return true;
        }
        return false;
    }

    private function prependSection(string $existing, YdlEvent $event, string $targetFile): string
    {
        $section = $this->buildMarkdownSection($event);
        return $section . "\n" . $existing;
    }

    private function buildMarkdownSection(YdlEvent $event): string
    {
        $badge = $this->actionBadge($event->action);
        $totalGates = $event->gatesPass
            + $event->gatesFail
            + $event->gatesBlockedExternal
            + $event->gatesBlockedInternal;
        $gateResults = "{$event->gatesPass}/{$totalGates} PASS";

        $sabLine = ($event->sabViolationsNew === 0 && $event->sabViolationsBlocking === 0)
            ? 'SAB: 0 new, 0 blocking ✅'
            : "SAB: {$event->sabViolationsNew} new, {$event->sabViolationsBlocking} blocking";

        $blockerLines = '';
        foreach ($event->blockerChanges as $change) {
            if (($change['op'] ?? '') === 'add') {
                $b = $change['blocker'] ?? [];
                $id = $b['id'] ?? '?';
                $gate = $b['gate'] ?? '?';
                $type = $b['type'] ?? '?';
                $owner = $b['owner'] ?? '?';
                $action = $b['development_action'] ?? '?';
                $blockerLines .= "- BLK-{$id} ({$gate}): {$type} — {$owner} | {$action}\n";
            }
        }
        if ($blockerLines === '') {
            $blockerLines = "No blocker changes.\n";
        }

        $parallelText = $event->parallelWorkAllowed ? '✅ ALLOWED' : '❌ BLOCKED';

        return <<<MD
## OTURUM YDL-Phase2 | {$event->occurredAt} | {$event->sprint} — {$event->action} {$badge}

### Certification Event: {$event->eventId}

| Metric | Value |
|--------|-------|
| Sprint | {$event->sprint} |
| Action | {$event->action} {$badge} |
| Target | {$event->target} |
| Confidence | {$event->confidence} |
| Certification | {$gateResults} |
| {$sabLine} |
| Git | {$event->gitStatus} |
| Parallel Work | {$parallelText} |

### Rationale

{$event->rationale}

### Blocker Changes

{$blockerLines}---

MD;
    }

    private function buildAgentChangelogEntry(YdlEvent $event): string
    {
        $parallelText = $event->parallelWorkAllowed ? 'YES' : 'NO';
        $gateSummary = "{$event->gatesPass} PASS / {$event->gatesFail} FAIL / "
            . "{$event->gatesBlockedExternal} EXT / {$event->gatesBlockedInternal} INT";

        return <<<MD

## {$event->occurredAt} | YDL Phase 2 | {$event->sprint} — {$event->action}

- **Event ID:** {$event->eventId}
- **Type:** {$event->type}
- **Sprint:** {$event->sprint}
- **Action:** {$event->action} → {$event->target}
- **Confidence:** {$event->confidence}
- **Gates:** {$gateSummary}
- **SAB:** {$event->sabViolationsNew} new / {$event->sabViolationsBlocking} blocking
- **Parallel:** {$parallelText}
- **Commit:** {$event->commit}
MD;
    }

    private function actionBadge(string $action): string
    {
        return match ($action) {
            'START' => '🚀',
            'STOP' => '🛑',
            'FIX' => '🔧',
            'CERTIFIED' => '✅',
            'NO_OP' => '⏸️',
            'REVIEW' => '👀',
            default => '📋',
        };
    }
}

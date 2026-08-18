<?php

namespace App\Console\Commands;

use App\DTOs\Ydl\Events\YdlEvent;
use App\Services\Ydl\Patchers\YdlPatch;
use App\Services\Ydl\Patchers\YdlStatePatcher;
use App\Services\Ydl\Phase2B\YdlControlledWriter;
use App\Services\Ydl\Phase2B\YdlWriteGuard;
use App\Services\Ydl\YdlEventLog;
use App\Services\Ydl\YdlStateOrchestrator;
use Illuminate\Console\Command;

/**
 * YDL Phase 2 Apply Command — Controlled write to institutional memory.
 *
 * Usage:
 *   php artisan ydl:apply           # Generate diff plan (read-only, dry-run)
 *   php artisan ydl:apply --confirm # Run gates + apply patches (Phase 2B)
 *   php artisan ydl:patch --status  # Show event log summary (alias)
 *
 * Phase 2A (default):  Generates diffs without writing files.
 * Phase 2B (--confirm): Runs 4 certification gates, then applies patches.
 *
 * Certification Gates (all must pass for write):
 *   G1 — Idempotency:    duplicate event detection
 *   G2 — Target Whitelist: patch targets restricted to institutional-memory files
 *   G3 — State Drift:    Git must be clean, no drift since certification
 *   G4 — File Hash:      pre-patch file hashes verified
 */
class YdlPatchCommand extends Command
{
    protected $signature = 'ydl:apply
                            {--confirm : Run certification gates and apply patches (Phase 2B)}
                            {--dry-run : Show what would change without applying}
                            {--event-id= : Process a specific event_id}';

    // Alias for backward compatibility
    protected $aliases = ['ydl:patch'];

    protected $description = 'YDL Phase 2: Apply whitelisted patches to institutional memory files';

    public function handle(): int
    {
        $this->info('YDL Phase 2 — Controlled Memory Write');
        $this->newLine();

        // Run the YDL pipeline to get current state
        $orchestrator = new YdlStateOrchestrator();

        try {
            $result = $orchestrator->run();
        } catch (\Throwable $e) {
            $this->error('YDL pipeline failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $state = $result['state'];
        $rec = $result['recommendation'];
        $drift = $result['drift'];

        // Build event from current state
        $eventId = YdlEvent::generateEventId($state->sprint, $state->commit, $rec->action);
        $event = new YdlEvent(
            eventId: $eventId,
            type: YdlEvent::TYPE_CERTIFICATION,
            sprint: $state->sprint,
            snapshotId: $state->snapshotId,
            commit: $state->commit,
            action: $rec->action,
            target: $rec->target,
            rationale: $rec->rationale,
            confidence: $rec->confidence,
            parallelWorkAllowed: $rec->parallelWorkAllowed,
            gatesPass: $state->gatesPass,
            gatesFail: $state->gatesFail,
            gatesBlockedExternal: $state->gatesBlockedExternal,
            gatesBlockedInternal: $state->gatesBlockedInternal,
            sabViolationsNew: $state->sabViolationsNew,
            sabViolationsBlocking: $state->sabViolationsBlocking,
            gitStatus: $state->gitClean ? 'clean' : 'dirty',
            blockerChanges: [],
            occurredAt: now()->toIso8601String(),
        );

        // Generate patches
        $patcher = new YdlStatePatcher();
        $patches = $patcher->generate($event);

        $changes = array_filter($patches, fn(YdlPatch $p) => $p->isChange());
        $noops = array_filter($patches, fn(YdlPatch $p) => $p->isNoOp());

        $this->info("Event ID: {$eventId}");
        $this->info("Sprint: {$event->sprint} — {$event->action}");
        $this->newLine();

        // ── Phase 2A: Dry-run output (always shown) ─────────────────────
        $this->info('Patches: ' . count($changes) . ' changes, ' . count($noops) . ' no-ops');
        $this->newLine();

        $this->table(
            ['Op', 'Target', 'Rationale'],
            collect($patches)->map(fn(YdlPatch $p) => [
                $p->isNoOp() ? '⏸️ NOOP' : '📝 ' . $p->operation,
                $p->target,
                $p->rationale,
            ])->toArray()
        );
        $this->newLine();

        // ── Confirm flag: run Phase 2B ─────────────────────────────────
        if (!$this->option('confirm')) {
            $this->warn('Phase 2A: Run with --confirm to apply patches (runs all 4 certification gates).');
            return self::SUCCESS;
        }

        return $this->runPhase2B($patches, $event, $state->gitClean, $drift);
    }

    private function runPhase2B(array $patches, YdlEvent $event, bool $gitClean, ?string $drift): int
    {
        $writer = new YdlControlledWriter();
        $guard = new YdlWriteGuard();

        $this->info('─── Phase 2B: Certification Gates ───────────────────────────');
        $this->newLine();

        // Early exit: Git dirty or drift detected (these don't need full guard check)
        if (!$gitClean) {
            $this->error('✗ G3a: Dirty Git — commit or stash changes before applying patch');
            $this->warn('  Fix: git add . && git commit');
            return self::FAILURE;
        }

        if ($drift !== null) {
            $this->error("✗ G3b: STATE_DRIFT detected — {$drift}");
            $this->warn('  Fix drift before patching memory files.');
            return self::FAILURE;
        }

        // Run all 4 certification gates
        $authResult = $guard->authorize($patches, $event);

        // Render gate results
        foreach ($authResult['gates'] as $gateName => $gateResult) {
            $icon = $gateResult['pass'] ? '✓' : '✗';
            $color = $gateResult['pass'] ? 'info' : 'error';
            $this->{$color}($icon . ' ' . $gateName . ': ' . $gateResult['reason']);
        }
        $this->newLine();

        if (!$authResult['pass']) {
            $this->error('─── BLOCKED ───────────────────────────────────────────────');
            $this->error('Certification gates failed. No patches applied.');
            $this->newLine();

            if (count($authResult['blocked_patches']) > 0) {
                $this->warn('Blocked patches:');
                foreach ($authResult['blocked_patches'] as $bp) {
                    $this->warn("  ✗ {$bp['target']}: {$bp['blocked_reason']}");
                }
            }

            return self::FAILURE;
        }

        // All gates passed — apply patches
        $this->info('─── All Gates Passed — Applying Patches ─────────────────────');
        $this->newLine();

        $applyResult = $writer->apply($patches, $event);

        foreach ($patches as $patch) {
            if ($patch->isNoOp()) {
                $this->line("  ⏸️  NOOP: {$patch->target}");
            } else {
                $this->info("  ✓ {$patch->target}");
            }
        }
        $this->newLine();

        $this->info("Applied: {$applyResult['applied']} patches");
        $this->info("Skipped: {$applyResult['skipped']} (already current or no-op)");
        if ($applyResult['failed'] > 0) {
            $this->error("Failed: {$applyResult['failed']}");
            foreach ($applyResult['errors'] as $err) {
                $this->error("  ✗ {$err}");
            }
        }

        $this->newLine();
        $this->info("Event logged: {$event->eventId}");
        $this->newLine();
        $this->info('Next: git add . && git commit -m "chore(ydl): update memory after ' . $event->sprint . '"');

        return $applyResult['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

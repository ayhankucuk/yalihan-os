<?php

namespace App\Console\Commands;

use App\DTOs\Ydl\Events\YdlEvent;
use App\Services\Ydl\Patchers\YdlPatch;
use App\Services\Ydl\Patchers\YdlStatePatcher;
use App\Services\Ydl\YdlEventLog;
use App\Services\Ydl\YdlStateOrchestrator;
use Illuminate\Console\Command;

/**
 * YDL Phase 2 Patch Command — Memory automation CLI
 *
 * Usage:
 *   php artisan ydl:patch           # Generate diff plan (Phase 2A — read-only)
 *   php artisan ydl:patch --confirm # Execute plan + write memory files (Phase 2B)
 *   php artisan ydl:patch --status  # Show event log summary
 *
 * Phase 2A (default): Generates diffs without writing files.
 * Phase 2B (--confirm): Idempotent writes to memory files.
 */
class YdlPatchCommand extends Command
{
    protected $signature = 'ydl:patch {--confirm : Apply patches (Phase 2B) --status : Show event log summary} {--event-id= : Process a specific event_id}';

    protected $description = 'YDL Phase 2: Generate and apply memory/progress file patches';

    public function handle(): int
    {
        $this->info('YDL Phase 2 — Memory Automation');

        // --status: show event log summary
        if ($this->option('status')) {
            return $this->showEventStatus();
        }

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

        // Event log
        $log = new YdlEventLog();

        if ($drift !== null) {
            $this->warn("⚠ STATE_DRIFT detected — patch blocked: {$drift}");
            $this->warn('Fix drift before patching memory files.');
            return self::FAILURE;
        }

        // Idempotency check
        if ($log->eventExists($eventId)) {
            $this->warn("Event {$eventId} already processed — idempotent skip.");
            $this->warn('Use --confirm to re-apply (idempotent — no duplicate entry created.');
            return self::SUCCESS;
        }

        // Generate patches
        $patcher = new YdlStatePatcher();
        $patches = $patcher->generate($event);

        $changes = array_filter($patches, fn(YdlPatch $p) => $p->isChange());
        $noops = array_filter($patches, fn(YdlPatch $p) => $p->isNoOp());

        $this->info("Event ID: {$eventId}");
        $this->info("Sprint: {$event->sprint} — {$event->action}");
        $this->newLine();
        $this->info("Patches: " . count($changes) . " changes, " . count($noops) . " no-ops");

        // Table output
        $rows = [];
        foreach ($patches as $p) {
            $rows[] = [
                $p->isNoOp() ? '⏸️ NOOP' : '📝 ' . $p->operation,
                $p->target,
                $p->rationale,
            ];
        }
        $this->table(['Op', 'Target', 'Rationale'], $rows);

        if ($this->option('confirm')) {
            $this->confirm('Apply ' . count($changes) . ' patches?');
            return $this->applyPatches($patches, $event, $log);
        }

        $this->newLine();
        $this->warn('Phase 2A: Run with --confirm to apply patches.');
        return self::SUCCESS;
    }

    private function applyPatches(array $patches, YdlEvent $event, YdlEventLog $log): int
    {
        $eventLog = new YdlEventLog();
        $applied = 0;
        $skipped = 0;

        foreach ($patches as $patch) {
            if ($patch->isNoOp()) {
                $this->line("  ⏸️  NOOP: {$patch->target}");
                $skipped++;
                continue;
            }

            $path = base_path($patch->target);
            $dir = dirname($path);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $written = file_put_contents($path, $patch->newContent);
            if ($written === false) {
                $this->error("  ✗ FAIL: {$patch->target}");
                return self::FAILURE;
            }

            $this->info("  ✓ {$patch->target}");
            $applied++;
        }

        // Append event AFTER successful writes
        $eventLog->append($event);

        $this->newLine();
        $this->info("Applied: {$applied} patches, skipped: {$skipped} no-ops");
        $this->info("Event logged: {$event->eventId}");
        $this->newLine();
        $this->info('Memory update complete. Run: git add . && git commit -m "chore(Ydl Phase 2: update memory after ' . $event->sprint . '"');

        return self::SUCCESS;
    }

    private function showEventStatus(): int
    {
        $log = new YdlEventLog();
        $all = $log->allEvents();

        $this->info("YDL Event Log — {$log->count()} events");
        $this->newLine();

        if (count($all) === 0) {
            $this->warn('No events in log. Run: php artisan ydl:patch');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($all as $e) {
            $rows[] = [
                substr($e->eventId, 0, 8),
                $e->sprint,
                $e->action,
                $e->confidence,
                $e->parallelWorkAllowed ? 'YES' : 'NO',
                substr($e->occurredAt, 0, 10),
            ];
        }
        $this->table(['EventID', 'Sprint', 'Action', 'Confidence', 'Parallel', 'Date'], $rows);

        return self::SUCCESS;
    }
}

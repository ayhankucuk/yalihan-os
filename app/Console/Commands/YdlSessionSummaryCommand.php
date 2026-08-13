<?php

namespace App\Console\Commands;

use App\DTOs\Ydl\Events\YdlEvent;
use App\Services\Ydl\Patchers\YdlStatePatcher;
use App\Services\Ydl\YdlBlockerEvaluator;
use App\Services\Ydl\YdlContextReader;
use App\Services\Ydl\YdlStateOrchestrator;
use Illuminate\Console\Command;

/**
 * YDL Phase 3: Session-End Event Generator.
 *
 * Creates a YdlEvent from session parameters and runs ydl:apply --dry-run.
 * The agent commits changes, then runs ydl:apply --confirm to close the loop.
 *
 * Usage:
 *   php artisan ydl:session-summary --action CONTINUE --target "YDL Phase 3" --commit $(git rev-parse HEAD)
 *   php artisan ydl:session-summary --action CERTIFIED --target "YDL Phase 3" --commit $(git rev-parse HEAD)
 *   php artisan ydl:session-summary --action CONTINUE --target "Sprint 4.16" --resolve-blocker BLK-001 --commit $(git rev-parse HEAD)
 *
 * Pipeline:
 *   session-summary --dry-run → event + patch plan
 *   → git commit
 *   → ydl:apply --confirm
 *
 * YDL v1 Phase 3
 */
class YdlSessionSummaryCommand extends Command
{
    protected $signature = 'ydl:session-summary
                            {--action= : Action taken: CONTINUE|FIX|START|CERTIFIED|REVIEW}
                            {--target= : Sprint name or target work item}
                            {--commit= : Git commit SHA to associate with this event}
                            {--resolve-blocker= : Blocker ID to resolve}
                            {--add-blocker= : Create new blocker (format: id|gate|type|owner|action|reason)}
                            {--dry-run : Show patch plan without running ydl:apply}';

    protected $description = 'YDL Phase 3: Generate session-end event and patch plan (dry-run)';

    public function handle(): int
    {
        // Validate required options
        $action = $this->option('action') ?: 'CONTINUE';
        $target = $this->option('target') ?: 'YDL Phase 3';
        $commit = $this->option('commit') ?: trim((string) shell_exec('git rev-parse HEAD 2>/dev/null')) ?: '';

        if (empty($commit)) {
            $this->error('Git commit SHA required. Use --commit or run from git repo.');
            return self::FAILURE;
        }

        $this->info("YDL Phase 3 — Session Summary");
        $this->newLine();

        // ── 1. Load current state ──────────────────────────────────────
        try {
            $orchestrator = new YdlStateOrchestrator();
            $result = $orchestrator->run();
            $state = $result['state'];
        } catch (\Throwable $e) {
            $this->error("Failed to load YDL state: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info("Sprint: {$state->sprint}");
        $this->info("Commit: {$commit}");
        $this->info("Action: {$action} → {$target}");
        $this->newLine();

        // ── 2. Build blocker changes ───────────────────────────────────
        $blockerChanges = [];

        // Resolve a blocker
        $resolveBlockerId = $this->option('resolve-blocker');
        if ($resolveBlockerId) {
            $blockerChanges[] = [
                'op'   => 'resolve',
                'id'   => $resolveBlockerId,
                'resolved_at' => now()->toIso8601String(),
                'resolution_note' => "Resolved via ydl session-summary — {$action}",
            ];
            $this->info("  → Resolving blocker: {$resolveBlockerId}");
        }

        // Add a new blocker (format: id|gate|type|owner|action|reason)
        $addBlocker = $this->option('add-blocker');
        if ($addBlocker) {
            $parts = explode('|', $addBlocker);
            if (count($parts) >= 6) {
                [$id, $gate, $type, $owner, $devAction, $reason] = $parts;
                $blockerChanges[] = [
                    'op'      => 'add',
                    'blocker' => [
                        'id'                  => $id,
                        'gate'                => $gate,
                        'type'                => $type,
                        'owner'               => $owner,
                        'development_action'  => $devAction,
                        'reason'              => $reason,
                        'created_at'          => now()->toIso8601String(),
                    ],
                ];
                $this->info("  → Adding blocker: {$id} [{$gate}]");
            } else {
                $this->warn("  ⚠ Malformed --add-blocker format. Expected: id|gate|type|owner|action|reason");
            }
        }

        // ── 3. Create YdlEvent ────────────────────────────────────────
        $eventId = YdlEvent::generateEventId($state->sprint, $commit, $action);

        // Determine confidence and parallelWorkAllowed based on action
        $confidence = match ($action) {
            'CERTIFIED' => 'HIGH',
            'START'      => 'HIGH',
            'FIX'        => 'MEDIUM',
            'CONTINUE'   => 'MEDIUM',
            'REVIEW'    => 'LOW',
            default      => 'MEDIUM',
        };

        $parallelWorkAllowed = $action !== 'FIX' && $action !== 'STOP';

        $event = new YdlEvent(
            eventId:               $eventId,
            type:                  YdlEvent::TYPE_SPRINT_STARTED,
            sprint:                $state->sprint,
            snapshotId:            $state->snapshotId,
            commit:               $commit,
            action:               $action,
            target:               $target,
            rationale:            "Session summary: {$action} for {$target}",
            confidence:           $confidence,
            parallelWorkAllowed:  $parallelWorkAllowed,
            gatesPass:            $state->gatesPass,
            gatesFail:            $state->gatesFail,
            gatesBlockedExternal: $state->gatesBlockedExternal,
            gatesBlockedInternal: $state->gatesBlockedInternal,
            sabViolationsNew:     $state->sabViolationsNew,
            sabViolationsBlocking: $state->sabViolationsBlocking,
            gitStatus:            'clean', // will be dirty before commit
            blockerChanges:       $blockerChanges,
            occurredAt:           now()->toIso8601String(),
        );

        $this->info("Event ID: {$eventId}");
        $this->newLine();

        // ── 4. Generate patch plan ───────────────────────────────────
        $patcher = new YdlStatePatcher();
        $patches = $patcher->generate($event);

        $changes = array_filter($patches, fn($p) => !$p->isNoOp());
        $noops   = array_filter($patches, fn($p) => $p->isNoOp());

        $this->info("Patch Plan: " . count($changes) . " changes, " . count($noops) . " no-ops");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->table(
                ['Op', 'Target', 'Rationale'],
                collect($patches)->map(fn($p) => [
                    $p->isNoOp() ? '⏸️ NOOP' : '📝 ' . $p->operation,
                    $p->target,
                    $p->rationale,
                ])->toArray()
            );
            $this->newLine();
        }

        // ── 5. Summary ────────────────────────────────────────────────
        $this->info("─── Next Steps ─────────────────────────────────────────");
        $this->line("  1. Review patch plan above");
        $this->line("  2. git add . && git commit -m 'chore(ydl): session {$action} — {$target} [{$eventId}]'");
        $this->line("  3. git push");
        $this->line("  4. php artisan ydl:apply --confirm  ← G3c will PASS after commit");
        $this->newLine();

        if (count($blockerChanges) > 0) {
            $this->warn("  Note: Blocker changes require ydl:apply --confirm to persist.");
        }

        $this->line("Event ID (for reference): {$eventId}");

        return self::SUCCESS;
    }
}

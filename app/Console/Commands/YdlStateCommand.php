<?php

namespace App\Console\Commands;

use App\Services\Ydl\YdlStateOrchestrator;
use Illuminate\Console\Command;

/**
 * YDL State Intelligence — Phase 1 Artisan Command
 *
 * Runs the full YDL pipeline:
 *   1. StateCollector  → current evidence
 *   2. SnapshotValidator → drift check
 *   3. BlockerEvaluator → blocker evaluation
 *   4. NextBestActionEngine → decision
 *
 * Usage:
 *   php artisan ydl:state          # Full pipeline + output
 *   php artisan ydl:state --json   # JSON output (for agents)
 *   php artisan ydl:state --dry-run # No file writes
 */
class YdlStateCommand extends Command
{
    protected $signature = 'ydl:state {--json : Output machine-readable JSON} {--dry-run : Run without writing files}';

    protected $description = 'YDL v1: Collect state, validate, evaluate blockers, and recommend next action';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════');
        $this->info('  YDL v1 — Repository State Intelligence');
        $this->info('═══════════════════════════════════════════════');

        $orchestrator = new YdlStateOrchestrator();

        try {
            $result = $orchestrator->run();
        } catch (\Throwable $e) {
            $this->error('YDL pipeline failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $state = $result['state'];
        $rec   = $result['recommendation'];
        $drift = $result['drift'];

        // ── State Summary ──────────────────────────────────
        $this->table(
            ['Metric', 'Value'],
            [
                ['Sprint',        $state->sprint],
                ['Branch',        $state->branch],
                ['Commit',        $state->commit],
                ['Git Status',    $state->branch !== 'unknown' ? 'clean' : 'N/A'],
            ]
        );

        $this->newLine();
        $this->line('  Certification: ' . $state->certificationScore());
        $totalTests = $state->testsPassed + $state->testsFailed;
        $this->line("  Tests:        {$state->testsPassed}/{$totalTests} PASS");
        $this->line("  SAB Violations: {$state->sabViolationsNew} new, {$state->sabViolationsBlocking} blocking");

        // ── Blocker Status ──────────────────────────────────
        if ($state->gatesBlockedExternal > 0) {
            $this->warn("  External Blockers: {$state->gatesBlockedExternal} (see memory/ydl/blockers.json)");
        }

        // ── Drift Warning ────────────────────────────────
        if ($drift !== null) {
            $this->error("  ⚠ STATE_DRIFT: {$drift}");
        }

        // ── Recommendation ───────────────────────────────
        $this->newLine();
        $this->info("  ╔═══════════════════════════════════════╗");
        $this->info("  ║  RECOMMENDATION: {$rec->action}  ║");
        $this->info("  ╚═══════════════════════════════════════╝");

        $this->line("  Target:  {$rec->target}");
        $this->line("  Parallel work: " . ($rec->parallelWorkAllowed ? '✅ ALLOWED' : '❌ BLOCKED'));

        $this->newLine();
        $this->line("  Rationale:");
        foreach (explode("\n", wordwrap($rec->rationale, 60)) as $line) {
            $this->line("    {$line}");
        }

        // ── JSON output ─────────────────────────────────
        if ($this->option('json')) {
            $this->newLine();
            $this->line(json_encode([
                'snapshot_id'  => $state->snapshotId,
                'sprint'       => $state->sprint,
                'certification_score' => $state->certificationScore(),
                'tests'        => [
                    'passed' => $state->testsPassed,
                    'failed' => $state->testsFailed,
                ],
                'sab'          => [
                    'new'     => $state->sabViolationsNew,
                    'blocking' => $state->sabViolationsBlocking,
                ],
                'drift'        => $drift,
                'recommendation' => [
                    'action'  => $rec->action,
                    'target'  => $rec->target,
                    'rationale' => $rec->rationale,
                    'confidence' => $rec->confidence,
                    'parallel_work_allowed' => $rec->parallelWorkAllowed,
                ],
            ], JSON_PRETTY_PRINT));
        }

        return $drift !== null ? self::FAILURE : self::SUCCESS;
    }
}

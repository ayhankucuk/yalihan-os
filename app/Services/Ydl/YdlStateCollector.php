<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\YdlStateDefinition;
use Illuminate\Support\Facades\File;

/**
 * YdlStateCollector — Aggregates raw evidence from repository files.
 *
 * YDL v1 Phase 1
 *
 * Deterministic: reads from pre-existing state files, no shell commands.
 * Pipeline reads latest snapshot and state file as the authoritative evidence.
 *
 * Evidence Sources:
 *   - memory/ydl/state/current.json → sprint state + gate summary
 *   - memory/ydl/snapshots/*.json → latest snapshot (file timestamp = newest)
 *   - memory/ydl/blockers.json → blocker registry
 *
 * NO shell_exec, NO artisan calls — pure file I/O.
 */
class YdlStateCollector
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Collect current state from repository files.
     *
     * Deterministic. No LLM inference. No shell commands.
     */
    public function collect(): YdlStateDefinition
    {
        $statePath   = $this->basePath . '/memory/ydl/state/current.json';
        $snapshotsDir = $this->basePath . '/memory/ydl/snapshots';
        $blockerPath  = $this->basePath . '/memory/ydl/blockers.json';

        // Active sprint state from current.json
        $stateData = $this->readStateFile($statePath);
        $activePrint = $stateData['active_sprint'] ?? [];

        // Latest snapshot by file mtime
        $latestSnapshot = $this->latestSnapshot($snapshotsDir);

        // Blocker count
        $activeBlockers = $this->activeBlockerCount($blockerPath);

        // Git info from state (updated by previous runs)
        $gitBranch = $stateData['git']['branch'] ?? 'integration/booking-production';
        $gitCommit = $stateData['git']['commit'] ?? 'HEAD';

        // Certification gates
        $gatesTotal   = (int) ($activePrint['gates_total'] ?? 0);
        $gatesPass    = (int) ($activePrint['gates_pass'] ?? 0);
        $gatesFail    = (int) ($activePrint['gates_fail'] ?? 0);
        $gatesBlockedExt = (int) ($activePrint['gates_blocked_external'] ?? 0);
        $gatesBlockedInt = (int) ($activePrint['gates_blocked_internal'] ?? 0);
        $gatesNa     = (int) ($activePrint['gates_na'] ?? 0);

        // Compute tests from snapshot if available
        $testPassed = 0;
        $testFailed = 0;
        if ($latestSnapshot !== []) {
            $testPassed = (int) ($latestSnapshot['test_results']['tests_passed'] ?? 0);
            $testFailed = (int) ($latestSnapshot['test_results']['tests_failed'] ?? 0);
        }

        // SAB from state file
        $sabNew      = (int) ($stateData['sab']['new_violations'] ?? 0);
        $sabBlocking = (int) ($stateData['sab']['blocking_violations'] ?? 0);

        return YdlStateDefinition::fromArray([
            'snapshot_id'             => $latestSnapshot['snapshot_id'] ?? 'snap-initial',
            'sprint'                 => $activePrint['id'] ?? $activePrint['name'] ?? 'Sprint 4.15',
            'sprint_status'           => $activePrint['status'] ?? YdlStateDefinition::STATUS_ACTIVE,
            'gates_total'            => $gatesTotal,
            'gates_pass'            => $gatesPass,
            'gates_fail'            => $gatesFail,
            'gates_blocked_external'  => $gatesBlockedExt,
            'gates_blocked_internal'  => $gatesBlockedInt,
            'gates_na'              => $gatesNa,
            'tests_passed'           => $testPassed,
            'tests_failed'           => $testFailed,
            'sab_violations_new'     => $sabNew,
            'sab_violations_blocking' => $sabBlocking,
            'branch'                => $gitBranch,
            'commit'                => $gitCommit,
            'generated_at'           => now()->toIso8601String(),
        ]);
    }

    private function readStateFile(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $raw = @file_get_contents($path);
        return $raw !== false ? json_decode($raw, true) ?? [] : [];
    }

    private function latestSnapshot(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.json');
        if ($files === false || count($files) === 0) {
            return [];
        }

        // Sort by mtime descending
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        $latest = $files[0];
        $raw = @file_get_contents($latest);
        return $raw !== false ? json_decode($raw, true) ?? [] : [];
    }

    private function activeBlockerCount(string $path): int
    {
        $data = $this->readStateFile($path);
        $blockers = $data['blockers'] ?? [];

        return count(array_filter($blockers, fn($b) => ($b['status'] ?? '') === 'ACTIVE'));
    }
}

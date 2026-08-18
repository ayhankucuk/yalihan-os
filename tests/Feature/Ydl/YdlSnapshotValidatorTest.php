<?php

namespace Tests\Feature\Ydl;

use Tests\TestCase;
use App\Services\Ydl\YdlSnapshotValidator;
use App\DTOs\Ydl\YdlStateDefinition;

/**
 * YdlSnapshotValidator Test Suite
 *
 * YDL v1 Phase 1 — Drift Detection Tests
 *
 * Rules:
 *   1. Active blockers in registry must appear in current snapshot
 *   2. Snapshot sprint must match blocker registry sprint
 *   3. Snapshot must have same blocker count as active blockers
 *
 * If any rule is violated → STATE_DRIFT action emitted.
 */
class YdlSnapshotValidatorTest extends TestCase
{
    private string $tempDir;
    private string $statePath;
    private string $snapshotDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ydl_validator_test_' . uniqid();
        mkdir($this->tempDir . '/snapshots', 0755, true);
        $this->statePath = $this->tempDir . '/blockers.json';
        $this->snapshotDir = $this->tempDir . '/snapshots';
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        @unlink($this->statePath);
        $files = glob($this->snapshotDir . '/*.json');
        if ($files) {
            foreach ($files as $f) { @unlink($f); }
        }
        @rmdir($this->snapshotDir);
        @rmdir($this->tempDir);
        parent::tearDown();
    }

    private function writeBlockers(array $data): void
    {
        file_put_contents($this->statePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function makeState(?string $sprint = 'Sprint 4.15', int $gatesBlockedExternal = 0, bool $gitClean = true): YdlStateDefinition
    {
        return YdlStateDefinition::fromArray([
            'snapshot_id'             => 'snap-test-' . uniqid(),
            'sprint'                  => $sprint,
            'sprint_status'            => YdlStateDefinition::STATUS_ACTIVE,
            'gates_total'            => 35,
            'gates_pass'             => 34,
            'gates_fail'             => 0,
            'gates_blocked_external'  => $gatesBlockedExternal,
            'gates_blocked_internal'  => 0,
            'gates_na'              => 0,
            'tests_passed'           => 83,
            'tests_failed'          => 0,
            'sab_violations_new'    => 0,
            'sab_violations_blocking' => 0,
            'branch'                => 'integration/booking-production',
            'commit'                => 'abc1234',
            'git_clean'             => $gitClean,
            'generated_at'          => now()->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Rule 0: Null state returns error string (not null)
    // ─────────────────────────────────────────────────────────────────

    public function test_null_state_returns_error_message(): void
    {
        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $result = $validator->validate(null);

        $this->assertNotNull($result);
        $this->assertIsString($result);
        $this->assertStringContainsString('No current state', $result);
    }

    // ─────────────────────────────────────────────────────────────────
    // Rule 1: Active blocker without corresponding external blocked gate = drift
    // ─────────────────────────────────────────────────────────────────

    public function test_active_blocker_without_external_blocked_gate_returns_drift(): void
    {
        // Blocker registry has an active blocker but state shows 0 external blocked
        $this->writeBlockers([
            'blockers' => [
                [
                    'id' => 'BLK-001',
                    'gate' => 'G35',
                    'status' => 'ACTIVE',
                    'type' => 'EXTERNAL_DEPENDENCY',
                ],
            ],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(gatesBlockedExternal: 0);

        $result = $validator->validate($state);

        $this->assertNotNull($result);
        $this->assertStringContainsString('BLK-001', $result);
        $this->assertStringContainsString('0 external blocked gates', $result);
    }

    public function test_active_blocker_with_external_blocked_gate_returns_null(): void
    {
        $this->writeBlockers([
            'blockers' => [
                [
                    'id' => 'BLK-001',
                    'gate' => 'G35',
                    'status' => 'ACTIVE',
                    'type' => 'EXTERNAL_DEPENDENCY',
                ],
            ],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(gatesBlockedExternal: 1);

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    public function test_no_active_blockers_returns_null(): void
    {
        $this->writeBlockers([
            'blockers' => [],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(gatesBlockedExternal: 0);

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    public function test_resolved_blocker_does_not_trigger_drift(): void
    {
        $this->writeBlockers([
            'blockers' => [
                [
                    'id' => 'BLK-001',
                    'gate' => 'G35',
                    'status' => 'RESOLVED',
                    'type' => 'EXTERNAL_DEPENDENCY',
                ],
            ],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(gatesBlockedExternal: 0);

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────────────────────────────
    // Rule 2: Sprint name must match between registry and snapshot
    // ─────────────────────────────────────────────────────────────────

    public function test_sprint_mismatch_returns_drift(): void
    {
        $this->writeBlockers([
            'active_sprint' => ['id' => 'Sprint 4.15'],
            'blockers' => [],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(sprint: 'Sprint 4.16');

        $result = $validator->validate($state);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Sprint mismatch', $result);
    }

    public function test_sprint_match_returns_null(): void
    {
        $this->writeBlockers([
            'active_sprint' => ['id' => 'Sprint 4.15'],
            'blockers' => [],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(sprint: 'Sprint 4.15');

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    public function test_sprint_match_using_name_field(): void
    {
        $this->writeBlockers([
            'active_sprint' => ['name' => 'Sprint 4.15'],
            'blockers' => [],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(sprint: 'Sprint 4.15');

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    public function test_empty_sprint_in_registry_returns_null(): void
    {
        $this->writeBlockers([
            'active_sprint' => [],
            'blockers' => [],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(sprint: 'Sprint 4.15');

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────────────────────────────
    // Rule 3: Registry active blocker count must match snapshot external gate count
    // ─────────────────────────────────────────────────────────────────

    public function test_registry_active_count_mismatch_returns_drift(): void
    {
        // Rule 1 short-circuits when blockers have non-empty gates.
        // Rule 3 fires when active blockers exist (regardless of gate field)
        // AND state shows 0 external blocked gates.
        // Using empty gate strings so Rule 1 skips these blockers.
        $this->writeBlockers([
            'blockers' => [
                ['id' => 'BLK-001', 'gate' => '', 'status' => 'ACTIVE'],
                ['id' => 'BLK-002', 'gate' => '', 'status' => 'ACTIVE'],
            ],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(gatesBlockedExternal: 0);

        $result = $validator->validate($state);

        $this->assertNotNull($result);
        $this->assertStringContainsString('2 active blockers', $result);
        $this->assertStringContainsString('0 external blocked gates', $result);
    }

    public function test_registry_active_count_matches_snapshot_returns_null(): void
    {
        $this->writeBlockers([
            'blockers' => [
                ['id' => 'BLK-001', 'gate' => 'G35', 'status' => 'ACTIVE'],
                ['id' => 'BLK-002', 'gate' => 'G34', 'status' => 'RESOLVED'],
            ],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(gatesBlockedExternal: 1);

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────────────────────────────
    // Edge cases
    // ─────────────────────────────────────────────────────────────────

    public function test_missing_blockers_key_returns_null(): void
    {
        $this->writeBlockers([]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState(gatesBlockedExternal: 0);

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    public function test_missing_registry_file_returns_error(): void
    {
        @unlink($this->statePath);
        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = $this->makeState();

        $result = $validator->validate($state);

        $this->assertNotNull($result);
        $this->assertStringContainsString('not found', $result);
    }

    public function test_multiple_active_blockers_with_external_blocked_gates_returns_null(): void
    {
        $this->writeBlockers([
            'blockers' => [
                ['id' => 'BLK-001', 'gate' => 'G35', 'status' => 'ACTIVE'],
                ['id' => 'BLK-002', 'gate' => 'G34', 'status' => 'ACTIVE'],
                ['id' => 'BLK-003', 'gate' => 'G33', 'status' => 'ACTIVE'],
            ],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = YdlStateDefinition::fromArray([
            'snapshot_id'              => 'snap-test-' . uniqid(),
            'sprint'                  => 'Sprint 4.15',
            'sprint_status'           => YdlStateDefinition::STATUS_ACTIVE,
            'gates_total'            => 35,
            'gates_pass'             => 32,
            'gates_fail'             => 0,
            'gates_blocked_external'  => 3,
            'gates_blocked_internal'  => 0,
            'gates_na'              => 0,
            'tests_passed'          => 83,
            'tests_failed'          => 0,
            'sab_violations_new'    => 0,
            'sab_violations_blocking' => 0,
            'branch'                => 'test',
            'commit'                => 'abc',
            'generated_at'          => now()->toIso8601String(),
        ]);

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────────────────────────────
    // Rule 4: CERTIFIED sprint + dirty git = CERTIFICATION_INTEGRITY_FAILURE
    // ─────────────────────────────────────────────────────────────────

    public function test_certified_sprint_dirty_git_returns_certification_integrity_failure(): void
    {
        // Sprint is CERTIFIED (all dev gates pass, no sab blocking)
        // but git is dirty → CERTIFICATION_INTEGRITY_FAILURE
        $this->writeBlockers(['blockers' => []]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = YdlStateDefinition::fromArray([
            'snapshot_id'              => 'snap-test-cert',
            'sprint'                 => 'Sprint 4.15',
            'sprint_status'           => YdlStateDefinition::STATUS_ACTIVE,
            'gates_total'            => 35,
            'gates_pass'             => 35,
            'gates_fail'             => 0,
            'gates_blocked_external'  => 0,
            'gates_blocked_internal'  => 0,
            'gates_na'              => 0,
            'tests_passed'          => 83,
            'tests_failed'          => 0,
            'sab_violations_new'    => 0,
            'sab_violations_blocking' => 0,
            'branch'                => 'integration/booking-production',
            'commit'                => 'abc1234',
            'git_clean'             => false, // ← dirty
            'generated_at'          => now()->toIso8601String(),
        ]);

        $result = $validator->validate($state);

        $this->assertNotNull($result);
        $this->assertStringContainsString('CERTIFICATION_INTEGRITY_FAILURE', $result);
        $this->assertStringContainsString('Sprint 4.15', $result);
    }

    public function test_certified_sprint_clean_git_returns_null(): void
    {
        $this->writeBlockers(['blockers' => []]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = YdlStateDefinition::fromArray([
            'snapshot_id'              => 'snap-test-cert',
            'sprint'                 => 'Sprint 4.15',
            'sprint_status'           => YdlStateDefinition::STATUS_ACTIVE,
            'gates_total'            => 35,
            'gates_pass'             => 35,
            'gates_fail'             => 0,
            'gates_blocked_external'  => 0,
            'gates_blocked_internal'  => 0,
            'gates_na'              => 0,
            'tests_passed'          => 83,
            'tests_failed'          => 0,
            'sab_violations_new'    => 0,
            'sab_violations_blocking' => 0,
            'branch'                => 'integration/booking-production',
            'commit'                => 'abc1234',
            'git_clean'             => true, // ← clean
            'generated_at'          => now()->toIso8601String(),
        ]);

        $result = $validator->validate($state);

        $this->assertNull($result);
    }

    public function test_non_certified_sprint_dirty_git_returns_null(): void
    {
        // If gates are not all PASS (failing gates exist), git being dirty is normal
        $this->writeBlockers(['blockers' => []]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = YdlStateDefinition::fromArray([
            'snapshot_id'              => 'snap-test-incomplete',
            'sprint'                 => 'Sprint 4.15',
            'sprint_status'           => YdlStateDefinition::STATUS_ACTIVE,
            'gates_total'            => 35,
            'gates_pass'             => 30,
            'gates_fail'             => 2, // ← not all PASS
            'gates_blocked_external'  => 0,
            'gates_blocked_internal'  => 0,
            'gates_na'              => 3,
            'tests_passed'          => 70,
            'tests_failed'          => 5,
            'sab_violations_new'    => 0,
            'sab_violations_blocking' => 0,
            'branch'                => 'feature/wip',
            'commit'                => 'abc1234',
            'git_clean'             => false, // ← dirty but work in progress
            'generated_at'          => now()->toIso8601String(),
        ]);

        $result = $validator->validate($state);

        // Rule 4 does NOT fire — incomplete sprint being dirty is expected
        $this->assertNull($result);
    }

    public function test_sprint_with_sab_blocking_dirty_git_returns_null(): void
    {
        // If SAB blocking violations exist, the sprint is NOT certified
        $this->writeBlockers(['blockers' => []]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = YdlStateDefinition::fromArray([
            'snapshot_id'              => 'snap-test-sab',
            'sprint'                 => 'Sprint 4.15',
            'sprint_status'           => YdlStateDefinition::STATUS_ACTIVE,
            'gates_total'            => 35,
            'gates_pass'             => 35,
            'gates_fail'             => 0,
            'gates_blocked_external'  => 0,
            'gates_blocked_internal'  => 0,
            'gates_na'              => 0,
            'tests_passed'          => 83,
            'tests_failed'          => 0,
            'sab_violations_new'    => 5,
            'sab_violations_blocking' => 1, // ← blocking SAB = not certified
            'branch'                => 'integration/booking-production',
            'commit'                => 'abc1234',
            'git_clean'             => false,
            'generated_at'          => now()->toIso8601String(),
        ]);

        $result = $validator->validate($state);

        // Rule 4 does NOT fire — SAB blocking means sprint is not certified
        $this->assertNull($result);
    }

    // ─────────────────────────────────────────────────────────────────
    // Sprint 4.15 baseline: current state should produce null (no drift)
    // ─────────────────────────────────────────────────────────────────

    public function test_sprint_4_15_current_state_produces_no_drift(): void
    {
        $this->writeBlockers([
            'active_sprint' => ['id' => 'Sprint 4.15'],
            'blockers' => [
                [
                    'id' => 'BLK-001',
                    'gate' => 'G35',
                    'status' => 'ACTIVE',
                    'type' => 'EXTERNAL_DEPENDENCY',
                ],
            ],
        ]);

        $validator = new YdlSnapshotValidator($this->statePath, $this->snapshotDir);
        $state = YdlStateDefinition::fromArray([
            'snapshot_id'              => 'snap-s4.15-20260812-1104',
            'sprint'                  => 'Sprint 4.15',
            'sprint_status'           => 'AWAITING_BOOKING_COM_ONBOARDING',
            'gates_total'            => 35,
            'gates_pass'             => 34,
            'gates_fail'             => 0,
            'gates_blocked_external'  => 1,
            'gates_blocked_internal'  => 0,
            'gates_na'              => 0,
            'tests_passed'          => 83,
            'tests_failed'          => 0,
            'sab_violations_new'    => 0,
            'sab_violations_blocking' => 0,
            'branch'                => 'integration/booking-production',
            'commit'                => 'HEAD',
            'generated_at'          => now()->toIso8601String(),
        ]);

        $result = $validator->validate($state);

        $this->assertNull($result, "Sprint 4.15 with G35 blocked + 1 external gate should produce no drift");
    }
}

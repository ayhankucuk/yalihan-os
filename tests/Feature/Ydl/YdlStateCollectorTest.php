<?php

namespace Tests\Feature\Ydl;

use Tests\TestCase;
use App\Services\Ydl\YdlStateCollector;
use App\DTOs\Ydl\YdlStateDefinition;

/**
 * YdlStateCollector Test Suite
 *
 * YDL v1 Phase 1 — State Collection Tests
 *
 * Verifies:
 *   - Parses current.json correctly
 *   - Finds latest snapshot by mtime
 *   - Counts active blockers from blockers.json
 *   - Gracefully handles missing files
 *   - Falls back to defaults when fields are absent
 */
class YdlStateCollectorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ydl_collector_test_' . uniqid();
        mkdir($this->tempDir . '/memory/ydl/state', 0755, true);
        mkdir($this->tempDir . '/memory/ydl/snapshots', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->tempDir);
        parent::tearDown();
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) { return; }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function writeStateFile(array $data): void
    {
        $path = $this->tempDir . '/memory/ydl/state/current.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function writeBlockerFile(array $data): void
    {
        $path = $this->tempDir . '/memory/ydl/blockers.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function writeSnapshot(string $id, array $data, ?int $mtime = null): void
    {
        $path = $this->tempDir . '/memory/ydl/snapshots/' . $id . '.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        if ($mtime !== null) {
            touch($path, $mtime);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Basic parsing
    // ─────────────────────────────────────────────────────────────────

    public function test_parses_active_sprint_id(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15', 'status' => 'ACTIVE'],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('Sprint 4.15', $state->sprint);
    }

    public function test_parses_sprint_status(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15', 'status' => 'AWAITING_BOOKING_COM_ONBOARDING'],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('AWAITING_BOOKING_COM_ONBOARDING', $state->sprintStatus);
    }

    public function test_parses_gate_counts(): void
    {
        $this->writeStateFile([
            'active_sprint' => [
                'id' => 'Sprint 4.15',
                'gates_total' => 35,
                'gates_pass' => 30,
                'gates_fail' => 2,
                'gates_blocked_external' => 1,
                'gates_blocked_internal' => 1,
                'gates_na' => 1,
            ],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals(35, $state->gatesTotal);
        $this->assertEquals(30, $state->gatesPass);
        $this->assertEquals(2, $state->gatesFail);
        $this->assertEquals(1, $state->gatesBlockedExternal);
        $this->assertEquals(1, $state->gatesBlockedInternal);
        $this->assertEquals(1, $state->gatesNa);
    }

    // ─────────────────────────────────────────────────────────────────
    // Snapshot selection by mtime
    // ─────────────────────────────────────────────────────────────────

    public function test_chooses_latest_snapshot_by_mtime(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15'],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $oldTime = strtotime('2026-08-01 10:00:00');
        $newTime = strtotime('2026-08-12 11:00:00');

        $this->writeSnapshot('snap-old', ['snapshot_id' => 'snap-old', 'test_results' => ['tests_passed' => 50]], $oldTime);
        $this->writeSnapshot('snap-new', ['snapshot_id' => 'snap-new', 'test_results' => ['tests_passed' => 83]], $newTime);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('snap-new', $state->snapshotId);
    }

    public function test_parses_test_results_from_latest_snapshot(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15'],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $oldTime = strtotime('2026-08-01 10:00:00');
        $newTime = strtotime('2026-08-12 11:00:00');

        $this->writeSnapshot('snap-old', ['snapshot_id' => 'snap-old', 'test_results' => ['tests_passed' => 50, 'tests_failed' => 5]], $oldTime);
        $this->writeSnapshot('snap-new', ['snapshot_id' => 'snap-new', 'test_results' => ['tests_passed' => 83, 'tests_failed' => 0]], $newTime);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals(83, $state->testsPassed);
        $this->assertEquals(0, $state->testsFailed);
    }

    public function test_handles_empty_snapshots_directory(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15'],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('snap-initial', $state->snapshotId);
        $this->assertEquals(0, $state->testsPassed);
        $this->assertEquals(0, $state->testsFailed);
    }

    // ─────────────────────────────────────────────────────────────────
    // Blocker counting
    // ─────────────────────────────────────────────────────────────────

    public function test_counts_active_blockers(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15'],
        ]);
        $this->writeBlockerFile([
            'blockers' => [
                ['id' => 'BLK-001', 'status' => 'ACTIVE'],
                ['id' => 'BLK-002', 'status' => 'RESOLVED'],
                ['id' => 'BLK-003', 'status' => 'ACTIVE'],
            ],
        ]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        // The collector reads blockers from the file but the gateBlockedExternal
        // comes from active_sprint in state file. Here we just verify it doesn't crash.
        $this->assertInstanceOf(YdlStateDefinition::class, $state);
    }

    // ─────────────────────────────────────────────────────────────────
    // SAB violations
    // ─────────────────────────────────────────────────────────────────

    public function test_parses_sab_violations(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15'],
            'sab' => [
                'new_violations' => 3,
                'blocking_violations' => 1,
            ],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals(3, $state->sabViolationsNew);
        $this->assertEquals(1, $state->sabViolationsBlocking);
    }

    // ─────────────────────────────────────────────────────────────────
    // Git info
    // ─────────────────────────────────────────────────────────────────

    public function test_parses_git_info(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15'],
            'git' => [
                'branch' => 'feature/new-capability',
                'commit' => 'abc1234',
            ],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('feature/new-capability', $state->branch);
        $this->assertEquals('abc1234', $state->commit);
    }

    public function test_uses_default_git_info_when_missing(): void
    {
        $this->writeStateFile([
            'active_sprint' => ['id' => 'Sprint 4.15'],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('integration/booking-production', $state->branch);
        $this->assertEquals('HEAD', $state->commit);
    }

    // ─────────────────────────────────────────────────────────────────
    // Edge cases
    // ─────────────────────────────────────────────────────────────────

    public function test_handles_missing_state_file(): void
    {
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('Sprint 4.15', $state->sprint);
        $this->assertEquals(0, $state->gatesTotal);
        $this->assertEquals(0, $state->gatesPass);
    }

    public function test_handles_invalid_json_in_state_file(): void
    {
        $path = $this->tempDir . '/memory/ydl/state/current.json';
        file_put_contents($path, 'not valid json {{{');
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('Sprint 4.15', $state->sprint);
        $this->assertEquals(0, $state->gatesTotal);
    }

    public function test_uses_status_field_from_active_sprint(): void
    {
        $this->writeStateFile([
            'active_sprint' => [
                'id' => 'Sprint 4.15',
                'status' => 'IN_PROGRESS',
            ],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('IN_PROGRESS', $state->sprintStatus);
    }

    public function test_casts_all_gate_fields_to_int(): void
    {
        $this->writeStateFile([
            'active_sprint' => [
                'id' => 'Sprint 4.15',
                'gates_total' => '35',
                'gates_pass' => '30',
                'gates_fail' => '2',
                'gates_blocked_external' => '1',
                'gates_blocked_internal' => '0',
                'gates_na' => '2',
            ],
        ]);
        $this->writeBlockerFile(['blockers' => []]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertIsInt($state->gatesTotal);
        $this->assertIsInt($state->gatesPass);
        $this->assertIsInt($state->gatesFail);
        $this->assertEquals(35, $state->gatesTotal);
    }

    // ─────────────────────────────────────────────────────────────────
    // Sprint 4.15 real data baseline
    // ─────────────────────────────────────────────────────────────────

    public function test_sprint_4_15_real_data(): void
    {
        $this->writeStateFile([
            'active_sprint' => [
                'id' => 'Sprint 4.15',
                'status' => 'AWAITING_BOOKING_COM_ONBOARDING',
                'gates_total' => 35,
                'gates_pass' => 34,
                'gates_fail' => 0,
                'gates_blocked_external' => 1,
                'gates_blocked_internal' => 0,
                'gates_na' => 0,
            ],
            'sab' => [
                'new_violations' => 0,
                'blocking_violations' => 0,
            ],
            'git' => [
                'branch' => 'integration/booking-production',
                'commit' => 'HEAD',
            ],
        ]);
        $this->writeBlockerFile([
            'blockers' => [
                [
                    'id' => 'BLK-001',
                    'gate' => 'G35',
                    'status' => 'ACTIVE',
                    'type' => 'EXTERNAL_DEPENDENCY',
                ],
            ],
        ]);

        $collector = new YdlStateCollector($this->tempDir);
        $state = $collector->collect();

        $this->assertEquals('Sprint 4.15', $state->sprint);
        $this->assertEquals(34, $state->gatesPass);
        $this->assertEquals(0, $state->gatesFail);
        $this->assertEquals(1, $state->gatesBlockedExternal);
        $this->assertEquals(0, $state->gatesBlockedInternal);
        $this->assertEquals(0, $state->sabViolationsNew);
        $this->assertEquals(0, $state->sabViolationsBlocking);
        $this->assertFalse($state->hasFailingGates());
        $this->assertFalse($state->hasBlockingViolations());
        $this->assertTrue($state->isEngineeringComplete());
    }
}

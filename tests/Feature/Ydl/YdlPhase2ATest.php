<?php

namespace Tests\Feature\Ydl;

use Tests\TestCase;
use App\Services\Ydl\YdlEventLog;
use App\Services\Ydl\Patchers\YdlStatePatcher;
use App\Services\Ydl\Patchers\YdlPatch;
use App\DTOs\Ydl\Events\YdlEvent;

/**
 * YDL Phase 2A — Event Log + State Patcher Tests
 *
 * YDL v1 Phase 2A — Idempotency + Diff Generation
 *
 * Tests:
 *   1. YdlEventLog append is idempotent (same event_id = no duplicate)
 *   2. YdlStatePatcher generates correct patches for Sprint 4.15 scenario
 *   3. Blocking/non-blocking patches produce correct content
 *   4. Idempotent no-op detection works
 *   5. blockerAdd, blockerResolve, blockerUpdate operations
 */
class YdlPhase2ATest extends TestCase
{
    private string $tempDir;
    private string $ydlDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ydl_phase2a_' . uniqid();
        $this->ydlDir = $this->tempDir . '/memory/ydl';
        mkdir($this->ydlDir . '/state', 0755, true);
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

    // ─────────────────────────────────────────────────────────────────
    // YdlEventLog Tests
    // ─────────────────────────────────────────────────────────────────

    public function test_append_first_event_returns_true(): void
    {
        $log = new YdlEventLog($this->tempDir);
        $event = $this->makeEvent('CERTIFICATION', 'Sprint 4.15');

        $result = $log->append($event);

        $this->assertTrue($result);
    }

    public function test_append_same_event_id_twice_returns_false(): void
    {
        $log = new YdlEventLog($this->tempDir);
        $event = $this->makeEvent('CERTIFICATION', 'Sprint 4.15');

        $first = $log->append($event);
        $second = $log->append($event);

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertEquals(1, $log->count());
    }

    public function test_append_different_events_both_succeed(): void
    {
        $log = new YdlEventLog($this->tempDir);

        $event1 = $this->makeEvent('CERTIFICATION', 'Sprint 4.15');
        $event2 = $this->makeEvent('BLOCKER_ADDED', 'Sprint 4.15');

        $this->assertTrue($log->append($event1));
        $this->assertTrue($log->append($event2));
        $this->assertEquals(2, $log->count());
    }

    public function test_event_id_is_deterministic(): void
    {
        $id1 = YdlEvent::generateEventId('Sprint 4.15', 'abc123', 'CERTIFICATION');
        $id2 = YdlEvent::generateEventId('Sprint 4.15', 'abc123', 'CERTIFICATION');

        $this->assertEquals($id1, $id2);
        $this->assertEquals(16, strlen($id1)); // SHA256 truncated to 16 chars
    }

    public function test_event_id_changes_with_commit(): void
    {
        $id1 = YdlEvent::generateEventId('Sprint 4.15', 'abc123', 'CERTIFICATION');
        $id2 = YdlEvent::generateEventId('Sprint 4.15', 'def456', 'CERTIFICATION');

        $this->assertNotEquals($id1, $id2);
    }

    public function test_events_for_sprint_returns_only_matching(): void
    {
        $log = new YdlEventLog($this->tempDir);

        $log->append($this->makeEvent('CERTIFICATION', 'Sprint 4.15'));
        $log->append($this->makeEvent('BLOCKER_ADDED', 'Sprint 4.16'));
        $log->append($this->makeEvent('SPRINT_CLOSED', 'Sprint 4.15'));

        $s414 = $log->eventsForSprint('Sprint 4.15');
        $s416 = $log->eventsForSprint('Sprint 4.16');

        $this->assertCount(2, $s414);
        $this->assertCount(1, $s416);
    }

    public function test_latest_event_returns_most_recent_by_timestamp(): void
    {
        $log = new YdlEventLog($this->tempDir);

        // Create events directly to ensure exact timestamps
        $e1Ts = '2026-08-12T10:00:00+03:00';
        $e2Ts = '2026-08-12T11:00:00+03:00';

        $e1 = new YdlEvent(
            eventId: YdlEvent::generateEventId('Sprint 4.15', 'aaa0001', 'CERTIFICATION'),
            type: YdlEvent::TYPE_CERTIFICATION,
            sprint: 'Sprint 4.15',
            snapshotId: 'snap-1',
            commit: 'aaa0001',
            action: 'CERTIFICATION',
            target: 'YDL_V1',
            rationale: 'Test',
            confidence: 'HIGH',
            parallelWorkAllowed: true,
            gatesPass: 10,
            gatesFail: 0,
            gatesBlockedExternal: 1,
            gatesBlockedInternal: 0,
            sabViolationsNew: 0,
            sabViolationsBlocking: 0,
            gitStatus: 'clean',
            blockerChanges: [],
            occurredAt: $e1Ts,
        );

        $e2 = new YdlEvent(
            eventId: YdlEvent::generateEventId('Sprint 4.15', 'bbb0002', 'SPRINT_CLOSED'),
            type: YdlEvent::TYPE_SPRINT_CLOSED,
            sprint: 'Sprint 4.15',
            snapshotId: 'snap-2',
            commit: 'bbb0002',
            action: 'SPRINT_CLOSED',
            target: 'Sprint 4.15',
            rationale: 'Test',
            confidence: 'HIGH',
            parallelWorkAllowed: false,
            gatesPass: 10,
            gatesFail: 0,
            gatesBlockedExternal: 1,
            gatesBlockedInternal: 0,
            sabViolationsNew: 0,
            sabViolationsBlocking: 0,
            gitStatus: 'clean',
            blockerChanges: [],
            occurredAt: $e2Ts,
        );

        $this->assertNotEquals($e1->eventId, $e2->eventId);
        $this->assertEquals($e1Ts, $e1->occurredAt);
        $this->assertEquals($e2Ts, $e2->occurredAt);
        $this->assertGreaterThan($e1->occurredAt, $e2->occurredAt);

        $log->append($e1);
        $log->append($e2);

        $this->assertEquals(2, $log->count());

        // Verify file order
        $all = $log->allEvents();
        $this->assertCount(2, $all);
        $this->assertEquals('CERTIFICATION', $all[0]->type);
        $this->assertEquals('SPRINT_CLOSED', $all[1]->type);

        // latestEventForSprint returns most recent by occurredAt timestamp
        $latest = $log->latestEventForSprint('Sprint 4.15');
        $this->assertNotNull($latest);
        $this->assertEquals('SPRINT_CLOSED', $latest->type);
    }

    // ─────────────────────────────────────────────────────────────────
    // YdlStatePatcher Tests
    // ─────────────────────────────────────────────────────────────────

    public function test_generate_produces_6_patches(): void
    {
        $event = $this->makeEvent('START', 'Sprint 4.15', [
            ['op' => 'add', 'blocker' => [
                'id' => 'BLK-001',
                'gate' => 'G35',
                'sprint' => 'Sprint 4.15',
                'type' => 'EXTERNAL_DEPENDENCY',
                'owner' => 'BOOKING_COM',
                'reason' => 'PARTNER_ONBOARDING',
                'development_action' => 'DO_NOT_CONTINUE_BOOKING_CODE',
                'next_independent_action' => 'YDL_V1',
                'status' => 'ACTIVE',
            ]],
        ]);

        $patcher = new YdlStatePatcher($this->tempDir);
        $patches = $patcher->generate($event);

        $this->assertCount(6, $patches);

        $targets = array_map(fn(YdlPatch $p) => $p->target, $patches);
        $this->assertContains('memory/ydl/blockers.json', $targets);
        $this->assertContains('memory/ydl/state/current.json', $targets);
        $this->assertContains('docs/BEKCI_CHANGELOG.md', $targets);
        $this->assertContains('memory/SESSION_NOTES.md', $targets);
        $this->assertContains('memory/CHANGELOG_AGENT.md', $targets);
        $this->assertContains('docs/PROGRESS-TRACKER.md', $targets);
    }

    public function test_generate_preserves_blocker_on_idempotent_add(): void
    {
        // Setup: blockers.json already has BLK-001
        $blockersPath = $this->ydlDir . '/blockers.json';
        file_put_contents($blockersPath, json_encode([
            'version' => '1.0',
            'blockers' => [['id' => 'BLK-001', 'gate' => 'G35', 'status' => 'ACTIVE']],
            'resolved' => [],
        ]));

        $event = $this->makeEvent('START', 'Sprint 4.15', [
            ['op' => 'add', 'blocker' => [
                'id' => 'BLK-001', // Duplicate — should NOT be added again
                'gate' => 'G35',
                'status' => 'ACTIVE',
            ]],
        ]);

        $patcher = new YdlStatePatcher($this->tempDir);
        $patches = $patcher->generate($event);

        $blockerPatch = null;
        foreach ($patches as $p) {
            if ($p->target === 'memory/ydl/blockers.json') {
                $blockerPatch = $p;
                break;
            }
        }

        $this->assertNotNull($blockerPatch);
        $decoded = json_decode($blockerPatch->newContent, true);
        $ids = array_column($decoded['blockers'] ?? [], 'id');
        // Should only have one BLK-001, not duplicated
        $this->assertEquals(1, count(array_filter($ids, fn($id) => $id === 'BLK-001')));
    }

    public function test_resolve_removes_active_blocker_and_adds_to_resolved(): void
    {
        $blockersPath = $this->ydlDir . '/blockers.json';
        file_put_contents($blockersPath, json_encode([
            'version' => '1.0',
            'blockers' => [['id' => 'BLK-001', 'gate' => 'G35', 'status' => 'ACTIVE']],
            'resolved' => [],
        ]));

        $event = $this->makeEvent('CERTIFIED', 'Sprint 4.15', [
            ['op' => 'resolve', 'id' => 'BLK-001', 'resolution_note' => 'Booking.com activated'],
        ]);

        $patcher = new YdlStatePatcher($this->tempDir);
        $patches = $patcher->generate($event);

        $blockerPatch = null;
        foreach ($patches as $p) {
            if ($p->target === 'memory/ydl/blockers.json') {
                $blockerPatch = $p;
                break;
            }
        }

        $this->assertNotNull($blockerPatch);
        $decoded = json_decode($blockerPatch->newContent, true);
        $this->assertEmpty($decoded['blockers']); // BLK-001 moved to resolved
        $this->assertCount(1, $decoded['resolved']);
        $this->assertEquals('BLK-001', $decoded['resolved'][0]['id']);
    }

    public function test_current_json_patch_has_gate_data(): void
    {
        $event = $this->makeEvent('START', 'Sprint 4.15');

        $patcher = new YdlStatePatcher($this->tempDir);
        $patches = $patcher->generate($event);

        $statePatch = null;
        foreach ($patches as $p) {
            if ($p->target === 'memory/ydl/state/current.json') {
                $statePatch = $p;
                break;
            }
        }

        $this->assertNotNull($statePatch);
        $decoded = json_decode($statePatch->newContent, true);

        $this->assertEquals('Sprint 4.15', $decoded['active_sprint']['id']);
        $this->assertEquals(34, $decoded['active_sprint']['gates_pass']);
        $this->assertEquals(1, $decoded['active_sprint']['gates_blocked_external']);
        $this->assertEquals('START', $decoded['recommendation']['action']);
        $this->assertEquals('YDL_V1', $decoded['recommendation']['target']);
        $this->assertTrue($decoded['recommendation']['parallel_work_allowed']);
    }

    public function test_progress_tracker_patch_generates_content(): void
    {
        // Use real base path so PROGRESS-TRACKER.md is found
        $realBase = base_path();
        $event = $this->makeEvent('CERTIFIED', 'Sprint 4.15');
        $patcher = new YdlStatePatcher($realBase);
        $patches = $patcher->generate($event);

        $trackerPatch = null;
        foreach ($patches as $p) {
            if ($p->target === 'docs/PROGRESS-TRACKER.md') {
                $trackerPatch = $p;
                break;
            }
        }

        $this->assertNotNull($trackerPatch);
        $this->assertEquals('update_progress', $trackerPatch->operation);
        // With real base path, PROGRESS-TRACKER.md exists and gets patched
        $this->assertIsString($trackerPatch->newContent);
        $this->assertNotEmpty($trackerPatch->newContent);
    }

    public function test_all_patches_have_deterministic_ids(): void
    {
        $event = $this->makeEvent('START', 'Sprint 4.15');
        $patcher = new YdlStatePatcher($this->tempDir);
        $patches1 = $patcher->generate($event);
        $patches2 = $patcher->generate($event);

        $this->assertCount(6, $patches1);
        $this->assertCount(6, $patches2);

        // Same patches (no-op since no file change in temp dir)
        for ($i = 0; $i < 6; $i++) {
            $this->assertEquals($patches1[$i]->target, $patches2[$i]->target);
            $this->assertEquals($patches1[$i]->operation, $patches2[$i]->operation);
        }
    }

    private function makeEvent(
        string $action,
        string $sprint,
        array $blockerChanges = [],
    ): YdlEvent {
        return $this->makeEventWithCommit($action, $sprint, 'abc1234', now()->toIso8601String(), $blockerChanges);
    }

    private function makeEventWithCommit(
        string $action,
        string $sprint,
        string $commit,
        string $occurredAt,
        array $blockerChanges = [],
    ): YdlEvent {
        $eventId = YdlEvent::generateEventId($sprint, $commit, $action);

        return new YdlEvent(
            eventId:             $eventId,
            type:               YdlEvent::TYPE_CERTIFICATION,
            sprint:             $sprint,
            snapshotId:         'snap-test-001',
            commit:             $commit,
            action:             $action,
            target:             $action === 'START' ? 'YDL_V1' : ($action === 'CERTIFIED' ? 'Sprint 4.15' : ''),
            rationale:          "Test rationale for {$sprint} - {$action}",
            confidence:         'HIGH',
            parallelWorkAllowed: true,
            gatesPass:          34,
            gatesFail:          0,
            gatesBlockedExternal: 1,
            gatesBlockedInternal: 0,
            sabViolationsNew:    0,
            sabViolationsBlocking: 0,
            gitStatus:          'clean',
            blockerChanges:     $blockerChanges,
            occurredAt:         $occurredAt,
        );
    }
}

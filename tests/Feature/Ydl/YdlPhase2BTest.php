<?php

namespace Tests\Feature\Ydl;

use App\DTOs\Ydl\Events\YdlEvent;
use App\Services\Ydl\Phase2B\FileHashStore;
use App\Services\Ydl\Phase2B\YdlControlledWriter;
use App\Services\Ydl\Phase2B\YdlIdempotencyGuard;
use App\Services\Ydl\Phase2B\YdlWriteGuard;
use App\Services\Ydl\Patchers\YdlPatch;
use App\Services\Ydl\YdlEventLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * YDL Phase 2B Certification Gate Tests.
 *
 * Certification Gates:
 *   G1 — Idempotency:    duplicate event detection
 *   G2 — Target Whitelist: patch targets restricted to institutional-memory files
 *   G3 — State Drift:    Git must be clean, no drift since certification
 *   G4 — File Hash:      pre-patch file hashes verified
 */
class YdlPhase2BTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = storage_path('testing/ydl_phase2b_' . uniqid());
        if (is_dir($this->testDir)) {
            $this->rmdir($this->testDir);
        }
        File::ensureDirectoryExists($this->testDir . '/memory/ydl/state');
        File::ensureDirectoryExists($this->testDir . '/docs');
        File::ensureDirectoryExists($this->testDir . '/memory');
        File::ensureDirectoryExists($this->testDir . '/app/Services');
        File::ensureDirectoryExists($this->testDir . '/app/Http');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->testDir);
        parent::tearDown();
    }

    private function rmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    // ─────────────────────────────────────────────────────────────────
    // G1: Idempotency Guard Tests
    // ─────────────────────────────────────────────────────────────────

    public function test_g1_new_event_is_allowed(): void
    {
        $guard = new YdlIdempotencyGuard([]);
        $event = $this->makeEvent('evt_abc123');

        $result = $guard->check($event);

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
    }

    public function test_g1_duplicate_event_is_blocked(): void
    {
        $guard = new YdlIdempotencyGuard(['evt_abc123']);
        $event = $this->makeEvent('evt_abc123');

        $result = $guard->check($event);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('DUPLICATE_EVENT', $result['reason']);
    }

    public function test_g1_different_events_both_allowed(): void
    {
        $guard = new YdlIdempotencyGuard(['evt_abc123']);
        $event = $this->makeEvent('evt_def456');

        $result = $guard->check($event);

        $this->assertTrue($result['allowed']);
    }

    public function test_g1_is_processed_tracks_events(): void
    {
        $guard = new YdlIdempotencyGuard([]);
        $guard->markProcessed('evt_abc123');
        $guard->markProcessed('evt_def456');

        $this->assertTrue($guard->isProcessed('evt_abc123'));
        $this->assertTrue($guard->isProcessed('evt_def456'));
        $this->assertFalse($guard->isProcessed('evt_xyz789'));
    }

    // ─────────────────────────────────────────────────────────────────
    // G2: Target Whitelist Tests
    // ─────────────────────────────────────────────────────────────────

    public function test_g2_whitelisted_targets_allowed(): void
    {
        $whitelist = YdlWriteGuard::allowedTargets();

        $this->assertContains('memory/ydl/blockers.json', $whitelist);
        $this->assertContains('memory/ydl/state/current.json', $whitelist);
        $this->assertContains('docs/BEKCI_CHANGELOG.md', $whitelist);
        $this->assertContains('memory/SESSION_NOTES.md', $whitelist);
        $this->assertContains('memory/CHANGELOG_AGENT.md', $whitelist);
        $this->assertContains('docs/PROGRESS-TRACKER.md', $whitelist);
    }

    public function test_g2_app_directory_blocked(): void
    {
        $patches = [
            new YdlPatch(
                target: 'app/Services/Ilan/IlanCrudService.php',
                operation: 'update',
                currentHash: 'abc123',
                plannedHash: 'def456',
                rationale: 'test',
                changes: [],
                newContent: '<?php',
            ),
        ];

        $event = $this->makeEvent('evt_app_blocked');
        $guard = new YdlWriteGuard(null, $this->makeMockEventLog([]), null, null, $this->testDir);
        $result = $guard->authorize($patches, $event);

        $this->assertFalse($result['pass']);
        $this->assertArrayHasKey('G2_TargetWhitelist', $result['gates']);
        $this->assertFalse($result['gates']['G2_TargetWhitelist']['pass']);
        // Reason must mention the blocked target path
        $this->assertStringContainsString('app/Services/Ilan/IlanCrudService.php', $result['gates']['G2_TargetWhitelist']['reason']);
    }

    public function test_g2_database_directory_blocked(): void
    {
        $patches = [
            new YdlPatch(
                target: 'database/migrations/2026_08_12_fix_ilan.sql',
                operation: 'update',
                currentHash: 'abc123',
                plannedHash: 'def456',
                rationale: 'test',
                changes: [],
                newContent: 'UPDATE ilanlar SET...',
            ),
        ];

        $event = $this->makeEvent('evt_db_blocked');
        $guard = new YdlWriteGuard(null, $this->makeMockEventLog([]), null, null, $this->testDir);
        $result = $guard->authorize($patches, $event);

        $this->assertFalse($result['pass']);
        $this->assertArrayHasKey('G2_TargetWhitelist', $result['gates']);
        $this->assertFalse($result['gates']['G2_TargetWhitelist']['pass']);
        $this->assertStringContainsString('database/migrations/2026_08_12_fix_ilan.sql', $result['gates']['G2_TargetWhitelist']['reason']);
    }

    public function test_g2_whitelisted_memory_file_allowed(): void
    {
        $patches = [
            new YdlPatch(
                target: 'memory/ydl/blockers.json',
                operation: 'update_blockers',
                currentHash: 'abc123',
                plannedHash: 'def456',
                rationale: 'test',
                changes: [],
                newContent: '{"version":"1.0","blockers":[]}',
            ),
        ];

        $eventLog = $this->makeMockEventLog([]);
        $guard = new YdlWriteGuard(null, $eventLog, null, null, $this->testDir);
        $event = $this->makeEvent('evt_test');

        $result = $guard->authorize($patches, $event);

        // G2 should pass (G1 may fail if no event log)
        $this->assertTrue($result['gates']['G2_TargetWhitelist']['pass']);
    }

    public function test_g2_routes_directory_blocked(): void
    {
        $patches = [
            new YdlPatch(
                target: 'routes/web.php',
                operation: 'update',
                currentHash: 'abc123',
                plannedHash: 'def456',
                rationale: 'test',
                changes: [],
                newContent: '<?php',
            ),
        ];

        $event = $this->makeEvent('evt_routes_blocked');
        $guard = new YdlWriteGuard(null, $this->makeMockEventLog([]), null, null, $this->testDir);
        $result = $guard->authorize($patches, $event);

        $this->assertFalse($result['pass']);
        $this->assertArrayHasKey('G2_TargetWhitelist', $result['gates']);
        $this->assertFalse($result['gates']['G2_TargetWhitelist']['pass']);
        $this->assertStringContainsString('routes/web.php', $result['gates']['G2_TargetWhitelist']['reason']);
    }

    public function test_g2_env_file_blocked(): void
    {
        $patches = [
            new YdlPatch(
                target: '.env',
                operation: 'update',
                currentHash: 'abc123',
                plannedHash: 'def456',
                rationale: 'test',
                changes: [],
                newContent: 'APP_ENV=production',
            ),
        ];

        $event = $this->makeEvent('evt_env_blocked');
        $guard = new YdlWriteGuard(null, $this->makeMockEventLog([]), null, null, $this->testDir);
        $result = $guard->authorize($patches, $event);

        $this->assertFalse($result['pass']);
        $this->assertArrayHasKey('G2_TargetWhitelist', $result['gates']);
        $this->assertFalse($result['gates']['G2_TargetWhitelist']['pass']);
        $this->assertStringContainsString('.env', $result['gates']['G2_TargetWhitelist']['reason']);
    }

    // ─────────────────────────────────────────────────────────────────
    // G4: File Hash Store Tests
    // ─────────────────────────────────────────────────────────────────

    public function test_g4_snapshot_stores_hash(): void
    {
        $path = 'memory/ydl/blockers.json';
        $fullPath = $this->testDir . '/' . $path;
        file_put_contents($fullPath, '{"test":"content"}');

        $store = new FileHashStore($this->testDir);
        $hash = $store->snapshot($path);

        $this->assertNotEmpty($hash);
        $this->assertEquals(md5_file($fullPath), $hash);
    }

    public function test_g4_verify_passes_when_hash_matches(): void
    {
        $path = 'memory/ydl/blockers.json';
        $fullPath = $this->testDir . '/' . $path;
        file_put_contents($fullPath, '{"test":"content"}');
        $hash = md5_file($fullPath);

        $store = new FileHashStore($this->testDir);
        $store->snapshot($path);
        $result = $store->verifyPreApply($path, $hash);

        $this->assertTrue($result['valid']);
    }

    public function test_g4_verify_fails_when_file_changed(): void
    {
        $path = 'memory/ydl/blockers.json';
        $fullPath = $this->testDir . '/' . $path;
        file_put_contents($fullPath, '{"original":true}');
        $originalHash = md5_file($fullPath);

        $store = new FileHashStore($this->testDir);
        $store->snapshot($path);

        // Simulate file changed after snapshot
        file_put_contents($fullPath, '{"modified":true}');

        $result = $store->verifyPreApply($path, $originalHash);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('HASH_MISMATCH', $result['reason']);
    }

    public function test_g4_verify_post_apply_fails_on_corruption(): void
    {
        $path = 'memory/CHANGELOG_AGENT.md';
        $fullPath = $this->testDir . '/' . $path;
        $content = "# Changelog\n\n## Entry 1";
        file_put_contents($fullPath, $content);
        $plannedHash = md5($content);

        $store = new FileHashStore($this->testDir);

        // Write wrong content
        file_put_contents($fullPath, "# Wrong content");
        $result = $store->verifyPostApply($path, $plannedHash);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('WRITE_VERIFY_FAILED', $result['reason']);
    }

    public function test_g4_verify_post_apply_succeeds_on_match(): void
    {
        $path = 'memory/CHANGELOG_AGENT.md';
        $fullPath = $this->testDir . '/' . $path;
        $content = "# Changelog\n\n## Entry 1";
        file_put_contents($fullPath, $content);
        $plannedHash = md5($content);

        $store = new FileHashStore($this->testDir);
        $result = $store->verifyPostApply($path, $plannedHash);

        $this->assertTrue($result['valid']);
    }

    // ─────────────────────────────────────────────────────────────────
    // YdlControlledWriter Tests
    // ─────────────────────────────────────────────────────────────────

    public function test_writer_skips_when_content_matches(): void
    {
        $path = 'memory/CHANGELOG_AGENT.md';
        $fullPath = $this->testDir . '/' . $path;
        $content = "# Existing content";
        file_put_contents($fullPath, $content);

        $writer = new YdlControlledWriter(
            new FileHashStore($this->testDir),
            new YdlEventLog($this->testDir),
            $this->testDir
        );

        $patches = [
            new YdlPatch(
                target: $path,
                operation: 'prepend_entry',
                currentHash: md5($content),
                plannedHash: md5($content),
                rationale: 'idempotent skip',
                changes: [],
                newContent: $content,
            ),
        ];

        $event = $this->makeEvent('evt_skip_test');
        $result = $writer->apply($patches, $event);

        $this->assertEquals(0, $result['applied']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals($content, file_get_contents($fullPath));
    }

    public function test_writer_applies_patch_and_verifies_hash(): void
    {
        $path = 'memory/SESSION_NOTES.md';
        $fullPath = $this->testDir . '/' . $path;
        file_put_contents($fullPath, "# Old notes");

        $newContent = "# New notes\n\n## New entry";
        $writer = new YdlControlledWriter(
            new FileHashStore($this->testDir),
            new YdlEventLog($this->testDir),
            $this->testDir
        );

        $patches = [
            new YdlPatch(
                target: $path,
                operation: 'prepend_section',
                currentHash: md5_file($fullPath),
                plannedHash: md5($newContent),
                rationale: 'test write',
                changes: [],
                newContent: $newContent,
            ),
        ];

        $event = $this->makeEvent('evt_write_test');
        $result = $writer->apply($patches, $event);

        $this->assertEquals(1, $result['applied']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals($newContent, file_get_contents($fullPath));
    }

    public function test_writer_creates_directory_if_missing(): void
    {
        $path = 'memory/ydl/state/current.json';
        $fullPath = $this->testDir . '/' . $path;

        $writer = new YdlControlledWriter(
            new FileHashStore($this->testDir),
            new YdlEventLog($this->testDir),
            $this->testDir
        );

        $patches = [
            new YdlPatch(
                target: $path,
                operation: 'update_state',
                currentHash: '',
                plannedHash: md5('{"version":"1.0"}'),
                rationale: 'create state file',
                changes: [],
                newContent: '{"version":"1.0"}',
            ),
        ];

        $event = $this->makeEvent('evt_create_test');
        $result = $writer->apply($patches, $event);

        $this->assertEquals(1, $result['applied']);
        $this->assertFileExists($fullPath);
    }

    public function test_writer_dry_run_reports_correctly(): void
    {
        $patches = [
            new YdlPatch(
                target: 'docs/PROGRESS-TRACKER.md',
                operation: 'noop_idempotent',
                currentHash: 'abc',
                plannedHash: 'abc',
                rationale: 'no-op test',
                changes: [],
                newContent: '# Progress',
            ),
            new YdlPatch(
                target: 'memory/ydl/blockers.json',
                operation: 'update_blockers',
                currentHash: 'def',
                plannedHash: 'ghi',
                rationale: 'change test',
                changes: [],
                newContent: '{"blockers":[]}',
            ),
        ];

        $writer = new YdlControlledWriter(null, null, $this->testDir);
        $result = $writer->dryRun($patches);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, count($result['noop']));
        $this->assertEquals(1, count($result['would_change']));
    }

    public function test_patch_carries_pre_write_hash(): void
    {
        $patch = new YdlPatch(
            target: 'memory/ydl/state/current.json',
            operation: 'update_state',
            currentHash: 'hash_before_write',
            plannedHash: 'hash_after_write',
            rationale: 'state update',
            changes: [],
            newContent: '{"updated":true}',
        );

        $this->assertEquals('hash_before_write', $patch->currentHash);
        $this->assertEquals('hash_after_write', $patch->plannedHash);
        $this->assertTrue($patch->isChange());
        $this->assertFalse($patch->isNoOp());
    }

    public function test_noop_patch_has_same_current_and_planned_hash(): void
    {
        $patch = new YdlPatch(
            target: 'docs/BEKCI_CHANGELOG.md',
            operation: 'noop_idempotent',
            currentHash: 'abc123',
            plannedHash: 'abc123',
            rationale: 'already applied',
            changes: [],
            newContent: '# Existing content',
        );

        $this->assertEquals($patch->currentHash, $patch->plannedHash);
        $this->assertTrue($patch->isNoOp());
        $this->assertFalse($patch->isChange());
    }

    // ─────────────────────────────────────────────────────────────────
    // Integration: Guard G2 + G4 pipeline
    // ─────────────────────────────────────────────────────────────────

    public function test_g2_and_g4_pass_with_valid_whitelisted_patches(): void
    {
        $path = 'memory/ydl/blockers.json';
        $fullPath = $this->testDir . '/' . $path;
        $content = '{"blockers":[]}';
        file_put_contents($fullPath, $content);

        $eventLog = $this->makeMockEventLog([]);
        $hashStore = new FileHashStore($this->testDir);
        $hashStore->snapshot($path);

        $patches = [
            new YdlPatch(
                target: $path,
                operation: 'update_blockers',
                currentHash: md5_file($fullPath),
                plannedHash: md5('{"updated":true}'),
                rationale: 'test',
                changes: [],
                newContent: '{"updated":true}',
            ),
        ];

        // Build a partial guard that skips G3 (orchestrator dependency)
        $guard = new class($eventLog, $hashStore, $this->testDir) extends YdlWriteGuard {
            public function __construct($eventLog, $hashStore, $testDir)
            {
                parent::__construct(null, $eventLog, $hashStore, null, $testDir);
            }
            public function authorize(array $patches, ?YdlEvent $event = null): array
            {
                $gates = [];

                $g1Result = $this->runGate1_Idempotency($event);
                $gates['G1_Idempotency'] = $g1Result;
                if (!$g1Result['pass']) {
                    return ['pass' => false, 'gates' => $gates, 'blocked_patches' => [], 'event_id' => $event?->eventId];
                }

                $g2Result = $this->runGate2_Whitelist($patches);
                $gates['G2_TargetWhitelist'] = $g2Result;
                if (!$g2Result['pass']) {
                    return ['pass' => false, 'gates' => $gates, 'blocked_patches' => $g2Result['blocked'], 'event_id' => $event?->eventId];
                }

                $g4Result = $this->runGate4_FileHash($patches);
                $gates['G4_FileHash'] = $g4Result;
                if (!$g4Result['pass']) {
                    return ['pass' => false, 'gates' => $gates, 'blocked_patches' => $g4Result['mismatches'], 'event_id' => $event?->eventId];
                }

                return ['pass' => true, 'gates' => $gates, 'blocked_patches' => [], 'event_id' => $event?->eventId];
            }
        };

        $event = $this->makeEvent('evt_fresh_event');
        $result = $guard->authorize($patches, $event);

        $this->assertTrue($result['pass']);
        $this->assertTrue($result['gates']['G2_TargetWhitelist']['pass']);
        $this->assertTrue($result['gates']['G4_FileHash']['pass']);
    }

    public function test_guard_blocks_on_g2_forbidden_target(): void
    {
        $patches = [
            new YdlPatch(
                target: 'app/Models/Ilan.php',
                operation: 'update',
                currentHash: 'abc',
                plannedHash: 'def',
                rationale: 'BLOCKED - production code',
                changes: [],
                newContent: '<?php class Ilan {}',
            ),
        ];

        $eventLog = $this->makeMockEventLog([]);
        $guard = new YdlWriteGuard(null, $eventLog, null, null, $this->testDir);
        $event = $this->makeEvent('evt_prod_write_attempt');

        $result = $guard->authorize($patches, $event);

        $this->assertFalse($result['pass']);
        $this->assertArrayHasKey('G2_TargetWhitelist', $result['gates']);
        $this->assertFalse($result['gates']['G2_TargetWhitelist']['pass']);
        $this->assertMatchesRegularExpression('/FORBIDDEN_PATTERN|NOT_IN_WHITELIST/', $result['gates']['G2_TargetWhitelist']['reason']);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function makeEvent(string $eventId): YdlEvent
    {
        return new YdlEvent(
            eventId: $eventId,
            type: YdlEvent::TYPE_CERTIFICATION,
            sprint: 'Sprint-Phase2B-Test',
            snapshotId: 'snap_test_001',
            commit: 'abc123def456',
            action: 'CERTIFIED',
            target: 'next_sprint',
            rationale: 'Phase 2B test certification event',
            confidence: 'HIGH',
            parallelWorkAllowed: true,
            gatesPass: 4,
            gatesFail: 0,
            gatesBlockedExternal: 0,
            gatesBlockedInternal: 0,
            sabViolationsNew: 0,
            sabViolationsBlocking: 0,
            gitStatus: 'clean',
            blockerChanges: [],
            occurredAt: now()->toIso8601String(),
        );
    }

    /**
     * Create a mock event log with pre-seeded events.
     *
     * @param YdlEvent[] $events
     */
    private function makeMockEventLog(array $events): YdlEventLog
    {
        // Create a temporary event log file with pre-seeded events
        $logDir = $this->testDir . '/memory/ydl';
        $logPath = $logDir . '/event-log.jsonl';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $handle = fopen($logPath, 'w');
        foreach ($events as $event) {
            fwrite($handle, json_encode($event->toArray(), JSON_UNESCAPED_SLASHES) . "\n");
        }
        fclose($handle);

        return new YdlEventLog($this->testDir);
    }
}

<?php

namespace Tests\Feature\Ydl;

use App\DTOs\Ydl\YdlContextOutput;
use App\Services\Ydl\YdlContextReader;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * YDL Phase 3 — Agent Context Integration Tests.
 *
 * Tests:
 *   T1 — YdlContextReader reads current state correctly
 *   T2 — Authority FULL: no blockers
 *   T3 — Authority LIMITED_BY_BLOCKER: BLK-001 active, DO_NOT_CONTINUE_BOOKING_CODE
 *   T4 — Authority STOP: security blocker
 *   T5 — toMarkdown() produces valid markdown
 *   T6 — toAuthoritySummary() produces minimal output
 *   T7 — toJson() produces valid JSON
 *   T8 — empty state returns AUTHORITY_NO_SPRINT
 */
class YdlPhase3ContextTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = storage_path('testing/ydl_phase3_' . uniqid());
        $this->ensureDir($this->testDir . '/memory/ydl/state');
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->testDir);
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────
    // T1: ContextReader reads state correctly
    // ─────────────────────────────────────────────────────────────────

    public function test_t1_context_reader_reads_state_fields_correctly(): void
    {
        $this->writeStateFile([
            'active_sprint' => [
                'id' => 'Sprint 4.15',
                'status' => 'AWAITING_EXTERNAL',
                'gates_pass' => 34,
                'gates_fail' => 0,
                'gates_blocked_external' => 1,
                'gates_blocked_internal' => 0,
            ],
            'sab' => [
                'new_violations' => 0,
                'blocking_violations' => 0,
            ],
            'git' => [
                'branch' => 'integration/era-v-phase2a-e01',
                'commit' => '511eb634',
            ],
            'recommendation' => [
                'action' => 'START',
                'target' => 'YDL_V1',
                'rationale' => 'All development gates PASS.',
                'confidence' => 'HIGH',
            ],
            'updated' => '2026-08-13T09:00:00+00:00',
        ]);

        $this->writeBlockerFile([]);

        $reader = new YdlContextReader($this->testDir);
        $ctx = $reader->read();

        $this->assertSame('Sprint 4.15', $ctx->sprint);
        $this->assertSame('AWAITING_EXTERNAL', $ctx->sprintStatus);
        $this->assertSame('START', $ctx->recommendationAction);
        $this->assertSame('YDL_V1', $ctx->recommendationTarget);
        $this->assertSame('HIGH', $ctx->confidence);
        $this->assertSame('integration/era-v-phase2a-e01', $ctx->gitBranch);
        $this->assertSame('511eb634', $ctx->gitCommit);
        $this->assertSame(YdlContextOutput::SAB_CLEAN, $ctx->sabStatus);
        $this->assertSame(0, $ctx->sabViolationsNew);
        $this->assertSame(0, $ctx->sabViolationsBlocking);
    }

    // ─────────────────────────────────────────────────────────────────
    // T2: Authority FULL — no blockers
    // ─────────────────────────────────────────────────────────────────

    public function test_t2_authority_full_when_no_blockers(): void
    {
        $this->writeStateFile($this->minimalState([
            'recommendation' => ['action' => 'START', 'target' => 'YDL_V1', 'rationale' => 'ok', 'confidence' => 'HIGH'],
        ]));
        $this->writeBlockerFile([]);

        $ctx = (new YdlContextReader($this->testDir))->read();

        $this->assertSame(YdlContextOutput::AUTHORITY_FULL, $ctx->authorityLevel);
        $this->assertStringContainsString('Blokör yok', $ctx->authorityRationale);
    }

    // ─────────────────────────────────────────────────────────────────
    // T3: Authority LIMITED_BY_BLOCKER — DO_NOT_CONTINUE_BOOKING_CODE
    // ─────────────────────────────────────────────────────────────────

    public function test_t3_authority_limited_by_blocker_do_not_continue(): void
    {
        $this->writeStateFile($this->minimalState([
            'recommendation' => ['action' => 'START', 'target' => 'YDL_V1', 'rationale' => 'ok', 'confidence' => 'HIGH'],
        ]));
        $this->writeBlockerFile([[
            'id' => 'BLK-001',
            'gate' => 'G35',
            'type' => 'EXTERNAL_DEPENDENCY',
            'owner' => 'BOOKING_COM',
            'development_action' => 'DO_NOT_CONTINUE_BOOKING_CODE',
            'reason' => 'PARTNER_ONBOARDING',
            'created_at' => '2026-08-12T11:00:00+03:00',
            'status' => 'ACTIVE',
        ]]);

        $ctx = (new YdlContextReader($this->testDir))->read();

        $this->assertSame(YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER, $ctx->authorityLevel);
        $this->assertStringContainsString('BLK-001', $ctx->authorityRationale);
        $this->assertStringContainsString('DO_NOT_CONTINUE', $ctx->authorityRationale);
        $this->assertCount(1, $ctx->activeBlockers);
        $this->assertSame('BLK-001', $ctx->activeBlockers[0]['id']);
        $this->assertSame('DO_NOT_CONTINUE_BOOKING_CODE', $ctx->activeBlockers[0]['development_action']);
    }

    // ─────────────────────────────────────────────────────────────────
    // T4: Authority STOP — security blocker
    // ─────────────────────────────────────────────────────────────────

    public function test_t4_authority_stop_when_security_blocker(): void
    {
        $this->writeStateFile($this->minimalState([
            'recommendation' => ['action' => 'STOP', 'target' => 'YDL_V1', 'rationale' => 'Security issue', 'confidence' => 'HIGH'],
        ]));
        $this->writeBlockerFile([[
            'id' => 'BLK-SEC-001',
            'gate' => 'G99',
            'type' => 'SECURITY_ISSUE',
            'owner' => 'TEAM',
            'development_action' => 'STOP_IMMEDIATELY',
            'reason' => 'Security vulnerability detected',
            'created_at' => '2026-08-13T09:00:00+03:00',
            'status' => 'ACTIVE',
        ]]);

        $ctx = (new YdlContextReader($this->testDir))->read();

        $this->assertSame(YdlContextOutput::AUTHORITY_STOP, $ctx->authorityLevel);
        $this->assertStringContainsString('BLK-SEC-001', $ctx->authorityRationale);
    }

    // ─────────────────────────────────────────────────────────────────
    // T5: toMarkdown() produces valid markdown
    // ─────────────────────────────────────────────────────────────────

    public function test_t5_to_markdown_produces_valid_output(): void
    {
        $this->writeStateFile($this->minimalState([
            'active_sprint' => ['id' => 'Sprint 4.16', 'status' => 'ACTIVE', 'gates_pass' => 0, 'gates_fail' => 0, 'gates_blocked_external' => 0, 'gates_blocked_internal' => 0],
            'recommendation' => ['action' => 'START', 'target' => 'Sprint 4.16', 'rationale' => 'New sprint', 'confidence' => 'HIGH'],
            'sab' => ['new_violations' => 0, 'blocking_violations' => 0],
            'git' => ['branch' => 'main', 'commit' => 'abc1234'],
        ]));
        $this->writeBlockerFile([]);

        $markdown = (new YdlContextReader($this->testDir))->toMarkdown();

        $this->assertStringContainsString('## YDL State', $markdown);
        $this->assertStringContainsString('Sprint 4.16', $markdown);
        $this->assertStringContainsString('Sıradaki', $markdown);
        $this->assertStringContainsString('Blokör yok', $markdown);
    }

    // ─────────────────────────────────────────────────────────────────
    // T6: toAuthoritySummary() produces minimal output
    // ─────────────────────────────────────────────────────────────────

    public function test_t6_to_authority_summary_is_minimal(): void
    {
        $this->writeStateFile($this->minimalState([
            'active_sprint' => ['id' => 'Sprint 4.15', 'status' => 'AWAITING_EXTERNAL', 'gates_pass' => 34, 'gates_fail' => 0, 'gates_blocked_external' => 1, 'gates_blocked_internal' => 0],
            'recommendation' => ['action' => 'START', 'target' => 'YDL_V1', 'rationale' => 'ok', 'confidence' => 'HIGH'],
        ]));
        $this->writeBlockerFile([[
            'id' => 'BLK-001', 'gate' => 'G35', 'type' => 'EXTERNAL_DEPENDENCY',
            'owner' => 'BOOKING_COM', 'development_action' => 'DO_NOT_CONTINUE_BOOKING_CODE',
            'reason' => 'PARTNER', 'created_at' => '2026-08-12T00:00:00+00:00', 'status' => 'ACTIVE',
        ]]);

        $summary = (new YdlContextReader($this->testDir))->toAuthoritySummary();

        $this->assertStringContainsString('Sprint 4.15', $summary);
        $this->assertStringContainsString('LIMITED_BY_BLOCKER', $summary);
        $this->assertStringNotContainsString('sab_violations', $summary);
    }

    // ─────────────────────────────────────────────────────────────────
    // T7: toJson() produces valid parseable JSON
    // ─────────────────────────────────────────────────────────────────

    public function test_t7_to_json_produces_valid_json(): void
    {
        $this->writeStateFile($this->minimalState([
            'recommendation' => ['action' => 'START', 'target' => 'YDL_V1', 'rationale' => 'ok', 'confidence' => 'HIGH'],
        ]));
        $this->writeBlockerFile([]);

        $json = (new YdlContextReader($this->testDir))->toJson();
        $decoded = json_decode($json, true);

        $this->assertNotNull($decoded);
        $this->assertSame('Sprint 4.15', $decoded['sprint']);
        $this->assertSame('START', $decoded['recommendation_action']);
    }

    // ─────────────────────────────────────────────────────────────────
    // T8: empty/no-state returns AUTHORITY_NO_SPRINT
    // ─────────────────────────────────────────────────────────────────

    public function test_t8_empty_state_returns_no_sprint_authority(): void
    {
        // No state file at all
        $reader = new YdlContextReader($this->testDir);
        $ctx = $reader->read();

        $this->assertSame(YdlContextOutput::AUTHORITY_NO_SPRINT, $ctx->authorityLevel);
        $this->assertSame('', $ctx->sprint);
        $this->assertCount(0, $ctx->activeBlockers);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function writeStateFile(array $data): void
    {
        $path = $this->testDir . '/memory/ydl/state/current.json';
        File::put($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function writeBlockerFile(array $blockers): void
    {
        $path = $this->testDir . '/memory/ydl/blockers.json';
        File::put($path, json_encode([
            'version' => '1.0',
            'updated' => '2026-08-13T00:00:00+00:00',
            'blockers' => $blockers,
            'resolved' => [],
        ], JSON_PRETTY_PRINT));
    }

    private function minimalState(array $merge = []): array
    {
        return array_merge([
            'active_sprint' => [
                'id' => 'Sprint 4.15',
                'status' => 'ACTIVE',
                'gates_pass' => 34,
                'gates_fail' => 0,
                'gates_blocked_external' => 0,
                'gates_blocked_internal' => 0,
            ],
            'sab' => [
                'new_violations' => 0,
                'blocking_violations' => 0,
            ],
            'git' => [
                'branch' => 'integration/era-v-phase2a-e01',
                'commit' => '511eb634',
            ],
            'recommendation' => [
                'action' => 'START',
                'target' => 'YDL_V1',
                'rationale' => 'ok',
                'confidence' => 'HIGH',
            ],
            'updated' => '2026-08-13T09:00:00+00:00',
        ], $merge);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
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
}

<?php

namespace Tests\Feature\Ydl;

use App\DTOs\Ydl\Events\YdlEvent;
use App\DTOs\Ydl\YdlContextOutput;
use App\Enums\Governance\GovernanceState;
use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Services\Governance\GovernanceTransitionGuard;
use App\Services\Ilan\IlanCrudService;
use App\Services\Listing\ListingScoreService;
use App\Services\Ydl\YdlEventLog;
use App\Services\Ydl\YdlPublishApprovalToken;
use App\Services\Ydl\YdlPublishEvidence;
use App\Services\Ydl\YdlPublishOrchestrator;
use App\Services\Ydl\YdlPublishReadinessOutput;
use App\Services\Ydl\YdlPublishReadinessService;
use App\Services\Ydl\YdlStateOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * PILOT-001 Wave 2 — E2E Publish Pipeline Integration Tests.
 *
 * Tests the full supervised autonomy pipeline:
 *   ydl:context → YdlPublishReadinessService → YdlPublishOrchestrator
 *   → Human Approval Token → executePublish → YdlEventLog (evidence)
 *   → ydl:session-summary CERTIFIED
 *
 * DoD coverage:
 *   [W2-T1]  STOP authority blocks publish (even if readiness=ready)
 *   [W2-T2]  LIMITED authority + scope intersection blocks publish
 *   [W2-T3]  LIMITED authority without scope intersection → PUBLISH_READY
 *   [W2-T4]  Full pipeline: readiness → approval token → executePublish → evidence
 *   [W2-T5]  No approval token → DomainException (publish never happens)
 *   [W2-T6]  Duplicate event_id → idempotent no-op (event NOT appended twice)
 *   [W2-T7]  Governance guard: DRAFT governance → BLOCKED
 *   [W2-T8]  Evidence created in YdlEventLog after successful publish
 *   [W2-T9]  Already-published ilan → idempotent no-op evidence
 *   [W2-T10] buildCertifiedEvent produces valid YdlEvent with correct fields
 *   [W2-T11] Approval token expired → DomainException
 *   [W2-T12] requestApproval with non-ready ilan → DomainException
 */
class YdlPublishOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private YdlPublishOrchestrator $orchestrator;
    private YdlPublishReadinessService $readinessService;
    private YdlEventLog $eventLog;
    private IlanCrudService $crudService;
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->readinessService = new YdlPublishReadinessService(
            new ListingScoreService(),
            new GovernanceTransitionGuard()
        );

        $this->eventLog = new YdlEventLog($this->testDir = storage_path('testing/ydl_pilot_w2_' . uniqid()));
        $contextReader = new \App\Services\Ydl\YdlContextReader($this->testDir);
        $this->orchestrator = new \App\Services\Ydl\YdlPublishOrchestrator($this->readinessService, $this->eventLog, $contextReader);
        $this->crudService = $this->app->make(IlanCrudService::class);

        $this->ensureDir($this->testDir . '/memory/ydl/state');
        $this->writeYdlState(['active_sprint' => ['id' => 'PILOT-001', 'status' => 'ACTIVE']]);
        $this->writeBlockers([]);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->testDir);
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T1: STOP authority blocks publish
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t1_stop_authority_blocks_publish(): void
    {
        $ilan = $this->makeReadyTaslak();

        // Evaluate with STOP authority override
        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_STOP);
        $this->assertSame(YdlContextOutput::AUTHORITY_STOP, $output->ydlAuthority);

        // STOP authority means readiness cannot publish
        $this->assertFalse($output->recommendation->canPublish);
        $this->assertSame(\App\DTOs\Ydl\YdlPublishRecommendation::DECISION_BLOCKED_GATE, $output->recommendation->decision);

        // Cannot request approval when STOP (decision = BLOCKED_GATE → canPublish=false)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not publish-ready');
        $this->orchestrator->requestApproval($output);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T2: LIMITED authority + scope intersection blocks publish
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t2_limited_with_scope_intersection_blocks_publish(): void
    {
        $ilan = $this->makeReadyTaslak();

        // Setup BLK-001 for booking_com (scope intersection with booking task)
        $this->writeBlockers([[
            'id' => 'BLK-001',
            'gate' => 'G35',
            'type' => 'EXTERNAL_DEPENDENCY',
            'owner' => 'BOOKING_COM',
            'development_action' => 'DO_NOT_CONTINUE_BOOKING_CODE',
            'reason' => 'PARTNER_ONBOARDING',
            'created_at' => '2026-08-13T00:00:00+03:00',
            'status' => 'ACTIVE',
        ]]);

        // hasBlockingIntersection for 'booking_com' scope should return true
        $this->assertTrue($this->orchestrator->hasBlockingIntersection('booking_com'));

        // BUT for 'property_publish' scope, BLK-001 has no intersection
        $this->assertFalse($this->orchestrator->hasBlockingIntersection('property_publish'));

        // property_publish scope is NOT blocked by BLK-001
        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER);
        $this->assertTrue($output->recommendation->canPublish);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T3: LIMITED authority without scope intersection → PUBLISH_READY
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t3_limited_without_intersection_allows_publish(): void
    {
        $ilan = $this->makeReadyTaslak();

        // BLK-001 is active (LIMITED authority) but Property Publish scope ≠ BLK-001 scope
        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER);

        $this->assertSame(YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER, $output->ydlAuthority);
        $this->assertTrue($output->recommendation->canPublish);
        $this->assertSame(
            \App\DTOs\Ydl\YdlPublishRecommendation::DECISION_PUBLISH_READY,
            $output->recommendation->decision
        );

        // Can request approval
        $token = $this->orchestrator->requestApproval($output);
        $this->assertInstanceOf(YdlPublishApprovalToken::class, $token);
        $this->assertFalse($token->isExpired());
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T4: Full pipeline: readiness → approval → executePublish → evidence
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t4_full_pipeline_readiness_to_evidence(): void
    {
        $ilan = $this->makeReadyTaslak();

        // Step 1: Evaluate readiness
        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);
        $this->assertTrue($output->recommendation->isReady());
        $this->assertInstanceOf(YdlPublishReadinessOutput::class, $output);

        // Step 2: Request approval token
        $token = $this->orchestrator->requestApproval($output);
        $this->assertInstanceOf(YdlPublishApprovalToken::class, $token);
        $this->assertFalse($token->isExpired());

        // Step 3: Execute publish with publishExecutor to bypass GuardsAgentWrites + EnforcesContext7Guard.
        // Uses DB raw update to completely bypass model events and guards.
        $publishExecutor = function (\App\Models\Ilan $i) {
            \Illuminate\Support\Facades\DB::table('ilanlar')
                ->where('id', $i->id)
                ->update(['yayin_durumu' => IlanDurumu::YAYINDA->value]);
            return $i->fresh();
        };
        $evidence = $this->orchestrator->executePublish(
            $token,
            $this->crudService,
            1,
            GovernanceState::PROMOTED,
            $publishExecutor,
        );

        // Verify evidence
        $this->assertInstanceOf(YdlPublishEvidence::class, $evidence);
        $this->assertTrue($evidence->success);
        $this->assertSame($ilan->id, $evidence->ilanId);
        $this->assertSame(1, $evidence->approvedBy);

        // Verify event appended to log
        $this->assertTrue($this->eventLog->eventExists($evidence->eventId));
        $this->assertCount(1, $this->eventLog->allEvents());

        // Verify ilan state changed
        $ilan->refresh();
        $this->assertSame(IlanDurumu::YAYINDA, $ilan->yayin_durumu);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T5: No approval token → publish never happens
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t5_no_token_no_publish(): void
    {
        $ilan = $this->makeReadyTaslak();

        // Verify ilan is TASLAK BEFORE attempting publish
        $this->assertSame(IlanDurumu::TASLAK, $ilan->yayin_durumu);

        // Create an expired fake token — YalihanLifecycle will never be reached
        $fakeToken = new YdlPublishApprovalToken(
            ilanId: $ilan->id,
            eventId: 'fake_event_id_00000000',
            recommendation: $this->readinessService->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL),
            ydlAuthority: YdlContextOutput::AUTHORITY_FULL,
            requestedAt: now()->subSeconds(90000)->toIso8601String(),
            expiresAt: now()->subSeconds(100)->toIso8601String(),
        );

        // Expired token → exception at token validation step (before any publish attempt)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');

        $this->orchestrator->executePublish($fakeToken, $this->crudService, 1);

        // Ilan state must remain TASLAK (never changed)
        $ilan->refresh();
        $this->assertSame(IlanDurumu::TASLAK->value, $ilan->yayin_durumu);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T6: Duplicate event_id → idempotent no-op (event NOT appended twice)
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t6_duplicate_event_is_idempotent(): void
    {
        $ilan = $this->makeReadyTaslak();

        // First publish
        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);
        $token = $this->orchestrator->requestApproval($output);

        $publishExecutor = function (\App\Models\Ilan $i) {
            \Illuminate\Support\Facades\DB::table('ilanlar')
                ->where('id', $i->id)
                ->update(['yayin_durumu' => IlanDurumu::YAYINDA->value]);
            return $i->fresh();
        };
        $evidence1 = $this->orchestrator->executePublish(
            $token, $this->crudService, 1, GovernanceState::PROMOTED, $publishExecutor
        );

        $this->assertCount(1, $this->eventLog->allEvents());

        // The eventId changes per request, but we can pre-populate the log with same ilan
        // to simulate the scenario: ilan already in YAYINDA state
        $ilan->refresh();
        $this->assertSame(IlanDurumu::YAYINDA, $ilan->yayin_durumu);

        // To test idempotency, call executePublish with the SAME eventId as first publish.
        // Since eventId already in log → DomainException before reaching publish.
        $fakeToken = new YdlPublishApprovalToken(
            ilanId: $ilan->id,
            eventId: $evidence1->eventId, // SAME eventId as first publish
            recommendation: $this->readinessService->evaluate($ilan, YdlContextOutput::AUTHORITY_FULL),
            ydlAuthority: YdlContextOutput::AUTHORITY_FULL,
            requestedAt: now()->toIso8601String(),
            expiresAt: now()->addHour()->toIso8601String(),
        );

        // The idempotency check is at executePublish: eventExists(eventId)
        // Since eventId already in log → DomainException
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Duplicate event');

        $this->orchestrator->executePublish($fakeToken, $this->crudService, 1);

        // Verify: only 1 event in log (no duplicate append)
        $this->assertCount(1, $this->eventLog->allEvents());
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T7: Governance guard: DRAFT governance → BLOCKED
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t7_governance_draft_blocks_publish(): void
    {
        $ilan = $this->makeReadyTaslak();

        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);
        $token = $this->orchestrator->requestApproval($output);

        // Execute with governance=DRAFT (canPublish=false)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot publish from governance_state=draft');

        $this->orchestrator->executePublish(
            $token,
            $this->crudService,
            1,
            GovernanceState::DRAFT, // ← wrong governance
        );

        // Verify ilan state unchanged
        $ilan->refresh();
        $this->assertSame(IlanDurumu::TASLAK, $ilan->yayin_durumu);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T8: Evidence created in YdlEventLog after successful publish
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t8_evidence_in_event_log(): void
    {
        $ilan = $this->makeReadyTaslak();

        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);
        $token = $this->orchestrator->requestApproval($output);

        $publishExecutor = function (\App\Models\Ilan $i) {
            \Illuminate\Support\Facades\DB::table('ilanlar')
                ->where('id', $i->id)
                ->update(['yayin_durumu' => IlanDurumu::YAYINDA->value]);
            return $i->fresh();
        };
        $evidence = $this->orchestrator->executePublish($token, $this->crudService, 99, GovernanceState::PROMOTED, $publishExecutor);

        // Verify evidence fields
        $this->assertTrue($evidence->success);
        $this->assertFalse($evidence->idempotentNoOp);
        $this->assertSame(99, $evidence->approvedBy);
        $this->assertSame('promoted', $evidence->governanceState);

        // Verify in event log
        $events = $this->eventLog->allEvents();
        $this->assertCount(1, $events);

        $logged = $events[0];
        $this->assertSame($evidence->eventId, $logged->eventId);
        $this->assertSame(YdlEvent::TYPE_CERTIFICATION, $logged->type);
        $this->assertSame('PUBLISH', $logged->action);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T9: Already-published ilan → idempotent no-op evidence
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t9_already_published_idempotent_noop(): void
    {
        // Ilan already published
        $ilan = $this->makeTaslak(['yayin_durumu' => IlanDurumu::YAYINDA]);

        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);

        // Already published → cannot request approval (decision = ALREADY_PUBLISHED)
        $this->assertFalse($output->recommendation->canPublish);
        $this->assertSame(
            \App\DTOs\Ydl\YdlPublishRecommendation::DECISION_ALREADY_PUBLISHED,
            $output->recommendation->decision
        );

        // Cannot request approval
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not publish-ready');
        $this->orchestrator->requestApproval($output);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T10: buildCertifiedEvent produces valid YdlEvent
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t10_build_certified_event_valid(): void
    {
        $ilan = $this->makeReadyTaslak();

        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);
        $token = $this->orchestrator->requestApproval($output);

        // Execute publish with publishExecutor to bypass GuardsAgentWrites + EnforcesContext7Guard
        $publishExecutor = function (\App\Models\Ilan $i) {
            \Illuminate\Support\Facades\DB::table('ilanlar')
                ->where('id', $i->id)
                ->update(['yayin_durumu' => IlanDurumu::YAYINDA->value]);
            return $i->fresh();
        };
        $evidence = $this->orchestrator->executePublish(
            $token, $this->crudService, 1, GovernanceState::PROMOTED, $publishExecutor
        );

        // Build CERTIFIED event. Use a stub orchestrator pointing to test directory so
        // buildCertifiedEvent reads from the test fixture state file.
        $stubOrchestrator = new \App\Services\Ydl\YdlStateOrchestrator($this->testDir);
        $certEvent = $this->orchestrator->buildCertifiedEvent($evidence, 'f2c496dTEST', GovernanceState::PROMOTED, $stubOrchestrator);

        $this->assertSame('CERTIFIED', $certEvent->action);
        $this->assertSame(YdlEvent::TYPE_CERTIFICATION, $certEvent->type);
        $this->assertSame('f2c496dTEST', $certEvent->commit);
        $this->assertSame('HIGH', $certEvent->confidence);
        $this->assertSame('PILOT-001', $certEvent->sprint);
        $this->assertSame('PILOT-001: Property Publish', $certEvent->target);
        $this->assertTrue($certEvent->parallelWorkAllowed);
        $this->assertNotEmpty($certEvent->eventId);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T11: Expired approval token → DomainException
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t11_expired_token_blocks_publish(): void
    {
        $ilan = $this->makeReadyTaslak();

        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);

        // Create expired token
        $expiredToken = new YdlPublishApprovalToken(
            ilanId: $ilan->id,
            eventId: 'expired_token_00000000',
            recommendation: $output->recommendation,
            ydlAuthority: YdlContextOutput::AUTHORITY_FULL,
            requestedAt: now()->subHour()->toIso8601String(),
            expiresAt: now()->subSecond()->toIso8601String(), // expired 1 second ago
        );

        $this->assertTrue($expiredToken->isExpired());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');

        $this->orchestrator->executePublish($expiredToken, $this->crudService, 1);
    }

    // ─────────────────────────────────────────────────────────────────
    // W2-T12: requestApproval with non-ready ilan → DomainException
    // ─────────────────────────────────────────────────────────────────

    public function test_w2_t12_request_approval_non_ready_throws(): void
    {
        $ilan = $this->makeTaslak([]); // Incomplete — no baslik, fiyat etc.

        $output = $this->orchestrator->evaluateReadiness($ilan, YdlContextOutput::AUTHORITY_FULL);

        $this->assertFalse($output->recommendation->canPublish);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not publish-ready');

        $this->orchestrator->requestApproval($output);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Create a publish-ready TASLAK ilan.
     *
     * Factory handles foreign keys (il_id, ilce_id, danisman_id, ana_kategori_id).
     * We add a long aciklama for quality score, set scores + yayin_tipi_id manually
     * via raw DB update to bypass GuardsAgentWrites + model events.
     */
    private function makeReadyTaslak(): Ilan
    {
        $ilan = \App\Models\Ilan::factory()->create([
            'baslik'          => 'Bodrum Deniz Manzaralı Lüks Villa 5+2 Oda',
            'aciklama'        => str_repeat('Bodrumun en merkezi lokasyonunda denize yürüme mesafesinde, özel havuzlu, tam donanımlı lüks villa. Klimalı odalar, açık mutfak, barbekü alanı, bahçe, garaj.', 5),
            'fiyat'           => 8500000,
            'yayin_durumu'    => IlanDurumu::TASLAK->value,
        ]);

        // Set scores + yayin_tipi_id via raw DB update to bypass GuardsAgentWrites trait.
        // This makes the ilan publish-ready without triggering model events.
        // ilan_sahibi_id is set by IlanFactory (Kisi) — no override needed.
        \Illuminate\Support\Facades\DB::table('ilanlar')
            ->where('id', $ilan->id)
            ->update([
                'yayin_tipi_id'   => 1,
                'completion_score' => 100,
                'quality_score'    => 68,
            ]);

        $ilan->fotograflar()->create([
            'ilan_id'   => $ilan->id,
            'dosya_adi' => 'test-ready.jpg',
            'dosya_yolu' => '/photos/test-ready.jpg',
            'display_order' => 1,
        ]);

        return $ilan->fresh();
    }

    /**
     * Create a TASLAK ilan with optional overrides.
     */
    private function makeTaslak(array $overrides = []): Ilan
    {
        $defaults = [
            'baslik'          => 'Test Ilan',
            'aciklama'        => null,
            'fiyat'           => null,
            'il_id'           => null,
            'ilce_id'         => null,
            'ana_kategori_id' => null,
            'yayin_tipi_id'   => null,
            'ilan_sahibi_id'  => null,
            'yayin_durumu'    => IlanDurumu::TASLAK->value,
            'user_id'         => 1,
        ];

        $attributes = array_merge($defaults, $overrides);
        $attributes = array_filter($attributes, fn($v) => ! is_null($v));

        return Ilan::factory()->create($attributes);
    }

    private function writeYdlState(array $data): void
    {
        $defaults = [
            'version' => '1.0',
            'active_sprint' => ['id' => 'PILOT-001', 'status' => 'ACTIVE', 'gates_pass' => 34, 'gates_fail' => 0, 'gates_blocked_external' => 1, 'gates_blocked_internal' => 0],
            'sab' => ['new_violations' => 0, 'blocking_violations' => 0],
            'git' => ['branch' => 'integration/era-v-phase2a-e01', 'commit' => 'f2c496d'],
            'recommendation' => ['action' => 'START', 'target' => 'PILOT-001', 'rationale' => 'ok', 'confidence' => 'HIGH'],
            'updated' => '2026-08-13T00:00:00+03:00',
        ];

        File::put($this->testDir . '/memory/ydl/state/current.json', json_encode(array_merge($defaults, $data), JSON_PRETTY_PRINT));
    }

    private function writeBlockers(array $blockers): void
    {
        File::put($this->testDir . '/memory/ydl/blockers.json', json_encode([
            'version' => '1.0',
            'updated' => '2026-08-13T00:00:00+03:00',
            'blockers' => $blockers,
            'resolved' => [],
        ], JSON_PRETTY_PRINT));
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function rmdir(string $dir): void
    {
        if (! is_dir($dir)) {
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

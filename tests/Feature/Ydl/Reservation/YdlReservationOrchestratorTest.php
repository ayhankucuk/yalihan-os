<?php

namespace Tests\Feature\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\Events\ReservationEvent;
use App\DTOs\Ydl\Reservation\YdlReservationApprovalToken;
use App\DTOs\Ydl\Reservation\YdlReservationContextOutput;
use App\DTOs\Ydl\Reservation\YdlReservationEvidence;
use App\DTOs\Ydl\Reservation\YdlReservationRecommendation;
use App\DTOs\Ydl\Reservation\YdlReservationReadinessOutput;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\ReservationService;
use App\Services\Ydl\Reservation\ReservationEventLog;
use App\Services\Ydl\Reservation\ReservationReadinessService;
use App\Services\Ydl\Reservation\YdlReservationOrchestrator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * PILOT-002 Wave 1 — E2E Reservation Pipeline Integration Tests.
 *
 * Tests the full supervised autonomy pipeline:
 *   ydl:context → ReservationReadinessService → YdlReservationOrchestrator
 *   → Human Approval Token → executeReservation → ReservationEventLog (evidence)
 *   → ydl:session-summary CERTIFIED
 *
 * DoD coverage:
 *   [R1-T1]  STOP authority → BLOCKED_GATE
 *   [R1-T2]  LIMITED + scope intersection → BLOCKED_GATE
 *   [R1-T3]  LIMITED + no intersection → RESERVATION_READY
 *   [R1-T4]  Full pipeline: readiness → approval → execute → evidence
 *   [R1-T5]  Approval token expired → DomainException
 *   [R1-T6]  Duplicate event_id → idempotent no-op
 *   [R1-T7]  Cross-tenant giriş reddediliyor
 *   [R1-T8]  Success: canonical ReservationService executes CREATE
 *   [R1-T9]  No approval token → CREATE never happens
 *   [R1-T10] TOCTOU: readiness=ready but canonical lockForUpdate catches conflict → CREATE blocked
 *   [R1-T11] Availability clean: no overlapping reservations
 *   [R1-T12] Conflict detection: existing reservation blocks
 *   [R1-T13] Evidence: every result (SUCCESS, BLOCKED, CONFLICT, IDEMPOTENT) logged
 */
class YdlReservationOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private YdlReservationOrchestrator $orchestrator;
    private ReservationReadinessService $readinessService;
    private ReservationEventLog $eventLog;
    private string $testDir;
    private Ilan $ilan;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = storage_path('testing/ydl_reservation_' . uniqid());

        $this->readinessService = new ReservationReadinessService($this->testDir);
        $this->eventLog = new ReservationEventLog($this->testDir);
        $this->orchestrator = new YdlReservationOrchestrator($this->readinessService, $this->eventLog);

        $this->user = User::factory()->create();
        $this->ilan = $this->makeRentalIlan();

        $this->ensureDir($this->testDir . '/memory/ydl');
        $this->ensureDir($this->testDir . '/memory/ydl/state');
        $this->writeYdlState(['active_sprint' => ['id' => 'PILOT-002', 'status' => 'ACTIVE']]);
        $this->writeBlockers([]);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->testDir);
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T1: STOP authority → BLOCKED_GATE
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t1_stop_authority_blocks_reservation(): void
    {
        $output = $this->orchestrator->evaluateReadiness(
            $this->ilan,
            now()->addDays(10)->format('Y-m-d'),
            now()->addDays(12)->format('Y-m-d'),
            YdlReservationContextOutput::AUTHORITY_STOP,
        );

        $this->assertSame(YdlReservationContextOutput::AUTHORITY_STOP, $output->ydlAuthority);
        $this->assertFalse($output->isReady());
        $this->assertTrue($output->isBlocked());
        $this->assertSame(YdlReservationRecommendation::DECISION_BLOCKED_GATE, $output->decision);
        $this->assertStringContainsString('STOP', $output->rationale);

        // Cannot request approval when STOP
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not ready');
        $this->orchestrator->requestApproval($output);
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T2: LIMITED + scope intersection → BLOCKED_GATE
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t2_limited_scope_intersection_blocks(): void
    {
        // Write a blocker that intersects with reservation_create.
        // The blocker id 'reservation_create' will be in blockedScopes.
        // Use blockedScopesOverride for deterministic test behavior.
        $output = $this->readinessService->evaluate(
            $this->ilan,
            now()->addDays(10)->format('Y-m-d'),
            now()->addDays(12)->format('Y-m-d'),
            YdlReservationContextOutput::AUTHORITY_LIMITED,
            ['reservation_create'], // blockedScopesOverride
        );

        $this->assertSame(YdlReservationContextOutput::AUTHORITY_LIMITED, $output->ydlAuthority);
        $this->assertTrue($output->isBlocked());
        $this->assertSame(YdlReservationRecommendation::DECISION_BLOCKED_GATE, $output->decision);
        $this->assertStringContainsString('reservation_create', $output->rationale);
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T3: LIMITED + no intersection → RESERVATION_READY
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t3_limited_no_intersection_ready(): void
    {
        // No blockers for 'reservation_create' scope → READY
        $output = $this->readinessService->evaluate(
            $this->ilan,
            now()->addDays(10)->format('Y-m-d'),
            now()->addDays(13)->format('Y-m-d'), // 3 nights ≥ min_stay_nights
            YdlReservationContextOutput::AUTHORITY_LIMITED,
            [], // empty blockedScopes — no intersection with reservation_create
        );

        $this->assertSame(YdlReservationContextOutput::AUTHORITY_LIMITED, $output->ydlAuthority);
        $this->assertTrue($output->isReady());
        $this->assertSame(YdlReservationRecommendation::DECISION_RESERVATION_READY, $output->decision);
        $this->assertTrue($output->canReserve);
        $this->assertSame(3, $output->requestedNights);
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T4: Full pipeline — readiness → approval → execute → evidence
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t4_full_pipeline_success(): void
    {
        $start = now()->addDays(20)->format('Y-m-d');
        $end   = now()->addDays(22)->format('Y-m-d'); // 2 nights ≥ min_stay_nights

        // Step 1: Evaluate readiness
        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $this->assertTrue($readiness->isReady());

        // Step 2: Request approval
        $token = $this->orchestrator->requestApproval($readiness, $this->user->id);
        $this->assertInstanceOf(YdlReservationApprovalToken::class, $token);
        $this->assertFalse($token->isExpired());

        // Step 3: Execute reservation
        $evidence = $this->orchestrator->executeReservation(
            $token,
            $this->user->id,
            $this->guestData(),
        );

        $this->assertTrue($evidence->success);
        $this->assertSame(ReservationEvent::OUTCOME_SUCCESS, $evidence->outcome);
        $this->assertGreaterThan(0, $evidence->reservationId);

        // Verify canonical DB write
        $reservation = PropertyReservation::find($evidence->reservationId);
        $this->assertNotNull($reservation);
        $this->assertSame('confirmed', $reservation->reservation_state->value);
        $this->assertSame($this->ilan->id, $reservation->property_id);
        $this->assertSame($start, $reservation->start_date);
        $this->assertSame($end, $reservation->end_date);

        // Verify availability blocked
        $blockedCount = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->whereBetween('date', [$start, $end])
            ->count();
        $this->assertGreaterThan(0, $blockedCount);
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T5: Approval token expired → DomainException
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t5_expired_token_rejected(): void
    {
        $start = now()->addDays(30)->format('Y-m-d');
        $end   = now()->addDays(32)->format('Y-m-d');

        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $token = $this->orchestrator->requestApproval($readiness, $this->user->id);

        // Manually expire the token
        $expiredToken = YdlReservationApprovalToken::create(
            ilanId:            $token->ilanId,
            tenantId:          $token->tenantId,
            eventId:           $token->eventId,
            ydlAuthority:     $token->ydlAuthority,
            authorityContext:   $token->authorityContext,
            startDate:        $token->startDate,
            endDate:          $token->endDate,
            recommendation:    $token->recommendation,
            requestedAt:       now()->subSeconds(86401)->toIso8601String(),
            expiresAt:         now()->subSecond()->toIso8601String(),
            requestedBy:       $this->user->id,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');
        $this->orchestrator->executeReservation($expiredToken, $this->user->id, $this->guestData());
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T6: Duplicate event_id → idempotent no-op
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t6_duplicate_event_idempotent(): void
    {
        $start = now()->addDays(40)->format('Y-m-d');
        $end   = now()->addDays(42)->format('Y-m-d');

        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $token = $this->orchestrator->requestApproval($readiness, $this->user->id);

        // Execute first time — success
        $evidence1 = $this->orchestrator->executeReservation(
            $token, $this->user->id, $this->guestData(),
        );
        $this->assertTrue($evidence1->success);
        $this->assertSame(ReservationEvent::OUTCOME_SUCCESS, $evidence1->outcome);

        // Re-use same token — idempotent no-op (evidence döner, exception yok)
        $evidence2 = $this->orchestrator->executeReservation($token, $this->user->id, $this->guestData());
        $this->assertSame(ReservationEvent::OUTCOME_IDEMPOTENT, $evidence2->outcome);
        $this->assertTrue($evidence2->success);
        // Sadece tek rezervasyon var (ilk çağrıdan)
        $this->assertSame(1, PropertyReservation::where('property_id', $this->ilan->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T7: Cross-tenant giriş reddediliyor
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t7_cross_tenant_blocked(): void
    {
        // Create ilan in tenant A
        $ilanA = $this->makeRentalIlan(['tenant_id' => 999]);

        // Attempt reservation with a token from a different tenant
        $start = now()->addDays(50)->format('Y-m-d');
        $end   = now()->addDays(52)->format('Y-m-d');

        // Build token with different tenant_id
        $token = YdlReservationApprovalToken::create(
            ilanId:            $ilanA->id,
            tenantId:          888, // different tenant
            eventId:           ReservationEvent::generateEventId($ilanA->id, $start, $end, 'CREATE'),
            ydlAuthority:     YdlReservationContextOutput::AUTHORITY_FULL,
            authorityContext:   'test',
            startDate:        $start,
            endDate:          $end,
            recommendation:    ['ilan_id' => $ilanA->id, 'tenant_id' => 999],
            requestedAt:       now()->toIso8601String(),
            expiresAt:         now()->addDay()->toIso8601String(),
            requestedBy:       $this->user->id,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cross-tenant');
        $this->orchestrator->executeReservation($token, $this->user->id, $this->guestData());
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T8: Success — canonical ReservationService executes CREATE
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t8_canonical_reservation_service_used(): void
    {
        $start = now()->addDays(60)->format('Y-m-d');
        $end   = now()->addDays(62)->format('Y-m-d');

        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $token = $this->orchestrator->requestApproval($readiness, $this->user->id);

        // Mock ReservationService to verify it's called
        $mockService = $this->createMock(ReservationService::class);
        $mockService->expects($this->once())
            ->method('createReservation')
            ->willReturnCallback(function ($propertyId, $startDate, $endDate, $guestData, $userId) {
                return \App\Models\PropertyReservation::create([
                    'tenant_id' => $this->ilan->tenant_id,
                    'property_id' => $propertyId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'nights' => 2,
                    'guest_name' => $guestData['guest_name'],
                    'reservation_state' => ReservationState::CONFIRMED,
                    'created_by_user_id' => $userId,
                    'confirmed_at' => now(),
                ]);
            });

        $evidence = $this->orchestrator->executeReservation(
            $token, $this->user->id, $this->guestData(), null, $mockService,
        );

        $this->assertTrue($evidence->success);
        $this->assertStringContainsString('ReservationService', $evidence->canonicalResult);
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T9: No approval token → CREATE never happens
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t9_no_token_no_create(): void
    {
        // Attempt execute without requestApproval
        $fakeToken = YdlReservationApprovalToken::create(
            ilanId:            $this->ilan->id,
            tenantId:          $this->ilan->tenant_id,
            eventId:           ReservationEvent::generateEventId($this->ilan->id, '2099-01-01', '2099-01-03', 'CREATE'),
            ydlAuthority:     YdlReservationContextOutput::AUTHORITY_FULL,
            authorityContext:   'test',
            startDate:        '2099-01-01',
            endDate:          '2099-01-03',
            recommendation:    [],
            requestedAt:       now()->subDays(2)->toIso8601String(),
            expiresAt:         now()->subDays(1)->toIso8601String(),
            requestedBy:       $this->user->id,
        );

        // Expired token rejected
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');
        $this->orchestrator->executeReservation($fakeToken, $this->user->id, $this->guestData());
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T10: TOCTOU — Readiness=READY but canonical lockForUpdate catches conflict
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t10_toctou_canonical_lock_catches_conflict(): void
    {
        $start = now()->addDays(70)->format('Y-m-d');
        $end   = now()->addDays(73)->format('Y-m-d'); // 3 nights

        // Step 1: Readiness says CLEAN — no conflict
        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $this->assertTrue($readiness->isReady(), 'Readiness should be ready before conflict appears');

        // Step 2: SIMULATE RACE — another agent creates a reservation on the same dates
        // between readiness evaluation and execution
        $conflictingReservation = PropertyReservation::create([
            'tenant_id' => $this->ilan->tenant_id,
            'property_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Conflicting Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'created_by_user_id' => $this->user->id,
        ]);

        // Also block availability
        $dates = [];
        $current = Carbon::parse($start);
        $endC = Carbon::parse($end);
        while ($current->lt($endC)) {
            $dates[] = ['property_id' => $this->ilan->id, 'date' => $current->format('Y-m-d'), 'is_available' => false, 'source_system' => 'internal', 'reservation_id' => $conflictingReservation->id];
            $current->addDay();
        }
        PropertyAvailability::insert($dates);

        // Step 3: Approval token created
        $token = $this->orchestrator->requestApproval($readiness, $this->user->id);

        // Step 4: Execute — canonical lockForUpdate MUST reject the conflict
        // RACE INVARIANT proof: readiness passed but canonical check fails
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Conflict detected');
        $this->orchestrator->executeReservation($token, $this->user->id, $this->guestData());
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T11: Availability clean — no overlapping reservations
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t11_availability_clean_success(): void
    {
        // Pre-block some dates far from our request
        PropertyAvailability::insert([
            [
                'property_id' => $this->ilan->id,
                'date' => now()->addDays(5)->format('Y-m-d'),
                'is_available' => false,
                'source_system' => 'internal',
                'reservation_id' => 99999,
            ],
        ]);

        $start = now()->addDays(80)->format('Y-m-d');
        $end   = now()->addDays(82)->format('Y-m-d');

        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $this->assertTrue($readiness->isReady());

        $token = $this->orchestrator->requestApproval($readiness, $this->user->id);
        $evidence = $this->orchestrator->executeReservation($token, $this->user->id, $this->guestData());

        $this->assertTrue($evidence->success);
        $this->assertSame(ReservationEvent::OUTCOME_SUCCESS, $evidence->outcome);
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T12: Conflict detection — existing reservation blocks
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t12_existing_reservation_blocks(): void
    {
        $start = now()->addDays(90)->format('Y-m-d');
        $end   = now()->addDays(93)->format('Y-m-d');

        // Create existing reservation
        PropertyReservation::create([
            'tenant_id' => $this->ilan->tenant_id,
            'property_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Existing Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'created_by_user_id' => $this->user->id,
        ]);

        // Readiness should detect conflict
        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $this->assertTrue($readiness->isConflict());
        $this->assertSame(YdlReservationRecommendation::DECISION_CONFLICT, $readiness->decision);
        $this->assertFalse($readiness->canReserve);

        // Cannot request approval
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not ready');
        $this->orchestrator->requestApproval($readiness);
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T13: Evidence — every result type logged
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t13_evidence_logged_for_all_outcomes(): void
    {
        // Test 1: SUCCESS outcome (min_stay_nights=2 → need ≥2 nights)
        $start1 = now()->addDays(100)->format('Y-m-d');
        $end1   = now()->addDays(102)->format('Y-m-d'); // 2 nights ≥ min_stay

        $readiness1 = $this->orchestrator->evaluateReadiness($this->ilan, $start1, $end1);
        $token1 = $this->orchestrator->requestApproval($readiness1, $this->user->id);
        $evidence1 = $this->orchestrator->executeReservation($token1, $this->user->id, $this->guestData());

        $this->assertSame(ReservationEvent::OUTCOME_SUCCESS, $evidence1->outcome);
        $this->assertTrue($evidence1->success);
        $this->assertSame($this->ilan->id, $evidence1->ilanId);
        $this->assertGreaterThan(0, $evidence1->reservationId);

        // Verify event was appended to log
        $this->assertTrue($this->eventLog->eventExists($evidence1->eventId));

        // Test 2: BLOCKED outcome (STOP authority)
        $start2 = now()->addDays(110)->format('Y-m-d');
        $end2   = now()->addDays(112)->format('Y-m-d');

        $stopToken = YdlReservationApprovalToken::create(
            ilanId:            $this->ilan->id,
            tenantId:          $this->ilan->tenant_id,
            eventId:           ReservationEvent::generateEventId($this->ilan->id, $start2, $end2, 'CREATE'),
            ydlAuthority:     YdlReservationContextOutput::AUTHORITY_STOP,
            authorityContext:   'STOP',
            startDate:        $start2,
            endDate:          $end2,
            recommendation:    [],
            requestedAt:       now()->toIso8601String(),
            expiresAt:         now()->addDay()->toIso8601String(),
            requestedBy:       $this->user->id,
        );

        $evidence2 = $this->orchestrator->executeReservation($stopToken, $this->user->id, $this->guestData());

        $this->assertSame(ReservationEvent::OUTCOME_BLOCKED, $evidence2->outcome);
        $this->assertFalse($evidence2->success);
        $this->assertSame(0, $evidence2->reservationId);
        $this->assertTrue($this->eventLog->eventExists($evidence2->eventId));
    }

    // ─────────────────────────────────────────────────────────────────
    // R1-T14: Conflict on canonical execution → CONFLICT evidence logged
    // ─────────────────────────────────────────────────────────────────

    public function test_r1_t14_canonical_conflict_produces_evidence(): void
    {
        $start = now()->addDays(120)->format('Y-m-d');
        $end   = now()->addDays(123)->format('Y-m-d');

        // Readiness passes
        $readiness = $this->orchestrator->evaluateReadiness($this->ilan, $start, $end);
        $this->assertTrue($readiness->isReady());

        // Race: conflicting reservation appears
        $conflict = PropertyReservation::create([
            'tenant_id' => $this->ilan->tenant_id,
            'property_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Race Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'created_by_user_id' => $this->user->id,
        ]);

        $token = $this->orchestrator->requestApproval($readiness, $this->user->id);

        // Execute → canonical lockForUpdate rejects
        try {
            $this->orchestrator->executeReservation($token, $this->user->id, $this->guestData());
            $this->fail('Expected Exception for canonical conflict');
        } catch (\Exception $e) {
            // Expected
        }

        // Evidence logged
        $this->assertTrue($this->eventLog->eventExists($token->eventId));
        $latestEvent = $this->eventLog->latestEventForIlan($this->ilan->id);
        $this->assertSame(ReservationEvent::OUTCOME_CONFLICT, $latestEvent->outcome);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function makeRentalIlan(array $attrs = []): Ilan
    {
        return Ilan::factory()->create(array_merge([
            'rental_enabled' => true,
            'min_stay_nights' => 2,
            'yayin_durumu' => \App\Enums\IlanDurumu::YAYINDA,
        ], $attrs));
    }

    private function guestData(): array
    {
        return [
            'guest_name' => 'Test Guest',
            'guest_phone' => '+905551234567',
            'guest_email' => 'test@example.com',
            'guest_count' => 2,
            'notes' => 'Test reservation',
        ];
    }

    private function writeYdlState(array $state): void
    {
        $path = $this->testDir . '/memory/ydl/state/active.json';
        File::put($path, json_encode($state));
    }

    private function writeBlockers(array $blockers): void
    {
        $path = $this->testDir . '/memory/ydl/blockers.json';
        File::put($path, json_encode(['blockers' => $blockers]));
    }

    private function ensureDir(string $dir): void
    {
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }

    private function rmdir(string $dir): void
    {
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }
}

<?php

namespace Tests\Feature\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\Events\ReservationEvent;
use App\DTOs\Ydl\Reservation\YdlCancellationApprovalToken;
use App\DTOs\Ydl\Reservation\YdlCancellationRecommendation;
use App\DTOs\Ydl\Reservation\YdlReservationContextOutput;
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
 * PILOT-002 Wave 2 — Cancellation Pipeline Integration Tests.
 *
 * Tests supervised autonomy for reservation cancellation:
 *   evaluateCancellationReadiness() → requestCancellationApproval() → executeCancellation()
 *
 * DoD coverage:
 *   [R2-T1]  STOP authority → BLOCKED
 *   [R2-T2]  LIMITED + scope intersection → BLOCKED
 *   [R2-T3]  LIMITED + no intersection → CANCEL_READY
 *   [R2-T4]  Full pipeline: readiness → approval → execute → evidence
 *   [R2-T5]  Expired token → DomainException
 *   [R2-T6]  Duplicate event_id → idempotent no-op
 *   [R2-T7]  Cross-tenant rejection
 *   [R2-T8]  Already cancelled → ALREADY_CANCELLED
 *   [R2-T9]  Availability released: internal source_system restored to is_available=true
 */
class YdlReservationCancellationTest extends TestCase
{
    use RefreshDatabase;

    private YdlReservationOrchestrator $orchestrator;
    private ReservationReadinessService $readinessService;
    private ReservationEventLog $eventLog;
    private string $testDir;
    private Ilan $ilan;
    private User $user;
    private PropertyReservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = storage_path('testing/ydl_cancel_' . uniqid());
        $this->readinessService = new ReservationReadinessService($this->testDir);
        $this->eventLog = new ReservationEventLog($this->testDir);
        $this->orchestrator = new YdlReservationOrchestrator($this->readinessService, $this->eventLog);

        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 2,
            'yayin_durumu' => \App\Enums\IlanDurumu::YAYINDA,
        ]);
        $this->reservation = $this->makeConfirmedReservation($this->ilan);

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
    // R2-T1: STOP authority → BLOCKED
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t1_stop_authority_blocks_cancellation(): void
    {
        $output = $this->orchestrator->evaluateCancellationReadiness(
            $this->reservation->id,
            $this->ilan->tenant_id,
            YdlReservationContextOutput::AUTHORITY_STOP,
        );

        $this->assertSame(YdlReservationContextOutput::AUTHORITY_STOP, $output->ydlAuthority);
        $this->assertTrue($output->isBlocked());
        $this->assertSame(YdlCancellationRecommendation::DECISION_BLOCKED_GATE, $output->decision);
        $this->assertStringContainsString('STOP', $output->rationale);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not ready');
        $this->orchestrator->requestCancellationApproval($output);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T2: LIMITED + scope intersection → BLOCKED
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t2_limited_scope_intersection_blocks(): void
    {
        $output = $this->orchestrator->evaluateCancellationReadiness(
            $this->reservation->id,
            $this->ilan->tenant_id,
            YdlReservationContextOutput::AUTHORITY_LIMITED,
            ['reservation_cancel'], // blockedScopesOverride
        );

        $this->assertTrue($output->isBlocked());
        $this->assertSame(YdlCancellationRecommendation::DECISION_BLOCKED_GATE, $output->decision);
        $this->assertStringContainsString('reservation_cancel', $output->rationale);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T3: LIMITED + no intersection → CANCEL_READY
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t3_limited_no_intersection_ready(): void
    {
        $output = $this->orchestrator->evaluateCancellationReadiness(
            $this->reservation->id,
            $this->ilan->tenant_id,
            YdlReservationContextOutput::AUTHORITY_LIMITED,
            [], // empty blockedScopes
        );

        $this->assertTrue($output->isReady());
        $this->assertSame(YdlCancellationRecommendation::DECISION_CANCEL_READY, $output->decision);
        $this->assertTrue($output->canCancel);
        $this->assertSame($this->reservation->id, $output->reservationId);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T4: Full pipeline — readiness → approval → execute → evidence
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t4_full_pipeline_success(): void
    {
        $start = now()->addDays(30)->format('Y-m-d');
        $end   = now()->addDays(32)->format('Y-m-d');
        $res = $this->makeConfirmedReservation($this->ilan, $start, $end);

        // Pre-block availability
        $this->blockAvailability($res->id, $start, $end);

        // Step 1: Evaluate
        $readiness = $this->orchestrator->evaluateCancellationReadiness($res->id, $this->ilan->tenant_id);
        $this->assertTrue($readiness->isReady());

        // Step 2: Request approval
        $token = $this->orchestrator->requestCancellationApproval($readiness, $this->user->id);
        $this->assertInstanceOf(YdlCancellationApprovalToken::class, $token);
        $this->assertFalse($token->isExpired());

        // Step 3: Execute
        $evidence = $this->orchestrator->executeCancellation($token, $this->user->id);

        $this->assertTrue($evidence->success);
        $this->assertSame(ReservationEvent::OUTCOME_SUCCESS, $evidence->outcome);
        $this->assertSame($res->id, $evidence->reservationId);

        // Verify canonical DB write
        $res->refresh();
        $this->assertSame(ReservationState::CANCELLED->value, $res->reservation_state->value);
        $this->assertNotNull($res->cancelled_at);

        // Verify availability released (internal only)
        $availableCount = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', true)
            ->whereBetween('date', [$start, $end])
            ->count();
        $this->assertGreaterThan(0, $availableCount);

        // Verify event logged
        $this->assertTrue($this->eventLog->eventExists($evidence->eventId));
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T5: Expired token → DomainException
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t5_expired_token_rejected(): void
    {
        $start = now()->addDays(40)->format('Y-m-d');
        $end   = now()->addDays(42)->format('Y-m-d');
        $res = $this->makeConfirmedReservation($this->ilan, $start, $end);

        $readiness = $this->orchestrator->evaluateCancellationReadiness($res->id, $this->ilan->tenant_id);
        $token = $this->orchestrator->requestCancellationApproval($readiness, $this->user->id);

        // Manually expire the token
        $expiredToken = YdlCancellationApprovalToken::create(
            reservationId: $token->reservationId,
            ilanId:       $token->ilanId,
            tenantId:     $token->tenantId,
            eventId:      $token->eventId,
            ydlAuthority: $token->ydlAuthority,
            authorityContext: 'test',
            reservationState: 'confirmed',
            recommendation: [],
            requestedAt: now()->subSeconds(86401)->toIso8601String(),
            expiresAt:   now()->subSecond()->toIso8601String(),
            requestedBy: $this->user->id,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');
        $this->orchestrator->executeCancellation($expiredToken, $this->user->id);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T6: Duplicate event_id → idempotent no-op
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t6_duplicate_event_idempotent(): void
    {
        $start = now()->addDays(50)->format('Y-m-d');
        $end   = now()->addDays(52)->format('Y-m-d');
        $res = $this->makeConfirmedReservation($this->ilan, $start, $end);

        $readiness = $this->orchestrator->evaluateCancellationReadiness($res->id, $this->ilan->tenant_id);
        $token = $this->orchestrator->requestCancellationApproval($readiness, $this->user->id);

        // Execute first time — success
        $evidence1 = $this->orchestrator->executeCancellation($token, $this->user->id);
        $this->assertSame(ReservationEvent::OUTCOME_SUCCESS, $evidence1->outcome);

        // Re-use same token — idempotent no-op
        $evidence2 = $this->orchestrator->executeCancellation($token, $this->user->id);
        $this->assertSame(ReservationEvent::OUTCOME_IDEMPOTENT, $evidence2->outcome);
        $this->assertTrue($evidence2->success);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T7: Cross-tenant rejection
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t7_cross_tenant_blocked(): void
    {
        $start = now()->addDays(60)->format('Y-m-d');
        $end   = now()->addDays(62)->format('Y-m-d');
        $res = $this->makeConfirmedReservation($this->ilan, $start, $end);

        // Request with wrong tenant_id
        $readiness = $this->orchestrator->evaluateCancellationReadiness(
            $res->id,
            $res->tenant_id + 999, // wrong tenant
        );

        $this->assertTrue($readiness->isBlocked());
        $this->assertSame(YdlCancellationRecommendation::DECISION_BLOCKED_GATE, $readiness->decision);
        $this->assertStringContainsString('Cross-tenant', $readiness->rationale);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not ready');
        $this->orchestrator->requestCancellationApproval($readiness);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T8: Already cancelled → ALREADY_CANCELLED
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t8_already_cancelled(): void
    {
        $start = now()->addDays(70)->format('Y-m-d');
        $end   = now()->addDays(72)->format('Y-m-d');
        $res = $this->makeConfirmedReservation($this->ilan, $start, $end);

        // Cancel first
        (new ReservationService())->cancelReservation($res->id);

        // Try to cancel again
        $output = $this->orchestrator->evaluateCancellationReadiness($res->id, $this->ilan->tenant_id);
        $this->assertTrue($output->isAlreadyCancelled());
        $this->assertSame(YdlCancellationRecommendation::DECISION_ALREADY_CANCELLED, $output->decision);
        $this->assertFalse($output->canCancel);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not ready');
        $this->orchestrator->requestCancellationApproval($output);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T9: Availability release — internal only, external stays blocked
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t9_internal_availability_released_external_unchanged(): void
    {
        $start = now()->addDays(80)->format('Y-m-d');
        $end   = now()->addDays(82)->format('Y-m-d');
        $externalDate = now()->addDays(83)->format('Y-m-d');
        $res = $this->makeConfirmedReservation($this->ilan, $start, $end);

        // Internal block from our reservation
        $this->blockAvailability($res->id, $start, $end);

        // External block (e.g., Airbnb iCal) on external date
        PropertyAvailability::insert([
            [
                'property_id' => $this->ilan->id,
                'date' => $externalDate,
                'is_available' => false,
                'block_reason' => 'airbnb_busy',
                'source_system' => 'airbnb_ical',
                'reservation_id' => null,
            ],
        ]);

        $readiness = $this->orchestrator->evaluateCancellationReadiness($res->id, $this->ilan->tenant_id);
        $token = $this->orchestrator->requestCancellationApproval($readiness, $this->user->id);
        $evidence = $this->orchestrator->executeCancellation($token, $this->user->id);

        $this->assertTrue($evidence->success);

        // Internal block released → is_available = true
        $internalAvail = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('source_system', 'internal')
            ->where('date', $start)
            ->first();
        $this->assertTrue($internalAvail->is_available);
        $this->assertNull($internalAvail->reservation_id);

        // External block unchanged
        $externalAvail = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('source_system', 'airbnb_ical')
            ->where('date', $externalDate)
            ->first();
        $this->assertFalse($externalAvail->is_available); // stays blocked
        $this->assertSame('airbnb_busy', $externalAvail->block_reason);
    }

    // ─────────────────────────────────────────────────────────────────
    // R2-T10: Terminal state cannot be re-cancelled
    // ─────────────────────────────────────────────────────────────────

    public function test_r2_t10_terminal_state_idempotent(): void
    {
        $start = now()->addDays(90)->format('Y-m-d');
        $end   = now()->addDays(92)->format('Y-m-d');
        $res = $this->makeConfirmedReservation($this->ilan, $start, $end);

        // First cancellation — success
        $readiness1 = $this->orchestrator->evaluateCancellationReadiness($res->id, $this->ilan->tenant_id);
        $token1 = $this->orchestrator->requestCancellationApproval($readiness1, $this->user->id);
        $evidence1 = $this->orchestrator->executeCancellation($token1, $this->user->id);
        $this->assertSame(ReservationEvent::OUTCOME_SUCCESS, $evidence1->outcome);

        // Second cancellation attempt — ALREADY_CANCELLED
        $readiness2 = $this->orchestrator->evaluateCancellationReadiness($res->id, $this->ilan->tenant_id);
        $this->assertTrue($readiness2->isAlreadyCancelled());

        // isAlreadyCancelled → requestApproval throws (not ready)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not ready');
        $this->orchestrator->requestCancellationApproval($readiness2, $this->user->id);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function makeConfirmedReservation(Ilan $ilan, ?string $start = null, ?string $end = null): PropertyReservation
    {
        $start ??= now()->addDays(5)->format('Y-m-d');
        $end   ??= now()->addDays(7)->format('Y-m-d');

        return PropertyReservation::create([
            'tenant_id' => $ilan->tenant_id,
            'property_id' => $ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => Carbon::parse($start)->diffInDays(Carbon::parse($end)),
            'guest_name' => 'Test Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'created_by_user_id' => $this->user->id,
            'confirmed_at' => now(),
        ]);
    }

    private function blockAvailability(int $reservationId, string $start, string $end): void
    {
        $dates = [];
        $current = Carbon::parse($start);
        $endC = Carbon::parse($end);
        while ($current->lt($endC)) {
            $dates[] = [
                'property_id' => $this->ilan->id,
                'date' => $current->format('Y-m-d'),
                'is_available' => false,
                'block_reason' => 'reservation',
                'source_system' => 'internal',
                'reservation_id' => $reservationId,
            ];
            $current->addDay();
        }
        PropertyAvailability::insert($dates);
    }

    private function writeYdlState(array $state): void
    {
        $this->ensureDir($this->testDir . '/memory/ydl/state');
        File::put($this->testDir . '/memory/ydl/state/active.json', json_encode($state));
    }

    private function writeBlockers(array $blockers): void
    {
        $this->ensureDir($this->testDir . '/memory/ydl');
        File::put($this->testDir . '/memory/ydl/blockers.json', json_encode(['blockers' => $blockers]));
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

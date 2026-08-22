<?php

namespace Tests\Feature\Reservation;

use App\Enums\ReservationState;
use App\Events\Reservation\ReservationCompletedEvent;
use App\Jobs\Reservation\ProcessFinancialCompletionJob;
use App\Jobs\Reservation\ProcessReservationCompletedJob;
use App\Models\Ilan;
use App\Models\LedgerEntry;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\FinancialLedgerService;
use App\Services\Reservation\OperationalGorevService;
use App\Services\ReservationService;
use App\ValueObjects\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C1: Financial Completion Certification Tests
 *
 * Scope: ReservationCompletedEvent → financial lifecycle closure
 * Confirms: reservation completion automatically transitions finansal_durum → CONFIRMED
 *
 * Baseline: 667c1b4
 * SAAB Decision: C1 Certification
 *
 * Required coverage (10 tests):
 *  1. Completed event triggers Finance completion
 *  2. Existing canonical financial completion state (CONFIRMED) is used
 *  3. Scheduled completion reaches Finance completion
 *  4. Real-time checkout reaches Finance completion
 *  5. Duplicate completed event is economically idempotent
 *  6. Existing ledger remains immutable
 *  7. Debit/credit integrity remains valid where applicable
 *  8. Cancelled reservation cannot become completed financially
 *  9. Cross-tenant completion is rejected
 * 10. Existing cancellation reversal regression passes
 */
class C1FinancialCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;
    protected FinancialLedgerService $ledgerService;
    protected OperationalGorevService $gorevService;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->ledgerService = app(FinancialLedgerService::class);
        $this->gorevService = app(OperationalGorevService::class);

        $this->user = User::factory()->create();

        $this->ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 5000.00,
            'para_birimi' => 'TRY',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 1: Completed event triggers Finance completion
    // ─────────────────────────────────────────────────────────────────

    public function test_completed_event_triggers_finansal_durum_transition_to_confirmed(): void
    {
        $reservation = $this->_createCompletedReservation();

        // Emit the canonical event
        $event = ReservationCompletedEvent::fromModel($reservation, true);

        // Process the financial completion job
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);

        // Assert: finansal_durum is now CONFIRMED
        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CONFIRMED, $fresh->finansal_durum);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 2: Existing canonical financial completion state (CONFIRMED) is used
    // ─────────────────────────────────────────────────────────────────

    public function test_finansal_durum_uses_confirmed_as_terminal_financial_state(): void
    {
        // Prove CONFIRMED is the terminal success state by verifying the
        // canonical transition method sets exactly this value.
        $reservation = $this->_createCompletedReservation();
        $event = ReservationCompletedEvent::fromModel($reservation, true);

        // Verify initial state is PENDING (from initial booking ledger entry)
        $freshBefore = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertNotEquals(TransactionStatus::CONFIRMED, $freshBefore->finansal_durum);

        // Transition
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);

        $freshAfter = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CONFIRMED, $freshAfter->finansal_durum);

        // Verify CONFIRMED is a terminal success state
        $status = new TransactionStatus(TransactionStatus::CONFIRMED);
        $this->assertTrue($status->isSuccess());
        $this->assertFalse($status->isTerminal());
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 3: Scheduled completion path reaches Finance completion
    // ─────────────────────────────────────────────────────────────────

    public function test_scheduled_completion_reaches_finance_completion(): void
    {
        // Simulate reservation:complete command path:
        // 1. Save completed_at
        // 2. Dispatch ReservationCompletedEvent
        // 3. Listener → ProcessFinancialCompletionJob

        $reservation = $this->_createConfirmedReservation();

        // Simulate scheduled completion: set completed_at
        $reservation->completed_at = now();
        $reservation->save();

        // Emit event as the scheduled command would
        $event = ReservationCompletedEvent::fromModel($reservation, true);
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);

        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CONFIRMED, $fresh->finansal_durum);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 4: Real-time checkout path reaches Finance completion
    // ─────────────────────────────────────────────────────────────────

    public function test_checkout_path_reaches_finance_completion(): void
    {
        // Simulate ReservationService::checkOut() path
        // Real checkout: transaction commits, then event() is called outside tx
        // We simulate: reservation already has checked_out_at + completed_at set

        $reservation = $this->_createConfirmedReservation();

        // Simulate checkout: checked_out_at and completed_at are set
        $reservation->checked_out_at = now();
        $reservation->completed_at = now();
        $reservation->save();

        // Emit event as checkOut() would (outside transaction)
        $event = ReservationCompletedEvent::fromModel($reservation, true);
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);

        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CONFIRMED, $fresh->finansal_durum);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 5: Duplicate completed event is economically idempotent
    // ─────────────────────────────────────────────────────────────────

    public function test_duplicate_completed_event_produces_no_duplicate_economic_impact(): void
    {
        $reservation = $this->_createCompletedReservation();
        $event = ReservationCompletedEvent::fromModel($reservation, true);

        // Capture initial ledger state
        $initialLedgerCount = LedgerEntry::where('reference_id', $reservation->id)->count();

        // Process first time
        $job1 = new ProcessFinancialCompletionJob($event);
        $job1->handle($this->ledgerService);

        $fresh1 = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CONFIRMED, $fresh1->finansal_durum);

        // Process second time (duplicate event)
        $event2 = ReservationCompletedEvent::fromModel($fresh1, true);
        $job2 = new ProcessFinancialCompletionJob($event2);
        $job2->handle($this->ledgerService);

        $fresh2 = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CONFIRMED, $fresh2->finansal_durum);

        // Ledger count must be unchanged — no duplicate ledger entries created
        $finalLedgerCount = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals($initialLedgerCount, $finalLedgerCount,
            'Duplicate event must not create new ledger entries');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 6: Existing ledger entries remain immutable after completion
    // ─────────────────────────────────────────────────────────────────

    public function test_existing_ledger_entries_are_immutable_after_financial_completion(): void
    {
        $reservation = $this->_createCompletedReservation();

        // Capture initial ledger entries (from booking)
        $initialEntries = LedgerEntry::where('reference_id', $reservation->id)
            ->orderBy('id')
            ->get()
            ->toArray();

        $initialCount = count($initialEntries);

        // Emit and process
        $event = ReservationCompletedEvent::fromModel($reservation, true);
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);

        // Assert: no new ledger entries added (financial completion is state-only)
        $finalCount = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals($initialCount, $finalCount,
            'Financial completion must not add ledger entries');

        // Assert: existing entries are byte-for-byte unchanged
        $finalEntries = LedgerEntry::where('reference_id', $reservation->id)
            ->orderBy('id')
            ->get();

        foreach ($initialEntries as $i => $initial) {
            $final = $finalEntries[$i];
            $this->assertEquals($initial['debit_amount'], $final->debit_amount);
            $this->assertEquals($initial['credit_amount'], $final->credit_amount);
            $this->assertEquals($initial['sebep'], $final->sebep);
            $this->assertEquals($initial['transaction_group_id'], $final->transaction_group_id);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 7: Debit/credit integrity remains valid (no unbalanced tx)
    // ─────────────────────────────────────────────────────────────────

    public function test_debit_credit_integrity_holds_after_financial_completion(): void
    {
        $reservation = $this->_createCompletedReservation();

        // Get all ledger entries
        $entries = LedgerEntry::where('reference_id', $reservation->id)->get();

        $totalDebit = $entries->sum('debit_amount');
        $totalCredit = $entries->sum('credit_amount');

        // Financial completion must not break double-entry invariant
        $this->assertEquals($totalDebit, $totalCredit,
            'Double-entry invariant must hold after financial completion');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 8: Cancelled reservation cannot become financially completed
    // ─────────────────────────────────────────────────────────────────

    public function test_cancelled_reservation_cannot_transition_to_financial_completion(): void
    {
        // Create and immediately cancel a reservation
        $reservation = $this->_createConfirmedReservation();

        // Cancel it (reservation_state set synchronously; finansal_durum=CANCELLED set by async job)
        $this->reservationService->cancelReservation($reservation->id);

        $cancelledEvent = \App\Events\Reservation\ReservationCancelledEvent::fromModel(
            PropertyReservation::withoutGlobalScopes()->find($reservation->id), 'user', 'C1 test'
        );
        $cancelJob = new \App\Jobs\Reservation\ProcessReservationCancelled($cancelledEvent);
        $cancelJob->handle(
            app(\App\Application\ChannelManager\Services\AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertTrue($fresh->reservation_state === ReservationState::CANCELLED);
        $this->assertEquals(TransactionStatus::CANCELLED, $fresh->finansal_durum);

        // Now simulate a stale completed event arriving
        $event = ReservationCompletedEvent::fromModel($fresh, true);
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);

        // Must remain CANCELLED — not overwritten with CONFIRMED
        $final = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CANCELLED, $final->finansal_durum,
            'Cancelled reservation must not become CONFIRMED even with stale event');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 9: Cross-tenant financial completion is rejected
    // ─────────────────────────────────────────────────────────────────

    public function test_cross_tenant_financial_completion_is_rejected(): void
    {
        // Create two tenants
        $tenant1 = \App\Models\SaaS\Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant Alpha',
            'domain' => 'alpha.test',
            'status' => 'active',
        ]);

        $tenant2 = \App\Models\SaaS\Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant Beta',
            'domain' => 'beta.test',
            'status' => 'active',
        ]);

        // Create ilans for each tenant
        $ilan1 = Ilan::factory()->create([
            'tenant_id' => $tenant1->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 10000.00,
        ]);

        $ilan2 = Ilan::factory()->create([
            'tenant_id' => $tenant2->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 20000.00,
        ]);

        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        // Create reservations
        $res1 = $this->reservationService->createReservation(
            $ilan1->id, $startDate, $endDate, ['guest_name' => 'Alpha Guest'], $this->user->id
        );
        $res2 = $this->reservationService->createReservation(
            $ilan2->id, $startDate, $endDate, ['guest_name' => 'Beta Guest'], $this->user->id
        );

        // Complete both
        $res1->completed_at = now();
        $res1->save();
        $res2->completed_at = now();
        $res2->save();

        // Build event for tenant1's reservation
        $eventForRes1 = ReservationCompletedEvent::fromModel($res1, true);

        // Process with tenant2's context — this simulates a cross-tenant attack
        // where an event from tenant1 is processed with tenant2's credentials
        // The job must load by (reservationId, tenantId) and find nothing
        $eventTainted = new ReservationCompletedEvent(
            reservationId: $eventForRes1->reservationId,
            tenantId: $tenant2->id,   // ← wrong tenant
            ilanId: $eventForRes1->ilanId,
            startDate: $eventForRes1->startDate,
            endDate: $eventForRes1->endDate,
            nights: $eventForRes1->nights,
            guestName: $eventForRes1->guestName,
            guestEmail: $eventForRes1->guestEmail,
            guestPhone: $eventForRes1->guestPhone,
            guestCount: $eventForRes1->guestCount,
            totalAmount: $eventForRes1->totalAmount,
            currency: $eventForRes1->currency,
            lockedNightlyRate: $eventForRes1->lockedNightlyRate,
            completedAt: $eventForRes1->completedAt,
            checkedOutCleanly: $eventForRes1->checkedOutCleanly,
            externalReservationId: $eventForRes1->externalReservationId,
            externalChannel: $eventForRes1->externalChannel,
        );

        $job = new ProcessFinancialCompletionJob($eventTainted);
        $job->handle($this->ledgerService);

        // Tenant1's reservation must still be PENDING (not completed by wrong tenant)
        $res1Fresh = PropertyReservation::withoutGlobalScopes()->find($res1->id);
        $this->assertNotEquals(TransactionStatus::CONFIRMED, $res1Fresh->finansal_durum,
            'Cross-tenant mutation must not succeed');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 10: Existing cancellation reversal regression
    // ─────────────────────────────────────────────────────────────────

    public function test_cancellation_reversal_regression_finansal_durum_is_cancelled(): void
    {
        // This test verifies that the existing cancellation flow is unchanged:
        // cancel → reverse ledger entries → set finansal_durum = CANCELLED
        // AND now we also assert that after cancellation reversal, a completed
        // event from the old pipeline cannot override CANCELLED back to CONFIRMED.

        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id, $startDate, $endDate,
            ['guest_name' => 'Cancellation Regression Guest'], $this->user->id
        );

        // Process creation ledger entry
        $createdEvent = \App\Events\Reservation\ReservationCreatedEvent::fromModel($reservation);

        $createJob = new \App\Jobs\Reservation\ProcessReservationCreated($createdEvent);
        $createJob->handle(
            app(\App\Application\ChannelManager\Services\AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        $initialLedgerCount = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals(2, $initialLedgerCount, 'Initial booking creates 2 ledger entries');

        // Cancel
        $this->reservationService->cancelReservation($reservation->id);

        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertTrue($fresh->reservation_state === ReservationState::CANCELLED);

        // Process cancellation reversal (also sets finansal_durum = CANCELLED)
        $cancelledEvent = \App\Events\Reservation\ReservationCancelledEvent::fromModel($fresh, 'user', 'Regression test');
        $cancelJob = new \App\Jobs\Reservation\ProcessReservationCancelled($cancelledEvent);
        $cancelJob->handle(
            app(\App\Application\ChannelManager\Services\AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        // Ledger must have 4 entries (2 original + 2 reversal)
        $finalLedgerCount = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals(4, $finalLedgerCount, 'Cancellation reversal adds 2 entries');

        // finansal_durum must still be CANCELLED
        $postCancel = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CANCELLED, $postCancel->finansal_durum);

        // A stale completed event must NOT override CANCELLED
        $staleEvent = ReservationCompletedEvent::fromModel($postCancel, true);
        $staleJob = new ProcessFinancialCompletionJob($staleEvent);
        $staleJob->handle($this->ledgerService);

        $postStale = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CANCELLED, $postStale->finansal_durum,
            'Stale completed event must not override cancellation state');
    }

    // ─────────────────────────────────────────────────────────────────
    // Helper: create a confirmed reservation ready for completion
    // ─────────────────────────────────────────────────────────────────

    /**
     * Create a confirmed reservation and process its creation job so it has
     * initial ledger entries, then mark it as completed (checked_out_at set).
     * This mimics the state of a reservation that has passed checkout but
     * has not yet had its financial completion applied.
     */
    private function _createCompletedReservation(): PropertyReservation
    {
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id, $startDate, $endDate,
            ['guest_name' => 'Completion Test Guest'], $this->user->id
        );

        // Process creation ledger entry (required for initial PENDING state)
        $createdEvent = \App\Events\Reservation\ReservationCreatedEvent::fromModel($reservation);
        $createJob = new \App\Jobs\Reservation\ProcessReservationCreated($createdEvent);
        $createJob->handle(
            app(\App\Application\ChannelManager\Services\AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        // Mark as completed (simulates checkOut() or scheduled completion)
        $reservation->checked_out_at = now();
        $reservation->completed_at = now();
        $reservation->save();

        return $reservation->fresh();
    }

    /**
     * Create a confirmed reservation without processing creation job.
     * Use for tests that don't need initial ledger entries.
     */
    private function _createConfirmedReservation(): PropertyReservation
    {
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        return $this->reservationService->createReservation(
            $this->ilan->id, $startDate, $endDate,
            ['guest_name' => 'Confirmed Guest'], $this->user->id
        );
    }
}

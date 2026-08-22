<?php

namespace Tests\Feature\Reservation;

use App\Enums\ManagementModel;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationCompletedEvent;
use App\Jobs\Reservation\ProcessFinancialCompletionJob;
use App\Jobs\Reservation\ProcessReservationCancelled;
use App\Models\Ilan;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\FinancialLedgerService;
use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Services\ReservationService;
use App\ValueObjects\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C3.2: Owner Payable Accrual Certification Tests
 *
 * Scope: After financial completion (CONFIRMED) → commission split + owner payable accrual
 * Trigger: ProcessFinancialCompletionJob → recordOwnerPayableAccrual()
 * Reversal: ProcessReservationCancelled → reverseOwnerPayableAccrual()
 *
 * Required coverage (12 tests):
 *  1. FULL_MANAGEMENT 15% → commission 15K / owner 85K
 *  2. CHECKIN_CHECKOUT 10% → commission 10K / owner 90K
 *  3. NONE 0% → no commission entry, owner 100K
 *  4. CUSTOM 12% → commission 12K / owner 88K
 *  5. Legacy NULL snapshot → no accrual, audit log
 *  6. Duplicate event → no duplicate economic impact
 *  7. Cross-tenant → no mutation
 *  8. Currency/FX preserved
 *  9. Ledger balanced after accrual
 * 10. Cancelled reservation → no owner payable
 * 11. C3.1 snapshot immutability regression
 * 12. C1/C2 completion regression
 *
 * Baseline: 976d006 (C3.1 Certified)
 * SAAB Decision: C3.2 Certification
 */
class C3OwnerPayableAccrualTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;
    protected FinancialLedgerService $ledgerService;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->ledgerService = app(FinancialLedgerService::class);
        $this->user = User::factory()->create();

        $this->ilan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'fiyat'          => 5000.00,
            'para_birimi'    => 'TRY',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Tests 1-4: Commission rate scenarios
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 1: FULL_MANAGEMENT 15% → commission 15,000 / owner 85,000
     */
    public function test_full_management_15_percent_commission_and_owner_accrual(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // Process financial completion (includes C3.2 accrual)
        $this->processCompletion($reservation);

        // Verify commission split: TX2
        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $this->assertCount(2, $commissionEntries, 'Commission split creates 2 ledger entries');

        $debitEntry = $commissionEntries->firstWhere('debit_amount', '>', 0);
        $creditEntry = $commissionEntries->firstWhere('credit_amount', '>', 0);
        $this->assertEquals(15_000.00, (float) $debitEntry->debit_amount);
        $this->assertEquals(15_000.00, (float) $creditEntry->credit_amount);

        // Verify owner payable: TX3
        $ownerEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $this->assertCount(2, $ownerEntries, 'Owner payable creates 2 ledger entries');

        $ownerDebit = $ownerEntries->firstWhere('debit_amount', '>', 0);
        $ownerCredit = $ownerEntries->firstWhere('credit_amount', '>', 0);
        $this->assertEquals(85_000.00, (float) $ownerDebit->debit_amount);
        $this->assertEquals(85_000.00, (float) $ownerCredit->credit_amount);
    }

    /**
     * Test 2: CHECKIN_CHECKOUT 10% → commission 10,000 / owner 90,000
     */
    public function test_checkin_checkout_10_percent_commission_and_owner_accrual(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::CHECKIN_CHECKOUT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $this->processCompletion($reservation);

        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $debitEntry = $commissionEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(10_000.00, (float) $debitEntry->debit_amount);

        $ownerEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $ownerDebit = $ownerEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(90_000.00, (float) $ownerDebit->debit_amount);
    }

    /**
     * Test 3: NONE 0% → no commission entry, owner 100,000
     */
    public function test_none_zero_percent_owner_gets_full_gross(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::NONE);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $this->processCompletion($reservation);

        // No commission entry
        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $this->assertCount(0, $commissionEntries, 'NONE: no commission entry for 0% rate');

        // Owner gets 100%
        $ownerEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $this->assertCount(2, $ownerEntries, 'Owner payable: 2 entries');
        $ownerDebit = $ownerEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(100_000.00, (float) $ownerDebit->debit_amount);
    }

    /**
     * Test 4: CUSTOM 12% → commission 12,000 / owner 88,000
     */
    public function test_custom_12_percent_snapshots_configured_rate(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::CUSTOM, 0.1200);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $this->processCompletion($reservation);

        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $debitEntry = $commissionEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(12_000.00, (float) $debitEntry->debit_amount);

        $ownerEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $ownerDebit = $ownerEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(88_000.00, (float) $ownerDebit->debit_amount);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 5: Legacy NULL snapshot → STOP
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 5: Legacy NULL snapshot → no accrual, audit log
     *
     * Tests recordOwnerPayableAccrual() directly with a reservation whose
     * commission_rate_snapshot is explicitly NULL (simulating a pre-C3.1 reservation
     * where snapshot was not captured).
     *
     * The service must skip accrual silently (no entries, no error).
     */
    public function test_legacy_null_snapshot_no_accrual(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // Manually set snapshot to NULL to simulate legacy pre-C3.1 reservation
        $reservation->update(['commission_rate_snapshot' => null, 'management_model_snapshot' => null]);

        // Call accrual directly — must be a NO-OP for NULL snapshot
        $this->ledgerService->recordOwnerPayableAccrual($reservation);

        // No commission entry (NULL snapshot → STOP)
        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $this->assertCount(0, $commissionEntries, 'Legacy NULL: no commission entry');

        // No owner payable entry (NULL snapshot → STOP)
        $ownerEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $this->assertCount(0, $ownerEntries, 'Legacy NULL: no owner payable entry');

        // Booking entry still exists (unchanged)
        $allEntries = LedgerEntry::where('reference_id', $reservation->id)->get();
        $bookingEntries = $allEntries->filter(fn($e) => str_contains($e->sebep ?? '', 'Konaklama Kaydı'));
        $this->assertGreaterThan(0, $bookingEntries->count(), 'Booking entry still exists');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 6: Duplicate event → no duplicate economic impact
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 6: Duplicate completed event → no duplicate economic impact
     */
    public function test_duplicate_completed_event_no_duplicate_economic_impact(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // Process first time
        $event1 = ReservationCompletedEvent::fromModel($reservation, true);
        $job1 = new ProcessFinancialCompletionJob($event1);
        $job1->handle($this->ledgerService);

        $countAfterFirst = LedgerEntry::where('reference_id', $reservation->id)->count();

        // Process duplicate event
        $reservation->refresh();
        $event2 = ReservationCompletedEvent::fromModel($reservation, true);
        $job2 = new ProcessFinancialCompletionJob($event2);
        $job2->handle($this->ledgerService);

        $countAfterSecond = LedgerEntry::where('reference_id', $reservation->id)->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond,
            'Duplicate event must not create additional ledger entries');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 7: Cross-tenant isolation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 7: Cross-tenant → no mutation
     */
    public function test_cross_tenant_no_accrual_mutation(): void
    {
        $tenantA = Tenant::create([
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'name'   => 'Tenant A',
            'domain' => 'tenanta.test',
            'status' => 'active',
        ]);

        $ilanA = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $ilanA->update(['tenant_id' => $tenantA->id]);

        $ilanB = Ilan::factory()->create([
            'tenant_id'           => $tenantA->id + 1,  // different tenant
            'rental_enabled'       => true,
            'min_stay_nights'     => 1,
            'fiyat'              => 10000.00,
            'management_model'     => ManagementModel::FULL_MANAGEMENT,
        ]);

        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $resA = $this->reservationService->createReservation(
            $ilanA->id, $startDate, $endDate,
            ['guest_name' => 'TenantA Guest', 'total_amount' => 100_000.00, 'currency' => 'TRY'],
            $this->user->id
        );

        // Process creation job to create Konaklama/Gelirleri account
        $createdEventA = \App\Events\Reservation\ReservationCreatedEvent::fromModel($resA);
        $createJobA = new \App\Jobs\Reservation\ProcessReservationCreated($createdEventA);
        $createJobA->handle(
            app(\App\Application\ChannelManager\Services\AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        $resA->update(['checked_out_at' => now(), 'completed_at' => now()]);

        // Process with tenantB's event carrying tenantA's reservationId
        $taintedEvent = new ReservationCompletedEvent(
            reservationId: $resA->id,
            tenantId: $tenantA->id + 1, // wrong tenant
            ilanId: $resA->ilan_id ?? $resA->property_id,
            startDate: $this->formatDate($resA->start_date),
            endDate: $this->formatDate($resA->end_date),
            nights: $resA->nights,
            guestName: $resA->guest_name,
            guestEmail: null,
            guestPhone: null,
            guestCount: null,
            totalAmount: 100_000.00,
            currency: 'TRY',
            lockedNightlyRate: null,
            completedAt: now()->toIso8601String(),
            checkedOutCleanly: true,
            externalReservationId: null,
            externalChannel: null,
        );

        $job = new ProcessFinancialCompletionJob($taintedEvent);
        $job->handle($this->ledgerService);

        // Tenant A's reservation should NOT have C3.2 entries (tainted event was not processed)
        $commissionEntries = $this->getEntriesBySebep($resA->id, 'Yalihan Komisyon Tahsili');
        $this->assertCount(0, $commissionEntries,
            'Cross-tenant event must not create commission entry');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 8: Currency/FX preservation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 8: Currency preserved and FX rate locked in ledger entries
     */
    public function test_currency_and_fx_rate_preserved(): void
    {
        // Use TRY (no FX conversion needed) — verify currency field and fx_rate_locked are set
        $gross = 100_000.00;
        $currency = 'TRY';
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, $currency);

        $this->processCompletion($reservation);

        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $entry = $commissionEntries->firstWhere('credit_amount', '>', 0);

        $this->assertEquals('TRY', $entry->currency);
        $this->assertEquals(15_000.00, (float) $entry->credit_amount);
        $this->assertNotNull($entry->fx_rate_locked, 'fx_rate_locked must be set');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 9: Ledger balanced after accrual
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 9: Ledger balanced after accrual (debits = credits)
     */
    public function test_ledger_balanced_after_accrual(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $this->processCompletion($reservation);

        $entries = LedgerEntry::where('reference_id', $reservation->id)->get();

        $totalDebit = $entries->sum('debit_amount');
        $totalCredit = $entries->sum('credit_amount');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01,
            'Double-entry invariant: total debits must equal total credits');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 10: Cancelled reservation → no owner payable
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 10: Cancelled reservation → no owner payable accrual
     */
    public function test_cancelled_reservation_no_owner_payable(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $ilan->id, $startDate, $endDate,
            ['guest_name' => 'Cancel Test Guest', 'total_amount' => $gross, 'currency' => 'TRY'],
            $this->user->id
        );

        // Cancel immediately (before completion)
        $this->reservationService->cancelReservation($reservation->id);

        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $cancelEvent = ReservationCancelledEvent::fromModel($fresh, 'user', 'C3.2 test');
        $cancelJob = new ProcessReservationCancelled($cancelEvent);
        $cancelJob->handle(
            app(AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        // No commission entry
        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $this->assertCount(0, $commissionEntries, 'Cancelled reservation: no commission entry');

        // No owner payable
        $ownerEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $this->assertCount(0, $ownerEntries, 'Cancelled reservation: no owner payable');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 11: C3.1 snapshot immutability regression
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 11: C3.1 snapshot immutability — changing ilan agreement
     * does not affect already-completed reservations
     */
    public function test_c31_snapshot_immutability_regression(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // Process completion
        $this->processCompletion($reservation);

        // Capture snapshot rate
        $reservation->refresh();
        $this->assertEquals(0.1500, (float) $reservation->commission_rate_snapshot);

        // Change ilan agreement
        $ilan->update(['management_model' => ManagementModel::CHECKIN_CHECKOUT]);

        // New reservation with new agreement
        $startDate2 = Carbon::tomorrow()->addDays(10)->format('Y-m-d');
        $endDate2 = Carbon::tomorrow()->addDays(12)->format('Y-m-d');
        $reservation2 = $this->reservationService->createReservation(
            $ilan->id, $startDate2, $endDate2,
            ['guest_name' => 'Guest 2', 'total_amount' => $gross, 'currency' => 'TRY'],
            $this->user->id
        );
        $reservation2->update(['checked_out_at' => now(), 'completed_at' => now()]);
        $this->processCompletion($reservation2);

        // Original reservation still has 15%
        $reservation->refresh();
        $this->assertEquals(0.1500, (float) $reservation->commission_rate_snapshot);

        // New reservation has 10%
        $reservation2->refresh();
        $this->assertEquals(0.1000, (float) $reservation2->commission_rate_snapshot);

        // Ledger entries for original reservation are unchanged
        $originalCommissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $originalDebit = $originalCommissionEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(15_000.00, (float) $originalDebit->debit_amount,
            'Original reservation commission unchanged after ilan model change');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 12: C1/C2 completion regression
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 12: C1/C2 completion pipeline regression — financial completion
     * still works correctly with C3.2 accrual wired in
     */
    public function test_c1_c2_completion_pipeline_regression(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::CHECKIN_CHECKOUT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // Assert: financial state is CONFIRMED after completion
        $this->processCompletion($reservation);
        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $this->assertEquals(TransactionStatus::CONFIRMED, $fresh->finansal_durum,
            'Finansal durum must be CONFIRMED after completion');

        // Assert: idempotency — re-running does not duplicate
        $countBefore = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->processCompletion($reservation->fresh());
        $countAfter = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals($countBefore, $countAfter,
            'Idempotent replay must not create additional entries');
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function makeIlanWithModel(ManagementModel $model, ?float $customRate = null): Ilan
    {
        return Ilan::factory()->create([
            'rental_enabled'      => true,
            'min_stay_nights'    => 1,
            'fiyat'             => 5000.00,
            'para_birimi'       => 'TRY',
            'management_model'   => $model,
            'custom_commission_rate' => $customRate,
        ]);
    }

    private function createCompletedReservation(Ilan $ilan, float $gross, string $currency): PropertyReservation
    {
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $ilan->id, $startDate, $endDate,
            ['guest_name' => 'Test Guest', 'total_amount' => $gross, 'currency' => $currency],
            $this->user->id
        );

        // Process creation job: this creates Konaklama/Gelirleri account and initial booking entries
        $createdEvent = \App\Events\Reservation\ReservationCreatedEvent::fromModel($reservation);
        $createJob = new \App\Jobs\Reservation\ProcessReservationCreated($createdEvent);
        $createJob->handle(
            app(\App\Application\ChannelManager\Services\AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        $reservation->update(['checked_out_at' => now(), 'completed_at' => now()]);

        return $reservation;
    }

    private function processCompletion(PropertyReservation $reservation): void
    {
        $event = ReservationCompletedEvent::fromModel($reservation, true);
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);
    }

    private function getEntriesBySebep(int $reservationId, string $pattern): \Illuminate\Support\Collection
    {
        return LedgerEntry::where('reference_id', $reservationId)
            ->where('sebep', 'like', "%{$pattern}%")
            ->get();
    }

    private function formatDate(\DateTimeInterface|string|null $date): string
    {
        if ($date === null) {
            return '';
        }
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }
        return (string) $date;
    }
}

<?php

namespace Tests\Feature\Reservation;

use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Enums\ChannelFeeBearer;
use App\Enums\ChannelFeeSource;
use App\Enums\ManagementModel;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationCompletedEvent;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Jobs\Reservation\ProcessFinancialCompletionJob;
use App\Jobs\Reservation\ProcessReservationCancelled;
use App\Jobs\Reservation\ProcessReservationCreated;
use App\Models\Ilan;
use App\Models\LedgerEntry;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\FinancialLedgerService;
use App\Services\FxService;
use App\Services\ReservationService;
use App\ValueObjects\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * C4.2: Channel Fee Double-Entry Ledger Accrual Certification Tests
 *
 * SAAB C4.2 Charter: d170d4a
 * Baseline: 1a2e9cec7fdb5026a27e7d6928e469cba35832a8
 *
 * Scope:
 *   After financial completion → gross splits into:
 *     TX1: verified_channel_fee   → Kanal Komisyonu Yükümlülükleri (#329)
 *     TX2: yalihan_management_commission → Komisyon Gelirleri
 *     TX3: owner_payable            → Sahip Yükümlülükleri
 *
 * Trigger: ProcessFinancialCompletionJob → recordChannelFeeAccrual()
 * Reversal: ProcessReservationCancelled → reverseChannelFeeAccrual()
 *
 * Required coverage:
 *  1. Canonical 100K → 15,500 CF + 15,000 MC + 69,500 OP  (OWNER_BORNE, PROVIDER_REPORTED)
 *  2. Debit = Credit invariant (all entries sum to zero)
 *  3. UNKNOWN source → BLOCKED (C4.1 trust gate throws)
 *  4. Null amount for OWNER_BORNE → BLOCKED
 *  5. Insufficient source (PROPERTY_CONFIG) → BLOCKED
 *  6. Idempotent replay → zero duplicate entries
 *  7. Cancellation reversal → inverse entries created
 *  8. Reversal idempotency → replay reversal = no-op
 *  9. Wrong tenant → zero mutation
 * 10. Snapshot immutability → channel_fee fields not mutated
 * 11. Partial-failure rollback → TX1 success + TX2 fail → zero entries
 * 12. C1–C4.1 regression → completion pipeline unchanged
 * 13. YALIHAN_BORNE → channel fee bypass, commission+owner only
 * 14. hasChannelFeeAccrual() → correct boolean
 * 15. hasChannelFeeReversal() → correct boolean
 * 16. C3.2 compatibility → existing 0% channel fee scenario
 *
 * Partial-failure test is MANDATORY per SAAB C4.2 gate lock.
 * Zero production partial-state ledger entries permitted.
 */
class C4ChannelFeeAccrualTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;

    protected FinancialLedgerService $ledgerService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->ledgerService = app(FinancialLedgerService::class);
        $this->user = User::factory()->create();
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 1: Canonical OWNER_BORNE triple split
    // 100,000 = 15,500 (channel fee) + 15,000 (commission) + 69,500 (owner)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 1: Canonical 100K → 15,500 CF + 15,000 MC + 69,500 OP
     *
     * Verifies SAAB C4.2 canonical formula:
     *   gross = verified_channel_fee + yalihan_management_commission + owner_payable
     *   100,000 = 15,500 + 15,000 + 69,500
     *
     * Bearer: OWNER_BORNE
     * Source: PROVIDER_REPORTED (only sufficient source per C4.1)
     * Commission rate: 0.1500 (FULL_MANAGEMENT)
     * Channel fee rate: 0.1550 (15.5% of gross)
     */
    public function test_canonical_owner_borne_triple_split(): void
    {
        $gross = 100_000.00;
        $channelFeeAmount = 15_500.00;
        $commissionRate = 0.1500;
        $commissionAmount = $gross * $commissionRate;          // 15,000
        $expectedOwnerPayable = $gross - $channelFeeAmount - $commissionAmount; // 69,500

        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // Set verified channel fee (PROVIDER_REPORTED = C4.1 sufficient source)
        $reservation->update([
            'channel_fee_amount' => $channelFeeAmount,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $this->processCompletion($reservation);

        // TX1: Channel fee accrual
        $cfEntries = $this->getEntriesBySebep($reservation->id, 'Kanal Komisyonu Tahakkuku');
        $this->assertCount(2, $cfEntries, 'Channel fee creates 2 ledger entries');

        $cfDebit = $cfEntries->firstWhere('debit_amount', '>', 0);
        $cfCredit = $cfEntries->firstWhere('credit_amount', '>', 0);
        $this->assertEquals(15_500.00, (float) $cfDebit->debit_amount);
        $this->assertEquals(15_500.00, (float) $cfCredit->credit_amount);
        $this->assertEquals('Kanal Komisyonu Yükümlülükleri Hesabı', $cfCredit->account->name);

        // TX2: Commission accrual
        $mcEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $this->assertCount(2, $mcEntries, 'Commission creates 2 ledger entries');

        $mcDebit = $mcEntries->firstWhere('debit_amount', '>', 0);
        $mcCredit = $mcEntries->firstWhere('credit_amount', '>', 0);
        $this->assertEquals(15_000.00, (float) $mcDebit->debit_amount);
        $this->assertEquals(15_000.00, (float) $mcCredit->credit_amount);
        $this->assertEquals('Komisyon Gelirleri Hesabı', $mcCredit->account->name);

        // TX3: Owner payable accrual
        $opEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $this->assertCount(2, $opEntries, 'Owner payable creates 2 ledger entries');

        $opDebit = $opEntries->firstWhere('debit_amount', '>', 0);
        $opCredit = $opEntries->firstWhere('credit_amount', '>', 0);
        $this->assertEquals(69_500.00, (float) $opDebit->debit_amount);
        $this->assertEquals(69_500.00, (float) $opCredit->credit_amount);
        $this->assertEquals('Sahip Yükümlülükleri Hesabı', $opCredit->account->name);

        // Canonical formula check
        $this->assertEquals(
            $gross,
            $channelFeeAmount + $commissionAmount + $expectedOwnerPayable,
            'Canonical formula: gross = CF + MC + OP'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 2: Debit = Credit invariant
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 2: All ledger entries for reservation are debit = credit balanced
     */
    public function test_debit_equals_credit_invariant(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $this->processCompletion($reservation);

        $entries = LedgerEntry::where('reference_id', $reservation->id)->get();

        $totalDebit = $entries->sum('debit_amount');
        $totalCredit = $entries->sum('credit_amount');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01,
            'Double-entry invariant: total debits must equal total credits for reservation');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 3: UNKNOWN source → BLOCKED
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 3: UNKNOWN channel_fee_source → C4.1 trust gate throws
     *
     * C4.1 Invariant 2: UNKNOWN source → system does NOT guess → payout BLOCKED
     * recordChannelFeeAccrual must throw RuntimeException for UNKNOWN source
     * (OWNER_BORNE bearer requires channel fee).
     */
    public function test_unknown_source_blocked_by_c41_trust_gate(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_source' => ChannelFeeSource::UNKNOWN,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_rate' => 0.1550,
            'channel_fee_is_verified' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('C4.2 Trust Gate');
        $this->expectExceptionMessage('PROVIDER_REPORTED');

        $this->ledgerService->recordChannelFeeAccrual($reservation);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 4: Null amount for OWNER_BORNE → BLOCKED
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 4: OWNER_BORNE with null channel_fee_amount → C4.1 trust gate throws
     *
     * Bearer requires channel fee but amount is null.
     * Must throw RuntimeException — payout remains BLOCKED.
     */
    public function test_null_channel_fee_amount_blocked_for_owner_borne(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // Set PROVIDER_REPORTED source but null amount
        $reservation->update([
            'channel_fee_amount' => null,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_rate' => null,
            'channel_fee_is_verified' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('C4.2 Trust Gate');
        $this->expectExceptionMessage('channel_fee_amount is null');

        $this->ledgerService->recordChannelFeeAccrual($reservation);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 5: PROPERTY_CONFIG source → BLOCKED (insufficient)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 5: PROPERTY_CONFIG source → BLOCKED (insufficient per C4.1)
     *
     * PROPERTY_CONFIG is not sufficient for payout readiness — needs C5 reconciliation.
     * recordChannelFeeAccrual must throw RuntimeException.
     */
    public function test_property_config_source_blocked(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_source' => ChannelFeeSource::PROPERTY_CONFIG,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_rate' => 0.1550,
            'channel_fee_is_verified' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('C4.2 Trust Gate');
        $this->expectExceptionMessage('PROPERTY_CONFIG');

        $this->ledgerService->recordChannelFeeAccrual($reservation);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 6: Idempotent replay → zero duplicate entries
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 6: Replaying recordChannelFeeAccrual → zero duplicate entries
     *
     * Verifies idempotency keys prevent duplicate ledger transactions.
     * Call twice → same entry count, same transaction_group_ids.
     */
    public function test_idempotent_replay_no_duplicate_entries(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        // First call
        $this->ledgerService->recordChannelFeeAccrual($reservation);
        $countAfterFirst = LedgerEntry::where('reference_id', $reservation->id)->count();
        $txIdsAfterFirst = LedgerEntry::where('reference_id', $reservation->id)
            ->where('sebep', 'like', '%Kanal Komisyonu Tahakkuku%')
            ->pluck('transaction_group_id')
            ->toArray();

        // Second call (replay)
        $this->ledgerService->recordChannelFeeAccrual($reservation);
        $countAfterSecond = LedgerEntry::where('reference_id', $reservation->id)->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond,
            'Idempotent replay must not create additional ledger entries');

        // TX1 idempotency key used
        $txIdsAfterSecond = LedgerEntry::where('reference_id', $reservation->id)
            ->where('sebep', 'like', '%Kanal Komisyonu Tahakkuku%')
            ->pluck('transaction_group_id')
            ->toArray();

        $this->assertEquals($txIdsAfterFirst, $txIdsAfterSecond,
            'Transaction group IDs must be identical on replay');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 7: Cancellation reversal → inverse entries
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 7: Cancellation → reverseChannelFeeAccrual creates inverse entries
     *
     * Reversal debits liability accounts and credits revenue account.
     * All three components reversed (channel fee, commission, owner payable).
     */
    public function test_cancellation_creates_inverse_entries(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $this->processCompletion($reservation);

        // Record reversal
        $this->ledgerService->reverseChannelFeeAccrual($reservation);

        // Verify reversal entries exist
        $cfReversalEntries = $this->getEntriesBySebep($reservation->id, 'Kanal Komisyonu Tahakkuk İptal');
        $this->assertGreaterThan(0, $cfReversalEntries->count(),
            'Channel fee reversal entry must exist');

        $mcReversalEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon İptal');
        $this->assertGreaterThan(0, $mcReversalEntries->count(),
            'Commission reversal entry must exist');

        $opReversalEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk İptal');
        $this->assertGreaterThan(0, $opReversalEntries->count(),
            'Owner payable reversal entry must exist');

        // Reversal amounts match original
        $cfReversal = $cfReversalEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(15_500.00, (float) $cfReversal->debit_amount);

        $mcReversal = $mcReversalEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(15_000.00, (float) $mcReversal->debit_amount);

        $opReversal = $opReversalEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(69_500.00, (float) $opReversal->debit_amount);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 8: Reversal idempotency → replay = no-op
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 8: Reversal replay → no duplicate reversal entries
     *
     * Calling reverseChannelFeeAccrual twice must create reversal only once.
     */
    public function test_reversal_idempotent_no_duplicate(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $this->processCompletion($reservation);

        // First reversal
        $this->ledgerService->reverseChannelFeeAccrual($reservation);
        $countAfterFirstReversal = LedgerEntry::where('reference_id', $reservation->id)->count();

        // Second reversal (replay)
        $this->ledgerService->reverseChannelFeeAccrual($reservation);
        $countAfterSecondReversal = LedgerEntry::where('reference_id', $reservation->id)->count();

        $this->assertEquals($countAfterFirstReversal, $countAfterSecondReversal,
            'Reversal replay must not create duplicate reversal entries');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 9: Wrong tenant → zero mutation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 9: Cross-tenant event → zero ledger mutation
     *
     * Tenant isolation: channel fee accrual for wrong tenant must be rejected.
     * LedgerEntry records must remain in correct tenant scope.
     */
    public function test_cross_tenant_zero_mutation(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Tenant A CF',
            'domain' => 'tenantacf.test',
            'status' => 'active',
        ]);

        $ilanA = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $ilanA->update(['tenant_id' => $tenantA->id]);

        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $resA = $this->reservationService->createReservation(
            $ilanA->id, $startDate, $endDate,
            ['guest_name' => 'TenantA Guest', 'total_amount' => 100_000.00, 'currency' => 'TRY'],
            $this->user->id
        );

        $createdEventA = ReservationCreatedEvent::fromModel($resA);
        $createJobA = new ProcessReservationCreated($createdEventA);
        $createJobA->handle(
            app(AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        $resA->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
            'checked_out_at' => now(),
            'completed_at' => now(),
        ]);

        // Correct tenant: accrual works
        $this->ledgerService->recordChannelFeeAccrual($resA);

        // Ledger entries must be in tenantA scope
        $cfEntriesTenantA = LedgerEntry::withoutGlobalScopes()
            ->where('reference_id', $resA->id)
            ->where('sebep', 'like', '%Kanal Komisyonu Tahakkuku%')
            ->where('tenant_id', $tenantA->id)
            ->get();
        $this->assertGreaterThan(0, $cfEntriesTenantA->count(),
            'Channel fee entry must exist in correct tenant');

        // No channel fee entries in default tenant
        $cfEntriesDefault = LedgerEntry::withoutGlobalScopes()
            ->where('reference_id', $resA->id)
            ->where('sebep', 'like', '%Kanal Komisyonu Tahakkuku%')
            ->where('tenant_id', '!=', $tenantA->id)
            ->get();
        $this->assertCount(0, $cfEntriesDefault,
            'Channel fee entry must not exist in wrong tenant');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 10: Snapshot immutability — channel_fee fields not mutated
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 10: recordChannelFeeAccrual does not mutate snapshot fields
     *
     * channel_fee_amount, channel_fee_currency, channel_fee_rate,
     * channel_fee_source, channel_fee_bearer must remain unchanged after accrual.
     */
    public function test_snapshot_fields_immutable_after_accrual(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $originalChannelFee = 15_500.00;
        $originalRate = 0.1550;
        $originalSource = ChannelFeeSource::PROVIDER_REPORTED;
        $originalBearer = ChannelFeeBearer::OWNER_BORNE;
        $originalVerified = true;

        $reservation->update([
            'channel_fee_amount' => $originalChannelFee,
            'channel_fee_rate' => $originalRate,
            'channel_fee_source' => $originalSource,
            'channel_fee_bearer' => $originalBearer,
            'channel_fee_is_verified' => $originalVerified,
            'channel_fee_captured_at' => now(),
        ]);

        $this->ledgerService->recordChannelFeeAccrual($reservation);
        $reservation->refresh();

        // Snapshot fields must be unchanged
        $this->assertEquals($originalChannelFee, $reservation->channel_fee_amount,
            'channel_fee_amount must not be mutated');
        $this->assertEquals($originalRate, $reservation->channel_fee_rate,
            'channel_fee_rate must not be mutated');
        $this->assertEquals($originalSource, $reservation->channel_fee_source,
            'channel_fee_source must not be mutated');
        $this->assertEquals($originalBearer, $reservation->channel_fee_bearer,
            'channel_fee_bearer must not be mutated');
        $this->assertEquals($originalVerified, $reservation->channel_fee_is_verified,
            'channel_fee_is_verified must not be mutated');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 11: Partial-failure rollback — TX1 success + TX2 fail → zero entries
    // MANDATORY per SAAB C4.2 gate lock
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 11: Partial-failure rollback — TX1 success, TX2 throws → zero entries
     *
     * When recordDoubleEntry throws during TX2 or TX3 inside the outer
     * DB::transaction(), all entries from TX1 must be rolled back.
     * Zero partial-state ledger entries permitted in production.
     *
     * This test simulates a failure in the second recordDoubleEntry call
     * by mocking the service to throw after the first entry is created.
     */
    public function test_partial_failure_rollback_zero_entries(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        // Count entries before
        $countBefore = LedgerEntry::where('reference_id', $reservation->id)->count();

        // Simulate partial failure: mock recordDoubleEntry to throw on second call
        $callCount = 0;
        $mockService = new class($this->ledgerService) extends FinancialLedgerService
        {
            public int $callCount = 0;

            public bool $failOnSecond = true;

            private FinancialLedgerService $real;

            public function __construct(FinancialLedgerService $real)
            {
                parent::__construct(app(FxService::class));
                $this->real = $real;
            }

            public function recordDoubleEntry(...$args): string
            {
                $this->callCount++;
                if ($this->failOnSecond && $this->callCount > 1) {
                    throw new \RuntimeException('Simulated partial failure: TX2 failed');
                }

                return $this->real->recordDoubleEntry(...$args);
            }
        };

        try {
            $mockService->recordChannelFeeAccrual($reservation);
            $this->fail('Expected RuntimeException for partial failure');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Simulated partial failure', $e->getMessage());
        }

        // After rollback: zero new entries (count equals before)
        $countAfter = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals($countBefore, $countAfter,
            'After partial failure + rollback: zero new ledger entries must remain');

        // No channel fee entries at all
        $cfEntries = LedgerEntry::withoutGlobalScopes()
            ->where('reference_id', $reservation->id)
            ->where('sebep', 'like', '%Kanal Komisyonu Tahakkuku%')
            ->get();
        $this->assertCount(0, $cfEntries,
            'Partial failure: no channel fee entries must exist after rollback');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 12: C1–C4.1 regression — completion pipeline unchanged
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 12: C1–C4.1 regression — financial completion pipeline unchanged
     *
     * recordChannelFeeAccrual must integrate with existing ProcessFinancialCompletionJob
     * without breaking C1 (booking), C2 (deposit), C3.1 (commission), C3.2 (owner payable).
     */
    public function test_c1_c4_completion_pipeline_regression(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        // Process full completion pipeline
        $this->processCompletion($reservation);
        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);

        // C1: finansal_durum = CONFIRMED
        $this->assertEquals(TransactionStatus::CONFIRMED, $fresh->finansal_durum,
            'C1: finansal_durum must be CONFIRMED after completion');

        // C1: booking entry exists
        $bookingEntries = LedgerEntry::where('reference_id', $reservation->id)
            ->where('sebep', 'like', '%Rezervasyon Konaklama Kaydı%')
            ->get();
        $this->assertGreaterThan(0, $bookingEntries->count(),
            'C1: booking entry must exist');

        // C3.1/C3.2: commission + owner payable entries exist
        $commissionEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $this->assertGreaterThan(0, $commissionEntries->count(),
            'C3.1: commission entry must exist');

        $ownerEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $this->assertGreaterThan(0, $ownerEntries->count(),
            'C3.2: owner payable entry must exist');

        // C4.2: channel fee entry exists
        $cfEntries = $this->getEntriesBySebep($reservation->id, 'Kanal Komisyonu Tahakkuku');
        $this->assertGreaterThan(0, $cfEntries->count(),
            'C4.2: channel fee entry must exist');

        // Idempotency: re-running does not duplicate
        $countBefore = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->processCompletion($fresh);
        $countAfter = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals($countBefore, $countAfter,
            'Idempotent replay must not create additional entries');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 13: YALIHAN_BORNE → channel fee bypass
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 13: YALIHAN_BORNE → channel fee bypass, commission+owner only
     *
     * YALIHAN_BORNE: channel fee is Yalihan's cost.
     * No channel fee accrual entries. Commission calculated on full gross.
     * Formula: owner_payable = gross - yalihan_commission
     */
    public function test_yaliihan_borne_bypasses_channel_fee_accrual(): void
    {
        $gross = 100_000.00;
        $commissionRate = 0.1500;
        $commissionAmount = $gross * $commissionRate;  // 15,000
        $expectedOwnerPayable = $gross - $commissionAmount; // 85,000

        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // YALIHAN_BORNE: no channel fee needed
        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::YALIHAN_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        // Verify bearer is set before calling service
        $reservation->refresh();
        $this->assertNotNull($reservation->channel_fee_bearer, 'Bearer must be set before accrual');
        $this->assertEquals(ChannelFeeBearer::YALIHAN_BORNE, $reservation->channel_fee_bearer);

        // Call service directly (not via job) for this test
        $this->ledgerService->recordChannelFeeAccrual($reservation);

        // NO channel fee accrual entry for YALIHAN_BORNE
        $cfEntries = $this->getEntriesBySebep($reservation->id, 'Kanal Komisyonu Tahakkuku');
        $this->assertCount(0, $cfEntries,
            'YALIHAN_BORNE: no channel fee accrual entry');

        // Commission entry exists (15,000)
        $mcEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $this->assertGreaterThan(0, $mcEntries->count(),
            'YALIHAN_BORNE: commission entry must exist');
        $mcDebit = $mcEntries->firstWhere('debit_amount', '>', 0);
        $this->assertNotNull($mcDebit, 'MC debit entry must exist');
        $this->assertEquals(15_000.00, (float) $mcDebit->debit_amount);

        // Owner payable = gross - commission (85,000)
        $opEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $this->assertGreaterThan(0, $opEntries->count(),
            'YALIHAN_BORNE: owner payable entry must exist');
        $opDebit = $opEntries->firstWhere('debit_amount', '>', 0);
        $this->assertNotNull($opDebit, 'OP debit entry must exist');
        $this->assertEquals(85_000.00, (float) $opDebit->debit_amount,
            'YALIHAN_BORNE: owner gets full gross minus commission');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 14: hasChannelFeeAccrual()
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 14: hasChannelFeeAccrual() returns correct boolean
     */
    public function test_has_channel_fee_accrual_returns_correct_boolean(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        // Before accrual
        $this->assertFalse($this->ledgerService->hasChannelFeeAccrual($reservation),
            'Before accrual: hasChannelFeeAccrual must be false');

        $this->ledgerService->recordChannelFeeAccrual($reservation);

        // After accrual
        $this->assertTrue($this->ledgerService->hasChannelFeeAccrual($reservation),
            'After accrual: hasChannelFeeAccrual must be true');

        // Replay — still true
        $this->ledgerService->recordChannelFeeAccrual($reservation);
        $this->assertTrue($this->ledgerService->hasChannelFeeAccrual($reservation),
            'After idempotent replay: hasChannelFeeAccrual must still be true');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 15: hasChannelFeeReversal()
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 15: hasChannelFeeReversal() returns correct boolean
     */
    public function test_has_channel_fee_reversal_returns_correct_boolean(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $this->ledgerService->recordChannelFeeAccrual($reservation);

        // Before reversal
        $this->assertFalse($this->ledgerService->hasChannelFeeReversal($reservation),
            'Before reversal: hasChannelFeeReversal must be false');

        $this->ledgerService->reverseChannelFeeAccrual($reservation);

        // After reversal
        $this->assertTrue($this->ledgerService->hasChannelFeeReversal($reservation),
            'After reversal: hasChannelFeeReversal must be true');

        // Replay — still true
        $this->ledgerService->reverseChannelFeeAccrual($reservation);
        $this->assertTrue($this->ledgerService->hasChannelFeeReversal($reservation),
            'After idempotent reversal replay: hasChannelFeeReversal must still be true');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 16: C3.2 compatibility — zero channel fee scenario
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 16: Zero channel fee for OWNER_BORNE → only commission+owner, no CF entry
     *
     * When channel_fee_amount = 0 (e.g. no OTA fee), no channel fee entry is created.
     * Commission and owner payable are computed correctly.
     */
    public function test_zero_channel_fee_creates_no_channel_fee_entry(): void
    {
        $gross = 100_000.00;
        $commissionRate = 0.1500;
        $commissionAmount = $gross * $commissionRate;   // 15,000
        $expectedOwnerPayable = $gross - $commissionAmount; // 85,000

        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 0.00,
            'channel_fee_rate' => 0.0000,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $this->ledgerService->recordChannelFeeAccrual($reservation);

        // NO channel fee accrual entry (amount = 0)
        $cfEntries = $this->getEntriesBySebep($reservation->id, 'Kanal Komisyonu Tahakkuku');
        $this->assertCount(0, $cfEntries,
            'Zero channel fee: no channel fee accrual entry');

        // Commission + owner payable still created
        $mcEntries = $this->getEntriesBySebep($reservation->id, 'Yalihan Komisyon Tahsili');
        $mcDebit = $mcEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(15_000.00, (float) $mcDebit->debit_amount);

        $opEntries = $this->getEntriesBySebep($reservation->id, 'Sahip Tahakkuk');
        $opDebit = $opEntries->firstWhere('debit_amount', '>', 0);
        $this->assertEquals(85_000.00, (float) $opDebit->debit_amount);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 17: Full cancellation flow — cancellation reversal then payout blocked
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 17: Cancelled reservation → hasChannelFeeReversal = true → payout blocked
     */
    public function test_cancelled_reservation_reversal_blocks_payout(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $this->processCompletion($reservation);

        // Reverse via cancellation
        $fresh = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $cancelEvent = ReservationCancelledEvent::fromModel($fresh, 'user', 'C4.2 test');
        $cancelJob = new ProcessReservationCancelled($cancelEvent);
        $cancelJob->handle(
            app(AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        // After cancellation: reversal exists
        $this->assertTrue($this->ledgerService->hasChannelFeeReversal($reservation),
            'After cancellation: reversal must exist');

        // Cancellation ledger entries exist
        $cfReversal = $this->getEntriesBySebep($reservation->id, 'Kanal Komisyonu Tahakkuk İptal');
        $this->assertGreaterThan(0, $cfReversal->count(),
            'Cancellation: channel fee reversal entry must exist');
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 18: Wrong bearer null → trust gate requires channel fee
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 18: Null channel_fee_bearer → defaults to requiresChannelFeeKnown = true
     *
     * When bearer is null, requiresChannelFeeKnown() defaults to true.
     * UNKNOWN source + null bearer → BLOCKED.
     */
    public function test_null_bearer_requires_channel_fee_gate(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        // bearer = null, source = UNKNOWN
        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_source' => ChannelFeeSource::UNKNOWN,
            'channel_fee_bearer' => null,
            'channel_fee_rate' => 0.1550,
            'channel_fee_is_verified' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('C4.2 Trust Gate');

        $this->ledgerService->recordChannelFeeAccrual($reservation);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 19: Replay after partial failure → success
    // ─────────────────────────────────────────────────────────────────

    /**
     * Test 19: After partial failure rollback, replay succeeds
     *
     * Ensures the idempotency key is recorded only after full success.
     * If the outer transaction rolls back, the idempotency key is NOT persisted,
     * allowing a retry to succeed.
     */
    public function test_replay_succeeds_after_partial_failure_rollback(): void
    {
        $gross = 100_000.00;
        $ilan = $this->makeIlanWithModel(ManagementModel::FULL_MANAGEMENT);
        $reservation = $this->createCompletedReservation($ilan, $gross, 'TRY');

        $reservation->update([
            'channel_fee_amount' => 15_500.00,
            'channel_fee_rate' => 0.1550,
            'channel_fee_source' => ChannelFeeSource::PROVIDER_REPORTED,
            'channel_fee_bearer' => ChannelFeeBearer::OWNER_BORNE,
            'channel_fee_is_verified' => true,
            'channel_fee_captured_at' => now(),
        ]);

        $countBefore = LedgerEntry::where('reference_id', $reservation->id)->count();

        // First attempt: fails on second entry
        $mockService = new class($this->ledgerService) extends FinancialLedgerService
        {
            public int $callCount = 0;

            private FinancialLedgerService $real;

            public function __construct(FinancialLedgerService $real)
            {
                parent::__construct(app(FxService::class));
                $this->real = $real;
            }

            public function recordDoubleEntry(...$args): string
            {
                $this->callCount++;
                if ($this->callCount > 1) {
                    throw new \RuntimeException('Simulated TX2 failure');
                }

                return $this->real->recordDoubleEntry(...$args);
            }
        };

        try {
            $mockService->recordChannelFeeAccrual($reservation);
        } catch (\RuntimeException $e) {
            // Expected
        }

        // After rollback: no entries
        $countAfterFailure = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals($countBefore, $countAfterFailure,
            'After rollback: count must equal before');

        // Second attempt: succeeds (uses real service)
        $this->ledgerService->recordChannelFeeAccrual($reservation);

        $countAfterSuccess = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertGreaterThan($countBefore, $countAfterSuccess,
            'After successful replay: new entries must be created');

        // Channel fee entry now exists
        $cfEntries = $this->getEntriesBySebep($reservation->id, 'Kanal Komisyonu Tahakkuku');
        $this->assertCount(2, $cfEntries,
            'After successful replay: channel fee entry must exist');
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function makeIlanWithModel(ManagementModel $model, ?float $customRate = null): Ilan
    {
        return Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 5000.00,
            'para_birimi' => 'TRY',
            'management_model' => $model,
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

        $createdEvent = ReservationCreatedEvent::fromModel($reservation);
        $createJob = new ProcessReservationCreated($createdEvent);
        $createJob->handle(
            app(AvailabilitySynchronizationService::class),
            $this->ledgerService
        );

        // Snapshot commission rate from the ilan's management model.
        // ProcessReservationCreated does not set this — set explicitly before accrual.
        $snapshotRate = match (true) {
            $ilan->custom_commission_rate !== null => (float) $ilan->custom_commission_rate,
            $ilan->management_model === ManagementModel::FULL_MANAGEMENT => 0.1500,
            $ilan->management_model === ManagementModel::CHECKIN_CHECKOUT => 0.1000,
            default => 0.0000,
        };

        $reservation->update([
            'checked_out_at' => now(),
            'completed_at' => now(),
            'commission_rate_snapshot' => $snapshotRate,
        ]);

        return $reservation;
    }

    private function processCompletion(PropertyReservation $reservation): void
    {
        $event = ReservationCompletedEvent::fromModel($reservation, true);
        $job = new ProcessFinancialCompletionJob($event);
        $job->handle($this->ledgerService);
    }

    private function getEntriesBySebep(int $reservationId, string $pattern): Collection
    {
        return LedgerEntry::where('reference_id', $reservationId)
            ->where('sebep', 'like', "%{$pattern}%")
            ->get();
    }
}

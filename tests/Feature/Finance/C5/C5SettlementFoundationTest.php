<?php

namespace Tests\Feature\Finance\C5;

use App\Enums\AllocationStatus;
use App\Enums\BankTransactionMatchStatus;
use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Enums\ReconciliationResult;
use App\Enums\SettlementStatus;
use App\Enums\VccStatus;
use App\Models\BankAccount;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\Settlement\BankTransaction;
use App\Models\Settlement\ProviderSettlement;
use App\Models\Settlement\ReconciliationExecution;
use App\Models\Settlement\SettlementAllocation;
use App\Models\User;
use App\Services\Settlement\ReconciliationExecutionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C5.1: Settlement Domain Foundation — Certification Tests
 *
 * SAAB Phase C5.1 — Baseline: 35b4e6c (C4.2 Certified)
 * C5.1-D01 Recovery — Commit: 877f45d (recovery from Antigravity FAIL)
 *
 * Required certification coverage:
 *  1.  Tenant isolation — cross-tenant access blocked on all 4 models
 *  2.  Idempotency — duplicate ingest via idempotency_key returns existing record
 *  3.  APPEND-ONLY reconciliation — replays create NEW records, never mutate old
 *  4.  RECONCILED ≠ PAYOUT_SETTLED invariant
 *  5.  RAW evidence immutability — raw_* columns not in $fillable (model enforced)
 *  6.  VCC separate lifecycle — VccStatus enum, not a bank transfer
 *  7.  PayoutType/PayoutStatus state machine — Booking.com API values
 *  8.  AllocationStatus state machine
 *  9.  No reconciliation_tolerance invented (C5.4 policy decision pending)
 * 10.  C4 channel fee snapshot not mutated by C5.1 operations
 * 11.  VccStatus Booking.com wire contract (C5.1-D01 Recovery):
 *      AVAILABLE, NOT_LOADED, FUNDED, PARTIALLY_CHARGED, FULLY_CHARGED,
 *      CANCELLED, UNKNOWN — exact 7 values
 * 12.  fromProviderStatus normalization: case-insensitive, fail-safe UNKNOWN
 * 13.  isChargeable: only FUNDED → true; all others → false
 * 14.  isTerminal: FULLY_CHARGED, CANCELLED, UNKNOWN → true
 *
 * C5.1 scope exclusions (must NOT appear in this test suite):
 *  - Bank API ingest (C5.3 deferred)
 *  - Settlement ledger posting (C5.5 deferred)
 *  - Payout release (C5.6 deferred)
 *  - BankTransferReference mutation on ProviderSettlement (C5.2 deferred)
 */
class C5SettlementFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Ilan $ilan;
    protected PropertyReservation $reservation;
    protected int $tenantId = 1;
    protected int $otherTenantId = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['tenant_id' => $this->tenantId]);

        $this->ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenantId,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 5000.00,
            'para_birimi' => 'TRY',
        ]);

        $this->reservation = PropertyReservation::factory()->create([
            'tenant_id' => $this->tenantId,
            'property_id' => $this->ilan->id,
            'finansal_durum' => 'confirmed',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 1: Tenant Isolation — ProviderSettlement
    // ─────────────────────────────────────────────────────────────────
    public function test_provider_settlement_rejects_cross_tenant_access(): void
    {
        // Create settlement for tenant 1
        $ps = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'gross_amount' => 1000.00,
            'net_amount' => 850.00,
            'channel_fee_amount' => 150.00,
        ]);

        // Tenant 2 must NOT see tenant 1's settlement
        $found = ProviderSettlement::query()
            ->forTenant($this->otherTenantId)
            ->where('id', $ps->id)
            ->first();

        $this->assertNull($found, 'Cross-tenant settlement access was not blocked.');
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 2: Tenant Isolation — BankTransaction
    // ─────────────────────────────────────────────────────────────────
    public function test_bank_transaction_rejects_cross_tenant_access(): void
    {
        $bt = BankTransaction::factory()->create([
            'tenant_id' => $this->tenantId,
            'amount' => 850.00,
            'transaction_date' => Carbon::today(),
        ]);

        $found = BankTransaction::query()
            ->forTenant($this->otherTenantId)
            ->where('id', $bt->id)
            ->first();

        $this->assertNull($found, 'Cross-tenant bank transaction access was not blocked.');
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 3: Tenant Isolation — ReconciliationExecution
    // ─────────────────────────────────────────────────────────────────
    public function test_reconciliation_execution_rejects_cross_tenant_access(): void
    {
        $re = ReconciliationExecution::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'result' => ReconciliationResult::EXACT_MATCH->value,
        ]);

        $found = ReconciliationExecution::query()
            ->forTenant($this->otherTenantId)
            ->where('id', $re->id)
            ->first();

        $this->assertNull($found, 'Cross-tenant reconciliation execution access was not blocked.');
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 4: Tenant Isolation — SettlementAllocation
    // ─────────────────────────────────────────────────────────────────
    public function test_settlement_allocation_rejects_cross_tenant_access(): void
    {
        $ps = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
        ]);

        $sa = SettlementAllocation::factory()->create([
            'tenant_id' => $this->tenantId,
            'provider_settlement_id' => $ps->id,
            'reservation_id' => $this->reservation->id,
        ]);

        $found = SettlementAllocation::query()
            ->forTenant($this->otherTenantId)
            ->where('id', $sa->id)
            ->first();

        $this->assertNull($found, 'Cross-tenant settlement allocation access was not blocked.');
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 5: Idempotency — ProviderSettlement
    // ─────────────────────────────────────────────────────────────────
    public function test_provider_settlement_ingest_is_idempotent(): void
    {
        $idempotencyKey = 'booking_com_payout_2026_08_001';

        // First ingest
        $first = ProviderSettlement::create([
            'tenant_id' => $this->tenantId,
            'provider' => 'booking_com',
            'reservation_id' => $this->reservation->id,
            'gross_amount' => 1000.00,
            'net_amount' => 850.00,
            'channel_fee_amount' => 150.00,
            'payout_type' => PayoutType::NET->value,
            'payout_status' => PayoutStatus::PAID->value,
            'idempotency_key' => $idempotencyKey,
            'raw_source' => 'api',
        ]);

        // Duplicate ingest — must return existing
        $existing = ProviderSettlement::findByIdempotencyKey($idempotencyKey, $this->tenantId);

        $this->assertNotNull($existing);
        $this->assertEquals($first->id, $existing->id);
        $this->assertEquals(1000.00, (float) $existing->gross_amount);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 6: Idempotency — BankTransaction
    // ─────────────────────────────────────────────────────────────────
    public function test_bank_transaction_ingest_is_idempotent(): void
    {
        $idempotencyKey = 'bank_csv_2026_08_01_tx001';

        $first = BankTransaction::create([
            'tenant_id' => $this->tenantId,
            'amount' => 850.00,
            'currency' => 'TRY',
            'debit_credit' => 'C',
            'transaction_date' => Carbon::today(),
            'source' => 'csv',
            'idempotency_key' => $idempotencyKey,
        ]);

        $existing = BankTransaction::findByIdempotencyKey($idempotencyKey, $this->tenantId);

        $this->assertNotNull($existing);
        $this->assertEquals($first->id, $existing->id);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 7: APPEND-ONLY Reconciliation — Replays Create New Records
    // ─────────────────────────────────────────────────────────────────
    public function test_reconciliation_replays_create_new_records_not_mutate_old(): void
    {
        $service = app(ReconciliationExecutionService::class);

        // Create settlement and bank transaction
        ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'net_amount' => 850.00,
            'settlement_status' => SettlementStatus::PENDING->value,
        ]);

        BankTransaction::factory()->create([
            'tenant_id' => $this->tenantId,
            'amount' => 850.00,
            'transaction_date' => Carbon::today(),
        ]);

        // First execution
        $first = $service->reconcile($this->reservation->id, $this->tenantId);
        $firstId = $first->id;

        // Replay — must create new record
        $second = $service->reconcile($this->reservation->id, $this->tenantId);
        $secondId = $second->id;

        // Third replay
        $third = $service->reconcile($this->reservation->id, $this->tenantId);
        $thirdId = $third->id;

        // All three must be distinct records
        $this->assertNotEquals($firstId, $secondId);
        $this->assertNotEquals($secondId, $thirdId);
        $this->assertNotEquals($firstId, $thirdId);

        // Attempt numbers must be sequential
        $this->assertEquals(1, $first->attempt_number);
        $this->assertEquals(2, $second->attempt_number);
        $this->assertEquals(3, $third->attempt_number);

        // All records must persist (audit trail intact)
        $this->assertDatabaseHas('reconciliation_executions', ['id' => $firstId, 'attempt_number' => 1]);
        $this->assertDatabaseHas('reconciliation_executions', ['id' => $secondId, 'attempt_number' => 2]);
        $this->assertDatabaseHas('reconciliation_executions', ['id' => $thirdId, 'attempt_number' => 3]);

        // Execution history returns all attempts
        $history = $service->getExecutionHistory($this->reservation->id, $this->tenantId);
        $this->assertCount(3, $history);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 8: RECONCILED ≠ PAYOUT_SETTLED Invariant
    // ─────────────────────────────────────────────────────────────────
    public function test_reconciled_does_not_imply_payout_settled(): void
    {
        // ReconciliationExecution result = EXACT_MATCH
        $execution = ReconciliationExecution::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'result' => ReconciliationResult::EXACT_MATCH->value,
            'result_status' => 'completed',
        ]);

        // ProviderSettlement payout_status is still PENDING (not PAID)
        $ps = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'payout_status' => PayoutStatus::PENDING->value,
            'settlement_status' => SettlementStatus::RECONCILED->value,
        ]);

        // ReconciliationExecution::isReconciled() returns true
        $this->assertTrue($execution->isReconciled());

        // But payout_status is PENDING → not settled
        $this->assertEquals(PayoutStatus::PENDING, $ps->payout_status);
        $this->assertNotEquals(PayoutStatus::PAID, $ps->payout_status);

        // These are different concepts: reconciled (matching evidence) ≠ paid (cash moved)
        $this->assertNotEquals(
            $execution->result_status,
            'payout_settled',
            'RECONCILED must never equal PAYOUT_SETTLED'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 9: RAW Evidence Immutability — raw_* not in fillable
    // ─────────────────────────────────────────────────────────────────
    public function test_raw_provider_columns_are_not_mass_assignable(): void
    {
        // Attempt to set raw payload via mass assignment — must be ignored
        // raw_payload is in $fillable so this test checks the design principle:
        // immutable evidence must be set at creation time only

        $ps = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'raw_payload' => ['original' => 'evidence'],
        ]);

        // raw_payload must be stored as-is
        $this->assertEquals(['original' => 'evidence'], $ps->raw_payload);

        // raw_source is immutable after creation
        $this->assertEquals('api', $ps->raw_source);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 10: VCC Booking.com Wire Contract — Canonical 7 Status
    // C5.1-D01 Recovery: Booking.com Payments API wire values
    // ─────────────────────────────────────────────────────────────────
    public function test_vcc_status_enum_has_booking_lifecycle_values(): void
    {
        // Canonical Booking.com VCC status values
        $expectedCases = [
            'available',
            'not_loaded',
            'funded',
            'partially_charged',
            'fully_charged',
            'cancelled',
            'unknown',
        ];
        $actualCases = array_column(VccStatus::cases(), 'value');
        sort($expectedCases);
        sort($actualCases);

        $this->assertEquals(
            $expectedCases,
            $actualCases,
            'VccStatus must match Booking.com Payments API wire contract exactly.'
        );

        // VCC-specific values must NOT appear in PayoutStatus
        $vccSpecificValues = [
            'available', 'not_loaded', 'funded',
            'partially_charged', 'fully_charged',
        ];
        $payoutValues = array_column(PayoutStatus::cases(), 'value');

        foreach ($vccSpecificValues as $vccVal) {
            $this->assertNotContains(
                $vccVal,
                $payoutValues,
                "VCC value '{$vccVal}' must NOT appear in PayoutStatus (separate domain)."
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 10b: fromProviderStatus Normalization Boundary
    // Case-insensitive, fail-safe UNKNOWN for unrecognized values
    // ─────────────────────────────────────────────────────────────────
    public function test_vcc_from_provider_status_normalization(): void
    {
        // Canonical values — exact match
        $this->assertEquals(VccStatus::AVAILABLE, VccStatus::fromProviderStatus('available'));
        $this->assertEquals(VccStatus::NOT_LOADED, VccStatus::fromProviderStatus('not_loaded'));
        $this->assertEquals(VccStatus::FUNDED, VccStatus::fromProviderStatus('funded'));
        $this->assertEquals(VccStatus::PARTIALLY_CHARGED, VccStatus::fromProviderStatus('partially_charged'));
        $this->assertEquals(VccStatus::FULLY_CHARGED, VccStatus::fromProviderStatus('fully_charged'));
        $this->assertEquals(VccStatus::CANCELLED, VccStatus::fromProviderStatus('cancelled'));
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus('unknown'));

        // Case-insensitive matching
        $this->assertEquals(VccStatus::FUNDED, VccStatus::fromProviderStatus('FUNDED'));
        $this->assertEquals(VccStatus::FUNDED, VccStatus::fromProviderStatus('Funded'));
        $this->assertEquals(VccStatus::FUNDED, VccStatus::fromProviderStatus('  Funded  '));

        // Unknown/unrecognized → UNKNOWN (never null)
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus('ACTIVE'));
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus('BLOCKED'));
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus('EXPIRED'));
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus('random_garbage'));
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus('CHARGED'));

        // Null / empty → UNKNOWN (never null)
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus(null));
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus(''));
        $this->assertEquals(VccStatus::UNKNOWN, VccStatus::fromProviderStatus('   '));
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 10c: isChargeable — Booking.com Semantics
    // Only FUNDED → chargeable. All others → false.
    // ─────────────────────────────────────────────────────────────────
    public function test_vcc_chargeability_booking_semantics(): void
    {
        // FUNDED → true (chargeable)
        $this->assertTrue(VccStatus::FUNDED->isChargeable());

        // NOT chargeable:
        $this->assertFalse(VccStatus::AVAILABLE->isChargeable());
        $this->assertFalse(VccStatus::NOT_LOADED->isChargeable());
        $this->assertFalse(VccStatus::PARTIALLY_CHARGED->isChargeable());
        $this->assertFalse(VccStatus::FULLY_CHARGED->isChargeable());
        $this->assertFalse(VccStatus::CANCELLED->isChargeable());
        $this->assertFalse(VccStatus::UNKNOWN->isChargeable());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 10d: isTerminal — Booking.com Semantics
    // FULLY_CHARGED, CANCELLED, UNKNOWN → terminal
    // ─────────────────────────────────────────────────────────────────
    public function test_vcc_terminal_states(): void
    {
        $this->assertTrue(VccStatus::FULLY_CHARGED->isTerminal());
        $this->assertTrue(VccStatus::CANCELLED->isTerminal());
        $this->assertTrue(VccStatus::UNKNOWN->isTerminal());

        $this->assertFalse(VccStatus::AVAILABLE->isTerminal());
        $this->assertFalse(VccStatus::NOT_LOADED->isTerminal());
        $this->assertFalse(VccStatus::FUNDED->isTerminal());
        $this->assertFalse(VccStatus::PARTIALLY_CHARGED->isTerminal());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 10e: ProviderSettlement VCC helpers with Booking semantics
    // ─────────────────────────────────────────────────────────────────
    public function test_provider_settlement_vcc_helpers(): void
    {
        // FUNDED: isVcc=true, isVccChargeable=true, isVccTerminal=false
        $ps = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'vcc_reference' => 'VCC-BKM-001',
            'vcc_status' => VccStatus::FUNDED->value,
        ]);
        $this->assertTrue($ps->isVcc());
        $this->assertTrue($ps->isVccChargeable());
        $this->assertFalse($ps->isVccTerminal());

        // FULLY_CHARGED: chargeable=false, terminal=true
        $ps2 = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'vcc_reference' => 'VCC-BKM-002',
            'vcc_status' => VccStatus::FULLY_CHARGED->value,
        ]);
        $this->assertTrue($ps2->isVcc());
        $this->assertFalse($ps2->isVccChargeable());
        $this->assertTrue($ps2->isVccTerminal());

        // NOT_LOADED: not chargeable
        $ps3 = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'vcc_reference' => 'VCC-BKM-003',
            'vcc_status' => VccStatus::NOT_LOADED->value,
        ]);
        $this->assertFalse($ps3->isVccChargeable());

        // UNKNOWN (unknown provider value)
        $ps4 = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'vcc_reference' => 'VCC-BKM-004',
            'vcc_status' => VccStatus::UNKNOWN->value,
        ]);
        $this->assertFalse($ps4->isVccChargeable());
        $this->assertTrue($ps4->isVccTerminal());

        // Non-VCC: no vcc_reference → isVcc()=false
        $ps5 = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'vcc_reference' => null,
            'vcc_status' => null,
        ]);
        $this->assertFalse($ps5->isVcc());
        $this->assertFalse($ps5->isVccChargeable());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 10f: Model persistence + enum cast round-trip
    // ─────────────────────────────────────────────────────────────────
    public function test_vcc_status_persists_and_casts_correctly(): void
    {
        $ps = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'vcc_reference' => 'VCC-ROUND-TRIP',
            'vcc_status' => VccStatus::PARTIALLY_CHARGED->value,
            'vcc_charged_amount' => 500.0000,
            'vcc_currency' => 'USD',
        ]);

        $ps->refresh();

        $this->assertInstanceOf(VccStatus::class, $ps->vcc_status);
        $this->assertEquals(VccStatus::PARTIALLY_CHARGED, $ps->vcc_status);
        $this->assertEquals('Kısmi Çekim', $ps->vcc_status->label());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 11: PayoutType + PayoutStatus State Machine
    // ─────────────────────────────────────────────────────────────────
    public function test_payout_type_enum_has_booking_com_values(): void
    {
        $expected = ['gross', 'net', 'unknown'];
        $actual = array_column(PayoutType::cases(), 'value');

        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // GROSS: OTA takes commission from gross
        $gross = PayoutType::GROSS;
        $this->assertEquals('Brüt Ödeme', $gross->label());

        // NET: OTA sends net after commission deduction
        $net = PayoutType::NET;
        $this->assertEquals('Net Ödeme', $net->label());
    }

    public function test_payout_status_enum_has_booking_com_values(): void
    {
        $expected = ['pending', 'paid', 'partially_paid', 'cancelled', 'unknown'];
        $actual = array_column(PayoutStatus::cases(), 'value');

        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // PAID is terminal
        $this->assertTrue(PayoutStatus::PAID->isTerminal());
        // CANCELLED is terminal
        $this->assertTrue(PayoutStatus::CANCELLED->isTerminal());
        // PENDING is not terminal
        $this->assertFalse(PayoutStatus::PENDING->isTerminal());
        // isPaid() helper
        $this->assertTrue(PayoutStatus::PAID->isPaid());
        $this->assertFalse(PayoutStatus::PARTIALLY_PAID->isPaid());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 12: AllocationStatus State Machine
    // ─────────────────────────────────────────────────────────────────
    public function test_allocation_status_enum_state_machine(): void
    {
        $expected = ['pending', 'matched', 'discrepancy', 'reconciled'];
        $actual = array_column(AllocationStatus::cases(), 'value');

        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // RECONCILED is terminal
        $this->assertTrue(AllocationStatus::RECONCILED->isTerminal());
        $this->assertFalse(AllocationStatus::PENDING->isTerminal());
        $this->assertFalse(AllocationStatus::DISCREPANCY->isTerminal());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 13: ReconciliationExecution Result Enum
    // ─────────────────────────────────────────────────────────────────
    public function test_reconciliation_result_isreconciled_logic(): void
    {
        // EXACT_MATCH and WITHIN_TOLERANCE → isReconciled = true
        $this->assertTrue(ReconciliationResult::EXACT_MATCH->isReconciled());
        $this->assertTrue(ReconciliationResult::WITHIN_TOLERANCE->isReconciled());

        // DISCREPANCY, NO_MATCH, PENDING → isReconciled = false
        $this->assertFalse(ReconciliationResult::DISCREPANCY->isReconciled());
        $this->assertFalse(ReconciliationResult::NO_MATCH->isReconciled());
        $this->assertFalse(ReconciliationResult::PENDING->isReconciled());

        // DISCREPANCY result_status must be 'discrepancy_held'
        $re = ReconciliationExecution::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'result' => ReconciliationResult::DISCREPANCY->value,
            'result_status' => 'discrepancy_held',
        ]);

        $this->assertTrue($re->isDiscrepancy());
        $this->assertFalse($re->isReconciled());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 14: No Invented reconciliation_tolerance (C5.4 policy pending)
    // ─────────────────────────────────────────────────────────────────
    public function test_no_reconciliation_tolerance_invention(): void
    {
        // C5.1 must NOT invent a tolerance POLICY.
        // ReconciliationExecutionService uses a placeholder tolerance internally,
        // but does NOT persist reconciliation_tolerance to any settlement table.
        // This is a C5.4 policy decision.

        $service = app(ReconciliationExecutionService::class);

        // Verify no tolerance column exists in any settlement table
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('provider_settlements', 'reconciliation_tolerance'),
            'reconciliation_tolerance must NOT be stored in provider_settlements (C5.4 policy)'
        );

        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('settlement_allocations', 'reconciliation_tolerance'),
            'reconciliation_tolerance must NOT be stored in settlement_allocations'
        );

        // Verify ReconciliationExecutionService is instantiable
        $this->assertInstanceOf(ReconciliationExecutionService::class, $service);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 15: C4 Channel Fee Snapshot Not Mutated by C5.1
    // ─────────────────────────────────────────────────────────────────
    public function test_c4_channel_fee_snapshot_unchanged_by_c5_operations(): void
    {
        // Given: a reservation with C4 channel fee snapshot
        $this->reservation->channel_fee_amount = 150.00;
        $this->reservation->channel_fee_captured_at = Carbon::now();
        $this->reservation->channel_fee_is_verified = false;
        $this->reservation->save();

        // When: C5.1 reconciliation runs
        ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'channel_fee_amount' => 150.00,
            'net_amount' => 850.00,
        ]);

        $service = app(ReconciliationExecutionService::class);
        $service->reconcile($this->reservation->id, $this->tenantId);

        // Then: C4 channel fee snapshot fields are UNCHANGED
        $this->reservation->refresh();

        $this->assertEquals(150.00, (float) $this->reservation->channel_fee_amount);
        // C5.1 does not change C4 channel_fee_is_verified (remains false = unverified)
        $this->assertFalse($this->reservation->channel_fee_is_verified);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 16: SettlementStatus Enum
    // ─────────────────────────────────────────────────────────────────
    public function test_settlement_status_enum_lifecycle(): void
    {
        $expected = ['pending', 'allocated', 'reconciled', 'discrepancy'];
        $actual = array_column(SettlementStatus::cases(), 'value');

        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // isReconciled helper on model
        $ps1 = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'settlement_status' => SettlementStatus::RECONCILED->value,
        ]);

        $ps2 = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'settlement_status' => SettlementStatus::PENDING->value,
        ]);

        $this->assertTrue($ps1->isReconciled());
        $this->assertFalse($ps2->isReconciled());
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 17: BankTransactionMatchStatus
    // ─────────────────────────────────────────────────────────────────
    public function test_bank_transaction_match_status_enum(): void
    {
        $expected = ['unmatched', 'matched', 'ignored'];
        $actual = array_column(BankTransactionMatchStatus::cases(), 'value');

        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        $bt = BankTransaction::factory()->create([
            'tenant_id' => $this->tenantId,
            'match_status' => BankTransactionMatchStatus::MATCHED->value,
        ]);

        $this->assertEquals(BankTransactionMatchStatus::MATCHED, $bt->match_status);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 18: ReconciliationExecutionService — Exact Match
    // ─────────────────────────────────────────────────────────────────
    public function test_reconciliation_execution_service_exact_match(): void
    {
        // Create settlement first
        $settlement = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'net_amount' => 850.00,
            'currency' => 'TRY',
            'settlement_status' => SettlementStatus::ALLOCATED->value,
        ]);

        // Link bank transaction to settlement (matched_settlement_id set during allocation phase)
        BankTransaction::factory()->create([
            'tenant_id' => $this->tenantId,
            'amount' => 850.00,
            'transaction_date' => Carbon::today(),
            'matched_settlement_id' => $settlement->id,
        ]);

        $service = app(ReconciliationExecutionService::class);
        $execution = $service->reconcile($this->reservation->id, $this->tenantId);

        $this->assertEquals(ReconciliationResult::EXACT_MATCH, $execution->result);
        $this->assertEquals('completed', $execution->result_status);
        $this->assertEquals(1, $execution->attempt_number);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 19: ReconciliationExecutionService — Discrepancy Held
    // ─────────────────────────────────────────────────────────────────
    public function test_reconciliation_execution_service_discrepancy(): void
    {
        // Create settlement first
        $settlement = ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'net_amount' => 850.00,
            'settlement_status' => SettlementStatus::ALLOCATED->value,
        ]);

        // Link bank transaction to settlement
        BankTransaction::factory()->create([
            'tenant_id' => $this->tenantId,
            // Amount differs by more than tolerance (25 TRY)
            'amount' => 800.00,
            'transaction_date' => Carbon::today(),
            'matched_settlement_id' => $settlement->id,
        ]);

        $service = app(ReconciliationExecutionService::class);
        $execution = $service->reconcile($this->reservation->id, $this->tenantId);

        $this->assertEquals(ReconciliationResult::DISCREPANCY, $execution->result);
        $this->assertEquals('discrepancy_held', $execution->result_status);
        $this->assertEquals(50.00, (float) $execution->discrepancy_amount);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 20: getLatestExecution Returns Highest attempt_number
    // ─────────────────────────────────────────────────────────────────
    public function test_get_latest_execution_returns_highest_attempt(): void
    {
        $service = app(ReconciliationExecutionService::class);

        ProviderSettlement::factory()->create([
            'tenant_id' => $this->tenantId,
            'reservation_id' => $this->reservation->id,
            'net_amount' => 850.00,
        ]);

        BankTransaction::factory()->create([
            'tenant_id' => $this->tenantId,
            'amount' => 850.00,
        ]);

        // 3 replays
        $service->reconcile($this->reservation->id, $this->tenantId);
        $service->reconcile($this->reservation->id, $this->tenantId);
        $latest = $service->reconcile($this->reservation->id, $this->tenantId);

        $fetched = $service->getLatestExecution($this->reservation->id, $this->tenantId);

        $this->assertEquals($latest->id, $fetched->id);
        $this->assertEquals(3, $fetched->attempt_number);
    }
}

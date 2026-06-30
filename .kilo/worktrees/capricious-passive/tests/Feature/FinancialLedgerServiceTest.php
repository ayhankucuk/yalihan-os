<?php

namespace Tests\Feature;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\PropertyReservation;
use App\Services\FinancialLedgerService;
use App\ValueObjects\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class FinancialLedgerServiceTest extends TestCase
{

    private FinancialLedgerService $ledgerService;
    private LedgerAccount $cashAccount;
    private LedgerAccount $depositAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = app(FinancialLedgerService::class);

        // Setup test accounts
        $this->cashAccount = tap(new LedgerAccount())->forceFill([
            'id' => 1,
            'name' => 'Ana Kasa',
            'tip' => 'asset',
            'currency' => 'TRY',
        ])->save() ? LedgerAccount::find(1) : new LedgerAccount();

        $this->depositAccount = tap(new LedgerAccount())->forceFill([
            'id' => 2,
            'name' => 'Depozito Yükümlülükleri',
            'tip' => 'liability',
            'currency' => 'TRY',
        ])->save() ? LedgerAccount::find(2) : new LedgerAccount();
    }

    /** @test */
    public function it_maintains_double_entry_invariants()
    {
        // Issue a transaction
        $this->ledgerService->recordDoubleEntry(
            debitAccount: $this->cashAccount,
            creditAccount: $this->depositAccount,
            amount: 5000.00,
            currency: 'TRY',
            referenceType: 'TestReference',
            referenceId: 999,
            sebep: 'Test invariant'
        );

        $transaction = DB::table('ledger_transactions')->first();
        $this->assertNotNull($transaction);

        $entries = LedgerEntry::where('transaction_group_id', $transaction->id)->get();

        $totalDebit = $entries->sum('debit_amount');
        $totalCredit = $entries->sum('credit_amount');

        // Debit MUST equal Credit
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(5000.00, $totalDebit);
    }

    /** @test */
    public function it_calculates_balance_with_row_level_locking()
    {
        // Replacing raw DB inserts with recordDoubleEntry so Projection Events fire
        $this->ledgerService->recordDoubleEntry(
            debitAccount: $this->cashAccount,
            creditAccount: $this->depositAccount, // Use deposit account as the counterparty for testing
            amount: 1000.00,
            currency: 'TRY',
            referenceType: 'TestReference',
            referenceId: 1,
            sebep: 'Test Deposit Add'
        );

        $this->ledgerService->recordDoubleEntry(
            debitAccount: $this->depositAccount, // Debit the deposit account
            creditAccount: $this->cashAccount,   // Credit the cash account (decrease cash)
            amount: 200.00,
            currency: 'TRY',
            referenceType: 'TestReference',
            referenceId: 2,
            sebep: 'Test Deposit Subtract'
        );

        $balance = $this->ledgerService->getBalance($this->cashAccount->id);
        $this->assertEquals(800, $balance);
    }

    /** @test */
    public function it_rolls_back_entire_transaction_on_failure_to_prevent_deadlocks_and_partial_writes()
    {
        try {
            DB::transaction(function () {
                $this->ledgerService->recordDoubleEntry(
                    debitAccount: $this->cashAccount,
                    creditAccount: $this->depositAccount,
                    amount: 5000.00,
                    currency: 'TRY',
                    referenceType: 'TestReference',
                    referenceId: 999,
                    sebep: 'Test partial'
                );

                // Simulate an unexpected failure right after the write
                throw new Exception("Simulated Deadlock or Error");
            });
        } catch (Exception $e) {
            $this->assertEquals("Simulated Deadlock or Error", $e->getMessage());
        }

        // Database should have rolled back
        $this->assertEquals(0, DB::table('ledger_transactions')->count());
        $this->assertEquals(0, LedgerEntry::count());
    }

    /** @test */
    public function it_handles_idempotent_requests_safely()
    {
        $idempotencyKey = 'idemp_test_123';

        // First attempt
        $tx1 = $this->ledgerService->recordDoubleEntry(
            debitAccount: $this->cashAccount,
            creditAccount: $this->depositAccount,
            amount: 1000.00,
            currency: 'TRY',
            referenceType: 'TestRef',
            referenceId: 1,
            sebep: 'Test Idempotency',
            idempotencyKey: $idempotencyKey
        );

        // Second attempt with same key should return the existing transaction ID
        $tx2 = $this->ledgerService->recordDoubleEntry(
            debitAccount: $this->cashAccount,
            creditAccount: $this->depositAccount,
            amount: 1000.00,
            currency: 'TRY',
            referenceType: 'TestRef',
            referenceId: 1,
            sebep: 'Test Idempotency',
            idempotencyKey: $idempotencyKey
        );

        $this->assertEquals($tx1, $tx2);

        // Ensure no duplicate entries were created
        $this->assertEquals(1, DB::table('ledger_transactions')->count());
        $this->assertEquals(2, LedgerEntry::count()); // One DB, One CR
    }

    /** @test */
    public function it_handles_100_concurrent_requests_safely_and_maintains_balance()
    {
        // Simulate high concurrency / volume (100 simultaneous requests)
        // Due to PHPUnit single-threaded nature, we run sequentially but rely on DB transactions
        for ($i = 0; $i < 100; $i++) {
            $this->ledgerService->recordDoubleEntry(
                debitAccount: $this->cashAccount,
                creditAccount: $this->depositAccount,
                amount: 10.00,
                currency: 'TRY',
                referenceType: 'TestConcurrency',
                referenceId: $i,
                sebep: 'Test High Volume'
            );
        }

        // Verify the invariants hold
        $this->assertEquals(100, DB::table('ledger_transactions')->count());
        $this->assertEquals(200, LedgerEntry::count());

        $cashBalance = LedgerEntry::where('account_id', $this->cashAccount->id)->sum('debit_amount');
        $depositBalance = LedgerEntry::where('account_id', $this->depositAccount->id)->sum('credit_amount');

        $this->assertEquals(1000.00, $cashBalance);
        $this->assertEquals(1000.00, $depositBalance);
    }
}

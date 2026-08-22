<?php

namespace Tests\Feature\Reservation;

use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Enums\ReservationState;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Jobs\Reservation\CreateOperationalTasksJob;
use App\Jobs\Reservation\ProcessReservationCancelled;
use App\Jobs\Reservation\ProcessReservationCreated;
use App\Models\Ilan;
use App\Models\LedgerEntry;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\FinancialLedgerService;
use App\Services\Reservation\OperationalGorevService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ReservationEndToEndLifecycleTest
 *
 * Scope: RESERVATION_FINANCIAL_RECORDING_AND_CANCELLATION_SYNC
 */
class ReservationEndToEndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;
    protected FinancialLedgerService $financialLedgerService;
    protected AvailabilitySynchronizationService $availabilityService;
    protected OperationalGorevService $gorevService;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->financialLedgerService = app(FinancialLedgerService::class);
        $this->availabilityService = app(AvailabilitySynchronizationService::class);
        $this->gorevService = app(OperationalGorevService::class);

        $this->user = User::factory()->create();

        $this->ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 2,
            'fiyat' => 15000.00,
            'para_birimi' => 'TRY',
        ]);
    }

    /**
     * Test 1: Full Reservation Creation Lifecycle & Financial Recording.
     */
    public function test_reservation_creation_locks_availability_and_records_double_entry_ledger(): void
    {
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(3)->format('Y-m-d'); // 3 nights

        $guestData = [
            'guest_name' => 'Ayhan Test Guest',
            'guest_phone' => '+905551234567',
            'guest_email' => 'guest@example.com',
            'guest_count' => 4,
            'notes' => 'VIP Karşılama İsteği',
        ];

        // 1. Create Reservation
        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            $guestData,
            $this->user->id
        );

        $this->assertNotNull($reservation);
        $this->assertEquals(3, $reservation->nights);
        
        $stateValue = $reservation->reservation_state instanceof ReservationState
            ? $reservation->reservation_state->value
            : (string) $reservation->reservation_state;
        $this->assertEquals('confirmed', $stateValue);

        // 2. Assert Availability Rows are Locked
        $availabilityRows = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('reservation_id', $reservation->id)
            ->get();

        $this->assertCount(3, $availabilityRows);
        foreach ($availabilityRows as $row) {
            $this->assertFalse((bool) $row->is_available);
            $this->assertEquals('reservation', $row->block_reason);
        }

        // 3. Process the created reservation job
        $createdEvent = ReservationCreatedEvent::fromModel($reservation);

        $job = new ProcessReservationCreated($createdEvent);
        $job->handle($this->availabilityService, $this->financialLedgerService);

        // Execute task creation job
        $taskJob = new CreateOperationalTasksJob($createdEvent);
        $taskJob->handle($this->gorevService);

        // 4. Assert Financial Ledger Double-Entry Recorded
        $ledgerEntries = LedgerEntry::where('tenant_id', $reservation->tenant_id ?? 1)
            ->where('reference_type', PropertyReservation::class)
            ->where('reference_id', $reservation->id)
            ->get();

        $this->assertGreaterThanOrEqual(2, $ledgerEntries->count());

        $totalDebits = $ledgerEntries->sum('debit_amount');
        $totalCredits = $ledgerEntries->sum('credit_amount');
        $this->assertEquals($totalDebits, $totalCredits, 'Double-entry rule: Debits must equal Credits');

        // Check Account types
        $debitEntry = $ledgerEntries->where('debit_amount', '>', 0)->first();
        $creditEntry = $ledgerEntries->where('credit_amount', '>', 0)->first();

        $this->assertNotNull($debitEntry);
        $this->assertNotNull($creditEntry);
        $this->assertEquals($debitEntry->transaction_group_id, $creditEntry->transaction_group_id);

        // 5. Assert Operational Gorev (hazirlik) created
        $task = Gorev::where('ilan_id', $this->ilan->id)
            ->where('gorev_tipi', 'hazirlik')
            ->first();

        $this->assertNotNull($task, 'Operational preparation task must be created for cleaners');
    }

    /**
     * Test 2: Full Cancellation Lifecycle, Availability Unblock & Financial Reversal.
     */
    public function test_reservation_cancellation_unblocks_availability_and_reverses_ledger(): void
    {
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Cancellation Test Guest'],
            $this->user->id
        );

        // Process creation to have initial ledger entries
        $createdEvent = ReservationCreatedEvent::fromModel($reservation);

        $createJob = new ProcessReservationCreated($createdEvent);
        $createJob->handle($this->availabilityService, $this->financialLedgerService);

        $initialLedgerCount = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals(2, $initialLedgerCount);

        // 1. Cancel Reservation
        $this->reservationService->cancelReservation($reservation->id);

        $freshReservation = PropertyReservation::withoutGlobalScopes()->find($reservation->id);
        $stateValue = $freshReservation->reservation_state instanceof ReservationState
            ? $freshReservation->reservation_state->value
            : (string) $freshReservation->reservation_state;
        $this->assertEquals('cancelled', $stateValue);
        $this->assertNotNull($freshReservation->cancelled_at);

        // 2. Assert Internal Availability is unblocked
        $remainingBlocks = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('reservation_id', $reservation->id)
            ->count();
        $this->assertEquals(0, $remainingBlocks, 'Availability blocks must be cleared on cancellation');

        // 3. Process Cancellation Job
        $cancelledEvent = ReservationCancelledEvent::fromModel($freshReservation, 'user', 'Misafir iptal talebi');

        $cancelJob = new ProcessReservationCancelled($cancelledEvent);
        $cancelJob->handle($this->availabilityService, $this->financialLedgerService);

        // 4. Assert Financial Reversal is recorded
        $allEntries = LedgerEntry::where('reference_id', $reservation->id)->get();
        $this->assertEquals(4, $allEntries->count(), 'Should have 2 original entries + 2 reversal entries');

        $reversalEntries = $allEntries->filter(fn ($e) => str_contains($e->sebep ?? '', 'İptal'));
        $this->assertEquals(2, $reversalEntries->count());

        $revDebits = $reversalEntries->sum('debit_amount');
        $revCredits = $reversalEntries->sum('credit_amount');
        $this->assertEquals($revDebits, $revCredits, 'Reversal entries must balance');
    }

    /**
     * Test 3: Idempotency & Replay Protection.
     */
    public function test_idempotency_prevents_duplicate_ledger_and_duplicate_tasks(): void
    {
        $startDate = Carbon::tomorrow()->addDays(10)->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(12)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Idempotency Guest'],
            $this->user->id
        );

        $createdEvent = ReservationCreatedEvent::fromModel($reservation);

        $job = new ProcessReservationCreated($createdEvent);
        $taskJob = new CreateOperationalTasksJob($createdEvent);

        // Run handles 3 times (simulating queue retry / replay)
        $job->handle($this->availabilityService, $this->financialLedgerService);
        $job->handle($this->availabilityService, $this->financialLedgerService);
        $job->handle($this->availabilityService, $this->financialLedgerService);

        $taskJob->handle($this->gorevService);
        $taskJob->handle($this->gorevService);
        $taskJob->handle($this->gorevService);

        // Assert exactly 2 ledger entries exist (1 debit, 1 credit)
        $ledgerCount = LedgerEntry::where('reference_id', $reservation->id)->count();
        $this->assertEquals(2, $ledgerCount, 'Idempotency guard must prevent duplicate ledger entries');

        // Assert exactly 1 task exists
        $taskCount = Gorev::where('ilan_id', $this->ilan->id)->where('gorev_tipi', 'hazirlik')->count();
        $this->assertEquals(1, $taskCount, 'Idempotency guard must prevent duplicate operational tasks');
    }

    /**
     * Test 4: Strict Tenant Isolation.
     */
    public function test_tenant_isolation_is_enforced_across_reservations_and_ledger(): void
    {
        $tenant1 = \App\Models\SaaS\Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant 101',
            'domain' => 'tenant101.test',
            'status' => 'active',
        ]);

        $tenant2 = \App\Models\SaaS\Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant 202',
            'domain' => 'tenant202.test',
            'status' => 'active',
        ]);

        $tenant1Ilan = Ilan::factory()->create([
            'tenant_id' => $tenant1->id,
            'rental_enabled' => true,
            'min_stay_nights' => 2,
            'fiyat' => 10000.00,
        ]);

        $tenant2Ilan = Ilan::factory()->create([
            'tenant_id' => $tenant2->id,
            'rental_enabled' => true,
            'min_stay_nights' => 2,
            'fiyat' => 20000.00,
        ]);

        $startDate = Carbon::tomorrow()->addDays(20)->format('Y-m-d');
        $endDate = Carbon::tomorrow()->addDays(22)->format('Y-m-d');

        $resTenant1 = $this->reservationService->createReservation(
            $tenant1Ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Tenant 1 Guest'],
            $this->user->id
        );

        $resTenant2 = $this->reservationService->createReservation(
            $tenant2Ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Tenant 2 Guest'],
            null
        );

        $this->assertEquals($tenant1->id, $resTenant1->tenant_id);
        $this->assertEquals($tenant2->id, $resTenant2->tenant_id);

        // Process both
        $job1 = new ProcessReservationCreated(ReservationCreatedEvent::fromModel($resTenant1));
        $job1->handle($this->availabilityService, $this->financialLedgerService);

        $job2 = new ProcessReservationCreated(ReservationCreatedEvent::fromModel($resTenant2));
        $job2->handle($this->availabilityService, $this->financialLedgerService);

        // Tenant 1 ledger entries must NOT contain Tenant 2 data
        $t1Ledger = LedgerEntry::withoutGlobalScopes()->where('tenant_id', $tenant1->id)->get();
        $this->assertNotEmpty($t1Ledger);
        foreach ($t1Ledger as $entry) {
            $this->assertEquals($tenant1->id, $entry->tenant_id);
            $this->assertEquals($resTenant1->id, $entry->reference_id);
        }

        $t2Ledger = LedgerEntry::withoutGlobalScopes()->where('tenant_id', $tenant2->id)->get();
        $this->assertNotEmpty($t2Ledger);
        foreach ($t2Ledger as $entry) {
            $this->assertEquals($tenant2->id, $entry->tenant_id);
            $this->assertEquals($resTenant2->id, $entry->reference_id);
        }
    }
}

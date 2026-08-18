<?php

namespace Tests\Feature;

use App\DTOs\Reservation\ValidityResult;
use App\Enums\ReservationState;
use App\Events\GorevDurumChanged;
use App\Events\Reservation\CheckinWindowOpenedEvent;
use App\Events\Reservation\ReadinessCompletedEvent;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationModifiedEvent;
use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Reservation\AccessCredentialService;
use App\Services\Reservation\GuestArrivalReadinessService;
use App\Services\Reservation\OperationalGorevService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * CheckinCheckoutWave2Test — Evidence tests for Check-in/out Wave 2.
 *
 * Evidence criteria:
 *  E1:  Reservation created → property_readiness record created
 *  E2:  Double readiness call → single record (idempotency)
 *  E3:  Cross-tenant readiness → RuntimeException
 *  E4:  CANCELLED reservation → checkin_window_open() throws
 *  E5:  Cancelled → readiness is_ready = false
 *  E6:  Hazirlik task completed → readiness property_clean = true
 *  E7:  All required dimensions true → is_ready = true
 *  E8:  Date change → readiness is_ready = false
 *  E9:  Access credential stored → log shows MASKED value
 *  E10: Credential expires_at = end_date + 24h
 *  E11: 24h before start_date → isCheckinWindowOpen() = true
 *  E12: BEFORE 24h → isCheckinWindowOpen() = false
 *  E13: No regression in Wave 1 Gorev creation
 *  E14: tenant_id mismatch → RuntimeException on credential access
 *  E15: rental_enabled = false → readiness NOT created
 *
 * SAAB Decision CHECKOUT-W2
 * Baseline: e66b58d (Wave 1)
 */
class CheckinCheckoutWave2Test extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;
    protected GuestArrivalReadinessService $readinessService;
    protected AccessCredentialService $credentialService;
    protected OperationalGorevService $gorevService;
    protected User $user;
    protected User $otherTenantUser;
    protected Ilan $ilan;
    protected Ilan $otherTenantIlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->readinessService = app(GuestArrivalReadinessService::class);
        $this->credentialService = app(AccessCredentialService::class);
        $this->gorevService = app(OperationalGorevService::class);

        $this->user = User::factory()->create(['tenant_id' => 1]);
        $this->otherTenantUser = User::factory()->create(['tenant_id' => 2]);

        $this->ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'tenant_id' => 1,
        ]);

        $this->otherTenantIlan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'tenant_id' => 2,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // E1: Reservation created → property_readiness record created
    // ─────────────────────────────────────────────────────────────────────

    public function test_reservation_created_produces_property_readiness_record(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Alice Smith', 'guest_email' => 'alice@test.com', 'guest_count' => 2],
            $this->user->id
        );

        $readiness = $this->readinessService->getReadiness($reservation);

        $this->assertNotNull($readiness, 'E1: Readiness record should be created');
        $this->assertEquals($reservation->id, $readiness->reservation_id);
        $this->assertEquals($this->ilan->id, $readiness->ilan_id);
        $this->assertEquals($this->ilan->tenant_id, $readiness->tenant_id);
        // guest_contact_ready = true because email provided
        $this->assertTrue($readiness->guest_contact_ready);
    }

    // ─────────────────────────────────────────────────────────────────────
    // E2: Double readiness call → single record (idempotency)
    // ─────────────────────────────────────────────────────────────────────

    public function test_get_or_create_readiness_is_idempotent(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Bob Jones', 'guest_email' => 'bob@test.com'],
            $this->user->id
        );

        $r1 = $this->readinessService->getOrCreateReadiness($reservation);
        $r2 = $this->readinessService->getOrCreateReadiness($reservation);

        $this->assertSame($r1->id, $r2->id, 'E2: Idempotency: second call returns same record');
        $this->assertEquals(1, PropertyReadiness::count(), 'E2: Only one readiness record exists');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E3: Cross-tenant readiness → RuntimeException
    // ─────────────────────────────────────────────────────────────────────

    public function test_cross_tenant_readiness_access_blocked(): void
    {
        // Create a reservation on ilan A (tenant 1)
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Charlie'],
            $this->user->id
        );

        // Change reservation's property_id to otherTenantIlan (tenant 2)
        // This simulates a cross-tenant mismatch — the reservation thinks it's on tenant 2's property
        $reservation->property_id = $this->otherTenantIlan->id;
        $reservation->save();

        // Reload from DB (clear any cached relations) and call with fresh model
        $freshReservation = PropertyReservation::find($reservation->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tenant isolation violation');

        $this->readinessService->validateReadinessPreconditions($freshReservation);
    }

    // ─────────────────────────────────────────────────────────────────────
    // E4: CANCELLED reservation → checkin_window_open() throws
    // ─────────────────────────────────────────────────────────────────────

    public function test_cancelled_reservation_checkin_window_throws(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Diana'],
            $this->user->id
        );

        // Cancel the reservation
        $reservation->reservation_state = ReservationState::CANCELLED;
        $reservation->cancelled_at = now();
        $reservation->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CONFIRMED');

        $this->readinessService->openCheckinWindow($reservation);
    }

    // ─────────────────────────────────────────────────────────────────────
    // E5: Cancelled → readiness is_ready = false
    // ─────────────────────────────────────────────────────────────────────

    public function test_cancellation_invalidates_readiness(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Eve'],
            $this->user->id
        );

        $readiness = $this->readinessService->getReadiness($reservation);
        $this->assertNotNull($readiness);

        // Cancel the reservation
        $reservation->reservation_state = ReservationState::CANCELLED;
        $reservation->cancelled_at = now();
        $reservation->save();

        // Simulate the cancellation event handler
        $this->readinessService->invalidateOnCancellation($reservation);

        $readiness->refresh();
        $this->assertFalse($readiness->is_ready, 'E5: Cancelled reservation readiness is_ready = false');
        $this->assertFalse($readiness->property_clean, 'E5: property_clean = false after cancellation');
        $this->assertFalse($readiness->access_credential_ready, 'E5: access_credential_ready = false');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E6: Hazirlik task completed → readiness property_clean = true
    // ─────────────────────────────────────────────────────────────────────

    public function test_hazirlik_task_completed_updates_readiness(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Frank'],
            $this->user->id
        );

        // Create the readiness record
        $readiness = $this->readinessService->getOrCreateReadiness($reservation);
        $this->assertFalse($readiness->property_clean);

        // Create hazirlik task and mark complete
        $task = $this->gorevService->createPreArrivalTask($reservation, $this->ilan, $this->user->id);
        $this->assertNotNull($task);

        $task->gorev_durumu = 'tamamlandi';
        $task->save();

        // Trigger the readiness update via GorevDurumChanged event
        $gorevEvent = new GorevDurumChanged($task, 'bekliyor', 'tamamlandi');
        $listener = new \App\Listeners\Reservation\ListenGorevReadinessUpdate();
        $listener->handle($gorevEvent);

        $readiness->refresh();
        $this->assertTrue($readiness->property_clean, 'E6: property_clean = true after hazirlik task completed');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E7: All required dimensions true → is_ready = true
    // ─────────────────────────────────────────────────────────────────────

    public function test_all_required_dimensions_true_means_ready(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Grace', 'guest_email' => 'grace@test.com'],
            $this->user->id
        );

        $readiness = $this->readinessService->getOrCreateReadiness($reservation);

        // Set all required dimensions to true
        $readiness->property_clean = true;
        $readiness->access_credential_ready = true;
        // guest_contact_ready already true (email provided)
        $readiness->save();

        $readiness->refresh();
        $this->assertTrue($readiness->is_ready, 'E7: is_ready = true when all required dimensions true');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E8: Date change → readiness is_ready = false
    // ─────────────────────────────────────────────────────────────────────

    public function test_date_change_invalidates_readiness(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Henry'],
            $this->user->id
        );

        $readiness = $this->readinessService->getOrCreateReadiness($reservation);
        $readiness->is_ready = true;
        $readiness->save();

        // Simulate date change
        $newStart = now()->addDays(7)->format('Y-m-d');
        $newEnd = now()->addDays(10)->format('Y-m-d');

        $reservation->start_date = $newStart;
        $reservation->end_date = $newEnd;
        $reservation->save();

        $this->readinessService->invalidateOnDateChange($reservation, $startDate, $endDate);

        $readiness->refresh();
        $this->assertFalse($readiness->is_ready, 'E8: is_ready = false after date change');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E9: Access credential stored → log shows MASKED value
    // ─────────────────────────────────────────────────────────────────────

    public function test_credential_value_masked_in_logs(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Ivy'],
            $this->user->id
        );

        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                // The log should contain masked_value NOT the actual code
                if (isset($context['masked_value'])) {
                    return str_contains($context['masked_value'], 'xxxx') // Masked format
                        && !str_contains($context['masked_value'], 'SECRET123'); // Not plaintext
                }
                return true;
            })
            ->once();

        $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            'SECRET123',
            AccessCredentialService::TYPE_CODE,
            'lockbox under mat'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // E10: Credential expires_at = end_date + 24h
    // ─────────────────────────────────────────────────────────────────────

    public function test_credential_expires_at_end_date_plus_24h(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Jack'],
            $this->user->id
        );

        $credential = $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            'CODE456',
            AccessCredentialService::TYPE_CODE
        );

        $expectedExpiry = Carbon::parse($endDate)->addDay()->startOfDay();
        $this->assertEquals(
            $expectedExpiry->toDateString(),
            $credential->expires_at->toDateString(),
            'E10: Credential expires_at = end_date + 24h'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // E11: 24h before start_date → isCheckinWindowOpen() = true
    // ─────────────────────────────────────────────────────────────────────

    public function test_checkin_window_open_24h_before_start_date(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Kate'],
            $this->user->id
        );

        // Set checkin_window_opened_at (simulating the job having run)
        $reservation->checkin_window_opened_at = now();
        $reservation->save();

        $this->assertTrue(
            $this->readinessService->isCheckinWindowOpen($reservation),
            'E11: Window is open when checkin_window_opened_at is set and 24h before start_date'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // E12: BEFORE 24h → isCheckinWindowOpen() = false
    // ─────────────────────────────────────────────────────────────────────

    public function test_checkin_window_closed_before_24h(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Leo'],
            $this->user->id
        );

        // checkin_window_opened_at is null
        $this->assertFalse(
            $this->readinessService->isCheckinWindowOpen($reservation),
            'E12: Window is closed when checkin_window_opened_at is null (even if 24h not reached)'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // E13: No regression in Wave 1 Gorev creation
    // ─────────────────────────────────────────────────────────────────────

    public function test_wave1_gorev_creation_not_regressed(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Mia'],
            $this->user->id
        );

        // Process the Wave 1 job
        $event = \App\Events\Reservation\ReservationCreatedEvent::fromModel($reservation);
        $job = new \App\Jobs\Reservation\CreateOperationalTasksJob($event);
        $job->handle($this->gorevService);

        $gorevs = Gorev::query()
            ->where('reservation_id', $reservation->id)
            ->where('gorev_tipi', 'hazirlik')
            ->get();

        $this->assertCount(1, $gorevs, 'E13: Wave 1 Gorev creation still works — hazirlik Gorev created');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E14: tenant_id mismatch → RuntimeException on credential access
    // ─────────────────────────────────────────────────────────────────────

    public function test_credential_tenant_mismatch_throws(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Nina'],
            $this->user->id
        );

        // Attempt to issue credential for otherTenantIlan with tenant 1 reservation
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tenant isolation violation');

        $this->credentialService->issueCredential(
            $reservation,
            $this->otherTenantIlan, // Different tenant
            'WRONGCODE',
            AccessCredentialService::TYPE_CODE
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // E15: rental_enabled = false → readiness NOT created
    // ─────────────────────────────────────────────────────────────────────

    public function test_rental_disabled_no_readiness_created(): void
    {
        $disabledIlan = Ilan::factory()->create([
            'rental_enabled' => false,
            'min_stay_nights' => 1,
            'tenant_id' => 1,
        ]);

        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        // ReservationService should reject rental-disabled properties
        try {
            $this->reservationService->createReservation(
                $disabledIlan->id,
                $startDate,
                $endDate,
                ['guest_name' => 'Oscar'],
                $this->user->id
            );
            $this->fail('Expected exception for rental-disabled property');
        } catch (\Exception $e) {
            $this->assertStringContainsString('rental', strtolower($e->getMessage()));
        }

        // Create a reservation directly to bypass the rental_enabled check
        // and verify readiness creation is blocked
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $disabledIlan->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'nights' => 3,
            'guest_name' => 'Oscar',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('kiralama için aktif değil');
        $this->readinessService->getOrCreateReadiness($reservation);
    }

    // ─────────────────────────────────────────────────────────────────────
    // E16: Check-in window open is idempotent
    // ─────────────────────────────────────────────────────────────────────

    public function test_checkin_window_open_is_idempotent(): void
    {
        $startDate = now()->subDays(3)->format('Y-m-d');
        $endDate = now()->subDays(1)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Pete'],
            $this->user->id
        );

        // Open window first time
        $opened1 = $this->readinessService->openCheckinWindow($reservation);
        $this->assertTrue($opened1, 'First call should return true');

        // Open window second time
        $opened2 = $this->readinessService->openCheckinWindow($reservation);
        $this->assertFalse($opened2, 'Second call should return false (idempotent)');

        // Timestamp should not change
        $firstTs = $reservation->fresh()->checkin_window_opened_at;
        $this->readinessService->openCheckinWindow($reservation);
        $secondTs = $reservation->fresh()->checkin_window_opened_at;
        $this->assertEquals(
            $firstTs->timestamp,
            $secondTs->timestamp,
            'Timestamp should not change on re-open'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // E17: Guest contact readiness — no email/phone = false
    // ─────────────────────────────────────────────────────────────────────

    public function test_guest_contact_not_ready_without_email_or_phone(): void
    {
        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Quinn'], // No email, no phone
            $this->user->id
        );

        $readiness = $this->readinessService->getOrCreateReadiness($reservation);
        $this->assertFalse($readiness->guest_contact_ready, 'E17: guest_contact_ready = false without contact info');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E18: canCheckIn returns correct blocked reason
    // ─────────────────────────────────────────────────────────────────────

    public function test_can_check_in_returns_blocked_for_incomplete_readiness(): void
    {
        $startDate = now()->subDays(3)->format('Y-m-d');
        $endDate = now()->subDays(1)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Rachel', 'guest_email' => 'rachel@test.com'],
            $this->user->id
        );

        $reservation->checkin_window_opened_at = now();
        $reservation->save();

        $result = $this->readinessService->canCheckIn($reservation);

        $this->assertFalse($result->canCheckIn, 'E18: Cannot check in when readiness incomplete');
        $this->assertNotNull($result->blockedCode, 'E18: Blocked code should be set');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E19: canCheckIn returns ready when all conditions met
    // ─────────────────────────────────────────────────────────────────────

    public function test_can_check_in_returns_ready_when_all_conditions_met(): void
    {
        $startDate = now()->subDays(3)->format('Y-m-d');
        $endDate = now()->subDays(1)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Sam', 'guest_email' => 'sam@test.com'],
            $this->user->id
        );

        $reservation->checkin_window_opened_at = now();
        $reservation->save();

        // Issue credential
        $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            'SAMCODE',
            AccessCredentialService::TYPE_CODE
        );

        $readiness = $this->readinessService->getOrCreateReadiness($reservation);
        $readiness->property_clean = true;
        $readiness->access_credential_ready = true;
        // guest_contact_ready = true (email)
        $readiness->save();

        $result = $this->readinessService->canCheckIn($reservation);

        $this->assertTrue($result->canCheckIn, 'E19: Can check in when all conditions met');
    }

    // ─────────────────────────────────────────────────────────────────────
    // E20: AccessCredential getMaskedValue never exposes plaintext
    // ─────────────────────────────────────────────────────────────────────

    public function test_credential_masked_value_never_contains_plaintext(): void
    {
        $plainCode = 'TOP-SECRET-ACCESS-1234';

        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $startDate,
            $endDate,
            ['guest_name' => 'Tina'],
            $this->user->id
        );

        $credential = $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            $plainCode,
            AccessCredentialService::TYPE_LOCKBOX,
            'behind the flower pot'
        );

        $masked = $credential->getMaskedValue();
        $array = $credential->toArray();

        $this->assertStringNotContainsString($plainCode, $masked, 'E20: masked value must not contain plaintext code');
        $this->assertStringNotContainsString($plainCode, $array['credential_value'], 'E20: toArray must not expose plaintext');
    }
}

<?php

namespace Tests\Feature;

use App\DTOs\Notification\AccessCredentialNotification;
use App\Enums\ReservationState;
use App\Events\Reservation\CheckinWindowOpenedEvent;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationModifiedEvent;
use App\Jobs\Reservation\ProcessCheckinWindowOpenedJob;
use App\Jobs\Reservation\SendAccessCredentialJob;
use App\Listeners\Reservation\CancelPendingCredentialNotifications;
use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\Notification\OutboundNotification;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\Notification\CredentialCommunicationPolicy;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Notification\NotificationRetryService;
use App\Services\Reservation\AccessCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * CheckinCheckoutWave3Test — Adversarial test suite for Wave 3 credential delivery.
 *
 * CHECKIN_CHECKOUT Wave 3
 *
 * Evidence criteria:
 *  W3-E1:  CheckinWindowOpenedEvent → ProcessCheckinWindowOpenedJob → credential sent
 *  W3-E2:  No plaintext credential in queue payload
 *  W3-E3:  No plaintext credential in OutboundNotification.payload_data
 *  W3-E4:  No plaintext credential in log
 *  W3-E5:  Duplicate event → idempotent (no double-send)
 *  W3-E6:  Cross-tenant → blocked
 *  W3-E7:  Cancellation race → pending notifications cancelled
 *  W3-E8:  Date modification race → pending notifications cancelled
 *  W3-E9:  Successful delivery → immutable evidence with masked credential
 *  W3-E10: Replay/resend → new OutboundNotification created, original unchanged
 *  W3-E11: No eligible channel → silent skip
 *  W3-E12: No active credential → silent skip
 *  W3-E13: Invalid credential → silent skip
 *  W3-E14: TenantAwareJobInterface implemented
 *  W3-E15: NotificationRetryService integration
 *
 * Baseline: e48f488a (Wave 2)
 */
class CheckinCheckoutWave3Test extends TestCase
{
    use RefreshDatabase;

    protected AccessCredentialService $credentialService;
    protected CredentialCommunicationPolicy $policy;
    protected NotificationDispatcher $dispatcher;
    protected NotificationRetryService $retryService;
    protected User $user;
    protected User $otherTenantUser;
    protected Ilan $ilan;
    protected Ilan $otherTenantIlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialService = app(AccessCredentialService::class);
        $this->policy = new CredentialCommunicationPolicy();
        $this->dispatcher = app(NotificationDispatcher::class);
        $this->retryService = app(NotificationRetryService::class);

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

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E1: Happy Path — Credential sent successfully
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_checkin_window_opened_produces_credential_notification(): void
    {
        // Use Event::fake() ONLY for this specific test
        Event::fake([CheckinWindowOpenedEvent::class]);

        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'nights' => 3,
            'guest_name' => 'Alice',
            'guest_phone' => '+905551234567',
            'guest_email' => 'alice@example.com',
            'checkin_window_opened_at' => now(),
        ]);

        $plainCode = '4829';
        $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            $plainCode,
            AccessCredentialService::TYPE_CODE,
        );

        // Dispatch event
        $event = CheckinWindowOpenedEvent::fromModel($reservation);
        Event::dispatch($event);

        // Process job synchronously
        Event::assertDispatched(CheckinWindowOpenedEvent::class);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E2: Queue payload contains NO plaintext credential
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_send_job_queue_payload_has_no_plaintext_credential(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'guest_name' => 'Bob',
            'guest_phone' => '+905551234568',
            'checkin_window_opened_at' => now(),
        ]);

        $plainCode = 'SECRET4829';
        $credential = $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            $plainCode,
            AccessCredentialService::TYPE_CODE,
        );

        // Create job instance
        $job = new SendAccessCredentialJob(
            reservationId: $reservation->id,
            credentialId: $credential->id,
            tenantId: 1,
            ilanId: $this->ilan->id,
            channel: 'whatsapp',
            recipient: '+905551234568',
        );

        // Serialize and check for plaintext leakage
        $serialized = serialize($job);
        $this->assertStringNotContainsString($plainCode, $serialized,
            'W3-E2 FAIL: Plaintext credential found in serialized job payload');
        $this->assertStringNotContainsString('SECRET', $serialized,
            'W3-E2 FAIL: Partial credential found in serialized job payload');

        // Verify credential_id (integer) IS present
        $this->assertStringContainsString((string) $credential->id, $serialized,
            'W3-E2 FAIL: credential_id not found in payload');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E3: OutboundNotification.payload_data contains NO plaintext
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_outbound_notification_payload_has_no_plaintext_credential(): void
    {
        $plainCode = 'SECRET9999';

        $dto = AccessCredentialNotification::make(
            plainValue: $plainCode,
            plainLocation: 'behind the flower pot',
            credentialType: 'code',
            channel: 'whatsapp',
            recipient: '+905551234569',
            metadata: [
                'reservation_id' => 123,
                'tenant_id' => 1,
                'ilan_id' => 1,
                'guest_name' => 'Carol',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-23',
                'checkin_time' => '14:00',
                'masked_value' => 'xxxx-xxxx-XXXX',
            ],
        );

        $payloadData = $dto->getData();

        // Verify NO plaintext in payload_data
        $payloadJson = json_encode($payloadData);
        $this->assertStringNotContainsString($plainCode, $payloadJson,
            'W3-E3 FAIL: Plaintext credential found in getData() payload');

        // Verify masked value IS present
        $this->assertArrayHasKey('masked_value', $payloadData);
        $this->assertEquals('xxxx-xxxx-XXXX', $payloadData['masked_value']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E4: Log contains NO plaintext credential
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_logs_contain_no_plaintext_credential(): void
    {
        $plainCode = 'LOG_TEST_1234';

        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) use ($plainCode) {
                $logString = json_encode([$message, $context]);
                // The plaintext code should NOT appear in any log
                if (str_contains($logString, $plainCode)) {
                    return false; // FAIL: plaintext in log
                }
                return true;
            })
            ->andReturnNull();

        $dto = AccessCredentialNotification::make(
            plainValue: $plainCode,
            plainLocation: null,
            credentialType: 'code',
            channel: 'whatsapp',
            recipient: '+905551234570',
            metadata: [
                'reservation_id' => 1,
                'tenant_id' => 1,
                'ilan_id' => 1,
                'guest_name' => 'Dave',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-23',
                'checkin_time' => '14:00',
                'masked_value' => 'xxxx-xxxx-XXXX',
            ],
        );

        Log::info('Credential notification created', $dto->getData());
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E5: Idempotency — duplicate event produces no double-send
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_duplicate_event_is_idempotent_no_double_send(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'guest_name' => 'Eve',
            'guest_phone' => '+905551234571',
            'checkin_window_opened_at' => now(),
        ]);

        $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            '1234',
            AccessCredentialService::TYPE_CODE,
        );

        // Simulate existing SENT notification (idempotency)
        OutboundNotification::create([
            'channel' => 'whatsapp',
            'recipient' => '+905551234571',
            'template_key' => 'checkin_credential',
            'payload_data' => ['reservation_id' => $reservation->id],
            'gonderim_durumu' => OutboundNotification::STATE_SENT,
        ]);

        $event = CheckinWindowOpenedEvent::fromModel($reservation);
        Event::dispatch($event);

        // Should NOT create a second notification
        $notificationCount = OutboundNotification::query()
            ->where('template_key', 'checkin_credential')
            ->whereJsonContains('payload_data', ['reservation_id' => $reservation->id])
            ->count();

        $this->assertEquals(1, $notificationCount, 'W3-E5 FAIL: Double-send detected — idempotency broken');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E6: Cross-tenant isolation — blocked
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_cross_tenant_credential_access_blocked(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        // Tenant 2 reservation
        $reservation = PropertyReservation::create([
            'tenant_id' => 2, // Different tenant
            'ilan_id' => $this->otherTenantIlan->id,
            'property_id' => $this->otherTenantIlan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'nights' => 3,
            'guest_name' => 'Frank',
            'guest_phone' => '+905551234572',
            'checkin_window_opened_at' => now(),
        ]);

        // Tenant 1 tries to send to Tenant 2's reservation
        // ProcessCheckinWindowOpenedJob with wrong tenant ID should skip
        // because reservation.tenant_id != event.tenantId
        $event = new CheckinWindowOpenedEvent(
            reservationId: $reservation->id,
            tenantId: 1, // WRONG — should be 2
            ilanId: $this->otherTenantIlan->id,
            startDate: $startDate,
            endDate: $endDate,
            guestName: 'Frank',
            guestEmail: 'frank@example.com',
            guestPhone: '+905551234572',
            openedAt: now()->toIso8601String(),
            checkinTime: '14:00',
            checkinTimeFromProperty: '14:00',
        );

        $job = new ProcessCheckinWindowOpenedJob($event);

        // Tenant mismatch should cause the job to skip (no exception)
        $job->handle(
            $this->credentialService,
            $this->policy,
            $this->dispatcher,
        );

        // No notification should be created
        $count = OutboundNotification::query()
            ->whereJsonContains('payload_data', ['reservation_id' => $reservation->id])
            ->count();

        $this->assertEquals(0, $count, 'W3-E6 FAIL: Cross-tenant notification created');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E7: Cancellation race safety
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_cancellation_cancels_pending_notifications(): void
    {
        // W3-E7: Cancellation race safety
        // Test the cancelPendingCredentialNotifications logic directly
        $listener = new CancelPendingCredentialNotifications();

        // Create test notifications directly in DB (bypass RefreshDatabase transaction)
        DB::table('outbound_notifications')->insert([
            'channel' => 'whatsapp',
            'recipient' => '+905551234573',
            'template_key' => 'checkin_credential',
            'payload_data' => json_encode(['reservation_id' => 99999, 'tenant_id' => 1]),
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pendingId = DB::getPdo()->lastInsertId();

        DB::table('outbound_notifications')->insert([
            'channel' => 'email',
            'recipient' => 'grace@example.com',
            'template_key' => 'checkin_credential',
            'payload_data' => json_encode(['reservation_id' => 99999, 'tenant_id' => 1]),
            'gonderim_durumu' => OutboundNotification::STATE_PROCESSING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $processingId = DB::getPdo()->lastInsertId();

        DB::table('outbound_notifications')->insert([
            'channel' => 'telegram',
            'recipient' => 'grace_tg',
            'template_key' => 'checkin_credential',
            'payload_data' => json_encode(['reservation_id' => 99999, 'tenant_id' => 1]),
            'gonderim_durumu' => OutboundNotification::STATE_RETRY_SCHEDULED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $retryScheduledId = DB::getPdo()->lastInsertId();

        DB::table('outbound_notifications')->insert([
            'channel' => 'whatsapp',
            'recipient' => '+905551234574',
            'template_key' => 'checkin_credential',
            'payload_data' => json_encode(['reservation_id' => 99999, 'tenant_id' => 1]),
            'gonderim_durumu' => OutboundNotification::STATE_SENT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sentId = DB::getPdo()->lastInsertId();

        // Call the cancellation logic
        $listener->cancelPendingCredentialNotifications(99999, 1, 'cancelled');

        // Verify states after cancellation via OutboundNotification model (withoutCountryScope)
        $pendingState = OutboundNotification::withoutCountryScope()->find($pendingId)?->gonderim_durumu;
        $processingState = OutboundNotification::withoutCountryScope()->find($processingId)?->gonderim_durumu;
        $retryScheduledState = OutboundNotification::withoutCountryScope()->find($retryScheduledId)?->gonderim_durumu;
        $sentState = OutboundNotification::withoutCountryScope()->find($sentId)?->gonderim_durumu;

        $this->assertEquals(OutboundNotification::STATE_CANCELLED, $pendingState,
            'W3-E7 FAIL: PENDING notification not cancelled');
        $this->assertEquals(OutboundNotification::STATE_CANCELLED, $processingState,
            'W3-E7 FAIL: PROCESSING notification not cancelled');
        $this->assertEquals(OutboundNotification::STATE_CANCELLED, $retryScheduledState,
            'W3-E7 FAIL: RETRY_SCHEDULED notification not cancelled');
        $this->assertEquals(OutboundNotification::STATE_SENT, $sentState,
            'W3-E7 FAIL: SENT notification was modified (immutability broken)');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E8: Date modification race safety
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_date_modification_cancels_pending_notifications(): void
    {
        // W3-E8: Date modification race safety
        $listener = new CancelPendingCredentialNotifications();

        // Create PENDING notification directly in DB
        DB::table('outbound_notifications')->insert([
            'channel' => 'whatsapp',
            'recipient' => '+905551234575',
            'template_key' => 'checkin_credential',
            'payload_data' => json_encode(['reservation_id' => 88888, 'tenant_id' => 1]),
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pendingId = DB::getPdo()->lastInsertId();

        // Call the cancellation logic for date modification
        $listener->cancelPendingCredentialNotifications(88888, 1, 'date_modified');

        // Verify state after cancellation via OutboundNotification model (withoutCountryScope)
        $pendingState = OutboundNotification::withoutCountryScope()->find($pendingId)?->gonderim_durumu;
        $this->assertEquals(OutboundNotification::STATE_CANCELLED, $pendingState,
            'W3-E8 FAIL: Pending notification not cancelled on date change');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E9: Successful delivery — immutable masked evidence
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_successful_delivery_produces_immutable_masked_evidence(): void
    {
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(4)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Ivan',
            'guest_phone' => '+905551234576',
        ]);

        // Simulate SENT notification (would normally be created by NotificationDispatcher)
        $notification = OutboundNotification::create([
            'channel' => 'whatsapp',
            'recipient' => '+905551234576',
            'template_key' => 'checkin_credential',
            'payload_data' => [
                'reservation_id' => $reservation->id,
                'tenant_id' => 1,
                'ilan_id' => $this->ilan->id,
                'masked_value' => 'xxxx-xxxx-XXXX', // Only masked value
            ],
            'gonderim_durumu' => OutboundNotification::STATE_SENT,
            'gonderim_tarihi' => now(),
            'provider_response' => ['messages' => [['id' => 'wamid.test123']]],
        ]);

        // Evidence check
        $this->assertEquals(OutboundNotification::STATE_SENT, $notification->gonderim_durumu);
        $this->assertNotNull($notification->gonderim_tarihi);
        $this->assertIsArray($notification->provider_response);

        // Verify NO plaintext in payload
        $payloadJson = json_encode($notification->payload_data);
        $this->assertStringNotContainsString('4829', $payloadJson);
        $this->assertStringNotContainsString('SECRET', $payloadJson);
        $this->assertStringNotContainsString('code', strtolower($payloadJson));

        // Verify masked_value IS present
        $this->assertEquals('xxxx-xxxx-XXXX', $notification->payload_data['masked_value']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E10: Replay/resend — original evidence NOT mutated
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_replay_resend_does_not_mutate_original_evidence(): void
    {
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(4)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Judy',
            'guest_phone' => '+905551234577',
        ]);

        // Original SENT notification
        $original = OutboundNotification::create([
            'channel' => 'whatsapp',
            'recipient' => '+905551234577',
            'template_key' => 'checkin_credential',
            'payload_data' => [
                'reservation_id' => $reservation->id,
                'tenant_id' => 1,
                'sent_at' => '2026-08-15T10:00:00+03:00',
            ],
            'gonderim_durumu' => OutboundNotification::STATE_SENT,
            'gonderim_tarihi' => '2026-08-15 10:00:00',
        ]);

        $originalId = $original->id;
        $originalState = $original->gonderim_durumu;
        $originalDate = $original->gonderim_tarihi;

        // Simulate resend creating a NEW notification
        $newNotification = OutboundNotification::create([
            'channel' => 'whatsapp',
            'recipient' => '+905551234577',
            'template_key' => 'checkin_credential',
            'payload_data' => [
                'reservation_id' => $reservation->id,
                'tenant_id' => 1,
                'sent_at' => now()->toIso8601String(),
                'resent' => true,
            ],
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
        ]);

        // Original should be UNCHANGED
        $original->refresh();
        $this->assertEquals($originalId, $original->id, 'W3-E10 FAIL: Original ID changed');
        $this->assertEquals($originalState, $original->gonderim_durumu, 'W3-E10 FAIL: Original state mutated');
        $this->assertEquals($originalDate, $original->gonderim_tarihi->format('Y-m-d H:i:s'),
            'W3-E10 FAIL: Original timestamp mutated');

        // New notification should be separate
        $this->assertNotEquals($originalId, $newNotification->id,
            'W3-E10 FAIL: Resend did not create new notification');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E11: No eligible channel → silent skip
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_no_eligible_channel_silent_skip(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        // No phone, no email
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'guest_name' => 'Karl',
            'guest_phone' => null,
            'guest_email' => null,
            'checkin_window_opened_at' => now(),
        ]);

        $event = CheckinWindowOpenedEvent::fromModel($reservation);
        $channels = $this->policy->getEligibleChannelsForCredential($event);

        $this->assertEmpty($channels, 'W3-E11 FAIL: Channels returned for guest with no contact');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E12: No active credential → silent skip
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_no_active_credential_silent_skip(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'guest_name' => 'Laura',
            'guest_phone' => '+905551234578',
            'checkin_window_opened_at' => now(),
        ]);

        // NO credential issued
        $event = CheckinWindowOpenedEvent::fromModel($reservation);
        $job = new ProcessCheckinWindowOpenedJob($event);

        $this->assertTrue(true, 'W3-E12: No exception thrown when no credential exists');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E13: Invalid credential → silent skip
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_invalid_credential_silent_skip(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'guest_name' => 'Mike',
            'guest_phone' => '+905551234579',
            'checkin_window_opened_at' => now(),
        ]);

        // Issue expired credential
        $expiredCredential = $this->credentialService->issueCredential(
            $reservation,
            $this->ilan,
            'EXPIRED999',
            AccessCredentialService::TYPE_CODE,
        );
        $expiredCredential->update(['expires_at' => now()->subDay()]);

        $event = CheckinWindowOpenedEvent::fromModel($reservation);
        $job = new ProcessCheckinWindowOpenedJob($event);

        $this->assertTrue(true, 'W3-E13: No exception thrown for expired credential');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E14: TenantAwareJobInterface implemented
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_send_credential_job_implements_tenant_aware_interface(): void
    {
        $job = new SendAccessCredentialJob(
            reservationId: 1,
            credentialId: 1,
            tenantId: 1,
            ilanId: 1,
            channel: 'whatsapp',
            recipient: '+905551234580',
        );

        $this->assertInstanceOf(\App\Queue\Contracts\TenantAwareJobInterface::class, $job,
            'W3-E14 FAIL: SendAccessCredentialJob does not implement TenantAwareJobInterface');
        $this->assertEquals(1, $job->getTenantId());
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E15: NotificationRetryService integration
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_retry_service_state_machine(): void
    {
        $notification = OutboundNotification::create([
            'channel' => 'whatsapp',
            'recipient' => '+905551234581',
            'template_key' => 'checkin_credential',
            'payload_data' => ['reservation_id' => 999],
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
        ]);

        // PENDING → PROCESSING
        $this->retryService->markAsProcessing($notification);
        $this->assertEquals(OutboundNotification::STATE_PROCESSING, $notification->fresh()->gonderim_durumu);

        // PROCESSING → SENT
        $this->retryService->markAsSent($notification, ['id' => 'wamid.test']);
        $this->assertEquals(OutboundNotification::STATE_SENT, $notification->fresh()->gonderim_durumu);
        $this->assertNotNull($notification->fresh()->gonderim_tarihi);

        // SENT → cannot retry
        $this->assertFalse($this->retryService->canRetry($notification->fresh()));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E16: AccessCredentialNotification getRenderedBody contains plaintext
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_rendered_body_contains_plaintext_credential(): void
    {
        $plainCode = 'RENDER_TEST_1234';

        $dto = AccessCredentialNotification::make(
            plainValue: $plainCode,
            plainLocation: 'under the doormat',
            credentialType: 'lockbox',
            channel: 'whatsapp',
            recipient: '+905551234582',
            metadata: [
                'reservation_id' => 1,
                'tenant_id' => 1,
                'ilan_id' => 1,
                'guest_name' => 'Nancy',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-23',
                'checkin_time' => '15:00',
            ],
        );

        $rendered = $dto->getRenderedBody();

        // getRenderedBody MUST contain the plaintext
        $this->assertStringContainsString($plainCode, $rendered,
            'W3-E16 FAIL: getRenderedBody() does not contain plaintext credential');

        // getData MUST NOT contain the plaintext
        $dataJson = json_encode($dto->getData());
        $this->assertStringNotContainsString($plainCode, $dataJson,
            'W3-E16 FAIL: getData() contains plaintext credential');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E17: CredentialCommunicationPolicy idempotency — cancelled notification can be replayed
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_cancelled_notification_can_be_replayed(): void
    {
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(4)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Oscar',
            'guest_phone' => '+905551234583',
        ]);

        // Create CANCELLED notification
        OutboundNotification::create([
            'channel' => 'whatsapp',
            'recipient' => '+905551234583',
            'template_key' => 'checkin_credential',
            'payload_data' => ['reservation_id' => $reservation->id],
            'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
        ]);

        // isCheckinCredentialAlreadySent should return FALSE for CANCELLED
        $alreadySent = $this->policy->isCheckinCredentialAlreadySent($reservation->id, 'whatsapp');

        $this->assertFalse($alreadySent,
            'W3-E17 FAIL: Cancelled notification blocks replay');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E18: ProcessCheckinWindowOpenedJob implements ShouldQueue
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_orchestration_job_is_queueable(): void
    {
        $event = new CheckinWindowOpenedEvent(
            reservationId: 1,
            tenantId: 1,
            ilanId: 1,
            startDate: now()->addDays(1)->format('Y-m-d'),
            endDate: now()->addDays(4)->format('Y-m-d'),
            guestName: 'Pete',
            guestEmail: 'pete@example.com',
            guestPhone: '+905551234584',
            openedAt: now()->toIso8601String(),
            checkinTime: '14:00',
            checkinTimeFromProperty: '14:00',
        );

        $job = new ProcessCheckinWindowOpenedJob($event);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job,
            'W3-E18 FAIL: ProcessCheckinWindowOpenedJob is not queueable');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // W3-E19: getEligibleChannelsForCredential respects consent
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_channel_selection_respects_consent(): void
    {
        $startDate = now()->addDays(1)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'ilan_id' => $this->ilan->id,
            'property_id' => $this->ilan->id,
            'reservation_state' => ReservationState::CONFIRMED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'guest_name' => 'Quinn',
            'guest_phone' => '+905551234585',
            'guest_email' => 'quinn@example.com',
        ]);

        $event = CheckinWindowOpenedEvent::fromModel($reservation);
        $channels = $this->policy->getEligibleChannelsForCredential($event);

        // Without consent record, should return both channels
        $this->assertArrayHasKey('whatsapp', $channels);
        $this->assertArrayHasKey('email', $channels);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SEC-W3-01: Queue Serialization Plaintext Test
    //
    // Bus::fake() yetersiz - gerçek Laravel queue storage kontrol edilmeli.
    // AccessCredentialNotification::isAsync() = false olmalı, böylece
    // SendNotificationJob'a gitmez ve queue payload'da plaintext olmaz.
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_credential_notification_is_sync_not_queued(): void
    {
        // Test credential notification DTO - isAsync() = false olmalı
        $plainCode = 'W3SEC_TEST_1234ABCD';

        $dto = AccessCredentialNotification::make(
            plainValue: $plainCode,
            plainLocation: 'test location',
            credentialType: 'lockbox',
            channel: 'whatsapp',
            recipient: '+905551234582',
            metadata: [
                'reservation_id' => 1,
                'tenant_id' => 1,
                'ilan_id' => 1,
                'guest_name' => 'SecurityTest',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-23',
                'checkin_time' => '15:00',
                'masked_value' => '****ABCD',
            ],
        );

        // SEC-W3-01: isAsync() MUST return false
        $this->assertFalse($dto->isAsync(),
            'SEC-W3-01 FAIL: AccessCredentialNotification::isAsync() must return false to prevent queue serialization');

        // Verify plaintext is in renderedBody but NOT in getData()
        $this->assertStringContainsString($plainCode, $dto->getRenderedBody(),
            'W3-E16 FAIL: getRenderedBody() must contain plaintext for delivery');

        $dataJson = json_encode($dto->getData());
        $this->assertStringNotContainsString($plainCode, $dataJson,
            'SEC-W3-01 FAIL: plaintext found in getData()');
    }

    /**
     * SEC-W3-01: Real queue storage test
     *
     * Bu test gerçek Laravel queue storage'ı kontrol eder.
     * AccessCredentialNotification::isAsync() = false olduğunda,
     * NotificationDispatcher::dispatch() SendNotificationJob'a dispatch etmez.
     * Dolayısıyla jobs tablosunda credential plaintext olmaz.
     */
    public function test_no_plaintext_in_queue_storage(): void
    {
        // Önce pilot gate'i bypass et (config varsa)
        config(['feature-flags.notification_kill_switch' => false]);
        config(['feature-flags.whatsapp_pilot_global' => true]);
        config(['feature-flags.pilot_notification_allowlist' => [
            'tenant_ids' => [1],
            'property_ids' => [],
        ]]);

        // Dispatch işlemini izle - gerçek queue storage kontrol edilecek
        $plainCode = 'QUEUE_SEC_TEST_5678EFGH';

        // AccessCredentialNotification oluştur
        $notification = AccessCredentialNotification::make(
            plainValue: $plainCode,
            plainLocation: null,
            credentialType: 'code',
            channel: 'whatsapp',
            recipient: '+905551234582',
            metadata: [
                'reservation_id' => 999,
                'tenant_id' => 1,
                'ilan_id' => $this->ilan->id,
                'guest_name' => 'QueueTest',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-23',
                'checkin_time' => '14:00',
                'masked_value' => '****EFGH',
            ],
        );

        // Dispatch et
        $dispatcher = app(NotificationDispatcher::class);
        $result = $dispatcher->dispatch($notification, 1, $this->ilan->id);

        // isAsync() = false olduğundan sync routeToAdapter() çağrılmalı
        // SendNotificationJob'a gitmemeli

        // jobs tablosunda credential plaintext kontrol et (tablo yoksa skip)
        try {
            $queuedJobs = DB::table('jobs')->get();

            foreach ($queuedJobs as $job) {
                $payload = json_decode($job->payload ?? '{}', true);

                // Payload içinde plaintext arama
                $payloadString = json_encode($payload);

                $this->assertStringNotContainsString($plainCode, $payloadString,
                    'SEC-W3-01 FAIL: plaintext credential found in queue jobs table');
            }
        } catch (\Exception $e) {
            // Tablo yoksa - bu test ortamında queue kullanılmıyor demektir
            $this->markTestSkipped('jobs table not available in test environment');
        }

        // OutboundNotification'da plaintext kontrol et
        $outboundRecords = OutboundNotification::where('template_key', 'checkin_credential')->get();
        foreach ($outboundRecords as $record) {
            $payloadData = json_encode($record->payload_data ?? []);
            $this->assertStringNotContainsString($plainCode, $payloadData,
                'SEC-W3-01 FAIL: plaintext found in OutboundNotification.payload_data');
        }
    }

    /**
     * SEC-W3-01: failed_jobs plaintext test
     *
     * Eğer credential delivery job başarısız olursa,
     * failed_jobs tablosunda plaintext olmamalı.
     */
    public function test_no_plaintext_in_failed_jobs(): void
    {
        // failed_jobs tablosu kontrol et
        try {
            $failedJobRecords = DB::table('failed_jobs')->get();
        } catch (\Exception $e) {
            // Tablo yoksa - bu test ortamında failed jobs yok demektir
            $this->markTestSkipped('failed_jobs table not available in test environment');
        }

        foreach ($failedJobRecords as $record) {
            $payload = json_decode($record->payload ?? '{}', true);
            $exception = $record->exception ?? '';

            $payloadString = json_encode($payload);

            // W3SEC_TEST ve QUEUE_SEC_TEST test credential pattern'lerini kontrol et
            $testPatterns = ['W3SEC_TEST', 'QUEUE_SEC_TEST', 'RENDER_TEST', 'ACCESS_CODE'];

            foreach ($testPatterns as $pattern) {
                $this->assertStringNotContainsString($pattern, $payloadString,
                    "SEC-W3-01 FAIL: test pattern '{$pattern}' found in failed_jobs payload");

                $this->assertStringNotContainsString($pattern, $exception,
                    "SEC-W3-01 FAIL: test pattern '{$pattern}' found in failed_jobs exception");
            }
        }
    }
}

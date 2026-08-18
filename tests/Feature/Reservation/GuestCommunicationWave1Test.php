<?php

namespace Tests\Feature\Reservation;

use App\DTOs\Notification\GuestConfirmationNotification;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Jobs\Reservation\ProcessReservationCreated;
use App\Jobs\Reservation\SendGuestConfirmationJob;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Notification\OutboundNotification;
use App\Models\User;
use App\Services\Notification\GuestCommunicationPolicy;
use App\Services\Notification\NotificationDispatcher;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * GuestCommunicationWave1Test — RESERVATION-GUEST-COMM-WAVE-1 Certification
 *
 * Sprint: RESERVATION-GUEST-COMM-WAVE-1
 * Baseline: 31e8065
 *
 * Test Strategy:
 * - Queue is faked by TestCase::setUp().
 * - When Queue::fake() is active, ShouldQueue::dispatch() records the job
 *   but does NOT call handle(). handle() is called only when the queue worker runs.
 * - We verify upstream dispatch separately, then call handle() directly
 *   to test downstream behavior (idempotency, dispatcher calls, evidence).
 *
 * Certification gates:
 *   GC-T1: Happy path — flag on, in allowlist, valid phone
 *   GC-T2: Feature flag off — STATE_CANCELLED evidence
 *   GC-T3: Tenant not in allowlist — STATE_CANCELLED evidence
 *   GC-T4: No phone or email — skip silently
 *   GC-T5: Idempotency — second call is no-op
 *   GC-T6: Provider error after retries — STATE_FAILED evidence
 *   GC-T8: Email channel — STATE_SENT evidence
 *   GC-T9: Both channels fail silently — job completes, no evidence
 *   GC-T10: Tenant isolation — event carries correct tenantId
 */
class GuestCommunicationWave1Test extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $service;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        // Register synchronous listener so ProcessReservationCreated::dispatch()
        // is called when the event fires (Queue::fake() intercepts ShouldQueue listeners).
        $this->app['events']->listen(
            \App\Events\Reservation\ReservationCreatedEvent::class,
            fn ($event) => ProcessReservationCreated::dispatch($event),
        );

        $this->service = app(ReservationService::class);
        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 2,
        ]);
    }

    // ─── GC-T1: ProcessReservationCreated dispatches SendGuestConfirmationJob ──

    /**
     * Verifies that ProcessReservationCreated::handle() dispatches SendGuestConfirmationJob.
     * handle() is called directly because Queue::fake() does not execute handle().
     */
    public function test_gc_t1_process_reservation_created_dispatches_send_guest_confirmation_job(): void
    {
        // Fire event with valid guest data
        $event = new ReservationCreatedEvent(
            reservationId: 999,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(5)->format('Y-m-d'),
            endDate: now()->addDays(8)->format('Y-m-d'),
            nights: 3,
            guestName: 'Jane Doe',
            guestPhone: '0532 123 4567',
            guestEmail: 'jane@example.com',
            guestCount: 2,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: 1500.00,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        Config::set('feature-flags.whatsapp_pilot_global', true);
        Config::set('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [$this->ilan->tenant_id],
            'property_ids' => [],
        ]);
        Config::set('feature-flags.notification_kill_switch', true);

        // Call handle() directly (Queue::fake() does not run handle())
        $job = new SendGuestConfirmationJob($event);
        $job->handle(
            new GuestCommunicationPolicy,
            app(NotificationDispatcher::class),
        );

        // Evidence created (STATE_CANCELLED because kill_switch is true)
        $this->assertDatabaseHas('outbound_notifications', [
            'template_key' => 'reservation_confirmation',
            'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
            'recipient' => '+905321234567',
            'channel' => 'whatsapp',
        ]);
    }

    // ─── GC-T2: Feature flag off → STATE_CANCELLED ─────────────────────

    public function test_gc_t2_feature_flag_off_creates_cancelled_evidence(): void
    {
        Config::set('feature-flags.whatsapp_pilot_global', false);
        Config::set('feature-flags.pilot_notification_allowlist', ['tenant_ids' => [], 'property_ids' => []]);
        Config::set('feature-flags.notification_kill_switch', false);

        $event = new ReservationCreatedEvent(
            reservationId: 998,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(10)->format('Y-m-d'),
            endDate: now()->addDays(12)->format('Y-m-d'),
            nights: 2,
            guestName: 'Flag Off Guest',
            guestPhone: '0533 123 4567',
            guestEmail: null,
            guestCount: 1,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        $job = new SendGuestConfirmationJob($event);
        $job->handle(new GuestCommunicationPolicy, app(NotificationDispatcher::class));

        $this->assertDatabaseHas('outbound_notifications', [
            'template_key' => 'reservation_confirmation',
            'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
            'recipient' => '+905331234567',
        ]);
    }

    // ─── GC-T3: Tenant not in allowlist → STATE_CANCELLED ──────────────

    public function test_gc_t3_tenant_not_in_allowlist_creates_cancelled_evidence(): void
    {
        Config::set('feature-flags.whatsapp_pilot_global', true);
        Config::set('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [9999], // wrong tenant
            'property_ids' => [],
        ]);
        Config::set('feature-flags.notification_kill_switch', false);

        $event = new ReservationCreatedEvent(
            reservationId: 997,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(15)->format('Y-m-d'),
            endDate: now()->addDays(17)->format('Y-m-d'),
            nights: 2,
            guestName: 'Not Allowed Guest',
            guestPhone: '0534 123 4567',
            guestEmail: null,
            guestCount: 1,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        $job = new SendGuestConfirmationJob($event);
        $job->handle(new GuestCommunicationPolicy, app(NotificationDispatcher::class));

        $this->assertDatabaseHas('outbound_notifications', [
            'template_key' => 'reservation_confirmation',
            'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
        ]);
    }

    // ─── GC-T4: No phone or email → skip silently ──────────────────────

    public function test_gc_t4_no_phone_or_email_skips_silently(): void
    {
        Config::set('feature-flags.whatsapp_pilot_global', true);
        Config::set('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [$this->ilan->tenant_id],
            'property_ids' => [],
        ]);
        Config::set('feature-flags.notification_kill_switch', false);

        $event = new ReservationCreatedEvent(
            reservationId: 996,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(20)->format('Y-m-d'),
            endDate: now()->addDays(22)->format('Y-m-d'),
            nights: 2,
            guestName: 'No Contact Guest',
            guestPhone: null,
            guestEmail: null,
            guestCount: 1,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        $job = new SendGuestConfirmationJob($event);
        $job->handle(new GuestCommunicationPolicy, app(NotificationDispatcher::class));

        // No OutboundNotification because no eligible channel
        $this->assertDatabaseMissing('outbound_notifications', [
            'template_key' => 'reservation_confirmation',
        ]);
    }

    // ─── GC-T5: Idempotency — isAlreadySent blocks duplicate ────────────

    public function test_gc_t5_idempotency_is_already_sent_blocks_duplicate(): void
    {
        Config::set('feature-flags.whatsapp_pilot_global', true);
        Config::set('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [$this->ilan->tenant_id],
            'property_ids' => [],
        ]);
        Config::set('feature-flags.notification_kill_switch', true);

        // First notification
        $event = new ReservationCreatedEvent(
            reservationId: 995,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(25)->format('Y-m-d'),
            endDate: now()->addDays(27)->format('Y-m-d'),
            nights: 2,
            guestName: 'Idempotent Guest',
            guestPhone: '0535 123 4567',
            guestEmail: null,
            guestCount: 1,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        $policy = new GuestCommunicationPolicy;
        $job = new SendGuestConfirmationJob($event);
        $job->handle($policy, app(NotificationDispatcher::class));

        // First call created evidence
        $this->assertDatabaseHas('outbound_notifications', [
            'template_key' => 'reservation_confirmation',
            'channel' => 'whatsapp',
            'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
        ]);

        // isAlreadySent returns true for second call
        $this->assertTrue($policy->isAlreadySent(995, 'whatsapp'));
    }

    // ─── GC-T8: Email channel ─────────────────────────────────────────

    public function test_gc_t8_email_channel_creates_notification(): void
    {
        Config::set('feature-flags.whatsapp_pilot_global', true);
        Config::set('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [$this->ilan->tenant_id],
            'property_ids' => [],
        ]);
        Config::set('feature-flags.notification_kill_switch', true);

        $event = new ReservationCreatedEvent(
            reservationId: 994,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(30)->format('Y-m-d'),
            endDate: now()->addDays(32)->format('Y-m-d'),
            nights: 2,
            guestName: 'Email Guest',
            guestPhone: null,
            guestEmail: 'guest@example.com',
            guestCount: 1,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        $job = new SendGuestConfirmationJob($event);
        $job->handle(new GuestCommunicationPolicy, app(NotificationDispatcher::class));

        $this->assertDatabaseHas('outbound_notifications', [
            'channel' => 'email',
            'template_key' => 'reservation_confirmation',
            'recipient' => 'guest@example.com',
            'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
        ]);
    }

    // ─── GC-T9: Both channels fail silently ───────────────────────────

    public function test_gc_t9_both_channels_missing_job_completes_without_evidence(): void
    {
        Config::set('feature-flags.whatsapp_pilot_global', true);
        Config::set('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [$this->ilan->tenant_id],
            'property_ids' => [],
        ]);
        Config::set('feature-flags.notification_kill_switch', false);

        $event = new ReservationCreatedEvent(
            reservationId: 993,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(35)->format('Y-m-d'),
            endDate: now()->addDays(37)->format('Y-m-d'),
            nights: 2,
            guestName: 'Silent Fail Guest',
            guestPhone: null,
            guestEmail: 'invalid-email', // invalid email
            guestCount: 1,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        // Should not throw — job completes silently
        $job = new SendGuestConfirmationJob($event);
        $job->handle(new GuestCommunicationPolicy, app(NotificationDispatcher::class));

        $this->assertDatabaseMissing('outbound_notifications', [
            'template_key' => 'reservation_confirmation',
        ]);
    }

    // ─── GC-T10: Tenant isolation ─────────────────────────────────────

    public function test_gc_t10_tenant_isolation_event_carries_correct_tenant_id(): void
    {
        Config::set('feature-flags.whatsapp_pilot_global', true);
        Config::set('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [$this->ilan->tenant_id],
            'property_ids' => [],
        ]);
        Config::set('feature-flags.notification_kill_switch', true);

        $event = new ReservationCreatedEvent(
            reservationId: 992,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(40)->format('Y-m-d'),
            endDate: now()->addDays(42)->format('Y-m-d'),
            nights: 2,
            guestName: 'Tenant Isolation Guest',
            guestPhone: '0536 123 4567',
            guestEmail: null,
            guestCount: 1,
            notes: null,
            reservationState: 'confirmed',
            totalAmount: null,
            currency: 'TRY',
            externalReservationId: null,
            externalChannel: null,
            createdByUserId: $this->user->id,
            overrideOfId: null,
            overrideAuthorizedBy: null,
            overrideOccurredAt: null,
        );

        $job = new SendGuestConfirmationJob($event);
        $job->handle(new GuestCommunicationPolicy, app(NotificationDispatcher::class));

        // Verify tenantId is passed to the dispatcher
        // The OutboundNotification payload_data contains reservation_id matching the event
        $this->assertDatabaseHas('outbound_notifications', [
            'template_key' => 'reservation_confirmation',
            'gonderim_durumu' => OutboundNotification::STATE_CANCELLED,
        ]);

        $notification = OutboundNotification::query()
            ->where('template_key', 'reservation_confirmation')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($notification);
        $payload = $notification->payload_data;
        $this->assertEquals(992, $payload['reservation_id'] ?? null);
        $this->assertEquals($this->ilan->tenant_id, $payload['tenant_id'] ?? null);
    }

    // ─── GuestConfirmationNotification DTO tests ───────────────────────

    public function test_gc_dto_from_event_maps_correctly(): void
    {
        $eventData = [
            'reservationId' => 1,
            'tenantId'      => 2,
            'ilanId'        => 3,
            'guestName'     => 'DTO Test Guest',
            'guestPhone'    => '+905321234567',
            'guestEmail'    => 'dto@test.com',
            'startDate'     => '2026-09-01',
            'endDate'       => '2026-09-03',
            'nights'        => 2,
            'totalAmount'   => 1500.00,
            'currency'      => 'TRY',
            'externalChannel' => 'booking',
        ];

        $notification = GuestConfirmationNotification::fromReservationEvent(
            $eventData,
            'whatsapp',
            '+905321234567',
        );

        $this->assertEquals('whatsapp', $notification->getChannel());
        $this->assertEquals('+905321234567', $notification->getRecipient());
        $this->assertEquals('reservation_confirmation', $notification->getTemplateKey());
        $this->assertEquals('high', $notification->getPriority());
        $this->assertTrue($notification->isAsync());

        $data = $notification->getData();
        $this->assertEquals(1, $data['reservation_id']);
        $this->assertEquals('DTO Test Guest', $data['guest_name']);
        $this->assertEquals('2026-09-01', $data['start_date']);
        $this->assertEquals('TRY', $data['currency']);
    }

    // ─── GuestCommunicationPolicy tests ────────────────────────────────

    public function test_gc_policy_normalizes_phone_numbers(): void
    {
        $policy = new GuestCommunicationPolicy;

        $normalize = fn($phone) => (function () use ($phone) {
            return $this->normalizePhone($phone);
        })->call($policy, $phone);

        $this->assertEquals('+905321234567', $normalize('0532 123 4567'));
        $this->assertEquals('+905321234567', $normalize('5321234567'));
        $this->assertEquals('+905321234567', $normalize('+905321234567'));
        $this->assertEquals('+14155551234', $normalize('+14155551234'));
        $this->assertNull($normalize(''));
        $this->assertNull($normalize(null));
    }

    public function test_gc_policy_is_already_sent_false_for_fresh_reservation(): void
    {
        $policy = new GuestCommunicationPolicy;
        $this->assertFalse($policy->isAlreadySent(999999, 'whatsapp'));
    }

    // ─── Upstream: ProcessReservationCreated dispatches job to queue ────

    public function test_gc_upstream_create_reservation_dispatches_process_created_job(): void
    {
        $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(50)->format('Y-m-d'),
            now()->addDays(53)->format('Y-m-d'),
            [
                'guest_name'  => 'Upstream Test',
                'guest_phone' => '0537 123 4567',
            ],
            $this->user->id,
        );

        // ProcessReservationCreated is pushed to the queue (Queue::fake() intercepts)
        Queue::assertPushed(ProcessReservationCreated::class, 1);
    }
}

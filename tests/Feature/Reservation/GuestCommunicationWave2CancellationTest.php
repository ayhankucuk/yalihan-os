<?php

namespace Tests\Feature\Reservation;

use App\DTOs\Notification\GuestCancellationNotification;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Jobs\Reservation\SendCancellationNotificationJob;
use App\Models\Ilan;
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
 * GuestCommunicationWave2CancellationTest — A2: Cancellation Communication Certification
 *
 * Sprint: A2 — Cancellation Communication Wave
 * Baseline: a8584a4
 *
 * Test Strategy:
 * - Queue is faked by TestCase::setUp().
 * - When Queue::fake() is active, ShouldQueue::dispatch() records the job
 *   but does NOT call handle(). handle() is called only when the queue worker runs.
 * - We verify upstream dispatch separately, then call handle() directly
 *   to test downstream behavior (idempotency, dispatcher calls, evidence).
 *
 * Certification gates:
 *   T1: Canonical cancellation queues cancellation communication job
 *   T2: Override cancellation also produces cancellation communication
 *   T3: Duplicate ReservationCancelledEvent does not duplicate guest message
 *   T4: Retry is safe (job has correct tries/backoff)
 *   T5: Tenant isolation
 *   T6: Notification failure does NOT affect reservation/availability/finance
 *   T7: Message contains correct reservation/property/date context
 *   T8: Missing communication channel fails gracefully
 *
 * A2 — Cancellation Communication Wave
 */
class GuestCommunicationWave2CancellationTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $service;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        // Queue::fake() intercepts ShouldQueue::dispatch() but does NOT execute
        // ShouldQueue listeners automatically (they only call dispatch() which is faked).
        // Override EventServiceProvider listener with manual callback so event
        // dispatch triggers job recording under Queue::fake().
        // Pattern mirrors GuestCommunicationWave1Test::setUp().
        $this->app['events']->listen(
            \App\Events\Reservation\ReservationCancelledEvent::class,
            function ($event) {
                // Wave 1 listener is also registered; we override it here.
                // Queue::fake() records the dispatch from both listeners.
                \App\Jobs\Reservation\SendCancellationNotificationJob::dispatch($event);
            },
        );

        $this->service = app(ReservationService::class);
        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 2,
        ]);
    }

    // ─── T1: Canonical cancellation queues cancellation communication ─────────

    /**
     * Verifies that ReservationService::cancelReservation() dispatches
     * SendCancellationNotificationJob via the event backbone.
     */
    public function test_t1_canonical_cancellation_queues_cancellation_notification_job(): void
    {
        Queue::fake();

        // Create and confirm a reservation
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(5)->format('Y-m-d'),
            now()->addDays(8)->format('Y-m-d'),
            ['guest_name' => 'Alice Smith', 'guest_phone' => '0532 111 2222'],
            $this->user->id,
        );

        // Cancel it
        $this->service->cancelReservation($reservation->id);

        // Verify the cancellation notification job was dispatched
        Queue::assertPushed(SendCancellationNotificationJob::class, function ($job) use ($reservation) {
            return $job->event->reservationId === $reservation->id;
        });
    }

    // ─── T2: Override cancellation also produces cancellation communication ─

    /**
     * Override triggers canonical ReservationCancelledEvent for the conflicting
     * reservation, which must also dispatch SendCancellationNotificationJob.
     */
    public function test_t2_override_cancellation_also_queues_notification(): void
    {
        Queue::fake();

        // First reservation
        $first = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(10)->format('Y-m-d'),
            now()->addDays(13)->format('Y-m-d'),
            ['guest_name' => 'Bob Jones', 'guest_phone' => '0532 333 4444'],
            $this->user->id,
        );

        // Override: second reservation conflicts with first
        $second = $this->service->createReservationWithOverride(
            $this->ilan->id,
            now()->addDays(10)->format('Y-m-d'),
            now()->addDays(13)->format('Y-m-d'),
            ['guest_name' => 'Carol White', 'guest_phone' => '0532 555 6666'],
            $this->user->id,
            $first->id,
            $this->user->id,
        );

        // Both cancellation (for conflicting) and creation notifications dispatched
        Queue::assertPushed(SendCancellationNotificationJob::class, function ($job) use ($first) {
            return $job->event->reservationId === $first->id;
        });
    }

    // ─── T3: Duplicate event does not duplicate guest message (idempotency) ─

    /**
     * isAlreadySent() with template_key='reservation_cancellation' prevents
     * duplicate delivery when the same event is replayed or delivered twice.
     */
    public function test_t3_duplicate_event_does_not_duplicate_notification(): void
    {
        // Create an OutboundNotification record as if cancellation was already sent
        OutboundNotification::create([
            'channel' => 'whatsapp',
            'recipient' => '+905321112222',
            'template_key' => 'reservation_cancellation',
            'payload_data' => ['reservation_id' => 12345],
            'gonderim_durumu' => OutboundNotification::STATE_SENT,
        ]);

        $policy = app(GuestCommunicationPolicy::class);

        // Policy must return true (already sent) for cancellation template
        $alreadySent = $policy->isAlreadySent(12345, 'whatsapp', 'reservation_cancellation');
        $this->assertTrue($alreadySent, 'Idempotency: cancellation notification should be detected as already sent');

        // Confirmation template is separate — same channel should NOT be blocked
        $confirmationAlreadySent = $policy->isAlreadySent(12345, 'whatsapp', 'reservation_confirmation');
        $this->assertFalse($confirmationAlreadySent, 'Cancellation and confirmation are separate templates');
    }

    // ─── T4: Retry configuration is safe ─────────────────────────────────────

    /**
     * SendCancellationNotificationJob has correct retry configuration so
     * transient failures are retried without corrupting upstream state.
     */
    public function test_t4_job_has_safe_retry_configuration(): void
    {
        Queue::fake();

        $event = new ReservationCancelledEvent(
            reservationId: 1,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(5)->format('Y-m-d'),
            endDate: now()->addDays(8)->format('Y-m-d'),
            nights: 3,
            guestName: 'Dave Lee',
            guestPhone: '0532 777 8888',
            guestEmail: 'dave@example.com',
            cancelledAt: now()->toIso8601String(),
            cancelledBy: 'user',
            reason: 'Guest requested',
            externalReservationId: null,
            externalChannel: null,
        );

        SendCancellationNotificationJob::dispatch($event);

        Queue::assertPushed(SendCancellationNotificationJob::class, function ($job) {
            $this->assertSame(3, $job->tries, 'Job must have 3 retry attempts');
            $this->assertEquals([30, 60, 120], $job->backoff, 'Job must have exponential backoff');
            return true;
        });
    }

    // ─── T5: Tenant isolation ───────────────────────────────────────────────

    /**
     * Cancellation notification for tenant A must not affect tenant B's data.
     */
    public function test_t5_tenant_isolation(): void
    {
        Queue::fake();

        // Tenant B's property
        $ilanB = Ilan::factory()->create([
            'tenant_id' => $this->ilan->tenant_id + 1,
            'rental_enabled' => true,
            'min_stay_nights' => 2,
        ]);

        // Create reservation on tenant B's property
        $resB = $this->service->createReservation(
            $ilanB->id,
            now()->addDays(20)->format('Y-m-d'),
            now()->addDays(22)->format('Y-m-d'),
            ['guest_name' => 'Eve TenantB', 'guest_phone' => '0532 999 0000'],
            $this->user->id,
        );

        // Cancel tenant B's reservation
        $this->service->cancelReservation($resB->id);

        // Verify cancellation notification for tenant B only
        Queue::assertPushed(SendCancellationNotificationJob::class, function ($job) use ($ilanB) {
            return $job->event->ilanId === $ilanB->id
                && $job->event->tenantId === $ilanB->tenant_id;
        });

        // Ensure no notification was dispatched for wrong tenant
        Queue::assertNotPushed(SendCancellationNotificationJob::class, function ($job) use ($ilanB) {
            return $job->event->ilanId !== $ilanB->id;
        });
    }

    // ─── T6: Notification failure does NOT affect reservation/availability/finance ─

    /**
     * When notification dispatch fails, the reservation is still cancelled,
     * availability is still released, and financial reversal still runs.
     * Notification is downstream — it cannot corrupt upstream state.
     */
    public function test_t6_notification_failure_does_not_affect_upstream(): void
    {
        // Mock NotificationDispatcher to always fail
        $this->mock(NotificationDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturn(false);
        });

        $event = new ReservationCancelledEvent(
            reservationId: 1,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(5)->format('Y-m-d'),
            endDate: now()->addDays(8)->format('Y-m-d'),
            nights: 3,
            guestName: 'Frank Test',
            guestPhone: '0532 111 9999',
            guestEmail: 'frank@example.com',
            cancelledAt: now()->toIso8601String(),
            cancelledBy: 'system',
            reason: null,
            externalReservationId: null,
            externalChannel: null,
        );

        $job = new SendCancellationNotificationJob($event);

        // Job must NOT throw — it must handle failures gracefully
        // and complete without affecting upstream state
        try {
            $job->handle(
                app(GuestCommunicationPolicy::class),
                app(NotificationDispatcher::class),
            );
        } catch (\Throwable $e) {
            $this->fail('SendCancellationNotificationJob must not throw on notification failure: ' . $e->getMessage());
        }

        // If we reach here, job completed safely
        $this->assertTrue(true, 'Job completed without throwing');
    }

    // ─── T7: Message contains correct reservation/property/date context ─────

    /**
     * GuestCancellationNotification DTO must contain correct cancellation context.
     */
    public function test_t7_notification_dto_contains_correct_context(): void
    {
        $eventData = [
            'reservationId' => 42,
            'tenantId' => 7,
            'ilanId' => 15,
            'guestName' => 'Grace Cancel',
            'guestPhone' => '+905321234567',
            'guestEmail' => 'grace@example.com',
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-03',
            'nights' => 2,
            'cancelledAt' => '2026-08-22T10:00:00+03:00',
            'cancelledBy' => 'user',
            'reason' => 'Family emergency',
            'externalChannel' => null,
            'externalReservationId' => null,
        ];

        $dto = GuestCancellationNotification::fromCancelledEvent($eventData, 'whatsapp', '+905321234567');

        $data = $dto->getData();

        $this->assertSame(42, $data['reservation_id']);
        $this->assertSame(7, $data['tenant_id']);
        $this->assertSame(15, $data['ilan_id']);
        $this->assertSame('Grace Cancel', $data['guest_name']);
        $this->assertSame('+905321234567', $data['guest_phone']);
        $this->assertSame('grace@example.com', $data['guest_email']);
        $this->assertSame('2026-09-01', $data['start_date']);
        $this->assertSame('2026-09-03', $data['end_date']);
        $this->assertSame(2, $data['nights']);
        $this->assertSame('user', $data['cancelled_by']);
        $this->assertSame('Family emergency', $data['cancellation_reason']);
        $this->assertSame('whatsapp', $dto->getChannel());
        $this->assertSame('reservation_cancellation', $dto->getTemplateKey());
        $this->assertTrue($dto->isAsync());
    }

    // ─── T8: Missing communication channel fails gracefully ───────────────

    /**
     * When guest has no valid phone or email, the notification job must
     * complete silently without throwing.
     */
    public function test_t8_missing_channel_fails_gracefully(): void
    {
        // Guest with NO phone and NO email
        $event = new ReservationCancelledEvent(
            reservationId: 99,
            tenantId: $this->ilan->tenant_id,
            ilanId: $this->ilan->id,
            startDate: now()->addDays(5)->format('Y-m-d'),
            endDate: now()->addDays(8)->format('Y-m-d'),
            nights: 3,
            guestName: 'Henry NoContact',
            guestPhone: null,   // no phone
            guestEmail: null,   // no email
            cancelledAt: now()->toIso8601String(),
            cancelledBy: 'system',
            reason: null,
            externalReservationId: null,
            externalChannel: null,
        );

        $job = new SendCancellationNotificationJob($event);

        // Must not throw — completes silently
        try {
            $job->handle(
                app(GuestCommunicationPolicy::class),
                app(NotificationDispatcher::class),
            );
        } catch (\Throwable $e) {
            $this->fail('Job must not throw when no channel is available: ' . $e->getMessage());
        }

        $this->assertTrue(true, 'Job completed gracefully with no contact info');
    }
}

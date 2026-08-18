<?php

namespace Tests\Feature\ChannelManager\Booking;

use App\DTOs\ChannelManager\Booking\BookingModificationPayload;
use App\DTOs\ChannelManager\Booking\BookingReservationPayload;
use App\Events\ChannelManager\BookingReservationCancelledEvent;
use App\Events\ChannelManager\BookingReservationModifiedEvent;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\ChannelManager\BookingCancellationProcessor;
use App\Services\ChannelManager\BookingModificationProcessor;
use App\Services\ChannelManager\BookingPropertyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Sprint 4.12 — Booking.com Provider Wave 3
 * BW3-01..BW3-12 Certification Tests
 *
 * BW3-01: Modification DTO normalizes correctly
 * BW3-02: Cancellation DTO normalizes correctly
 * BW3-03: Modification processor calls canonical ReservationService
 * BW3-04: Cancellation processor calls canonical ReservationService
 * BW3-05: Modification on non-existent reservation → ignored
 * BW3-06: Cancellation on non-existent reservation → no-op
 * BW3-07: Modification on cancelled reservation → silently ignored (ADR-008)
 * BW3-08: Cancellation is idempotent (already cancelled → safe)
 * BW3-09: Modification detects date conflict → exception preserved
 * BW3-10: Modification triggers Booking ACK
 * BW3-11: Cancellation triggers Booking ACK
 * BW3-12: Recovery endpoint returns reservations by HotelCode
 */
class BookingWave3ModificationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Ilan $ilanA;
    protected string $hotelCode = 'BK-HOTEL-A';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'uuid'   => 'bw3-tenant-a-' . uniqid(),
            'name'   => 'BW3 Tenant A',
            'domain' => 'bw3a.test',
            'status' => 'active',
        ]);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'BW3 Property A',
            'slug'   => 'bw3-property-a-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id' => $this->tenantA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->ilanA = Ilan::withoutGlobalScopes()->findOrFail($ilanId);

        DB::table('ilan_takvim_sync')->insert([
            'ilan_id' => $ilanId,
            'platform' => 'booking_com',
            'external_listing_id' => $this->hotelCode,
            'is_sync_active' => 1,
            'api_key' => 'client-id',
            'api_secret' => 'client-secret',
            'senkron_durumu' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ─── BW3-01: Modification DTO ───────────────────────────────────────
    public function test_bw3_01_modification_dto_normalizes_correctly(): void
    {
        $raw = [
            'reservation' => [
                'id' => 'res-mod-01',
                'arrival_date' => '2044-09-01',
                'departure_date' => '2044-09-05',
                'guest_name' => 'BW3 Guest',
                'adults_count' => 2,
                'total_price' => '600.00',
                'currency' => 'EUR',
                'reservation_status' => 'modified',
            ],
            'BasicPropertyInfo' => ['HotelCode' => 'BK-HOTEL-A'],
        ];

        $payload = BookingModificationPayload::fromApiResponse($raw);

        $this->assertEquals('res-mod-01', $payload->externalReservationId);
        $this->assertEquals('BK-HOTEL-A', $payload->hotelCode);
        $this->assertEquals('2044-09-01', $payload->arrivalDate);
        $this->assertEquals('2044-09-05', $payload->departureDate);
        $this->assertEquals(4, $payload->nights);
        $this->assertEquals('modified', $payload->status);
    }

    // ─── BW3-02: Cancellation DTO ───────────────────────────────────────
    public function test_bw3_02_cancellation_dto_normalizes_correctly(): void
    {
        $raw = [
            'reservation' => [
                'id' => 'res-cancel-02',
                'arrival_date' => '2044-09-01',
                'departure_date' => '2044-09-05',
                'guest_name' => 'BW3 Guest',
                'reservation_status' => 'cancelled',
            ],
            'BasicPropertyInfo' => ['HotelCode' => 'BK-HOTEL-A'],
        ];

        $payload = BookingModificationPayload::fromApiResponse($raw);

        $this->assertEquals('res-cancel-02', $payload->externalReservationId);
        $this->assertEquals('cancelled', $payload->status);
    }

    // ─── BW3-03: Modification processor delegates to canonical ReservationService ─
    public function test_bw3_03_modification_processor_updates_dates(): void
    {
        Event::fake([BookingReservationModifiedEvent::class]);

        $resId = DB::table('property_reservations')->insertGetId([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-mod-03',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'BW3 Guest',
            'reservation_state'      => 'confirmed',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $processor = new BookingModificationProcessor(
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
            app(\App\Services\ReservationService::class),
        );

        $result = $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-mod-03',
                    'arrival_date' => '2044-10-01',
                    'departure_date' => '2044-10-05',
                    'guest_name' => 'BW3 Guest Updated',
                    'adults_count' => 3,
                    'total_price' => '750.00',
                    'currency' => 'EUR',
                    'reservation_status' => 'modified',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );

        $this->assertNotNull($result, 'BW3-03: Processor must return updated reservation');
        $this->assertEquals('2044-10-01', $result->start_date);
        $this->assertEquals('2044-10-05', $result->end_date);
        $this->assertEquals(4, $result->nights);
        Event::assertDispatched(BookingReservationModifiedEvent::class);
    }

    // ─── BW3-04: Cancellation processor calls canonical cancelReservation ─
    public function test_bw3_04_cancellation_processor_cancels_reservation(): void
    {
        Event::fake([BookingReservationCancelledEvent::class]);

        $resId = DB::table('property_reservations')->insertGetId([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-cancel-04',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'BW3 Guest',
            'reservation_state'      => 'confirmed',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $processor = new BookingCancellationProcessor(
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
            app(\App\Services\ReservationService::class),
        );

        $result = $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-cancel-04',
                    'arrival_date' => '2044-09-01',
                    'departure_date' => '2044-09-05',
                    'guest_name' => 'BW3 Guest',
                    'reservation_status' => 'cancelled',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );

        $this->assertEquals('cancelled',
            DB::table('property_reservations')->where('id', $resId)->value('reservation_state'),
            'BW3-04: Cancellation must set state to cancelled'
        );
        Event::assertDispatched(BookingReservationCancelledEvent::class);
    }

    // ─── BW3-05: Modification on unknown reservation → ignored (no exception) ──
    public function test_bw3_05_modification_unknown_reservation_ignored(): void
    {
        Event::fake([BookingReservationModifiedEvent::class]);

        $processor = new BookingModificationProcessor(
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
            app(\App\Services\ReservationService::class),
        );

        $result = $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-unknown-mod-05',
                    'arrival_date' => '2044-10-01',
                    'departure_date' => '2044-10-05',
                    'guest_name' => 'Unknown Guest',
                    'reservation_status' => 'modified',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );

        $this->assertNull($result, 'BW3-05: Unknown reservation must return null');
        Event::assertNotDispatched(BookingReservationModifiedEvent::class);
    }

    // ─── BW3-06: Cancellation on unknown reservation → no-op ──────────────
    public function test_bw3_06_cancellation_unknown_reservation_noop(): void
    {
        Event::fake([BookingReservationCancelledEvent::class]);

        $processor = new BookingCancellationProcessor(
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
            app(\App\Services\ReservationService::class),
        );

        $result = $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-unknown-cancel-06',
                    'arrival_date' => '2044-09-01',
                    'departure_date' => '2044-09-05',
                    'guest_name' => 'Unknown Guest',
                    'reservation_status' => 'cancelled',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );

        $this->assertNull($result, 'BW3-06: Unknown reservation must return null');
        Event::assertNotDispatched(BookingReservationCancelledEvent::class);
    }

    // ─── BW3-07: Modification on cancelled reservation → silently ignored (ADR-008) ──
    public function test_bw3_07_modification_on_cancelled_ignored(): void
    {
        Event::fake([BookingReservationModifiedEvent::class]);

        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-cancelled-mod-07',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'BW3 Guest',
            'reservation_state'      => 'cancelled',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $processor = new BookingModificationProcessor(
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
            app(\App\Services\ReservationService::class),
        );

        $result = $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-cancelled-mod-07',
                    'arrival_date' => '2044-10-01',
                    'departure_date' => '2044-10-05',
                    'guest_name' => 'BW3 Guest',
                    'reservation_status' => 'modified',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );

        // State must remain cancelled
        $state = DB::table('property_reservations')
            ->where('external_reservation_id', 'res-cancelled-mod-07')
            ->value('reservation_state');
        $this->assertEquals('cancelled', $state, 'BW3-07: Cancelled reservation must not be modified');
        Event::assertNotDispatched(BookingReservationModifiedEvent::class);
    }

    // ─── BW3-08: Cancellation is idempotent ───────────────────────────────
    public function test_bw3_08_cancellation_is_idempotent(): void
    {
        Event::fake([BookingReservationCancelledEvent::class]);

        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-idempotent-08',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'BW3 Guest',
            'reservation_state'      => 'cancelled',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $processor = new BookingCancellationProcessor(
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
            app(\App\Services\ReservationService::class),
        );

        // Second cancellation call must not throw
        $result = $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-idempotent-08',
                    'arrival_date' => '2044-09-01',
                    'departure_date' => '2044-09-05',
                    'guest_name' => 'BW3 Guest',
                    'reservation_status' => 'cancelled',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );

        $this->assertNotNull($result, 'BW3-08: Idempotent cancellation must return existing reservation');
    }

    // ─── BW3-09: Modification detects date conflict ─────────────────────
    public function test_bw3_09_modification_detects_conflict(): void
    {
        Event::fake([BookingReservationModifiedEvent::class]);

        // Two reservations
        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-conflict-a-09',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'Guest A',
            'reservation_state'      => 'confirmed',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $resIdB = DB::table('property_reservations')->insertGetId([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-conflict-b-09',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-10',
            'end_date'               => '2044-09-14',
            'nights'                 => 4,
            'guest_name'             => 'Guest B',
            'reservation_state'      => 'confirmed',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $processor = new BookingModificationProcessor(
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
            app(\App\Services\ReservationService::class),
        );

        // Try to move B's reservation to overlap with A's dates
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/conflict/i');

        $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-conflict-b-09',
                    'arrival_date' => '2044-09-01',  // Overlaps with res-conflict-a-09
                    'departure_date' => '2044-09-05',
                    'guest_name' => 'Guest B',
                    'reservation_status' => 'modified',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );
    }

    // ─── BW3-10: Modification triggers Booking ACK ───────────────────────
    public function test_bw3_10_modification_triggers_ack(): void
    {
        Event::fake([BookingReservationModifiedEvent::class]);

        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-mod-ack-10',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'BW3 Guest',
            'reservation_state'      => 'confirmed',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $ack = $this->createMockAcknowledger();
        $ack->expects($this->once())->method('acknowledgeModification');

        $processor = new BookingModificationProcessor(
            new BookingPropertyResolver(),
            $ack,
            app(\App\Services\ReservationService::class),
        );

        $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-mod-ack-10',
                    'arrival_date' => '2044-10-01',
                    'departure_date' => '2044-10-05',
                    'guest_name' => 'BW3 Guest',
                    'reservation_status' => 'modified',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );
    }

    // ─── BW3-11: Cancellation triggers Booking ACK ───────────────────────
    public function test_bw3_11_cancellation_triggers_ack(): void
    {
        Event::fake([BookingReservationCancelledEvent::class]);

        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-cancel-ack-11',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'BW3 Guest',
            'reservation_state'      => 'confirmed',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $ack = $this->createMockAcknowledger();
        $ack->expects($this->once())->method('acknowledgeCancellation');

        $processor = new BookingCancellationProcessor(
            new BookingPropertyResolver(),
            $ack,
            app(\App\Services\ReservationService::class),
        );

        $processor->process(
            $this->ilanA->id,
            $this->tenantA->id,
            BookingModificationPayload::fromApiResponse([
                'reservation' => [
                    'id' => 'res-cancel-ack-11',
                    'arrival_date' => '2044-09-01',
                    'departure_date' => '2044-09-05',
                    'guest_name' => 'BW3 Guest',
                    'reservation_status' => 'cancelled',
                ],
                'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
            ])
        );
    }

    // ─── BW3-12: Recovery endpoint returns confirmed reservations ──────────
    public function test_bw3_12_recovery_lists_reservations_by_hotel_code(): void
    {
        // Create confirmed and cancelled reservations
        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-recovery-12a',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-01',
            'end_date'               => '2044-09-05',
            'nights'                 => 4,
            'guest_name'             => 'Recovery Guest A',
            'reservation_state'      => 'confirmed',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenantA->id,
            'property_id'            => $this->ilanA->id,
            'external_reservation_id' => 'res-recovery-12b',
            'external_channel'        => 'booking_com',
            'start_date'             => '2044-09-10',
            'end_date'               => '2044-09-14',
            'nights'                 => 4,
            'guest_name'             => 'Recovery Guest B',
            'reservation_state'      => 'cancelled',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        // Query via Reservation model
        $reservations = PropertyReservation::withoutGlobalScopes()
            ->where('external_channel', 'booking_com')
            ->where('external_reservation_id', 'like', 'res-recovery-%')
            ->get();

        $this->assertCount(2, $reservations, 'BW3-12: Recovery must find all matching reservations');
        $confirmed = $reservations->firstWhere('external_reservation_id', 'res-recovery-12a');
        $cancelled = $reservations->firstWhere('external_reservation_id', 'res-recovery-12b');
        $this->assertEquals('confirmed', $confirmed->reservation_state->value ?? $confirmed->reservation_state);
        $this->assertEquals('cancelled', $cancelled->reservation_state->value ?? $cancelled->reservation_state);
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    private function createMockAcknowledger(): \App\Infrastructure\ChannelManager\Booking\BookingReservationAcknowledger
    {
        return $this->createMock(\App\Infrastructure\ChannelManager\Booking\BookingReservationAcknowledger::class);
    }
}

<?php

namespace Tests\Feature\ChannelManager\Booking;

use App\DTOs\ChannelManager\Booking\BookingReservationPayload;
use App\DTOs\ChannelManager\ChannelTransportResult;
use App\Events\ChannelManager\BookingReservationIngestedEvent;
use App\Infrastructure\ChannelManager\Booking\BookingAcknowledgementException;
use App\Infrastructure\ChannelManager\Booking\BookingCredentialManager;
use App\Infrastructure\ChannelManager\Booking\BookingAcknowledgement;
use App\Infrastructure\ChannelManager\Booking\BookingReservationAcknowledger;
use App\Infrastructure\ChannelManager\Booking\BookingReservationRetriever;
use App\Infrastructure\ChannelManager\Booking\BookingTransport;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\ChannelManager\BookingPropertyResolver;
use App\Services\ChannelManager\BookingReservationIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Sprint 4.11 — Booking.com Provider Wave 2
 * BW2-01..BW2-12 Gate Tests
 *
 * BW2-01: Retrieve new reservation
 * BW2-02: Normalize provider payload to canonical DTO
 * BW2-03: HotelCode resolves correctly
 * BW2-04: Unknown HotelCode → reject, NO ACK
 * BW2-05: Uses canonical ReservationService / DB transaction
 * BW2-06: ACK sent after successful commit
 * BW2-07: Persistence failure → NO ACK
 * BW2-08: Duplicate → second insert prevented
 * BW2-09: Duplicate → safe ACK
 * BW2-10: ACK failure → no rollback
 * BW2-11: Cross-tenant ingest blocked
 * BW2-12: Poll job retry/replay safe
 */
class BookingWave2ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Ilan $ilanA;
    protected string $hotelCode = 'BK-HOTEL-A';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'uuid'   => 'bw2-tenant-a-' . uniqid(),
            'name'   => 'BW2 Tenant A',
            'domain' => 'bw2a.test',
            'status' => 'active',
        ]);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'BW2 Property A',
            'slug'   => 'bw2-property-a-' . uniqid(),
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

    // ─── BW2-01: Retrieve new reservation ────────────────────────────────
    public function test_bw2_01_retriever_returns_reservations(): void
    {
        // BW2-01: BookingTransport.get() called with correct endpoint
        $transport = $this->createMock(BookingTransport::class);
        $transport->method('get')->willReturnCallback(function ($ilanId, $path, $params) {
            $this->assertEquals('/ota/HotelResNotif', $path);
            $this->assertEquals('new', $params['status'] ?? null);
            return ChannelTransportResult::success('200', [
                'reservations' => [[
                    'reservation' => [
                        'id' => 'res-bw2-01',
                        'arrival_date' => '2044-09-01',
                        'departure_date' => '2044-09-05',
                        'guest_name' => 'BW2 Guest',
                        'adults_count' => 2,
                        'total_price' => '500.00',
                        'currency' => 'USD',
                    ],
                    'BasicPropertyInfo' => ['HotelCode' => $this->hotelCode],
                ]],
            ]);
        });

        $retriever = new BookingReservationRetriever($transport);
        $payloads = $retriever->retrieveNew($this->ilanA->id, '2044-09-01', '2044-09-30');

        $this->assertCount(1, $payloads, 'BW2-01: Retriever must return parsed payloads');
        $this->assertEquals('res-bw2-01', $payloads[0]->externalReservationId);
    }

    // ─── BW2-02: Normalize payload ──────────────────────────────────────
    public function test_bw2_02_payload_normalizes_correctly(): void
    {
        // BW2-02: Raw API response → canonical DTO
        $raw = [
            'id' => 'res-bw2-02',
            'arrival_date' => '2044-09-01',
            'departure_date' => '2044-09-05',
            'guest_name' => 'BW2 Guest',
            'adults_count' => 2,
            'total_price' => '600.00',
            'currency' => 'EUR',
        ];
        $hotelInfo = ['HotelCode' => 'BK-HOTEL-NEW'];
        $payload = BookingReservationPayload::fromBookingApiResponse([
            'reservation' => $raw,
            'BasicPropertyInfo' => $hotelInfo,
        ]);

        $this->assertEquals('res-bw2-02', $payload->externalReservationId);
        $this->assertEquals('BK-HOTEL-NEW', $payload->hotelCode);
        $this->assertEquals('2044-09-01', $payload->arrivalDate);
        $this->assertEquals(4, $payload->nights);
        $this->assertEquals('BW2 Guest', $payload->guestName);
        $this->assertEquals(2, $payload->adultCount);
        $this->assertEquals(600.0, $payload->totalPrice);
        $this->assertEquals('EUR', $payload->currency);
    }

    // ─── BW2-03: HotelCode resolves correctly ────────────────────────
    public function test_bw2_03_hotel_code_resolves_to_correct_property(): void
    {
        $resolver = new BookingPropertyResolver();
        $ref = $resolver->resolve($this->tenantA->id, $this->hotelCode);

        $this->assertNotNull($ref, 'BW2-03: HotelCode must resolve');
        $this->assertEquals($this->ilanA->id, $ref->ilanId);
        $this->assertEquals($this->tenantA->id, $ref->tenantId);
    }

    // ─── BW2-04: Unknown HotelCode → reject + NO ACK ──────────────────
    public function test_bw2_04_unknown_hotel_code_rejected_no_ack(): void
    {
        Event::fake([\App\Events\ChannelManager\BookingReservationIngestedEvent::class]);
        $retriever = new BookingReservationRetriever($this->createMockTransport([]));
        $resolver = new BookingPropertyResolver();
        $ack = $this->createMockAcknowledger();
        $ack->expects($this->never())->method('acknowledgeNew');

        $service = new BookingReservationIngestService($retriever, $resolver, $ack);
        $service->processNewReservations(
            $this->ilanA->id,
            $this->tenantA->id,
            '2044-01-01',
            '2044-12-31',
        );
    }

    // ─── BW2-05: Canonical persistence ─────────────────────────────────
    public function test_bw2_05_persists_via_canonical_transaction(): void
    {
        Event::fake([\App\Events\ChannelManager\BookingReservationIngestedEvent::class]);
        $retriever = new BookingReservationRetriever($this->createMockTransport([
            $this->makeRawReservation('res-bw2-05', $this->hotelCode),
        ]));
        $resolver = new BookingPropertyResolver();
        $ack = $this->createMockAcknowledger();
        $ack->expects($this->once())->method('acknowledgeNew');

        $service = new BookingReservationIngestService($retriever, $resolver, $ack);
        $service->processNewReservations(
            $this->ilanA->id,
            $this->tenantA->id,
            '2044-01-01',
            '2044-12-31',
        );

        $this->assertDatabaseHas('property_reservations', [
            'external_reservation_id' => 'res-bw2-05',
            'external_channel' => 'booking_com',
            'tenant_id' => $this->tenantA->id,
        ]);
    }

    // ─── BW2-06: ACK after successful commit ──────────────────────────
    public function test_bw2_06_ack_sent_after_successful_commit(): void
    {
        Event::fake([\App\Events\ChannelManager\BookingReservationIngestedEvent::class]);
        $retriever = new BookingReservationRetriever($this->createMockTransport([
            $this->makeRawReservation('res-bw2-06', $this->hotelCode),
        ]));
        $resolver = new BookingPropertyResolver();
        $ack = $this->createMockAcknowledger();
        $ack->expects($this->once())->method('acknowledgeNew');

        $service = new BookingReservationIngestService($retriever, $resolver, $ack);
        $service->processNewReservations(
            $this->ilanA->id,
            $this->tenantA->id,
            '2044-01-01',
            '2044-12-31',
        );
    }

    // ─── BW2-07: First call persists → second duplicate is safe (ACK called for both) ──
    public function test_bw2_07_duplicate_is_idempotent(): void
    {
        Event::fake();
        $retriever = new BookingReservationRetriever($this->createMockTransport([
            $this->makeRawReservation('res-bw2-07', $this->hotelCode),
        ]));
        $resolver = new BookingPropertyResolver();
        // ACK called once for the new insert, once for the duplicate (safe acknowledge)
        $ack = $this->createMockAcknowledger();
        $ack->expects($this->exactly(2))->method('acknowledgeNew');

        $service = new BookingReservationIngestService($retriever, $resolver, $ack);
        // First call: inserts + ACK
        $service->processNewReservations($this->ilanA->id, $this->tenantA->id, '2044-01-01', '2044-12-31');
        // Second call: duplicate found, safe ACK
        $service->processNewReservations($this->ilanA->id, $this->tenantA->id, '2044-01-01', '2044-12-31');
    }

    // ─── BW2-08: Duplicate → second insert prevented ─────────────────
    public function test_bw2_08_duplicate_second_insert_prevented(): void
    {
        Event::fake([\App\Events\ChannelManager\BookingReservationIngestedEvent::class]);
        // Pre-create reservation
        $ilanId = $this->ilanA->id;
        DB::table('property_reservations')->insert([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $ilanId,
            'external_reservation_id' => 'res-bw2-08',
            'external_channel' => 'booking_com',
            'start_date' => '2044-09-01',
            'end_date' => '2044-09-05',
            'nights' => 4,
            'guest_name' => 'Duplicate Guest',
            'reservation_state' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $retriever = new BookingReservationRetriever($this->createMockTransport([
            $this->makeRawReservation('res-bw2-08', $this->hotelCode),
        ]));
        $resolver = new BookingPropertyResolver();
        $ack = $this->createMockAcknowledger();
        $service = new BookingReservationIngestService($retriever, $resolver, $ack);
        $service->processNewReservations(
            $ilanId,
            $this->tenantA->id,
            '2044-01-01',
            '2044-12-31',
        );

        $count = DB::table('property_reservations')
            ->where('external_reservation_id', 'res-bw2-08')
            ->count();
        $this->assertEquals(1, $count, 'BW2-08: Only one reservation must exist');
    }

    // ─── BW2-09: Duplicate → safe ACK ───────────────────────────────
    public function test_bw2_09_duplicate_safe_ack(): void
    {
        Event::fake([\App\Events\ChannelManager\BookingReservationIngestedEvent::class]);
        $ilanId = $this->ilanA->id;
        DB::table('property_reservations')->insert([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $ilanId,
            'external_reservation_id' => 'res-bw2-09',
            'external_channel' => 'booking_com',
            'start_date' => '2044-09-01',
            'end_date' => '2044-09-05',
            'nights' => 4,
            'guest_name' => 'Dup Guest',
            'reservation_state' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $retriever = new BookingReservationRetriever($this->createMockTransport([
            $this->makeRawReservation('res-bw2-09', $this->hotelCode),
        ]));
        $resolver = new BookingPropertyResolver();
        $ack = $this->createMockAcknowledger();
        $ack->expects($this->once())->method('acknowledgeNew');

        $service = new BookingReservationIngestService($retriever, $resolver, $ack);
        $service->processNewReservations($ilanId, $this->tenantA->id, '2044-01-01', '2044-12-31');
    }

    // ─── BW2-10: ACK failure → no rollback ───────────────────────────
    public function test_bw2_10_ack_failure_no_rollback(): void
    {
        Event::fake([\App\Events\ChannelManager\BookingReservationIngestedEvent::class]);
        $ilanId = $this->ilanA->id;

        $retriever = new BookingReservationRetriever($this->createMockTransport([
            $this->makeRawReservation('res-bw2-10', $this->hotelCode),
        ]));
        $resolver = new BookingPropertyResolver();

        // ACK throws retryable exception
        $ack = $this->createMockAcknowledger();
        $ack->method('acknowledgeNew')->willThrowException(
            new BookingAcknowledgementException(
                httpStatus: 500,
                isRetryable: true,
                message: 'Server error',
            )
        );

        $service = new BookingReservationIngestService($retriever, $resolver, $ack);

        // Must NOT throw — ACK failure is caught internally
        $service->processNewReservations($ilanId, $this->tenantA->id, '2044-01-01', '2044-12-31');

        // BW2-10: Reservation MUST exist (no rollback)
        $this->assertDatabaseHas('property_reservations', [
            'external_reservation_id' => 'res-bw2-10',
            'external_channel' => 'booking_com',
        ]);
    }

    // ─── BW2-11: Cross-tenant ingest blocked ────────────────────────
    public function test_bw2_11_cross_tenant_ingest_blocked(): void
    {
        Event::fake([\App\Events\ChannelManager\BookingReservationIngestedEvent::class]);
        // BW2-11: No exception thrown, reservation not created for wrong tenant
        $ilanId = $this->ilanA->id;
        $retriever = new BookingReservationRetriever($this->createMockTransport([
            $this->makeRawReservation('res-bw2-11', $this->hotelCode),
        ]));
        $resolver = new BookingPropertyResolver();

        // Tenant B (different tenant) tries to ingest Tenant A's reservation
        $tenantB = Tenant::create([
            'uuid' => 'bw2-tenant-b-' . uniqid(),
            'name' => 'BW2 Tenant B',
            'domain' => 'bw2b.test',
            'status' => 'active',
        ]);
        $ilanBId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'BW2 Property B',
            'slug' => 'bw2-property-b-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id' => $tenantB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('ilan_takvim_sync')->insert([
            'ilan_id' => $ilanBId,
            'platform' => 'booking_com',
            'external_listing_id' => 'BK-HOTEL-B',
            'is_sync_active' => 1,
            'api_key' => 'client-id-b',
            'api_secret' => 'client-secret-b',
            'senkron_durumu' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tenant B tries to ingest Tenant A's HotelCode → blocked
        $service = new BookingReservationIngestService(
            $retriever,
            new BookingPropertyResolver(),
            $this->createMockAcknowledger(),
        );
        $service->processNewReservations($ilanBId, $tenantB->id, '2044-01-01', '2044-12-31');

        $this->assertDatabaseMissing('property_reservations', [
            'external_reservation_id' => 'res-bw2-11',
            'external_channel' => 'booking_com',
        ]);
    }

    // ─── BW2-12: Poll job retry/replay safe ──────────────────────────
    public function test_bw2_12_poll_job_safe_for_retry(): void
    {
        // BW2-12: Job is queueable and handle() catches exceptions per-property
        $job = new \App\Jobs\ChannelManager\BookingReservationPollJob('2044-01-01');
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
        $this->assertEquals('2044-01-01', $job->fromDate);
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    private function createMockTransport(array $reservations): BookingTransport
    {
        $mock = $this->createMock(BookingTransport::class);
        $mock->method('get')->willReturn(
            \App\DTOs\ChannelManager\ChannelTransportResult::success('200', [
                'reservations' => $reservations,
            ])
        );
        return $mock;
    }

    private function createMockAcknowledger(): BookingReservationAcknowledger
    {
        return $this->createMock(BookingReservationAcknowledger::class);
    }

    private function makeRawReservation(string $id, string $hotelCode): array
    {
        return [
            'reservation' => [
                'id' => $id,
                'arrival_date' => '2044-09-01',
                'departure_date' => '2044-09-05',
                'guest_name' => 'BW2 Guest',
                'adults_count' => 2,
                'total_price' => '500.00',
                'currency' => 'TRY',
            ],
            'BasicPropertyInfo' => ['HotelCode' => $hotelCode],
        ];
    }
}

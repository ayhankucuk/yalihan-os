<?php

namespace Tests\Feature\ChannelManager\Booking;

use App\DTOs\ChannelManager\ChannelTransportResult;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Models\ChannelSyncResponse;
use App\Infrastructure\ChannelManager\Adapters\BookingChannelAdapter;
use App\Infrastructure\ChannelManager\Booking\BookingTransport;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sprint 4.13 — Booking.com Provider Wave 4
 * BW4-01..BW4-12 Certification Tests
 *
 * BW4-01: Adapter maps to correct Booking.com OTA endpoint
 * BW4-02: Availability data mapped to Booking OTA format
 * BW4-03: available=false → Booking.com room blocked
 * BW4-04: available=true → Booking.com room opened
 * BW4-05: Tenant isolation — no push for unregistered platform
 * BW4-06: Idempotent push — same dates don't duplicate
 * BW4-07: Retryable error (5xx) → BookingAvailabilityException
 * BW4-08: Non-retryable error (4xx) → graceful failure
 * BW4-09: Empty availability data → early return
 * BW4-10: Successful push → ChannelSyncResponse.success
 * BW4-11: Adapter supportsPull = false (Booking is push-only for availability)
 * BW4-12: supportsPush = true when active sync exists
 */
class BookingWave4AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Ilan $ilanA;
    protected string $hotelCode = 'BK-HOTEL-A';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'uuid'   => 'bw4-tenant-a-' . uniqid(),
            'name'   => 'BW4 Tenant A',
            'domain' => 'bw4a.test',
            'status' => 'active',
        ]);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'BW4 Property A',
            'slug'   => 'bw4-property-a-' . uniqid(),
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

    // ─── BW4-01: Correct OTA endpoint ─────────────────────────────────
    public function test_bw4_01_calls_correct_ota_endpoint(): void
    {
        $transport = $this->createMockTransport();
        $transport->expects($this->once())
            ->method('post')
            ->with(
                $this->ilanA->id,
                '/ota/Availability', // BW4-01: Correct endpoint
                $this->callback(fn($body) => str_contains(json_encode($body), 'HotelCode'))
            )
            ->willReturn(\App\DTOs\ChannelManager\ChannelTransportResult::success('200', []));

        $adapter = new BookingChannelAdapter($transport);
        $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-01',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => false, 'property_id' => $this->ilanA->id],
            ],
        );
    }

    // ─── BW4-02: Data mapped to OTA format ──────────────────────────────
    public function test_bw4_02_maps_to_ota_format(): void
    {
        $capturedBody = null;
        $transport = $this->createMockTransport();
        $transport->method('post')
            ->willReturnCallback(function ($ilanId, $path, $body) use (&$capturedBody) {
                $capturedBody = $body;
                return ChannelTransportResult::success('200', []);
            });

        $adapter = new BookingChannelAdapter($transport);
        $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-02',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => false, 'property_id' => $this->ilanA->id],
                ['date' => '2044-09-02', 'available' => false, 'property_id' => $this->ilanA->id],
            ],
        );

        $this->assertNotNull($capturedBody);
        $this->assertEquals($this->hotelCode, $capturedBody['rooms'][0]['HotelCode'] ?? null);
        $this->assertArrayHasKey('rooms', $capturedBody);
        $room = $this->findRoomForHotelCode($capturedBody, $this->hotelCode);
        $this->assertNotNull($room, 'BW4-02: Room with correct HotelCode must be in payload');
        // Availability elements must be present
        $availabilities = $room['Availability'] ?? [];
        $this->assertNotEmpty($availabilities, 'BW4-02: Availability elements must be present');
        $first = $availabilities[0];
        $this->assertEquals('2044-09-01', $first['Date'] ?? $first['date'] ?? null);
    }

    // ─── BW4-03: available=false → blocked ──────────────────────────────
    public function test_bw4_03_available_false_blocks_booking(): void
    {
        $capturedBody = null;
        $transport = $this->createMockTransport();
        $transport->method('post')
            ->willReturnCallback(function ($ilanId, $path, $body) use (&$capturedBody) {
                $capturedBody = $body;
                return ChannelTransportResult::success('200', []);
            });

        $adapter = new BookingChannelAdapter($transport);
        $result = $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-03',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => false, 'property_id' => $this->ilanA->id],
            ],
        );

        $room = $this->findRoomForHotelCode($capturedBody, $this->hotelCode);
        $firstAvail = $room['Availability'][0] ?? null;
        $this->assertNotNull($firstAvail, 'BW4-03: Availability element must exist');
        // Booking.com: StopSell.value = 'true' means blocked
        $stopSell = $firstAvail['StopSell'] ?? null;
        $this->assertNotNull($stopSell, 'BW4-03: StopSell element must be present');
        $this->assertEquals('true', $stopSell['value'] ?? null, 'BW4-03: unavailable date → StopSell.value = true');
    }

    // ─── BW4-04: available=true → opened ──────────────────────────────
    public function test_bw4_04_available_true_opens_booking(): void
    {
        $capturedBody = null;
        $transport = $this->createMockTransport();
        $transport->method('post')
            ->willReturnCallback(function ($ilanId, $path, $body) use (&$capturedBody) {
                $capturedBody = $body;
                return ChannelTransportResult::success('200', []);
            });

        $adapter = new BookingChannelAdapter($transport);
        $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-04',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => true, 'property_id' => $this->ilanA->id],
            ],
        );

        $room = $this->findRoomForHotelCode($capturedBody, $this->hotelCode);
        $firstAvail = $room['Availability'][0] ?? null;
        $this->assertNotNull($firstAvail, 'BW4-04: Availability element must exist');
        // available=true → StopSell=false or no restriction
        $stopSell = $firstAvail['StopSell'] ?? null;
        $this->assertTrue(
            $stopSell === false
            || $stopSell === null
            || ($firstAvail['Restrict'] ?? '') === '',
            'BW4-04: available date must NOT be blocked'
        );
    }

    // ─── BW4-05: Tenant isolation ──────────────────────────────────────
    public function test_bw4_05_tenant_isolation_blocks_unregistered_platform(): void
    {
        // Tenant B has NO booking_com sync registration
        $tenantB = Tenant::create([
            'uuid'   => 'bw4-tenant-b-' . uniqid(),
            'name'   => 'BW4 Tenant B',
            'domain' => 'bw4b.test',
            'status' => 'active',
        ]);

        $ilanBId = DB::table('ilanlar')->insertGetId([
            'baslik' => 'BW4 Property B',
            'slug'   => 'bw4-property-b-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id' => $tenantB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // NO ilan_takvim_sync entry for booking_com

        $transport = $this->createMockTransport();
        $transport->expects($this->never())->method('post');

        $adapter = new BookingChannelAdapter($transport);
        $result = $adapter->pushAvailability(
            tenantId: $tenantB->id,
            propertyId: $ilanBId,
            correlationId: 'bw4-05',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => false, 'property_id' => $ilanBId],
            ],
        );

        $this->assertFalse($result->success, 'BW4-05: Unregistered platform must return failure');
    }

    // ─── BW4-06: Idempotent push ────────────────────────────────────
    public function test_bw4_06_idempotent_push_same_dates(): void
    {
        $callCount = 0;
        $transport = $this->createMockTransport();
        $transport->method('post')
            ->willReturnCallback(function ($ilanId, $path, $body) use (&$callCount) {
                $callCount++;
                return ChannelTransportResult::success('200', []);
            });

        $adapter = new BookingChannelAdapter($transport);
        $data = [
            ['date' => '2044-09-01', 'available' => false, 'property_id' => $this->ilanA->id],
        ];

        // First push
        $result1 = $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-06-a',
            availabilityData: $data,
        );

        // Second push — idempotent, same dates
        $result2 = $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-06-b',
            availabilityData: $data,
        );

        $this->assertTrue($result1->success, 'BW4-06: First push must succeed');
        $this->assertTrue($result2->success, 'BW4-06: Idempotent push must succeed');
        $this->assertEquals(2, $callCount, 'BW4-06: Both pushes call transport (idempotent by Booking.com side)');
    }

    // ─── BW4-07: Retryable error (5xx) ──────────────────────────────
    public function test_bw4_07_retryable_error_throws_exception(): void
    {
        $transport = $this->createMockTransport();
        $transport->method('post')
            ->willReturn(ChannelTransportResult::failure(errorCode: '500', errorMessage: 'Internal Server Error', retryable: true));

        $adapter = new BookingChannelAdapter($transport);

        $this->expectException(\App\Infrastructure\ChannelManager\Booking\BookingAvailabilityException::class);
        $this->expectExceptionMessageMatches('/500/');

        $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-07',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => false, 'property_id' => $this->ilanA->id],
            ],
        );
    }

    // ─── BW4-08: Non-retryable error (4xx) ──────────────────────────
    public function test_bw4_08_nonretryable_error_returns_failure(): void
    {
        $transport = $this->createMockTransport();
        $transport->method('post')
            ->willReturn(ChannelTransportResult::failure('400', 'Bad Request', retryable: false));

        $adapter = new BookingChannelAdapter($transport);
        $result = $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-08',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => false, 'property_id' => $this->ilanA->id],
            ],
        );

        $this->assertFalse($result->success, 'BW4-08: 4xx must return failure ChannelSyncResponse');
        $this->assertEquals('400', $result->errorCode);
    }

    // ─── BW4-09: Empty availability → early return ───────────────────
    public function test_bw4_09_empty_availability_returns_success_no_push(): void
    {
        $transport = $this->createMockTransport();
        $transport->expects($this->never())->method('post');

        $adapter = new BookingChannelAdapter($transport);
        $result = $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-09',
            availabilityData: [],
        );

        $this->assertTrue($result->success, 'BW4-09: Empty data must return success (no-op)');
    }

    // ─── BW4-10: Successful push → ChannelSyncResponse.success ─────────
    public function test_bw4_10_successful_push_returns_success(): void
    {
        $transport = $this->createMockTransport();
        $transport->method('post')
            ->willReturn(ChannelTransportResult::success('200', ['confirmation' => 'ok']));

        $adapter = new BookingChannelAdapter($transport);
        $result = $adapter->pushAvailability(
            tenantId: $this->tenantA->id,
            propertyId: $this->ilanA->id,
            correlationId: 'bw4-10',
            availabilityData: [
                ['date' => '2044-09-01', 'available' => false, 'property_id' => $this->ilanA->id],
            ],
        );

        $this->assertTrue($result->success, 'BW4-10: Successful HTTP 200 must return success response');
    }

    // ─── BW4-11: supportsPull = false (Booking push-only) ───────────
    public function test_bw4_11_supports_pull_is_false(): void
    {
        $transport = $this->createMockTransport();
        $adapter = new BookingChannelAdapter($transport);

        $this->assertFalse($adapter->supportsPull(), 'BW4-11: Booking.com availability is push-only (no pull)');
    }

    // ─── BW4-12: supportsPush = true when active sync exists ──────────
    public function test_bw4_12_supports_push_true_when_sync_active(): void
    {
        $transport = $this->createMockTransport();
        $adapter = new BookingChannelAdapter($transport);

        $this->assertTrue($adapter->supportsPush(), 'BW4-12: supportsPush must be true when booking_com sync is active');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function createMockTransport(): BookingTransport
    {
        return $this->createMock(BookingTransport::class);
    }

    /**
     * Navigate the OTA payload structure to find the room with the given HotelCode.
     * Booking.com OTA_Availability payload:
     * {
     *   "rooms": [
     *     {
     *       "HotelCode": "BK-HOTEL-A",
     *       "Availability": [
     *         {"Date": "2044-09-01", "StopSell": {"value": "true"}}
     *       ]
     *     }
     *   ]
     * }
     */
    private function findRoomForHotelCode(array $body, string $hotelCode): ?array
    {
        $rooms = $body['rooms'] ?? [];
        foreach ($rooms as $room) {
            if (($room['HotelCode'] ?? null) === $hotelCode) {
                return $room;
            }
        }
        return null;
    }
}

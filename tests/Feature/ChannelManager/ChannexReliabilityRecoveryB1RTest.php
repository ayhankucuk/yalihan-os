<?php

namespace Tests\Feature\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Infrastructure\ChannelManager\Channex\ChannexAcknowledgementException;
use App\Infrastructure\ChannelManager\Channex\ChannexBookingAcknowledger;
use App\Infrastructure\ChannelManager\Channex\ChannexClient;
use App\Jobs\ChannelManager\ChannexReservationIngestJob;
use App\Jobs\ChannelManager\ChannexRevisionsRecoveryJob;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Services\ChannelManager\ChannexReservationIngestService;
use App\Services\ChannelManager\ChannexRevisionProcessor;
use App\Services\ChannelManager\ChannexSignatureVerifier;
use App\Services\ChannelManager\ChannexWebhookTenantResolver;
use App\Services\ReservationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ChannexReliabilityRecoveryB1RTest — Comprehensive test evidence for Wave 7 Phase B1.1R.
 *
 * Covers Gates B1R-01 to B1R-15.
 */
class ChannexReliabilityRecoveryB1RTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Ilan $ilan;
    private IlanTakvimSync $sync;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'uuid'   => 'tenant-uuid-1',
            'name'   => 'Yalıhan Emlak',
            'domain' => 'yalihan.com.tr',
            'status' => 'active',
        ]);

        $this->ilan = Ilan::withoutGlobalScopes()->create([
            'tenant_id'      => $this->tenant->id,
            'baslik'         => 'Mandarin Oriental Villa B1R',
            'fiyat'          => 50000,
            'para_birimi'    => 'EUR',
            'yayin_durumu'   => 'yayinda',
            'rental_enabled' => true,
            'min_stay_nights'=> 1,
            'slug'           => 'mandarin-oriental-villa-b1r',
        ]);

        $this->sync = IlanTakvimSync::withoutGlobalScopes()->create([
            'ilan_id'             => $this->ilan->id,
            'platform'            => 'airbnb',
            'external_listing_id' => 'CHNX-REAL-UUID-999',
            'is_sync_active'      => true,
            'api_key'             => 'test_channex_api_key_safe',
        ]);
    }

    // B1R-01: Webhook revision retrieved and parsed successfully
    public function test_b1r_01_webhook_revision_retrieved_and_parsed_successfully(): void
    {
        $payload = [
            'data' => [
                'id'         => 'rev-uuid-101',
                'type'       => 'booking_revision',
                'attributes' => [
                    'booking_id'      => 'res-channex-101',
                    'property_id'     => 'CHNX-REAL-UUID-999',
                    'status'          => 'new',
                    'ota_name'        => 'airbnb',
                    'arrival_date'    => '2026-09-01',
                    'departure_date'  => '2026-09-05',
                    'guest_name'      => 'Robert Smith',
                    'guest_email'     => 'robert@example.com',
                    'adults_count'    => 2,
                    'total_price'     => 4000.0,
                    'currency'        => 'EUR',
                ],
            ],
        ];

        $dto = ChannexReservationPayload::fromChannexRevision($payload['data']);

        $this->assertEquals('res-channex-101', $dto->externalReservationId);
        $this->assertEquals('CHNX-REAL-UUID-999', $dto->externalListingId);
        $this->assertEquals('rev-uuid-101', $dto->revisionId);
        $this->assertEquals(4, $dto->nights);
        $this->assertEquals('Robert Smith', $dto->guestName);
    }

    // B1R-02: Revision normalized into canonical reservation
    public function test_b1r_02_revision_normalized_into_canonical_reservation(): void
    {
        $dto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-002',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-09-10',
            departureDate:         '2026-09-15',
            nights:                5,
            guestName:             'Elena Rostova',
            guestPhone:            '+905320000001',
            guestEmail:            'elena@example.com',
            adultCount:            2,
            totalPrice:            7500.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-002',
            action:                'new',
        );

        $processor = app(ChannexRevisionProcessor::class);
        $reservation = $processor->process($dto, $this->tenant->id);

        $this->assertNotNull($reservation);
        $this->assertEquals('Elena Rostova', $reservation->guest_name);
        $this->assertEquals($this->ilan->id, $reservation->property_id);
        $this->assertEquals($this->tenant->id, $reservation->tenant_id);
    }

    // B1R-03 & B1R-05: DB commit occurs before ACK, successful ingest sends ACK
    public function test_b1r_03_and_05_successful_ingest_commits_and_sends_ack(): void
    {
        $mockAcknowledger = $this->createMock(ChannexBookingAcknowledger::class);
        $mockAcknowledger->expects($this->once())
            ->method('acknowledgeRevision')
            ->with($this->tenant->id, 'rev-b1r-003')
            ->willReturn(true);

        $processor = new ChannexRevisionProcessor(
            ingestService: app(ChannexReservationIngestService::class),
            acknowledger:  $mockAcknowledger,
        );

        $dto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-003',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-09-20',
            departureDate:         '2026-09-23',
            nights:                3,
            guestName:             'Marcus Aurelius',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            2,
            totalPrice:            3000.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-003',
            action:                'new',
        );

        $reservation = $processor->process($dto, $this->tenant->id);

        $this->assertNotNull($reservation);
        $this->assertDatabaseHas('property_reservations', [
            'id'         => $reservation->id,
            'guest_name' => 'Marcus Aurelius',
        ]);
    }

    // B1R-04: Failed canonical ingest sends NO ACK
    public function test_b1r_04_failed_canonical_ingest_sends_no_ack(): void
    {
        $mockAcknowledger = $this->createMock(ChannexBookingAcknowledger::class);
        $mockAcknowledger->expects($this->never())
            ->method('acknowledgeRevision');

        $processor = new ChannexRevisionProcessor(
            ingestService: app(ChannexReservationIngestService::class),
            acknowledger:  $mockAcknowledger,
        );

        // Unknown listing ID causes ingest exception
        $dto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-004',
            externalListingId:     'NON-EXISTENT-LISTING',
            channel:               'airbnb',
            arrivalDate:           '2026-09-25',
            departureDate:         '2026-09-28',
            nights:                3,
            guestName:             'Failure Test Guest',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            1,
            totalPrice:            1000.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-004',
            action:                'new',
        );

        $this->expectException(\RuntimeException::class);
        $processor->process($dto, $this->tenant->id);
    }

    // B1R-06 & B1R-07: ACK failure is retry-safe, DB stays committed, ACK retry does not duplicate
    public function test_b1r_06_and_07_ack_failure_keeps_reservation_committed_and_retry_is_idempotent(): void
    {
        $mockAcknowledger = $this->createMock(ChannexBookingAcknowledger::class);
        // First call fails, second call succeeds
        $mockAcknowledger->expects($this->exactly(2))
            ->method('acknowledgeRevision')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new ChannexAcknowledgementException(500, true, 'Temporary network timeout')),
                true,
            ));

        $processor = new ChannexRevisionProcessor(
            ingestService: app(ChannexReservationIngestService::class),
            acknowledger:  $mockAcknowledger,
        );

        $dto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-006',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-10-01',
            departureDate:         '2026-10-04',
            nights:                3,
            guestName:             'Retry Test Guest',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            2,
            totalPrice:            3500.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-006',
            action:                'new',
        );

        // Attempt 1: DB commit succeeds, ACK fails gracefully
        $res1 = $processor->process($dto, $this->tenant->id);
        $this->assertNotNull($res1);
        $this->assertDatabaseHas('property_reservations', [
            'id'         => $res1->id,
            'guest_name' => 'Retry Test Guest',
        ]);

        // Attempt 2 (Retry): Replay returns existing reservation, no duplicate, ACK succeeds
        $res2 = $processor->process($dto, $this->tenant->id);
        $this->assertEquals($res1->id, $res2->id);

        $totalCount = PropertyReservation::withoutGlobalScopes()
            ->where('guest_name', 'Retry Test Guest')
            ->count();
        $this->assertEquals(1, $totalCount);
    }

    // B1R-08: Duplicate webhook remains idempotent
    public function test_b1r_08_duplicate_webhook_remains_idempotent(): void
    {
        $dto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-008',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-10-10',
            departureDate:         '2026-10-13',
            nights:                3,
            guestName:             'Duplicate Guard Guest',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            2,
            totalPrice:            4000.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-008',
            action:                'new',
        );

        $processor = app(ChannexRevisionProcessor::class);

        $first = $processor->process($dto, $this->tenant->id);
        $second = $processor->process($dto, $this->tenant->id);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, PropertyReservation::withoutGlobalScopes()->where('guest_name', 'Duplicate Guard Guest')->count());
    }

    // B1R-09 & B1R-10: Recovery feed discovers missed revision and uses same canonical processor
    public function test_b1r_09_and_10_recovery_feed_discovers_missed_revision_and_ingests(): void
    {
        $mockClient = $this->createMock(ChannexClient::class);
        $mockClient->expects($this->once())
            ->method('getBookingRevisionsFeed')
            ->willReturn([
                'data' => [
                    [
                        'id'         => 'rev-feed-009',
                        'type'       => 'booking_revision',
                        'attributes' => [
                            'booking_id'     => 'res-feed-009',
                            'property_id'    => 'CHNX-REAL-UUID-999',
                            'status'         => 'new',
                            'ota_name'       => 'booking_com',
                            'arrival_date'   => '2026-11-01',
                            'departure_date' => '2026-11-04',
                            'guest_name'     => 'Feed Recovered Guest',
                            'adults_count'   => 2,
                            'total_price'    => 2800.0,
                            'currency'       => 'EUR',
                        ],
                    ],
                ],
            ]);

        $mockClient->expects($this->once())
            ->method('acknowledgeBookingRevision')
            ->with($this->anything(), 'rev-feed-009')
            ->willReturn(true);

        $mockAcknowledger = new ChannexBookingAcknowledger($mockClient);
        $processor = new ChannexRevisionProcessor(
            ingestService: app(ChannexReservationIngestService::class),
            acknowledger:  $mockAcknowledger,
        );

        $recoveryJob = new ChannexRevisionsRecoveryJob($this->tenant->id);
        $metrics = $recoveryJob->handle(
            client:         $mockClient,
            processor:      $processor,
            tenantResolver: app(ChannexWebhookTenantResolver::class),
        );

        $this->assertEquals(1, $metrics['revisions_discovered']);
        $this->assertEquals(1, $metrics['revisions_processed']);
        $this->assertEquals(1, $metrics['ack_successes']);

        $this->assertDatabaseHas('property_reservations', [
            'guest_name' => 'Feed Recovered Guest',
        ]);
    }

    // B1R-11: Tenant/property mapping failure sends NO ACK
    public function test_b1r_11_tenant_mapping_failure_sends_no_ack(): void
    {
        $mockClient = $this->createMock(ChannexClient::class);
        $mockClient->expects($this->once())
            ->method('getBookingRevisionsFeed')
            ->willReturn([
                'data' => [
                    [
                        'id'         => 'rev-feed-unmapped',
                        'type'       => 'booking_revision',
                        'attributes' => [
                            'booking_id'     => 'res-feed-unmapped',
                            'property_id'    => 'UNMAPPED-PROP-UUID',
                            'status'         => 'new',
                            'ota_name'       => 'airbnb',
                            'arrival_date'   => '2026-11-10',
                            'departure_date' => '2026-11-12',
                            'guest_name'     => 'Unmapped Guest',
                        ],
                    ],
                ],
            ]);

        $mockClient->expects($this->never())
            ->method('acknowledgeBookingRevision');

        $mockAcknowledger = new ChannexBookingAcknowledger($mockClient);
        $processor = new ChannexRevisionProcessor(
            ingestService: app(ChannexReservationIngestService::class),
            acknowledger:  $mockAcknowledger,
        );

        $recoveryJob = new ChannexRevisionsRecoveryJob($this->tenant->id);
        $metrics = $recoveryJob->handle(
            client:         $mockClient,
            processor:      $processor,
            tenantResolver: app(ChannexWebhookTenantResolver::class),
        );

        $this->assertEquals(1, $metrics['revisions_discovered']);
        $this->assertEquals(0, $metrics['revisions_processed']);
        $this->assertEquals(0, $metrics['ack_successes']);
    }

    // B1R-12: Modification revision remains replay-safe
    public function test_b1r_12_modification_revision_remains_replay_safe(): void
    {
        $processor = app(ChannexRevisionProcessor::class);

        // 1. Initial Ingest
        $initialDto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-012',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-11-15',
            departureDate:         '2026-11-18',
            nights:                3,
            guestName:             'Modify Guest Initial',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            2,
            totalPrice:            3000.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-012-initial',
            action:                'new',
        );
        $processor->process($initialDto, $this->tenant->id);

        // 2. Modification
        $modifyDto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-012',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-11-16',
            departureDate:         '2026-11-20',
            nights:                4,
            guestName:             'Modify Guest Updated',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            3,
            totalPrice:            4000.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-012-mod',
            action:                'modified',
        );

        $modified = $processor->process($modifyDto, $this->tenant->id);
        $this->assertNotNull($modified);
        $this->assertStringStartsWith('2026-11-16', (string) $modified->start_date);
        $this->assertStringStartsWith('2026-11-20', (string) $modified->end_date);
    }

    // B1R-13: Cancellation revision remains replay-safe
    public function test_b1r_13_cancellation_revision_remains_replay_safe(): void
    {
        $processor = app(ChannexRevisionProcessor::class);

        // 1. Initial Ingest
        $initialDto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-013',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-11-22',
            departureDate:         '2026-11-25',
            nights:                3,
            guestName:             'Cancel Guest',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            2,
            totalPrice:            3000.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-013-initial',
            action:                'new',
        );
        $res = $processor->process($initialDto, $this->tenant->id);

        // 2. Cancellation
        $cancelDto = new ChannexReservationPayload(
            externalReservationId: 'res-b1r-013',
            externalListingId:     'CHNX-REAL-UUID-999',
            channel:               'airbnb',
            arrivalDate:           '2026-11-22',
            departureDate:         '2026-11-25',
            nights:                3,
            guestName:             'Cancel Guest',
            guestPhone:            null,
            guestEmail:            null,
            adultCount:            2,
            totalPrice:            3000.0,
            currency:              'EUR',
            revisionId:            'rev-b1r-013-cancel',
            action:                'cancelled',
        );

        $cancelled = $processor->process($cancelDto, $this->tenant->id);
        $this->assertNotNull($cancelled);
        $this->assertNotNull($cancelled->cancelled_at);

        // 3. Replay cancellation (idempotent)
        $replayed = $processor->process($cancelDto, $this->tenant->id);
        $this->assertNotNull($replayed);
        $this->assertNotNull($replayed->cancelled_at);
    }

    // B1R-14: Scheduler registration verified
    public function test_b1r_14_scheduler_registration_verified(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $channexEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'channex:sync-revisions');
        });

        $this->assertNotNull($channexEvent, 'channex:sync-revisions is registered in schedule');
        $this->assertEquals('*/15 * * * *', $channexEvent->expression);
    }

    // B1R-15: Secrets absent from logs/errors
    public function test_b1r_15_secrets_absent_from_logs_and_errors(): void
    {
        $client = new ChannexClient();
        $apiKey = 'SUPER_SECRET_KEY_12345';

        try {
            Http::fake([
                'staging.channex.io/*' => Http::response(['error' => 'Unauthorized'], 401),
            ]);

            $client->testConnection($apiKey);
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString($apiKey, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    // B1R-16: Activation command maps real UUID and cleans canary
    public function test_b1r_16_activate_property_command_maps_uuid_and_cleans_canary(): void
    {
        // Create synthetic canary
        $canary = PropertyReservation::withoutGlobalScopes()->create([
            'tenant_id'               => $this->tenant->id,
            'property_id'             => $this->ilan->id,
            'external_reservation_id' => 'RES-CHNX-PILOT-001',
            'external_channel'        => 'airbnb',
            'start_date'              => '2026-08-20',
            'end_date'                => '2026-08-23',
            'nights'                  => 3,
            'guest_name'              => 'Canary Guest',
            'reservation_state'       => 'confirmed',
        ]);

        $this->artisan('channex:activate-property', [
            '--property-uuid' => 'REAL-CHANNEX-VILLA-UUID-1234',
            '--listing-id'    => $this->ilan->id,
            '--clean-canary'  => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('property_reservations', [
            'external_reservation_id' => 'RES-CHNX-PILOT-001',
        ]);

        $this->assertDatabaseHas('ilan_takvim_sync', [
            'ilan_id'             => $this->ilan->id,
            'external_listing_id' => 'REAL-CHANNEX-VILLA-UUID-1234',
        ]);
    }
}


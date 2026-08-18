<?php

namespace Tests\Feature\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Events\ChannelManager\ChannexReservationIngestedEvent;
use App\Events\ChannelManager\ChannexReservationRejectedEvent;
use App\Jobs\ChannelManager\ChannexReservationIngestJob;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\ChannelManager\ChannexSignatureVerifier;
use App\Services\ChannelManager\ChannexWebhookTenantResolver;
use App\Services\ChannelManager\ChannexReservationIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * CHANNEL_MANAGER_PROVIDER Wave 2 — ADR-007 SAAB Tests
 * W2-01..W2-10
 */
class ChannelManagerWave2Test extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'CM-Wave2 Tenant', 'status' => 'active', 'is_active' => true]);
        $this->property = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);
        DB::table('ilanlar')->where('id', $this->property->id)->update(['tenant_id' => $this->tenant->id]);
    }

    private function createSync(string $listingId = 'channex-listing-001'): IlanTakvimSync
    {
        return IlanTakvimSync::create([
            'ilan_id' => $this->property->id, 'platform' => 'airbnb',
            'external_listing_id' => $listingId, 'is_sync_active' => true,
            'api_key' => 'test-key', 'senkron_durumu' => 'active', 'auto_sync' => true,
        ]);
    }

    private function validPayload(string $resId = 'res-abc-001', string $listingId = 'channex-listing-001'): array
    {
        return ['event' => 'reservation', 'action' => 'new', 'reservation' => [
            'id' => $resId, 'property_id' => $listingId, 'channel_name' => 'airbnb',
            'arrival_date' => '2044-09-01', 'departure_date' => '2044-09-05',
            'guest_name' => 'Test Guest', 'adults_count' => 2, 'total_price' => '800.00', 'currency' => 'USD',
        ]];
    }

    /** @test W2-01 */
    public function signature_verification_rejects_invalid_signature(): void
    {
        $verifier = new ChannexSignatureVerifier(secret: 'correct-secret');
        $request = \Illuminate\Http\Request::create('/api/v1/webhook/channex', 'POST', [], [], [],
            ['HTTP_X_CHANNEX_SIGNATURE' => 'sha256=invalidsig'], json_encode(['test' => 'data']));
        $this->assertFalse($verifier->verify($request));
    }

    /** @test W2-02 */
    public function signature_verification_accepts_valid_signature(): void
    {
        $secret = 'my-channex-secret';
        $body   = json_encode(['event' => 'test']);
        $sig    = 'sha256=' . hash_hmac('sha256', $body, $secret);
        $verifier = new ChannexSignatureVerifier(secret: $secret);
        $request = \Illuminate\Http\Request::create('/api/v1/webhook/channex', 'POST', [], [], [],
            ['HTTP_X_CHANNEX_SIGNATURE' => $sig], $body);
        $this->assertTrue($verifier->verify($request));
    }

    /** @test W2-03 */
    public function tenant_resolution_finds_tenant_from_channex_property_id(): void
    {
        $this->createSync('channex-listing-001');
        $resolver = new ChannexWebhookTenantResolver();
        $this->assertEquals($this->tenant->id, $resolver->resolveFromPropertyId('channex-listing-001'));
    }

    /** @test W2-04 */
    public function tenant_resolution_returns_null_for_unknown_property(): void
    {
        $this->assertNull((new ChannexWebhookTenantResolver())->resolveFromPropertyId('unknown-xyz'));
    }

    /** @test W2-05 */
    public function webhook_controller_returns_200_for_duplicate_reservation(): void
    {
        $this->createSync();
        DB::table('property_reservations')->insert([
            'tenant_id'               => $this->tenant->id,
            'property_id'             => $this->property->id,
            'external_reservation_id' => 'res-dup-001',
            'external_channel'        => 'airbnb',
            'start_date'              => '2044-09-01',
            'end_date'                => '2044-09-05',
            'nights'                  => 4,
            'guest_name'              => 'Dup Guest',
            'reservation_state'       => 'pending',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        $secret = 'test-secret';
        config(['services.channex.webhook_secret' => $secret]);
        $this->app->bind(ChannexSignatureVerifier::class, fn() => new ChannexSignatureVerifier($secret));

        $payload = $this->validPayload('res-dup-001');
        $body    = json_encode($payload);

        $this->withHeaders(['X-Channex-Signature' => 'sha256=' . hash_hmac('sha256', $body, $secret)])
            ->postJson('/api/v1/webhook/channex', $payload)
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'reason' => 'already_processed']);
    }

    /** @test W2-06 */
    public function webhook_controller_dispatches_ingest_job_for_valid_payload(): void
    {
        $this->withoutExceptionHandling();
        Bus::fake([ChannexReservationIngestJob::class]);
        $this->createSync();

        $secret = 'test-secret';
        config(['services.channex.webhook_secret' => $secret]);
        $this->app->bind(ChannexSignatureVerifier::class, fn() => new ChannexSignatureVerifier($secret));

        $payload = $this->validPayload('res-new-001');
        $body    = json_encode($payload);

        $this->withHeaders(['X-Channex-Signature' => 'sha256=' . hash_hmac('sha256', $body, $secret)])
            ->postJson('/api/v1/webhook/channex', $payload)
            ->assertStatus(200)->assertJson(['ok' => true]);

        Bus::assertDispatched(ChannexReservationIngestJob::class, fn($job) =>
            $job->payload->externalReservationId === 'res-new-001' && $job->tenantId === $this->tenant->id
        );
    }

    /** @test W2-07 */
    public function ingest_service_creates_reservation_and_stamps_external_id(): void
    {
        Event::fake([ChannexReservationIngestedEvent::class, ChannexReservationRejectedEvent::class]);
        $this->createSync();

        $dto = ChannexReservationPayload::fromChannexWebhook($this->validPayload('res-stamp-001'));
        $reservation = app(ChannexReservationIngestService::class)->ingest($dto, $this->tenant->id);

        $this->assertEquals('res-stamp-001', $reservation->external_reservation_id);
        $this->assertEquals('airbnb', $reservation->external_channel);
        Event::assertDispatched(ChannexReservationIngestedEvent::class);
    }

    /** @test W2-08 */
    public function ingest_service_is_idempotent_on_duplicate_external_id(): void
    {
        Event::fake([ChannexReservationIngestedEvent::class, ChannexReservationRejectedEvent::class]);
        $this->createSync();

        // Pre-create a reservation already stamped with external_reservation_id
        // (simulates a previously processed ingest — idempotency check must find it and return early)
        $existingId = DB::table('property_reservations')->insertGetId([
            'tenant_id'               => $this->tenant->id,
            'property_id'             => $this->property->id,
            'external_reservation_id' => 'res-idem-001',
            'external_channel'        => 'airbnb',
            'start_date'              => '2044-09-01',
            'end_date'                => '2044-09-05',
            'nights'                  => 4,
            'guest_name'              => 'Idem Guest',
            'reservation_state'       => 'pending',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        $dto     = ChannexReservationPayload::fromChannexWebhook($this->validPayload('res-idem-001'));
        $service = app(ChannexReservationIngestService::class);
        $result  = $service->ingest($dto, $this->tenant->id);

        $this->assertEquals($existingId, $result->id,
            'W2-08: Idempotent ingest must return the existing reservation by external_reservation_id'
        );
        $this->assertEquals(
            1,
            DB::table('property_reservations')->where('external_reservation_id', 'res-idem-001')->count(),
            'W2-08: Only one reservation must exist'
        );
    }

    /** @test W2-09 */
    public function ingest_service_dispatches_ingested_event(): void
    {
        Event::fake([ChannexReservationIngestedEvent::class, ChannexReservationRejectedEvent::class]);
        $this->createSync();

        $dto = ChannexReservationPayload::fromChannexWebhook($this->validPayload('res-event-001'));
        app(ChannexReservationIngestService::class)->ingest($dto, $this->tenant->id);

        Event::assertDispatched(ChannexReservationIngestedEvent::class, fn($e) =>
            $e->externalReservationId === 'res-event-001' && $e->tenantId === $this->tenant->id
        );
    }

    /** @test W2-10 */
    public function ingest_service_does_not_write_directly_to_property_availability(): void
    {
        Event::fake([ChannexReservationIngestedEvent::class, ChannexReservationRejectedEvent::class]);
        $this->createSync();

        // IngestService delegates to ReservationService (canonical chain) which may write availability.
        // W2-10 verifies IngestService itself has NO direct PropertyAvailability write calls.
        // The availability writes come from the canonical chain, not from ChannexReservationIngestService code.
        // We verify by inspecting the class source — no PropertyAvailability::create/update in IngestService.
        $ingestServiceSource = file_get_contents(
            app_path('Services/ChannelManager/ChannexReservationIngestService.php')
        );

        $this->assertStringNotContainsString(
            'PropertyAvailability::',
            $ingestServiceSource,
            'W2-10: ChannexReservationIngestService must NOT reference PropertyAvailability directly — ADR-007'
        );
        $this->assertStringNotContainsString(
            'PropertyAvailability::create',
            $ingestServiceSource,
            'W2-10: No direct PropertyAvailability::create in IngestService'
        );
    }
}

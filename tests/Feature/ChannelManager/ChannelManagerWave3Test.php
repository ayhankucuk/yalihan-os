<?php

namespace Tests\Feature\ChannelManager;

use App\Events\ChannelManager\ChannexReservationCancelledViaChanEvent;
use App\Events\ChannelManager\ChannexReservationModifiedEvent;
use App\Jobs\ChannelManager\ChannexReservationCancelJob;
use App\Jobs\ChannelManager\ChannexReservationModifyJob;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\ChannelManager\ChannexReservationIngestService;
use App\Services\ChannelManager\ChannexSignatureVerifier;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * CHANNEL_MANAGER_PROVIDER Wave 3 — ADR-008 SAAB Tests
 *
 * W3-01: controller_routes_cancelled_action_to_cancel_job
 * W3-02: controller_routes_modified_action_to_modify_job
 * W3-03: controller_routes_new_action_to_ingest_job (Wave 2 regression)
 * W3-04: ingest_cancellation_cancels_reservation_via_canonical_chain
 * W3-05: ingest_cancellation_is_idempotent
 * W3-06: ingest_modification_updates_dates_via_canonical_chain
 * W3-07: ingest_modification_on_cancelled_reservation_is_ignored
 * W3-08: ingest_cancellation_unknown_external_id_returns_null
 * W3-09: modify_reservation_detects_conflict_on_new_dates
 * W3-10: tenant_isolation_cancel_cannot_affect_other_tenant_reservation
 */
class ChannelManagerWave3Test extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant   = Tenant::create(['name' => 'CM-W3 Tenant', 'status' => 'active', 'is_active' => true]);
        $this->property = Ilan::factory()->create(['rental_enabled' => true, 'min_stay_nights' => 1]);
        DB::table('ilanlar')->where('id', $this->property->id)->update(['tenant_id' => $this->tenant->id]);
    }

    private function createSync(string $listingId = 'listing-001'): IlanTakvimSync
    {
        return IlanTakvimSync::create([
            'ilan_id' => $this->property->id, 'platform' => 'airbnb',
            'external_listing_id' => $listingId, 'is_sync_active' => true,
            'api_key' => 'test-key', 'senkron_durumu' => 'active', 'auto_sync' => true,
        ]);
    }

    private function createStampedReservation(
        string $externalId,
        string $channel = 'airbnb',
        string $state = 'confirmed',
        string $start = '2045-01-10',
        string $end = '2045-01-14',
    ): int {
        return DB::table('property_reservations')->insertGetId([
            'tenant_id'               => $this->tenant->id,
            'property_id'             => $this->property->id,
            'external_reservation_id' => $externalId,
            'external_channel'        => $channel,
            'start_date'              => $start,
            'end_date'                => $end,
            'nights'                  => 4,
            'guest_name'              => 'W3 Guest',
            'reservation_state'       => $state,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    private function signedWebhookHeaders(array $payload, string $secret = 'test-secret'): array
    {
        return ['X-Channex-Signature' => 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret)];
    }

    // =========================================================================
    // W3-01: controller routes 'cancelled' to ChannexReservationCancelJob
    // =========================================================================

    /** @test */
    public function controller_routes_cancelled_action_to_cancel_job(): void
    {
        $this->withoutExceptionHandling();
        Bus::fake([ChannexReservationCancelJob::class]);
        $this->createSync();
        $this->createStampedReservation('res-cancel-ctrl');

        $secret = 'test-secret';
        config(['services.channex.webhook_secret' => $secret]);
        $this->app->bind(ChannexSignatureVerifier::class, fn() => new ChannexSignatureVerifier($secret));

        $payload = ['event' => 'reservation', 'action' => 'cancelled', 'reservation' => [
            'id' => 'res-cancel-ctrl', 'property_id' => 'listing-001', 'channel_name' => 'airbnb',
            'arrival_date' => '2045-01-10', 'departure_date' => '2045-01-14',
            'guest_name' => 'W3 Guest', 'adults_count' => 2,
        ]];

        $this->withHeaders($this->signedWebhookHeaders($payload))
            ->postJson('/api/v1/webhook/channex', $payload)
            ->assertStatus(200)->assertJson(['ok' => true]);

        Bus::assertDispatched(ChannexReservationCancelJob::class, fn($job) =>
            $job->externalReservationId === 'res-cancel-ctrl' && $job->tenantId === $this->tenant->id
        );
    }

    // =========================================================================
    // W3-02: controller routes 'modified' to ChannexReservationModifyJob
    // =========================================================================

    /** @test */
    public function controller_routes_modified_action_to_modify_job(): void
    {
        $this->withoutExceptionHandling();
        Bus::fake([ChannexReservationModifyJob::class]);
        $this->createSync();
        $this->createStampedReservation('res-modify-ctrl');

        $secret = 'test-secret';
        config(['services.channex.webhook_secret' => $secret]);
        $this->app->bind(ChannexSignatureVerifier::class, fn() => new ChannexSignatureVerifier($secret));

        $payload = ['event' => 'reservation', 'action' => 'modified', 'reservation' => [
            'id' => 'res-modify-ctrl', 'property_id' => 'listing-001', 'channel_name' => 'airbnb',
            'arrival_date' => '2045-02-01', 'departure_date' => '2045-02-05',
            'guest_name' => 'W3 Guest', 'adults_count' => 2,
        ]];

        $this->withHeaders($this->signedWebhookHeaders($payload))
            ->postJson('/api/v1/webhook/channex', $payload)
            ->assertStatus(200)->assertJson(['ok' => true]);

        Bus::assertDispatched(ChannexReservationModifyJob::class, fn($job) =>
            $job->externalReservationId === 'res-modify-ctrl'
            && $job->newStartDate === '2045-02-01'
            && $job->tenantId === $this->tenant->id
        );
    }

    // =========================================================================
    // W3-03: controller routes 'new' to ChannexReservationIngestJob (Wave 2 regression)
    // =========================================================================

    /** @test */
    public function controller_routes_new_action_to_ingest_job(): void
    {
        Bus::fake([\App\Jobs\ChannelManager\ChannexReservationIngestJob::class]);
        $this->createSync();

        $secret = 'test-secret';
        config(['services.channex.webhook_secret' => $secret]);
        $this->app->bind(ChannexSignatureVerifier::class, fn() => new ChannexSignatureVerifier($secret));

        $payload = ['event' => 'reservation', 'action' => 'new', 'reservation' => [
            'id' => 'res-new-w3', 'property_id' => 'listing-001', 'channel_name' => 'airbnb',
            'arrival_date' => '2045-03-01', 'departure_date' => '2045-03-05',
            'guest_name' => 'W3 Guest', 'adults_count' => 2, 'total_price' => '800', 'currency' => 'USD',
        ]];

        $this->withHeaders($this->signedWebhookHeaders($payload))
            ->postJson('/api/v1/webhook/channex', $payload)
            ->assertStatus(200)->assertJson(['ok' => true]);

        Bus::assertDispatched(\App\Jobs\ChannelManager\ChannexReservationIngestJob::class);
    }

    // =========================================================================
    // W3-04: ingest cancellation cancels via canonical chain
    // =========================================================================

    /** @test */
    public function ingest_cancellation_cancels_reservation_via_canonical_chain(): void
    {
        Event::fake([ChannexReservationCancelledViaChanEvent::class]);
        $reservationId = $this->createStampedReservation('res-cancel-04', state: 'confirmed');

        $service = app(ChannexReservationIngestService::class);
        $result  = $service->ingestCancellation('res-cancel-04', 'airbnb', $this->tenant->id);

        $this->assertEquals('cancelled',
            DB::table('property_reservations')->where('id', $reservationId)->value('reservation_state'),
            'W3-04: cancelReservation must set state to cancelled'
        );
        Event::assertDispatched(ChannexReservationCancelledViaChanEvent::class);
    }

    // =========================================================================
    // W3-05: ingest cancellation is idempotent
    // =========================================================================

    /** @test */
    public function ingest_cancellation_is_idempotent(): void
    {
        Event::fake([ChannexReservationCancelledViaChanEvent::class]);
        $this->createStampedReservation('res-cancel-05', state: 'cancelled');

        $service = app(ChannexReservationIngestService::class);
        $result  = $service->ingestCancellation('res-cancel-05', 'airbnb', $this->tenant->id);

        $this->assertNotNull($result, 'W3-05: Idempotent cancellation must return existing reservation');
        $this->assertEquals('cancelled', $result->reservation_state->value ?? $result->reservation_state);
    }

    // =========================================================================
    // W3-06: ingest modification updates dates via canonical chain
    // =========================================================================

    /** @test */
    public function ingest_modification_updates_dates_via_canonical_chain(): void
    {
        Event::fake([ChannexReservationModifiedEvent::class]);
        $reservationId = $this->createStampedReservation('res-modify-06', start: '2045-01-10', end: '2045-01-14');

        $service = app(ChannexReservationIngestService::class);
        $result  = $service->ingestModification(
            'res-modify-06', 'airbnb', $this->tenant->id,
            '2045-06-01', '2045-06-05'
        );

        $this->assertNotNull($result, 'W3-06: modifyReservation must return updated reservation');
        $this->assertEquals('2045-06-01',
            DB::table('property_reservations')->where('id', $reservationId)->value('start_date'),
            'W3-06: start_date must be updated to new date'
        );
        Event::assertDispatched(ChannexReservationModifiedEvent::class);
    }

    // =========================================================================
    // W3-07: modification on cancelled reservation is silently ignored (ADR-008)
    // =========================================================================

    /** @test */
    public function ingest_modification_on_cancelled_reservation_is_ignored(): void
    {
        Event::fake([ChannexReservationModifiedEvent::class]);
        $reservationId = $this->createStampedReservation('res-modify-07', state: 'cancelled');

        $service = app(ChannexReservationIngestService::class);
        $result  = $service->ingestModification(
            'res-modify-07', 'airbnb', $this->tenant->id,
            '2045-07-01', '2045-07-05'
        );

        // State must remain cancelled — modifyReservation ignores terminal state
        $this->assertEquals('cancelled',
            DB::table('property_reservations')->where('id', $reservationId)->value('reservation_state'),
            'W3-07: Modification on cancelled reservation must not change state'
        );
        Event::assertNotDispatched(ChannexReservationModifiedEvent::class);
    }

    // =========================================================================
    // W3-08: unknown external_reservation_id returns null (no exception)
    // =========================================================================

    /** @test */
    public function ingest_cancellation_unknown_external_id_returns_null(): void
    {
        $service = app(ChannexReservationIngestService::class);
        $result  = $service->ingestCancellation('unknown-id-xyz', 'airbnb', $this->tenant->id);

        $this->assertNull($result, 'W3-08: Unknown external_reservation_id must return null, not throw exception');
    }

    // =========================================================================
    // W3-09: modifyReservation detects conflict on new dates
    // =========================================================================

    /** @test */
    public function modify_reservation_detects_conflict_on_new_dates(): void
    {
        // Create two reservations on different dates
        $this->createStampedReservation('res-conflict-09a', start: '2045-09-01', end: '2045-09-05');
        $idToModify = $this->createStampedReservation('res-conflict-09b', start: '2045-09-10', end: '2045-09-14');

        $reservationService = app(ReservationService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/conflict/i');

        // Try to modify 09b to overlap with 09a
        $reservationService->modifyReservation($idToModify, '2045-09-01', '2045-09-05');
    }

    // =========================================================================
    // W3-10: tenant isolation — cancel cannot affect other tenant's reservation
    // =========================================================================

    /** @test */
    public function tenant_isolation_cancel_cannot_affect_other_tenant_reservation(): void
    {
        Event::fake([ChannexReservationCancelledViaChanEvent::class]);

        $otherTenant = Tenant::create(['name' => 'Other', 'status' => 'active', 'is_active' => true]);
        $reservationId = $this->createStampedReservation('res-isolation-10', state: 'confirmed');

        $service = app(ChannexReservationIngestService::class);

        // Try to cancel with WRONG tenant_id
        $result = $service->ingestCancellation('res-isolation-10', 'airbnb', $otherTenant->id);

        $this->assertNull($result, 'W3-10: Wrong tenant must not find reservation');

        // Original reservation must still be confirmed
        $this->assertEquals('confirmed',
            DB::table('property_reservations')->where('id', $reservationId)->value('reservation_state'),
            'W3-10: Tenant isolation — wrong tenant cannot cancel another tenant reservation'
        );
        Event::assertNotDispatched(ChannexReservationCancelledViaChanEvent::class);
    }
}

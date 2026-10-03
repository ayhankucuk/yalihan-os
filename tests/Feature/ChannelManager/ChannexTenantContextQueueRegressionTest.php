<?php

declare(strict_types=1);

namespace Tests\Feature\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Infrastructure\ChannelManager\Channex\ChannexBookingAcknowledger;
use App\Jobs\ChannelManager\ChannexReservationCancelJob;
use App\Jobs\ChannelManager\ChannexReservationIngestJob;
use App\Jobs\ChannelManager\ChannexReservationModifyJob;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Queue\Contracts\TenantAwareJobInterface;
use App\Queue\Middleware\RestoreTenantContext;
use App\Services\ChannelManager\ChannexReservationIngestService;
use App\Services\ChannelManager\ChannexRevisionProcessor;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ChannexTenantContextQueueRegressionTest
 *
 * EXT_02_CHANNEX_TENANT_CONTEXT_REMEDIATION_02
 * Canonical verification of queue tenant isolation and context propagation
 * across Channex queue execution boundaries.
 *
 * Covers Gates R0 to R9.
 */
class ChannexTenantContextQueueRegressionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private TenantContextService $tenantContext;
    private Ilan $ilanB;
    private IlanTakvimSync $syncB;

    protected function setUp(): void
    {
        parent::setUp();

        // R9: Disallow any external HTTP calls
        Http::fake();

        $this->tenantContext = app(TenantContextService::class);

        // Tenant A represents ambient queue worker context
        $this->tenantA = Tenant::create([
            'uuid'   => 'tenant-uuid-ambient-a',
            'name'   => 'Tenant A (Ambient Worker)',
            'domain' => 'tenant-a.yalihan.local',
            'status' => 'active',
        ]);

        // Tenant B represents the target property owner tenant resolved from Channex
        $this->tenantB = Tenant::create([
            'uuid'   => 'tenant-uuid-target-b',
            'name'   => 'Tenant B (Channex Owner)',
            'domain' => 'tenant-b.yalihan.local',
            'status' => 'active',
        ]);

        // Property belongs to Tenant B
        $this->ilanB = Ilan::withoutGlobalScopes()->create([
            'tenant_id'       => $this->tenantB->id,
            'baslik'          => 'Bodrum Cennet Koyu Villa B',
            'fiyat'           => 120000,
            'para_birimi'     => 'EUR',
            'yayin_durumu'    => 'yayinda',
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'slug'            => 'bodrum-cennet-koyu-villa-b',
        ]);

        $this->syncB = IlanTakvimSync::withoutGlobalScopes()->create([
            'ilan_id'             => $this->ilanB->id,
            'platform'            => 'airbnb',
            'external_listing_id' => 'CHNX-LISTING-B-001',
            'is_sync_active'      => true,
            'api_key'             => 'safe_test_key_chnx',
        ]);
    }

    /**
     * Helper to execute a job through its defined queue middleware pipeline.
     */
    private function executeJobThroughPipeline(object $job): mixed
    {
        return app(Pipeline::class)
            ->send($job)
            ->through($job->middleware())
            ->then(function ($job) {
                return app()->call([$job, 'handle']);
            });
    }

    /**
     * Helper to build a standard Channex reservation payload.
     */
    private function makePayload(
        string $resId = 'res-reg-001',
        string $listingId = 'CHNX-LISTING-B-001',
        string $startDate = '2026-11-01',
        string $endDate = '2026-11-05',
        string $guestName = 'Reg Test Guest',
        ?string $revisionId = 'rev-reg-001',
        string $action = 'new'
    ): ChannexReservationPayload {
        return new ChannexReservationPayload(
            externalReservationId: $resId,
            externalListingId:     $listingId,
            channel:               'airbnb',
            arrivalDate:           $startDate,
            departureDate:         $endDate,
            nights:                4,
            guestName:             $guestName,
            guestPhone:            '+905550000001',
            guestEmail:            'reg@example.com',
            adultCount:            2,
            totalPrice:            5000.0,
            currency:              'EUR',
            revisionId:            $revisionId,
            action:                $action,
        );
    }

    /** @test */
    public function job_family_implements_canonical_tenant_aware_contract(): void
    {
        $payload = $this->makePayload();

        $ingestJob = new ChannexReservationIngestJob($payload, $this->tenantB->id);
        $this->assertInstanceOf(TenantAwareJobInterface::class, $ingestJob);
        $this->assertSame($this->tenantB->id, $ingestJob->getTenantId());
        $this->assertNull($ingestJob->getUserId());
        $ingestMiddleware = $ingestJob->middleware();
        $this->assertCount(1, $ingestMiddleware);
        $this->assertInstanceOf(RestoreTenantContext::class, $ingestMiddleware[0]);

        $modifyJob = new ChannexReservationModifyJob('res-1', 'airbnb', $this->tenantB->id, '2026-11-02', '2026-11-06');
        $this->assertInstanceOf(TenantAwareJobInterface::class, $modifyJob);
        $this->assertSame($this->tenantB->id, $modifyJob->getTenantId());
        $this->assertNull($modifyJob->getUserId());
        $modifyMiddleware = $modifyJob->middleware();
        $this->assertCount(1, $modifyMiddleware);
        $this->assertInstanceOf(RestoreTenantContext::class, $modifyMiddleware[0]);

        $cancelJob = new ChannexReservationCancelJob('res-1', 'airbnb', $this->tenantB->id);
        $this->assertInstanceOf(TenantAwareJobInterface::class, $cancelJob);
        $this->assertSame($this->tenantB->id, $cancelJob->getTenantId());
        $this->assertNull($cancelJob->getUserId());
        $cancelMiddleware = $cancelJob->middleware();
        $this->assertCount(1, $cancelMiddleware);
        $this->assertInstanceOf(RestoreTenantContext::class, $cancelMiddleware[0]);
    }

    /**
     * R0: Correct tenant job succeeds.
     * R1: Ambient worker context belonging to Tenant A does NOT prevent a Tenant B Channex job from executing under Tenant B.
     * R2: Reservation is persisted with resolved Tenant B.
     * R3: Tenant A receives no reservation/mutation.
     * R4: After successful job completion tenant context is restored according to existing RestoreTenantContext contract.
     *
     * @test
     */
    public function r0_to_r4_ambient_tenant_a_does_not_block_tenant_b_job_and_context_is_restored(): void
    {
        // 1. Establish ambient worker context as Tenant A
        $this->tenantContext->setTenant($this->tenantA);
        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id);

        // 2. Prepare Channex job resolved for Tenant B
        $payload = $this->makePayload('res-r0-to-r4', 'CHNX-LISTING-B-001', '2026-11-01', '2026-11-05', 'Alexander Hamilton', 'rev-r0-to-r4');
        $job = new ChannexReservationIngestJob($payload, $this->tenantB->id);

        // 3. Execute job through queue middleware pipeline
        $this->executeJobThroughPipeline($job);

        // 4. Verification R2: Reservation persisted with Tenant B
        $reservationB = PropertyReservation::withoutGlobalScopes()
            ->where('external_reservation_id', 'res-r0-to-r4')
            ->first();

        $this->assertNotNull($reservationB, 'Reservation must be persisted');
        $this->assertSame($this->tenantB->id, $reservationB->tenant_id, 'R2: Reservation must belong to Tenant B');
        $this->assertSame($this->ilanB->id, $reservationB->property_id);
        $this->assertSame('Alexander Hamilton', $reservationB->guest_name);

        // 5. Verification R3: Tenant A receives zero mutations / records
        $countTenantA = PropertyReservation::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->count();
        $this->assertSame(0, $countTenantA, 'R3: Tenant A must not receive any reservation');

        // 6. Verification R4: Ambient worker context is restored back to Tenant A
        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id, 'R4: Tenant A ambient context must be restored');
    }

    /**
     * R4 (Clean Worker variant): When worker starts clean (no tenant), it remains clean after job.
     *
     * @test
     */
    public function r4_clean_worker_leaves_no_ambient_context_after_job(): void
    {
        $this->tenantContext->clearTenant();
        $this->assertFalse($this->tenantContext->hasTenant());

        $payload = $this->makePayload('res-clean-001', 'CHNX-LISTING-B-001', '2026-11-10', '2026-11-14', 'Clean Worker Guest');
        $job = new ChannexReservationIngestJob($payload, $this->tenantB->id);

        $this->executeJobThroughPipeline($job);

        $this->assertDatabaseHas('property_reservations', [
            'external_reservation_id' => 'res-clean-001',
            'tenant_id'               => $this->tenantB->id,
        ]);

        $this->assertFalse($this->tenantContext->hasTenant(), 'R4: Clean worker must have no tenant context after completion');
    }

    /**
     * R5: After exception tenant context is restored/cleared.
     *
     * @test
     */
    public function r5_exception_in_job_restores_original_ambient_context(): void
    {
        $this->tenantContext->setTenant($this->tenantA);
        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id);

        // Invalid dates (departure before arrival) causes canonical exception in ReservationService
        $invalidPayload = $this->makePayload('res-fail-001', 'CHNX-LISTING-B-001', '2026-11-20', '2026-11-15', 'Invalid Date Guest');
        $job = new ChannexReservationIngestJob($invalidPayload, $this->tenantB->id);

        $exceptionThrown = false;
        try {
            $this->executeJobThroughPipeline($job);
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, 'Job must throw exception for invalid dates');

        // R5: Ambient tenant A must be restored even after exception
        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id, 'R5: Tenant A ambient context must be restored after exception');

        // No reservation created
        $this->assertDatabaseMissing('property_reservations', [
            'external_reservation_id' => 'res-fail-001',
        ]);
    }

    /**
     * R6: Duplicate Channex event remains idempotent.
     *
     * @test
     */
    public function r6_duplicate_channex_event_remains_idempotent(): void
    {
        $this->tenantContext->setTenant($this->tenantA);

        $payload = $this->makePayload('res-dup-reg', 'CHNX-LISTING-B-001', '2026-11-10', '2026-11-13', 'Idempotent Guest', 'rev-dup-1');
        $job = new ChannexReservationIngestJob($payload, $this->tenantB->id);

        // Attempt 1
        $this->executeJobThroughPipeline($job);

        // Attempt 2 (replay)
        $this->executeJobThroughPipeline($job);

        $count = PropertyReservation::withoutGlobalScopes()
            ->where('external_reservation_id', 'res-dup-reg')
            ->where('tenant_id', $this->tenantB->id)
            ->count();

        $this->assertSame(1, $count, 'R6: Duplicate event must produce exactly 1 reservation');
        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id);
    }

    /**
     * R7: Unknown property/tenant remains fail-closed.
     *
     * @test
     */
    public function r7_unknown_property_remains_fail_closed(): void
    {
        $this->tenantContext->setTenant($this->tenantA);

        // Unknown listing ID
        $payload = $this->makePayload('res-unknown-prop', 'CHNX-UNKNOWN-PROPERTY-999', '2026-11-10', '2026-11-13');
        $job = new ChannexReservationIngestJob($payload, $this->tenantB->id);

        $exceptionThrown = false;
        try {
            $this->executeJobThroughPipeline($job);
        } catch (\RuntimeException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('ILAN_NOT_FOUND', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'R7: Unknown property must fail closed');
        $this->assertSame(0, PropertyReservation::withoutGlobalScopes()->where('external_reservation_id', 'res-unknown-prop')->count());
        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id);
    }

    /**
     * R8: ACK occurs only according to existing COMMIT → ACK invariant.
     *
     * @test
     */
    public function r8_ack_occurs_only_when_commit_succeeds_never_on_failure(): void
    {
        $mockAcknowledger = $this->createMock(ChannexBookingAcknowledger::class);

        // Expect ACK exactly once for the successful commit
        $mockAcknowledger->expects($this->once())
            ->method('acknowledgeRevision')
            ->with($this->tenantB->id, 'rev-ack-ok')
            ->willReturn(true);

        $processor = new ChannexRevisionProcessor(
            ingestService: app(ChannexReservationIngestService::class),
            acknowledger:  $mockAcknowledger,
        );
        $this->app->instance(ChannexRevisionProcessor::class, $processor);

        // 1. Successful commit test
        $this->tenantContext->setTenant($this->tenantA);
        $okPayload = $this->makePayload('res-ack-ok', 'CHNX-LISTING-B-001', '2026-11-01', '2026-11-04', 'ACK Ok Guest', 'rev-ack-ok');
        $okJob = new ChannexReservationIngestJob($okPayload, $this->tenantB->id);
        $this->executeJobThroughPipeline($okJob);

        $this->assertDatabaseHas('property_reservations', [
            'external_reservation_id' => 'res-ack-ok',
            'tenant_id'               => $this->tenantB->id,
        ]);

        // 2. Failed commit test (invalid dates) — must NOT call acknowledgeRevision
        $badPayload = $this->makePayload('res-ack-fail', 'CHNX-LISTING-B-001', '2026-11-10', '2026-11-08', 'ACK Fail Guest', 'rev-ack-fail');
        $badJob = new ChannexReservationIngestJob($badPayload, $this->tenantB->id);

        try {
            $this->executeJobThroughPipeline($badJob);
        } catch (\Throwable) {
            // Expected
        }

        $this->assertDatabaseMissing('property_reservations', [
            'external_reservation_id' => 'res-ack-fail',
        ]);
    }

    /**
     * Verify Modify and Cancel jobs under ambient Tenant A executing for Tenant B.
     *
     * @test
     */
    public function modify_and_cancel_jobs_switch_context_and_restore_properly(): void
    {
        // 1. Initial Ingest for Tenant B
        $this->tenantContext->setTenant($this->tenantA);
        $payload = $this->makePayload('res-lifecycle-01', 'CHNX-LISTING-B-001', '2026-12-01', '2026-12-05', 'Lifecycle Guest');
        $ingestJob = new ChannexReservationIngestJob($payload, $this->tenantB->id);
        $this->executeJobThroughPipeline($ingestJob);

        $reservation = PropertyReservation::withoutGlobalScopes()
            ->where('external_reservation_id', 'res-lifecycle-01')
            ->firstOrFail();

        // 2. Modify Job
        $this->tenantContext->setTenant($this->tenantA);
        $modifyJob = new ChannexReservationModifyJob(
            externalReservationId: 'res-lifecycle-01',
            externalChannel:       'airbnb',
            tenantId:              $this->tenantB->id,
            newStartDate:          '2026-12-02',
            newEndDate:            '2026-12-07',
            guestData:             ['guest_name' => 'Lifecycle Guest Renamed']
        );
        $this->executeJobThroughPipeline($modifyJob);

        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id);

        $reservation->refresh();
        $this->assertStringStartsWith('2026-12-02', (string) $reservation->start_date);
        $this->assertStringStartsWith('2026-12-07', (string) $reservation->end_date);
        $this->assertSame('Lifecycle Guest Renamed', $reservation->guest_name);

        // 3. Cancel Job
        $this->tenantContext->setTenant($this->tenantA);
        $cancelJob = new ChannexReservationCancelJob(
            externalReservationId: 'res-lifecycle-01',
            externalChannel:       'airbnb',
            tenantId:              $this->tenantB->id
        );
        $this->executeJobThroughPipeline($cancelJob);

        $this->assertSame($this->tenantA->id, $this->tenantContext->getTenant()->id);

        $reservation->refresh();
        $this->assertNotNull($reservation->cancelled_at);
        $stateValue = is_object($reservation->reservation_state)
            ? $reservation->reservation_state->value
            : $reservation->reservation_state;
        $this->assertSame('cancelled', $stateValue);
    }
}

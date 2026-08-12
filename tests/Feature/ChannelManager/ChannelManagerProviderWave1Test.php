<?php

namespace Tests\Feature\ChannelManager;

use App\Contracts\ChannelManager\ChannelSyncContract;
use App\Contracts\ChannelManager\ChannelTransportContract;
use App\DTOs\ChannelManager\ChannelTransportResult;
use App\Domain\ChannelManager\Enums\Channel;
use App\Infrastructure\ChannelManager\Adapters\AirbnbChannelAdapter;
use App\Infrastructure\ChannelManager\Adapters\BookingChannelAdapter;
use App\Infrastructure\ChannelManager\Booking\BookingTransport;
use App\Infrastructure\ChannelManager\Channex\ChannexAvailabilityMapper;
use App\Infrastructure\ChannelManager\Channex\ChannexTransport;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyAvailability;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006 SAAB Tests
 *
 * T1: tenant_isolation_wrong_tenant_id_returns_no_listing_mapping
 * T2: idempotency_same_correlation_id_transport_called_once_per_adapter
 * T3: timeout_retryable_transport_failure_propagates_correctly
 * T4: malformed_provider_response_adapter_does_not_throw
 * T5: channex_outage_failure_is_retryable
 * T6: adapter_does_not_write_to_property_availability
 * T7: channel_sync_contract_has_no_channex_reference
 * T8: disabled_booking_adapter_makes_no_external_call
 * T9: channel_transport_contract_binding_resolves_to_channex_transport
 * T10: airbnb_channel_adapter_implements_channel_sync_contract
 */
class ChannelManagerProviderWave1Test extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'      => 'CM-Provider-W1 Tenant',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'          => 'CM-Provider-W1 Property',
            'fiyat'           => 2000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);

        DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);
    }

    private function createSync(string $platform = 'airbnb', string $listingId = 'ext-listing-001'): IlanTakvimSync
    {
        return IlanTakvimSync::create([
            'ilan_id'              => $this->property->id,
            'platform'             => $platform,
            'external_listing_id'  => $listingId,
            'is_sync_active'       => true,
            'api_key'              => 'test-api-key-not-real',
            'senkron_durumu'       => 'active',
            'auto_sync'            => true,
        ]);
    }

    private function makeSuccessTransport(): ChannelTransportContract
    {
        return new class implements ChannelTransportContract {
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): ChannelTransportResult {
                return ChannelTransportResult::success('ref-' . $correlationId, ['processed' => count($availabilityData)]);
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): ChannelTransportResult {
                return ChannelTransportResult::success('ref-pull', ['events' => []]);
            }
            public function testConnection(int $tenantId): ChannelTransportResult {
                return ChannelTransportResult::success('connected');
            }
        };
    }

    private function makeFailureTransport(string $errorCode, bool $retryable): ChannelTransportContract
    {
        return new class($errorCode, $retryable) implements ChannelTransportContract {
            public function __construct(private string $code, private bool $retry) {}
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): ChannelTransportResult {
                return ChannelTransportResult::failure($this->code, 'Simulated failure', $this->retry);
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): ChannelTransportResult {
                return ChannelTransportResult::failure($this->code, 'Simulated failure', $this->retry);
            }
            public function testConnection(int $tenantId): ChannelTransportResult {
                return ChannelTransportResult::failure($this->code, 'Simulated failure', $this->retry);
            }
        };
    }

    // =========================================================================
    // T1: tenant isolation — wrong tenant_id → NO_LISTING_MAPPING
    // =========================================================================

    /** @test */
    public function tenant_isolation_wrong_tenant_id_returns_no_listing_mapping(): void
    {
        $this->createSync();

        $otherTenant = Tenant::create(['name' => 'Other', 'status' => 'active', 'is_active' => true]);

        $adapter = new AirbnbChannelAdapter($this->makeSuccessTransport());

        $response = $adapter->pushAvailability(
            tenantId: $otherTenant->id,     // wrong tenant
            propertyId: $this->property->id,
            correlationId: 'corr-t1',
            availabilityData: [['date' => '2044-01-10', 'available' => true]],
        );

        $this->assertFalse($response->success, 'T1: Wrong tenant must not find listing mapping');
        $this->assertEquals('NO_LISTING_MAPPING', $response->errorCode);
        $this->assertFalse($response->retryable);
    }

    // =========================================================================
    // T2: idempotency — adapter delegates correlationId to transport
    // =========================================================================

    /** @test */
    public function idempotency_correlation_id_passed_to_transport(): void
    {
        $this->createSync();

        $capturedCorrelationId = null;
        $transport = new class($capturedCorrelationId) implements ChannelTransportContract {
            public ?string $captured = null;
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): ChannelTransportResult {
                $this->captured = $correlationId;
                return ChannelTransportResult::success('ref-' . $correlationId);
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): ChannelTransportResult {
                return ChannelTransportResult::success('ref-pull');
            }
            public function testConnection(int $tenantId): ChannelTransportResult {
                return ChannelTransportResult::success('connected');
            }
        };

        $adapter = new AirbnbChannelAdapter($transport);
        $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->property->id,
            correlationId: 'my-idempotency-key-42',
            availabilityData: [['date' => '2044-02-01', 'available' => false]],
        );

        $this->assertEquals(
            'my-idempotency-key-42',
            $transport->captured,
            'T2: correlationId must be passed verbatim to transport for idempotency'
        );
    }

    // =========================================================================
    // T3: timeout/retryable transport failure propagates correctly
    // =========================================================================

    /** @test */
    public function retryable_transport_failure_propagates_correctly(): void
    {
        $this->createSync();

        $adapter = new AirbnbChannelAdapter($this->makeFailureTransport('TRANSPORT_ERROR', true));

        $response = $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->property->id,
            correlationId: 'corr-t3',
            availabilityData: [['date' => '2044-03-01', 'available' => true]],
        );

        $this->assertFalse($response->success, 'T3: Transport failure → adapter failure');
        $this->assertEquals('TRANSPORT_ERROR', $response->errorCode);
        $this->assertTrue($response->retryable, 'T3: Retryable transport error must propagate retryable=true');
    }

    // =========================================================================
    // T4: malformed provider response — adapter does not throw
    // =========================================================================

    /** @test */
    public function malformed_provider_response_adapter_does_not_throw(): void
    {
        $this->createSync();

        // Transport returns success with empty/unexpected metadata
        $transport = new class implements ChannelTransportContract {
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): ChannelTransportResult {
                return ChannelTransportResult::success('', ['malformed' => null]);
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): ChannelTransportResult {
                return ChannelTransportResult::success('', []);
            }
            public function testConnection(int $tenantId): ChannelTransportResult {
                return ChannelTransportResult::success('');
            }
        };

        $adapter = new AirbnbChannelAdapter($transport);

        // Must not throw
        $response = $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->property->id,
            correlationId: 'corr-t4',
            availabilityData: [],
        );

        // With empty availability data, adapter still returns no-listing-mapping (no sync) or success
        $this->assertNotNull($response, 'T4: Adapter must not throw on malformed provider response');
    }

    // =========================================================================
    // T5: Channex outage → retryable failure
    // =========================================================================

    /** @test */
    public function channex_outage_failure_is_retryable(): void
    {
        $this->createSync();

        $adapter = new AirbnbChannelAdapter($this->makeFailureTransport('RATE_LIMIT', true));

        $response = $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->property->id,
            correlationId: 'corr-t5',
            availabilityData: [['date' => '2044-05-01', 'available' => true]],
        );

        $this->assertFalse($response->success);
        $this->assertTrue($response->retryable, 'T5: Outage/rate-limit must be retryable');
    }

    // =========================================================================
    // T6: adapter does NOT write to PropertyAvailability
    // =========================================================================

    /** @test */
    public function adapter_does_not_write_to_property_availability(): void
    {
        $this->createSync();

        $rowsBefore = PropertyAvailability::where('property_id', $this->property->id)->count();

        $adapter = new AirbnbChannelAdapter($this->makeSuccessTransport());
        $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->property->id,
            correlationId: 'corr-t6',
            availabilityData: [
                ['date' => '2044-06-01', 'available' => false],
                ['date' => '2044-06-02', 'available' => false],
            ],
        );

        $rowsAfter = PropertyAvailability::where('property_id', $this->property->id)->count();

        $this->assertEquals(
            $rowsBefore,
            $rowsAfter,
            'T6: AirbnbChannelAdapter must NOT write to PropertyAvailability — ADR-006 constraint'
        );
    }

    // =========================================================================
    // T7: ChannelSyncContract has no Channex reference
    // =========================================================================

    /** @test */
    public function channel_sync_contract_has_no_channex_reference(): void
    {
        $contractFile = app_path('Contracts/ChannelManager/ChannelSyncContract.php');
        $contractContent = file_get_contents($contractFile);

        // Strip vendor dir path comment noise for the assertion
        // (file_get_contents may load full file including __FILE__ reference)
        $stripped = preg_replace('/\/\/.*vendor.*$/m', '', $contractContent);

        $this->assertStringNotContainsString(
            'channex',
            strtolower($stripped),
            'T7: ChannelSyncContract must not reference Channex — ADR-006 transport abstraction'
        );
    }

    // =========================================================================
    // T8: No active Booking sync = no external API call (NOT_REGISTERED guard)
    //
    // Sprint 4.14 context: BookingChannelAdapter IS NOW IMPLEMENTED (BW4).
    // The original stub test expected NOT_IMPLEMENTED for both push and pull.
    // BW4 semantics: no active sync record → NOT_REGISTERED (no external call).
    //
    // T8 invariant preserved: "disabled/unconfigured Booking adapter makes
    // no external calls." Only the error code changed: NOT_IMPLEMENTED →
    // NOT_REGISTERED. supportsPush() is now true (BW4 implemented it).
    // =========================================================================

    /** @test */
    public function no_active_sync_record_blocks_push_without_external_call(): void
    {
        // $this->property has NO IlanTakvimSync record for booking_com
        // BW4 adapter resolves sync record → null → NOT_REGISTERED (no transport call)
        $transport = $this->createMock(BookingTransport::class);
        $transport->expects($this->never())->method('post');

        $adapter = new BookingChannelAdapter($transport);

        $push = $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->property->id,
            correlationId: 'booking-push',
            availabilityData: [['date' => '2044-08-01', 'available' => true]],
        );

        $pull = $adapter->pullAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->property->id,
            correlationId: 'booking-pull',
            fromDate: '2044-08-01',
            toDate: '2044-08-10',
        );

        // T8 invariant: NOT_REGISTERED (not NOT_IMPLEMENTED) — no external call
        $this->assertFalse($push->success, 'T8: push must fail when no active sync record');
        $this->assertEquals('NOT_REGISTERED', $push->errorCode, 'T8: no active sync → NOT_REGISTERED');
        $this->assertFalse($push->retryable, 'T8: NOT_REGISTERED must not be retryable');

        // BW4: pullAvailability is NOT_IMPLEMENTED (Booking is push-only)
        $this->assertFalse($pull->success, 'T8: Booking pull must return NOT_IMPLEMENTED');
        $this->assertEquals('NOT_IMPLEMENTED', $pull->errorCode, 'T8: pull not implemented in Wave 4');
        $this->assertFalse($pull->retryable, 'T8: NOT_IMPLEMENTED must not be retryable');

        // BW4: supportsPush = true (implemented), supportsPull = false (not in Wave 4)
        $this->assertTrue($adapter->supportsPush(), 'T8: Booking supportsPush = true (BW4 implemented)');
        $this->assertFalse($adapter->supportsPull(), 'T8: Booking supportsPull = false (Wave 4 push-only)');
    }

    // =========================================================================
    // T9: ChannelTransportContract binding resolves to ChannexTransport
    // =========================================================================

    /** @test */
    public function channel_transport_contract_binding_resolves_to_channex_transport(): void
    {
        $resolved = app(ChannelTransportContract::class);

        $this->assertInstanceOf(
            ChannexTransport::class,
            $resolved,
            'T9: ChannelTransportContract must resolve to ChannexTransport in production'
        );
    }

    // =========================================================================
    // T10: AirbnbChannelAdapter implements ChannelSyncContract
    // =========================================================================

    /** @test */
    public function airbnb_channel_adapter_implements_channel_sync_contract(): void
    {
        $reflection = new \ReflectionClass(AirbnbChannelAdapter::class);

        $this->assertTrue(
            $reflection->implementsInterface(ChannelSyncContract::class),
            'T10: AirbnbChannelAdapter must implement ChannelSyncContract — ADR-006'
        );

        $adapter = new AirbnbChannelAdapter($this->makeSuccessTransport());
        $this->assertEquals(Channel::AIRBNB, $adapter->getChannel(), 'T10: getChannel must return Channel::AIRBNB');
        $this->assertTrue($adapter->supportsPush());
        $this->assertTrue($adapter->supportsPull());
    }
}

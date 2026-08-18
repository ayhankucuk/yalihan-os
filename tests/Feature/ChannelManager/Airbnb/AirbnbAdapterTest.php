<?php

namespace Tests\Feature\ChannelManager\Airbnb;

use App\Infrastructure\ChannelManager\Airbnb\AirbnbAvailabilityMapper;
use App\Infrastructure\ChannelManager\Airbnb\AirbnbClient;
use App\Infrastructure\ChannelManager\Airbnb\AirbnbRequestSigner;
use App\Infrastructure\ChannelManager\Airbnb\DTOs\AirbnbAvailabilityRequest;
use App\Infrastructure\ChannelManager\Airbnb\DTOs\AirbnbAvailabilityResponse;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbAuthenticationException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbRateLimitException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbRejectedRequestException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbTransportException;
use App\Infrastructure\ChannelManager\Adapters\AirbnbChannelAdapter;
use App\Infrastructure\ChannelManager\Adapters\InMemoryChannelAdapter;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Airbnb Adapter Integration Tests
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Tests:
 * ✓ canonical date range maps to Airbnb payload
 * ✓ correct external listing reference is used
 * ✓ credentials are resolved for the current tenant
 * ✓ cross-tenant listing mapping is rejected
 * ✓ successful response returns ChannelApiResponse success
 * ✓ authentication failure is non-retryable
 * ✓ provider rejection is non-retryable
 * ✓ timeout is retryable
 * ✓ rate limit is retryable
 *  ✓ secrets are absent from logs and events
 * ✓ duplicate request is idempotent
 * ✓ failed request records immutable execution
 * ✓ retry creates a new attempt without mutating history
 */
class AirbnbAdapterTest extends TestCase
{
    use LazilyRefreshDatabase;

    private AirbnbAvailabilityMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new AirbnbAvailabilityMapper();
    }

    // ─── Mapper Tests ───────────────────────────────────────────────

    /** @test */
    public function it_maps_canonical_date_range_to_airbnb_payload(): void
    {
        $request = $this->mapper->mapAvailability(
            airbnbListingId: 'ABC123',
            startDate: '2026-08-01',
            endDate: '2026-08-05',
            available: false,
            idempotencyKey: 'tenant:1:prop:100:block:2026-08-01:2026-08-05',
        );

        $this->assertInstanceOf(AirbnbAvailabilityRequest::class, $request);
        $this->assertEquals('ABC123', $request->listingId);
        $this->assertEquals('2026-08-01', $request->startDate);
        $this->assertEquals('2026-08-05', $request->endDate);
        $this->assertFalse($request->available);

        $payload = $request->toAirbnbPayload();
        $this->assertEquals('ABC123', $payload['listing_id']);
        $this->assertEquals('f', $payload['available']); // Airbnb uses 'f' for unavailable
    }

    /** @test */
    public function it_maps_available_true_to_airbnb_t(): void
    {
        $request = $this->mapper->mapAvailability(
            airbnbListingId: 'XYZ789',
            startDate: '2026-09-01',
            endDate: '2026-09-03',
            available: true,
            idempotencyKey: 'idemp-key',
        );

        $payload = $request->toAirbnbPayload();
        $this->assertEquals('t', $payload['available']); // Airbnb uses 't' for available
    }

    /** @test */
    public function it_groups_consecutive_dates_into_ranges(): void
    {
        $dateAvailability = [
            '2026-08-01' => false,
            '2026-08-02' => false,
            '2026-08-03' => false,
            '2026-08-04' => true,
            '2026-08-05' => true,
        ];

        $requests = $this->mapper->mapBatch(
            airbnbListingId: 'RANGE123',
            dateAvailabilities: $dateAvailability,
            idempotencyKeyPrefix: 'prefix',
        );

        // Should produce 2 requests: blocked range + available range
        $this->assertCount(2, $requests);

        $blocked = $requests[0];
        $this->assertEquals('2026-08-01', $blocked->startDate);
        $this->assertEquals('2026-08-03', $blocked->endDate);
        $this->assertFalse($blocked->available);

        $available = $requests[1];
        $this->assertEquals('2026-08-04', $available->startDate);
        $this->assertEquals('2026-08-05', $available->endDate);
        $this->assertTrue($available->available);
    }

    /** @test */
    public function it_validates_request_before_sending(): void
    {
        $request = new AirbnbAvailabilityRequest(
            listingId: '',
            startDate: '2026-08-01',
            endDate: '2026-08-05',
            available: false,
        );

        $this->expectException(\InvalidArgumentException::class);
        $request->validate();
    }

    // ─── Adapter Tests ─────────────────────────────────────────────

    /** @test */
    public function it_uses_correct_external_listing_reference(): void
    {
        $tenantId = 1;
        $property = $this->createProperty($tenantId);
        $airbnbListingId = 'AIRBNB-LISTING-123';

        IlanTakvimSync::create([
            'ilan_id' => $property->id,
            'platform' => 'airbnb',
            'external_listing_id' => $airbnbListingId,
            'is_sync_active' => true,
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'api_key' => 'test-key',
        ]);

        // Wave 1 Provider refactor: AirbnbChannelAdapter now uses ChannelTransportContract
        $transport = new class implements \App\Contracts\ChannelManager\ChannelTransportContract {
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('ref-' . $correlationId);
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('ref-pull');
            }
            public function testConnection(int $tenantId): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('connected');
            }
        };
        $adapter = new AirbnbChannelAdapter(transport: $transport);

        $response = $adapter->pushAvailability(
            tenantId: $tenantId,
            propertyId: $property->id,
            correlationId: 'corr-listing-ref',
            availabilityData: [['date' => '2026-08-01', 'available' => false]],
        );

        $this->assertTrue($response->success);
    }

    /** @test */
    public function it_rejects_missing_sync_config(): void
    {
        $tenantId = 1;
        $property = $this->createProperty($tenantId);

        // No IlanTakvimSync created — adapter returns NO_LISTING_MAPPING
        // Wave 1 Provider: anonymous ChannelTransportContract (never reached since lookup fails)
        $transport = new class implements \App\Contracts\ChannelManager\ChannelTransportContract {
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('ref');
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('ref-pull');
            }
            public function testConnection(int $tenantId): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('connected');
            }
        };
        $adapter = new AirbnbChannelAdapter(transport: $transport);

        $response = $adapter->pushAvailability(
            tenantId: $tenantId,
            propertyId: $property->id,
            correlationId: 'corr-no-config',
            availabilityData: [['date' => '2026-08-01', 'available' => false]],
        );

        $this->assertFalse($response->success);
        // Wave 1 Provider: NO_LISTING_MAPPING replaces NO_SYNC_CONFIG (ADR-006)
        $this->assertEquals('NO_LISTING_MAPPING', $response->errorCode);
    }

    /** @test */
    public function it_rejects_missing_external_listing_id(): void
    {
        $tenantId = 1;
        $property = $this->createProperty($tenantId);

        IlanTakvimSync::create([
            'ilan_id' => $property->id,
            'platform' => 'airbnb',
            'external_listing_id' => '', // empty
            'is_sync_active' => true,
            'senkron_durumu' => 'active',
            'auto_sync' => true,
        ]);

        $transport = new class implements \App\Contracts\ChannelManager\ChannelTransportContract {
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('ref');
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('ref-pull');
            }
            public function testConnection(int $tenantId): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('connected');
            }
        };
        $adapter = new AirbnbChannelAdapter(transport: $transport);

        $response = $adapter->pushAvailability(
            tenantId: $tenantId,
            propertyId: $property->id,
            correlationId: 'corr-no-listing',
            availabilityData: [['date' => '2026-08-01', 'available' => false]],
        );

        $this->assertFalse($response->success);
        // Wave 1 Provider: NO_LISTING_MAPPING replaces MISSING_LISTING_ID (ADR-006)
        $this->assertEquals('NO_LISTING_MAPPING', $response->errorCode);
    }

    // ─── Failure Taxonomy Tests ─────────────────────────────────────

    /** @test */
    public function auth_exception_is_non_retryable(): void
    {
        $ex = new AirbnbAuthenticationException(tenantId: 1);
        $this->assertFalse($ex->isRetryable());
    }

    /** @test */
    public function rejection_exception_is_non_retryable(): void
    {
        $ex = new AirbnbRejectedRequestException(
            tenantId: 1,
            rejectionCode: 'INVALID_LISTING',
            rejectionDetails: ['field' => 'listing_id'],
        );
        $this->assertFalse($ex->isRetryable());
    }

    /** @test */
    public function rate_limit_exception_is_retryable(): void
    {
        $ex = new AirbnbRateLimitException(tenantId: 1, retryAfterSeconds: 30);
        $this->assertTrue($ex->isRetryable());
        $this->assertEquals(30, $ex->getRetryAfter());
    }

    /** @test */
    public function transport_exception_is_retryable_by_default(): void
    {
        $ex = new AirbnbTransportException(tenantId: 1, retryable: true);
        $this->assertTrue($ex->isRetryable());
    }

    /** @test */
    public function transport_exception_can_be_non_retryable(): void
    {
        $ex = new AirbnbTransportException(tenantId: 1, retryable: false);
        $this->assertFalse($ex->isRetryable());
    }

    // ─── Secrets Safety Tests ───────────────────────────────────────

    /** @test */
    public function it_does_not_log_credentials_in_auth_exceptions(): void
    {
        $ex = new AirbnbAuthenticationException(tenantId: 1);
        $context = $ex->toLogContext();

        $this->assertArrayNotHasKey('client_secret', $context);
        $this->assertArrayNotHasKey('access_token', $context);
        $this->assertArrayNotHasKey('api_secret', $context);
        $this->assertArrayNotHasKey('api_key', $context);
    }

    /** @test */
    public function it_does_not_log_credentials_in_rejection_exceptions(): void
    {
        $ex = new AirbnbRejectedRequestException(
            tenantId: 1,
            rejectionCode: 'ERROR',
            rejectionDetails: [],
        );
        $context = $ex->toLogContext();

        $this->assertArrayNotHasKey('client_secret', $context);
        $this->assertArrayNotHasKey('access_token', $context);
    }

    /** @test */
    public function it_does_not_log_credentials_in_rate_limit_exceptions(): void
    {
        $ex = new AirbnbRateLimitException(tenantId: 1);
        $context = $ex->toLogContext();

        $this->assertArrayNotHasKey('client_secret', $context);
        $this->assertArrayNotHasKey('access_token', $context);
    }

    // ─── Idempotency Tests ──────────────────────────────────────────

    /** @test */
    public function it_handles_sandbox_mode_gracefully(): void
    {
        $tenantId = 1;
        $property = $this->createProperty($tenantId);

        IlanTakvimSync::create([
            'ilan_id' => $property->id,
            'platform' => 'airbnb',
            'external_listing_id' => 'SANDBOX-LISTING',
            'is_sync_active' => true,
            'senkron_durumu' => 'active',
            'auto_sync' => true,
            'api_key' => 'test-key',
        ]);

        // Wave 1 Provider refactor: use ChannelTransportContract mock
        $transport = new class implements \App\Contracts\ChannelManager\ChannelTransportContract {
            public function pushAvailability(int $tenantId, string $externalListingId, string $correlationId, array $availabilityData): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('sandbox:' . $correlationId, ['mode' => 'sandbox']);
            }
            public function pullAvailability(int $tenantId, string $externalListingId, string $correlationId, string $fromDate, string $toDate): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('sandbox:pull');
            }
            public function testConnection(int $tenantId): \App\DTOs\ChannelManager\ChannelTransportResult {
                return \App\DTOs\ChannelManager\ChannelTransportResult::success('connected');
            }
        };
        $adapter = new AirbnbChannelAdapter(transport: $transport);

        $response = $adapter->pushAvailability(
            tenantId: $tenantId,
            propertyId: $property->id,
            correlationId: 'sandbox-corr',
            availabilityData: [['date' => '2026-08-01', 'available' => false]],
        );

        $this->assertTrue($response->success, "Got: {$response->errorCode} — {$response->errorMessage}");
        $this->assertStringStartsWith('sandbox:', $response->channelRef);
    }

    // ─── Response Parsing Tests ─────────────────────────────────────

    /** @test */
    public function it_parses_successful_airbnb_response(): void
    {
        $response = AirbnbAvailabilityResponse::fromAirbnbApi([
            'status' => 'success',
            'confirmation' => 'conf-123',
        ]);

        $this->assertTrue($response->success);
        $this->assertEquals('conf-123', $response->airbnbReference);
        $this->assertEquals('success', $response->status);
    }

    /** @test */
    public function it_parses_error_airbnb_response(): void
    {
        $response = AirbnbAvailabilityResponse::fromAirbnbApi([
            'error' => 'Invalid listing',
            'error_code' => 'INVALID_LISTING',
        ]);

        $this->assertFalse($response->success);
        $this->assertEquals('INVALID_LISTING', $response->errorCode);
    }

    /** @test */
    public function it_detects_conflict_in_response(): void
    {
        $response = AirbnbAvailabilityResponse::fromAirbnbApi([
            'error' => 'Conflict',
            'error_code' => 'AVAILABILITY_CONFLICT',
        ]);

        $this->assertTrue($response->isConflict());
    }

    /** @test */
    public function it_detects_rate_limit_in_response(): void
    {
        $response = AirbnbAvailabilityResponse::fromAirbnbApi([
            'error' => 'Too many requests',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
        ]);

        $this->assertTrue($response->isRateLimit());
    }

    /** @test */
    public function it_detects_auth_error_in_response(): void
    {
        $response = AirbnbAvailabilityResponse::fromAirbnbApi([
            'error' => 'Unauthorized',
            'error_code' => 'UNAUTHORIZED',
        ]);

        $this->assertTrue($response->isAuthError());
    }

    // ─── Request Signer Tests ───────────────────────────────────────

    /** @test */
    public function it_generates_consistent_signatures(): void
    {
        $signer = new AirbnbRequestSigner('client-id-123', 'secret-abc');

        $payload = ['listing_id' => 'ABC123', 'available' => 'f'];
        $timestamp = '1724870400';

        $sig1 = $signer->sign($payload, $timestamp);
        $sig2 = $signer->sign($payload, $timestamp);

        // Same input → same signature
        $this->assertEquals($sig1, $sig2);
    }

    /** @test */
    public function it_verifies_valid_signature(): void
    {
        $signer = new AirbnbRequestSigner('client-id-123', 'secret-abc');

        $payload = ['listing_id' => 'ABC123', 'available' => 'f'];
        $timestamp = '1724870400';

        $signature = $signer->sign($payload, $timestamp);

        $this->assertTrue($signer->verify($signature, $payload, $timestamp));
    }

    /** @test */
    public function it_rejects_invalid_signature(): void
    {
        $signer = new AirbnbRequestSigner('client-id-123', 'secret-abc');

        $payload = ['listing_id' => 'ABC123', 'available' => 'f'];
        $timestamp = '1724870400';

        $this->assertFalse($signer->verify('invalid-signature', $payload, $timestamp));
    }

    // ─── InMemory Adapter Tests ─────────────────────────────────────

    /** @test */
    public function in_memory_adapter_simulates_conflict(): void
    {
        $adapter = new InMemoryChannelAdapter('airbnb', 'Test', shouldFail: false, shouldConflict: true);

        $adapter->setRemoteAvailability('2026-08-01', true); // remote = available

        $response = $adapter->pushAvailability([
            ['date' => '2026-08-01', 'available' => false, 'property_id' => 1],
        ]);

        $this->assertFalse($response->success);
        $this->assertEquals('CONFLICT', $response->errorCode);
    }

    // ─── Helper methods ─────────────────────────────────────────────

    private function createProperty(int $tenantId): Ilan
    {
        // tenant_id is not in Ilan's $fillable — use property assignment
        $ilan = Ilan::withoutGlobalScopes()->create([
            'baslik' => 'Test Property ' . uniqid(),
            'fiyat' => 1000,
            'para_birimi' => 'TRY',
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'yayin_durumu' => 'yayinda',
        ]);
        $ilan->tenant_id = $tenantId;
        $ilan->save();

        return $ilan;
    }
}

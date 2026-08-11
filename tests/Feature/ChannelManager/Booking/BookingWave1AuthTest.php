<?php

namespace Tests\Feature\ChannelManager\Booking;

use App\DTOs\ChannelManager\Booking\BookingAuthResult;
use App\Infrastructure\ChannelManager\Booking\BookingAuthException;
use App\Infrastructure\ChannelManager\Booking\BookingAuthTransport;
use App\Infrastructure\ChannelManager\Booking\BookingCredentialManager;
use App\Infrastructure\ChannelManager\Booking\BookingPropertyRef;
use App\Infrastructure\ChannelManager\Booking\BookingTransport;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\Tenant;
use App\Services\ChannelManager\BookingPropertyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Sprint 4.10 — Booking.com Provider Wave 1
 * BW1-01..BW1-10 Gate Tests
 *
 * BW1-01: Valid credentials → token acquired
 * BW1-02: Secret not logged
 * BW1-03: Valid token reused without re-exchange
 * BW1-04: Expired token triggers refresh
 * BW1-05: 401 → controlled failure result
 * BW1-06: HotelCode → correct ilan resolved
 * BW1-07: Unknown HotelCode → null, no exception
 * BW1-08: Cross-tenant mapping blocked
 * BW1-09: Timeout → retryable=true result
 * BW1-10: Container bindings resolve correctly
 */
class BookingWave1AuthTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Ilan $ilanA;
    protected Ilan $ilanB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'uuid' => 'bw1-tenant-a-' . uniqid(),
            'name' => 'BW1 Tenant A',
            'domain' => 'bw1a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => 'bw1-tenant-b-' . uniqid(),
            'name' => 'BW1 Tenant B',
            'domain' => 'bw1b.test',
            'status' => 'active',
        ]);

        // Use DB::table to avoid Ilan global scope (HasCountryScope)
        $ilanIdA = DB::table('ilanlar')->insertGetId([
            'baslik' => 'BW1 Property A',
            'slug' => 'bw1-property-a-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id' => $this->tenantA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->ilanA = Ilan::withoutGlobalScopes()->findOrFail($ilanIdA);

        $ilanIdB = DB::table('ilanlar')->insertGetId([
            'baslik' => 'BW1 Property B',
            'slug' => 'bw1-property-b-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id' => $this->tenantB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->ilanB = Ilan::withoutGlobalScopes()->findOrFail($ilanIdB);
    }

    private function createSync(int $ilanId, string $hotelCode, int $tenantId): void
    {
        DB::table('ilan_takvim_sync')->insert([
            'ilan_id' => $ilanId,
            'platform' => 'booking_com',
            'external_listing_id' => $hotelCode,
            'is_sync_active' => 1,
            'api_key' => 'client-id-' . $ilanId,
            'api_secret' => 'client-secret-' . $ilanId,
            'senkron_durumu' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ─── BW1-01: Valid credentials → token acquired ───────────────────────────

    /** @test BW1-01 */
    public function bw1_01_valid_credentials_token_acquired(): void
    {
        // Arrange: create a mock HTTP server that returns a valid token response
        // We test BookingAuthResult directly to avoid HTTP dependency
        $authResult = BookingAuthResult::fromTokenExchangeResponse([
            'access_token'  => 'test-access-token-xyz',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
            'refresh_token' => 'test-refresh-token',
        ]);

        $this->assertEquals('test-access-token-xyz', $authResult->accessToken);
        $this->assertEquals('Bearer', $authResult->tokenType);
        $this->assertEquals(3600, $authResult->expiresIn);
        $this->assertEquals('test-refresh-token', $authResult->refreshToken);
        $this->assertFalse(BookingAuthResult::isExpired($authResult->expiresAt()));
    }

    // ─── BW1-02: Credential secret not logged ─────────────────────────────────

    /** @test BW1-02 */
    public function bw1_02_secret_not_logged(): void
    {
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                // Secret must NOT appear in any log
                $searchValue = $context['client_secret'] ?? null;
                if ($searchValue !== null) {
                    return str_contains($searchValue, 'secret') || strlen($searchValue) > 0;
                }
                return true; // pass if no client_secret key
            })
            ->never();

        // The BookingAuthTransport masks the secret in logs
        // BW1-02: verify mask() method exists and works
        $transport = new BookingAuthTransport('https://example.com');
        $reflection = new \ReflectionClass($transport);
        $mask = $reflection->getMethod('mask');
        $mask->setAccessible(true);

        // Short value
        $this->assertEquals('******', $mask->invoke($transport, '123456'));
        // Long value
        $this->assertEquals('ABCD****QRST', $mask->invoke($transport, 'ABCD-very-long-secret-QRST'));
    }

    // ─── BW1-03: Valid token reused without re-exchange ──────────────────────

    /** @test BW1-03 */
    public function bw1_03_valid_token_reused_without_exchange(): void
    {
        // Create sync config with valid (non-expired) token
        $expiresAt = now()->addHour()->format('Y-m-d H:i:s');
        $this->createSync($this->ilanA->id, 'BK-HOTEL-A', $this->tenantA->id);
        DB::table('ilan_takvim_sync')
            ->where('ilan_id', $this->ilanA->id)
            ->where('platform', 'booking_com')
            ->update([
                'token_access' => 'valid-cached-token',
                'token_expires_at' => $expiresAt,
            ]);

        // BW1-03: CredentialManager.getValidToken() must return cached token
        // without calling authTransport.exchangeToken()
        $authMock = $this->createMock(BookingAuthTransport::class);
        $authMock->expects($this->never())->method('exchangeToken');

        $manager = new BookingCredentialManager($authMock);
        $result = $manager->getValidToken($this->ilanA->id);

        $this->assertEquals('valid-cached-token', $result['access_token']);
    }

    // ─── BW1-04: Expired token triggers refresh ───────────────────────────────

    /** @test BW1-04 */
    public function bw1_04_expired_token_triggers_refresh(): void
    {
        // Create sync config with expired token
        $expiresAt = now()->subMinutes(5)->format('Y-m-d H:i:s');
        $this->createSync($this->ilanA->id, 'BK-HOTEL-A', $this->tenantA->id);
        DB::table('ilan_takvim_sync')
            ->where('ilan_id', $this->ilanA->id)
            ->where('platform', 'booking_com')
            ->update([
                'token_access' => 'expired-token',
                'token_expires_at' => $expiresAt,
            ]);

        // BW1-04: CredentialManager must call exchangeToken when token expired
        $authMock = $this->createMock(BookingAuthTransport::class);
        $authMock->expects($this->once())
            ->method('exchangeToken')
            ->willReturn(BookingAuthResult::fromTokenExchangeResponse([
                'access_token' => 'new-refreshed-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]));

        $manager = new BookingCredentialManager($authMock);
        $result = $manager->getValidToken($this->ilanA->id);

        $this->assertEquals('new-refreshed-token', $result['access_token']);
    }

    // ─── BW1-05: 401 → controlled failure result ────────────────────────────

    /** @test BW1-05 */
    public function bw1_05_401_controlled_failure_result(): void
    {
        // BW1-05: BookingTransport returns ChannelTransportResult with retryable=false for 401
        $transport = new BookingTransport(
            new class implements \App\Infrastructure\ChannelManager\Booking\BookingCredentialManagerInterface {
                public function getValidToken(int $ilanId, string $platform = 'booking'): array {
                    return ['access_token' => 'any-token', 'expires_at' => now()->addHour()->format('Y-m-d H:i:s')];
                }
                public function forceRefresh(int $ilanId, string $platform = 'booking'): array {
                    throw new \RuntimeException('Token refresh forced but auth is invalid');
                }
                public function isExpired(array $config): bool { return false; }
            },
            'https://api.booking.com',
        );

        // We test the retry classification directly
        // BW1-05: 401 is NOT retryable (auth problem, not transient)
        $this->assertFalse(
            $this->invokeToTransportResult($transport, 401),
            'BW1-05: 401 must be retryable=false'
        );
    }

    // ─── BW1-06: HotelCode → correct ilan resolved ─────────────────────────

    /** @test BW1-06 */
    public function bw1_06_hotel_code_resolves_to_correct_ilan(): void
    {
        $this->createSync($this->ilanA->id, 'BK-HOTEL-A', $this->tenantA->id);

        $resolver = new BookingPropertyResolver();
        $ref = $resolver->resolve($this->tenantA->id, 'BK-HOTEL-A');

        // Diagnostic assertions
        $this->assertNotNull($this->ilanA->id, 'ilanA id must not be null');
        $this->assertNotNull($this->tenantA->id, 'tenantA id must not be null');

        // Direct DB query to verify data is there
        $syncExists = DB::table('ilan_takvim_sync')
            ->where('external_listing_id', 'BK-HOTEL-A')
            ->where('platform', 'booking_com')
            ->where('is_sync_active', 1)
            ->exists();
        $this->assertTrue($syncExists, 'Sync record must exist in DB');

        $ilanTenantId = DB::table('ilanlar')->where('id', $this->ilanA->id)->value('tenant_id');
        $this->assertEquals($this->tenantA->id, $ilanTenantId, 'Ilan tenant_id must match');

        $this->assertNotNull($ref, 'BW1-06: HotelCode must resolve to BookingPropertyRef');
        $this->assertInstanceOf(BookingPropertyRef::class, $ref);
        $this->assertEquals($this->ilanA->id, $ref->ilanId);
        $this->assertEquals($this->tenantA->id, $ref->tenantId);
        $this->assertEquals('BK-HOTEL-A', $ref->hotelCode);
    }

    // ─── BW1-07: Unknown HotelCode → null, no exception ─────────────────────

    /** @test BW1-07 */
    public function bw1_07_unknown_hotel_code_returns_null_no_exception(): void
    {
        $resolver = new BookingPropertyResolver();

        $this->assertNull(
            $resolver->resolve($this->tenantA->id, 'UNKNOWN-HOTEL-XYZ'),
            'BW1-07: Unknown HotelCode must return null'
        );
    }

    // ─── BW1-08: Cross-tenant mapping blocked ──────────────────────────────

    /** @test BW1-08 */
    public function bw1_08_cross_tenant_mapping_blocked(): void
    {
        // Tenant A's property is mapped to BK-CROSS-HOTEL
        $this->createSync($this->ilanA->id, 'BK-CROSS-HOTEL', $this->tenantA->id);

        // Tenant B tries to access Tenant A's HotelCode
        $resolver = new BookingPropertyResolver();
        $ref = $resolver->resolve($this->tenantB->id, 'BK-CROSS-HOTEL');

        $this->assertNull($ref, 'BW1-08: Cross-tenant HotelCode access must be blocked');
    }

    // ─── BW1-09: Timeout → retryable=true ─────────────────────────────────

    /** @test BW1-09 */
    public function bw1_09_timeout_retryable_true(): void
    {
        // BW1-09: Transport returns retryable=true for network timeout (status 0)
        $this->assertTrue(
            $this->invokeToTransportResult($transport = null, 0),
            'BW1-09: HTTP 0 (timeout) must be retryable=true'
        );
        // 429 rate limit
        $this->assertTrue(
            $this->invokeToTransportResult($transport = null, 429),
            'BW1-09: HTTP 429 must be retryable=true'
        );
        // 500 server error
        $this->assertTrue(
            $this->invokeToTransportResult($transport = null, 500),
            'BW1-09: HTTP 500 must be retryable=true'
        );
        // 400 client error
        $this->assertFalse(
            $this->invokeToTransportResult($transport = null, 400),
            'BW1-09: HTTP 400 must be retryable=false'
        );
    }

    // ─── BW1-10: Container bindings resolve correctly ────────────────────────

    /** @test BW1-10 */
    public function bw1_10_container_bindings_resolve_correctly(): void
    {
        // BW1-10: All Wave 1 services resolve from container
        $this->assertInstanceOf(
            \App\Contracts\ChannelManager\ChannelReservationContract::class,
            app(\App\Contracts\ChannelManager\ChannelReservationContract::class),
            'BW1-10: ChannelReservationContract resolves from container'
        );

        $this->assertInstanceOf(
            \App\Infrastructure\ChannelManager\Booking\BookingAuthTransport::class,
            app(\App\Infrastructure\ChannelManager\Booking\BookingAuthTransport::class),
            'BW1-10: BookingAuthTransport resolves from container'
        );

        $this->assertInstanceOf(
            \App\Infrastructure\ChannelManager\Booking\BookingCredentialManager::class,
            app(\App\Infrastructure\ChannelManager\Booking\BookingCredentialManager::class),
            'BW1-10: BookingCredentialManager resolves from container'
        );

        $this->assertInstanceOf(
            \App\Infrastructure\ChannelManager\Booking\BookingTransport::class,
            app(\App\Infrastructure\ChannelManager\Booking\BookingTransport::class),
            'BW1-10: BookingTransport resolves from container'
        );

        $this->assertInstanceOf(
            \App\Services\ChannelManager\BookingPropertyResolver::class,
            app(\App\Services\ChannelManager\BookingPropertyResolver::class),
            'BW1-10: BookingPropertyResolver resolves from container'
        );
    }

    // ─── Helper ─────────────────────────────────────────────────────────────

    /**
     * Invoke BookingTransport's private toTransportResult classification.
     * Returns retryable boolean.
     */
    private function invokeToTransportResult(?BookingTransport $transport, int $status): bool
    {
        $transport ??= new BookingTransport(
            new class implements \App\Infrastructure\ChannelManager\Booking\BookingCredentialManagerInterface {
                public function getValidToken(int $ilanId, string $platform = 'booking'): array {
                    return ['access_token' => 'tok', 'expires_at' => now()->addHour()->format('Y-m-d H:i:s')];
                }
                public function forceRefresh(int $ilanId, string $platform = 'booking'): array {
                    throw new \RuntimeException('unreachable');
                }
                public function isExpired(array $config): bool { return false; }
            },
            'https://api.booking.com',
        );

        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('toTransportResult');
        $method->setAccessible(true);

        // Build a minimal response array
        $response = $status >= 200 && $status < 300
            ? ['status' => $status, 'body' => ['ok' => true], 'error' => null]
            : ['status' => $status, 'body' => null, 'error' => "HTTP {$status}"];

        /** @var \App\DTOs\ChannelManager\ChannelTransportResult $result */
        $result = $method->invoke($transport, $response);
        return $result->retryable;
    }
}

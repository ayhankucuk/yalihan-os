<?php

namespace Tests\Feature\ChannelManager\Booking;

use App\DTOs\ChannelManager\ChannelTransportResult;
use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;
use App\Infrastructure\ChannelManager\Booking\BookingAuthException;
use App\Infrastructure\ChannelManager\Booking\BookingConnectivityAdapter;
use App\Infrastructure\ChannelManager\Booking\BookingConnectionProbeService;
use App\Infrastructure\ChannelManager\Booking\BookingCredentialManagerInterface;
use App\Infrastructure\ChannelManager\Booking\BookingTransport;
use App\Infrastructure\ChannelManager\Booking\DTOs\BookingConnectionResult;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sprint 4.15 — G34 Connectivity Probe
 *
 * BookingConnectivityAdapter::testConnection() — Non-destructive connectivity probe.
 *
 * G34-01: probe() → NOT_REGISTERED when no sync record exists for tenant
 * G34-02: probe() → AUTH_FAILED when token exchange throws BookingAuthException
 * G34-03: probe() → AUTH_FAILED when token exchange returns empty access_token
 * G34-04: probe() → CONNECTED when credentials valid and API responds 200
 * G34-05: probe() → PROVIDER_ERROR when API responds 5xx
 * G34-06: probe() → CONNECTION_ERROR when network error (status=0)
 * G34-07: probe() → AUTH_FAILED when API responds 401/403
 * G34-08: probeProperty() → correct property resolved, AUTH_FAILED on bad credentials
 * G34-09: testConnection() → ChannelSyncResponse.success when connected
 * G34-10: testConnection() → ChannelSyncResponse.failure when not connected
 */
class BookingG34ConnectivityProbeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Ilan $ilanA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['uuid' => 'g34-tA', 'name' => 'G34 Tenant A', 'status' => 'active', 'is_active' => true]);
        $this->tenantB = Tenant::create(['uuid' => 'g34-tB', 'name' => 'G34 Tenant B', 'status' => 'active', 'is_active' => true]);

        $ilanId = DB::table('ilanlar')->insertGetId([
            'baslik'            => 'G34 Property A',
            'slug'              => 'g34-prop-a-' . uniqid(),
            'yayin_durumu'      => 'yayinda',
            'aktiflik_durumu'   => true,
            'tenant_id'         => $this->tenantA->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        $this->ilanA = Ilan::withoutGlobalScopes()->findOrFail($ilanId);

        // Active booking_com sync for ilanA (tenantA)
        DB::table('ilan_takvim_sync')->insert([
            'ilan_id'                => $ilanId,
            'platform'               => 'booking_com',
            'external_listing_id'    => 'BK-G34-A',
            'is_sync_active'         => 1,
            'api_key'                => 'test-client-id',
            'api_secret'            => 'test-client-secret',
            'senkron_durumu'        => 'active',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    // ─── G34-01: No sync record → NOT_REGISTERED ─────────────────────────────────

    public function test_g34_01_no_sync_record_returns_not_registered(): void
    {
        // tenantB has no sync record
        $probe = $this->makeProbeService();

        $result = $probe->probe($this->tenantB->id);

        $this->assertFalse($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_NOT_REGISTERED, $result->probeDurumu);
        $this->assertStringContainsString((string) $this->tenantB->id, $result->errorMessage);
        $this->assertFalse($result->retryable);
    }

    // ─── G34-02: Token exchange throws → AUTH_FAILED ──────────────────────────────

    public function test_g34_02_token_exchange_failure_returns_auth_failed(): void
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockCredManager->method('getValidToken')
            ->willThrowException(new BookingAuthException('Invalid client credentials'));

        $mockTransport = $this->makeMockTransportNeverCalled();

        $probe = new BookingConnectionProbeService($mockCredManager, $mockTransport);
        $result = $probe->probe($this->tenantA->id);

        $this->assertFalse($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_AUTH_FAILED, $result->probeDurumu);
        $this->assertFalse($result->retryable);
    }

    // ─── G34-03: Empty access token → AUTH_FAILED ──────────────────────────────────

    public function test_g34_03_empty_access_token_returns_auth_failed(): void
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockCredManager->method('getValidToken')
            ->willReturn(['access_token' => '', 'expires_at' => null]);

        $mockTransport = $this->makeMockTransportNeverCalled();

        $probe = new BookingConnectionProbeService($mockCredManager, $mockTransport);
        $result = $probe->probe($this->tenantA->id);

        $this->assertFalse($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_AUTH_FAILED, $result->probeDurumu);
    }

    // ─── G34-04: Valid credentials + API 200 → CONNECTED ─────────────────────────

    public function test_g34_04_valid_credentials_and_api_200_returns_connected(): void
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockCredManager->method('getValidToken')
            ->willReturn(['access_token' => 'valid-token', 'expires_at' => now()->addHour()->toDateTimeString()]);

        $mockTransport = $this->createMock(BookingTransport::class);
        $mockTransport->expects($this->once())->method('get')
            ->with($this->ilanA->id, '/reservations', $this->anything())
            ->willReturn(ChannelTransportResult::success('200', ['reservations' => []]));

        $probe = new BookingConnectionProbeService($mockCredManager, $mockTransport);
        $result = $probe->probe($this->tenantA->id);

        $this->assertTrue($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_CONNECTED, $result->probeDurumu);
        $this->assertFalse($result->retryable);
        $this->assertArrayHasKey('probed_at', $result->metadata);
    }

    // ─── G34-05: API 5xx → PROVIDER_ERROR ──────────────────────────────────────────

    public function test_g34_05_api_5xx_returns_provider_error(): void
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockCredManager->method('getValidToken')
            ->willReturn(['access_token' => 'valid-token', 'expires_at' => now()->addHour()->toDateTimeString()]);

        $mockTransport = $this->createMock(BookingTransport::class);
        $mockTransport->expects($this->once())->method('get')
            ->willReturn(ChannelTransportResult::failure('503', 'Service Unavailable', false));

        $probe = new BookingConnectionProbeService($mockCredManager, $mockTransport);
        $result = $probe->probe($this->tenantA->id);

        $this->assertFalse($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_PROVIDER_ERROR, $result->probeDurumu);
    }

    // ─── G34-06: Network error (status=0) → CONNECTION_ERROR ─────────────────────

    public function test_g34_06_network_error_returns_connection_error(): void
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockCredManager->method('getValidToken')
            ->willReturn(['access_token' => 'valid-token', 'expires_at' => now()->addHour()->toDateTimeString()]);

        $mockTransport = $this->createMock(BookingTransport::class);
        $mockTransport->expects($this->once())->method('get')
            ->willReturn(ChannelTransportResult::failure('0', 'Connection refused', true));

        $probe = new BookingConnectionProbeService($mockCredManager, $mockTransport);
        $result = $probe->probe($this->tenantA->id);

        $this->assertFalse($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_CONNECTION_ERROR, $result->probeDurumu);
        $this->assertTrue($result->retryable);  // CONNECTION_ERROR is retryable
    }

    // ─── G34-07: API 401/403 → AUTH_FAILED ────────────────────────────────────────

    public function test_g34_07_api_401_returns_auth_failed(): void
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockCredManager->method('getValidToken')
            ->willReturn(['access_token' => 'expired-token', 'expires_at' => now()->addHour()->toDateTimeString()]);

        $mockTransport = $this->createMock(BookingTransport::class);
        $mockTransport->expects($this->once())->method('get')
            ->willReturn(ChannelTransportResult::failure('401', 'Unauthorized', false));

        $probe = new BookingConnectionProbeService($mockCredManager, $mockTransport);
        $result = $probe->probe($this->tenantA->id);

        $this->assertFalse($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_AUTH_FAILED, $result->probeDurumu);
        $this->assertFalse($result->retryable);
    }

    // ─── G34-08: probeProperty() — specific ilan ──────────────────────────────────

    public function test_g34_08_probe_property_auth_failure(): void
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockCredManager->method('getValidToken')
            ->willThrowException(new BookingAuthException('Invalid secret'));

        $mockTransport = $this->makeMockTransportNeverCalled();

        $probe = new BookingConnectionProbeService($mockCredManager, $mockTransport);
        $result = $probe->probeProperty($this->tenantA->id, $this->ilanA->id);

        $this->assertFalse($result->connected);
        $this->assertEquals(BookingConnectionResult::STATUS_AUTH_FAILED, $result->probeDurumu);
    }

    // ─── G34-09: testConnection() → ChannelSyncResponse.success ──────────────────

    public function test_g34_09_adapter_test_connection_returns_success_response(): void
    {
        $mockProbeService = $this->createMock(BookingConnectionProbeService::class);
        $mockProbeService->method('probe')
            ->with($this->tenantA->id)
            ->willReturn(BookingConnectionResult::connected('conn-probe-abc', ['probed_at' => now()->toIso8601String()]));

        $adapter = new BookingConnectivityAdapter($mockProbeService);
        $response = $adapter->testConnection($this->tenantA->id);

        $this->assertTrue($response->success);
        $this->assertEquals(Channel::BOOKING, $response->channel);
        $this->assertEquals(SyncDirection::EXPORT, $response->direction);
        $this->assertNull($response->errorCode);
        $this->assertFalse($response->retryable);
    }

    // ─── G34-10: testConnection() → ChannelSyncResponse.failure ───────────────────

    public function test_g34_10_adapter_test_connection_returns_failure_response(): void
    {
        $mockProbeService = $this->createMock(BookingConnectionProbeService::class);
        $mockProbeService->method('probe')
            ->with($this->tenantB->id)
            ->willReturn(BookingConnectionResult::notRegistered('conn-probe-xyz', $this->tenantB->id));

        $adapter = new BookingConnectivityAdapter($mockProbeService);
        $response = $adapter->testConnection($this->tenantB->id);

        $this->assertFalse($response->success);
        $this->assertEquals('NOT_REGISTERED', $response->errorCode);
        $this->assertFalse($response->retryable);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────────

    private function makeProbeService(): BookingConnectionProbeService
    {
        $mockCredManager = $this->createMock(BookingCredentialManagerInterface::class);
        $mockTransport  = $this->makeMockTransportNeverCalled();

        return new BookingConnectionProbeService($mockCredManager, $mockTransport);
    }

    /**
     * Returns a transport mock that MUST NOT be called.
     * Used for tests where we expect early-return before any API call.
     */
    private function makeMockTransportNeverCalled(): BookingTransport
    {
        $mock = $this->createMock(BookingTransport::class);
        $mock->expects($this->never())->method('get');
        $mock->expects($this->never())->method('post');

        return $mock;
    }
}

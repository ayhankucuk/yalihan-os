<?php

namespace Tests\Feature\ChannelManager;

use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyAvailability;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use App\Infrastructure\ChannelManager\Adapters\AirbnbChannelAdapter;
use App\Contracts\ChannelManager\ChannelTransportContract;
use App\DTOs\ChannelManager\ChannelTransportResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CW1-09: No Canonical Mutation
 *
 * Verifies that when channel sync fails, PropertyAvailability SSOT remains unchanged.
 * Provider outage must not corrupt canonical state.
 */
class ChannexCanonicalMutationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Ilan $ilan;
    private PropertyAvailability $availability;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'uuid' => 'mutext-' . uniqid(),
            'name' => 'Mutation Test Tenant',
            'domain' => 'mutation.test',
            'status' => 'active',
        ]);

        $this->ilan = Ilan::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Mutation Test Property',
            'yayin_durumu' => 'yayinda',
        ]);

        $this->availability = PropertyAvailability::create([
            'property_id' => $this->ilan->id,
            'date' => '2026-09-01',
            'available' => true,
        ]);

        IlanTakvimSync::create([
            'ilan_id' => $this->ilan->id,
            'platform' => 'airbnb',
            'external_listing_id' => 'airbnb-test-listing-123',
            'is_sync_active' => true,
        ]);

        app(TenantContextService::class)->setTenant($this->tenant);
    }

    public function test_canonical_availability_unchanged_after_transport_5xx_failure(): void
    {
        // Snapshot canonical state before
        $beforeAvailable = $this->availability->available;
        $beforeUpdatedAt = $this->availability->updated_at;

        // Simulate 5xx transport failure
        $mockTransport = new class implements ChannelTransportContract {
            public function pushAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                array $availabilityData
            ): ChannelTransportResult {
                return ChannelTransportResult::failure(
                    errorCode: 'TRANSPORT_ERROR',
                    errorMessage: 'Connection timeout',
                    retryable: true,
                );
            }

            public function pullAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                string $fromDate,
                string $toDate
            ): ChannelTransportResult {
                return ChannelTransportResult::failure('TRANSPORT_ERROR', 'Not implemented', false);
            }

            public function testConnection(int $tenantId): ChannelTransportResult
            {
                return ChannelTransportResult::failure('TRANSPORT_ERROR', 'Connection timeout', true);
            }
        };

        $adapter = new AirbnbChannelAdapter($mockTransport);

        // Attempt sync
        $result = $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->ilan->id,
            correlationId: 'mutext-' . uniqid(),
            availabilityData: [['date' => '2026-09-01', 'available' => false]],
        );

        // Transport failed with retryable error
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->retryable);

        // CRITICAL INVARIANT: Reload and verify canonical unchanged
        $this->availability->refresh();
        $this->assertEquals($beforeAvailable, $this->availability->available,
            'Canonical availability mutated despite transport failure!');
        $this->assertEquals(
            $beforeUpdatedAt->timestamp,
            $this->availability->updated_at->timestamp,
            'Canonical timestamp changed despite transport failure!'
        );
    }

    public function test_canonical_availability_unchanged_after_auth_failure(): void
    {
        $beforeAvailable = $this->availability->available;

        $mockTransport = new class implements ChannelTransportContract {
            public function pushAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                array $availabilityData
            ): ChannelTransportResult {
                return ChannelTransportResult::failure('AUTH_FAILED', 'Invalid credentials', false);
            }

            public function pullAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                string $fromDate,
                string $toDate
            ): ChannelTransportResult {
                return ChannelTransportResult::failure('AUTH_FAILED', 'Not implemented', false);
            }

            public function testConnection(int $tenantId): ChannelTransportResult
            {
                return ChannelTransportResult::failure('AUTH_FAILED', 'Invalid credentials', false);
            }
        };

        $adapter = new AirbnbChannelAdapter($mockTransport);

        $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->ilan->id,
            correlationId: 'mutext-auth-' . uniqid(),
            availabilityData: [['date' => '2026-09-01', 'available' => false]],
        );

        $this->availability->refresh();
        $this->assertEquals($beforeAvailable, $this->availability->available,
            'Canonical mutated after auth failure!');
    }

    public function test_canonical_availability_unchanged_after_rate_limit(): void
    {
        $beforeAvailable = $this->availability->available;

        $mockTransport = new class implements ChannelTransportContract {
            public function pushAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                array $availabilityData
            ): ChannelTransportResult {
                return ChannelTransportResult::failure(
                    'RATE_LIMIT',
                    'Too many requests',
                    true,
                    ['retry_after' => 60]
                );
            }

            public function pullAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                string $fromDate,
                string $toDate
            ): ChannelTransportResult {
                return ChannelTransportResult::failure('RATE_LIMIT', 'Not implemented', true);
            }

            public function testConnection(int $tenantId): ChannelTransportResult
            {
                return ChannelTransportResult::failure('RATE_LIMIT', 'Rate limited', true);
            }
        };

        $adapter = new AirbnbChannelAdapter($mockTransport);

        $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->ilan->id,
            correlationId: 'mutext-rate-' . uniqid(),
            availabilityData: [['date' => '2026-09-01', 'available' => false]],
        );

        $this->availability->refresh();
        $this->assertEquals($beforeAvailable, $this->availability->available,
            'Canonical mutated after rate limit!');
    }

    public function test_no_orphan_availability_on_transport_failure(): void
    {
        $countBefore = PropertyAvailability::where('property_id', $this->ilan->id)->count();

        $mockTransport = new class implements ChannelTransportContract {
            public function pushAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                array $availabilityData
            ): ChannelTransportResult {
                return ChannelTransportResult::failure('TRANSPORT_ERROR', 'Provider outage', true);
            }

            public function pullAvailability(
                int $tenantId,
                string $externalListingId,
                string $correlationId,
                string $fromDate,
                string $toDate
            ): ChannelTransportResult {
                return ChannelTransportResult::failure('TRANSPORT_ERROR', 'Not implemented', false);
            }

            public function testConnection(int $tenantId): ChannelTransportResult
            {
                return ChannelTransportResult::failure('TRANSPORT_ERROR', 'Provider outage', true);
            }
        };

        $adapter = new AirbnbChannelAdapter($mockTransport);
        $adapter->pushAvailability(
            tenantId: $this->tenant->id,
            propertyId: $this->ilan->id,
            correlationId: 'orphan-test-' . uniqid(),
            availabilityData: [['date' => '2026-09-01', 'available' => false]],
        );

        $countAfter = PropertyAvailability::where('property_id', $this->ilan->id)->count();
        $this->assertEquals($countBefore, $countAfter,
            'Orphan availability records created!');
    }
}

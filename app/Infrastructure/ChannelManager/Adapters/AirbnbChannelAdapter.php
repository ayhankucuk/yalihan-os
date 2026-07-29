<?php

namespace App\Infrastructure\ChannelManager\Adapters;

use App\Domain\ChannelManager\Contracts\ChannelAdapter;
use App\Domain\ChannelManager\Models\ChannelApiResponse;
use App\Infrastructure\ChannelManager\Airbnb\AirbnbAvailabilityMapper;
use App\Infrastructure\ChannelManager\Airbnb\AirbnbClient;
use App\Infrastructure\ChannelManager\Airbnb\DTOs\AirbnbAvailabilityRequest;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbAuthenticationException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbRateLimitException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbRejectedRequestException;
use App\Infrastructure\ChannelManager\Airbnb\Exceptions\AirbnbTransportException;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use Illuminate\Support\Facades\Log;

/**
 * AirbnbChannelAdapter — Airbnb implementation of ChannelAdapter contract
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Production connectivity: BLOCKED (no real Airbnb API credentials)
 * Architecture status: CERTIFIED
 *
 * Responsibilities:
 * - Map canonical availability to Airbnb API payload
 * - Resolve credentials from tenant-scoped IlanTakvimSync record
 * - Enforce listing ownership: internal property_id → external listing mapping
 * - Handle failure taxonomy: auth, rate limit, rejection, transport
 * - Idempotent requests via idempotency key
 * - NEVER log credentials, tokens, or secrets
 *
 * Does NOT:
 * - Make domain decisions
 * - Store secrets in logs/events
 * - Send internal property_id to Airbnb
 */
class AirbnbChannelAdapter implements ChannelAdapter
{
    private const CHANNEL_ID = 'airbnb';
    private const CHANNEL_NAME = 'Airbnb';

    public function __construct(
        private readonly AirbnbAvailabilityMapper $mapper,
        private readonly ?AirbnbClient $client = null, // nullable for testing
    ) {}

    /**
     * Get channel identifier
     */
    public function getChannelId(): string
    {
        return self::CHANNEL_ID;
    }

    /**
     * Get channel display name
     */
    public function getChannelName(): string
    {
        return self::CHANNEL_NAME;
    }

    /**
     * Push availability to Airbnb
     *
     * @param array $availabilityData Array of ['date' => 'Y-m-d', 'available' => bool, 'property_id' => int]
     * @return ChannelApiResponse
     */
    public function pushAvailability(array $availabilityData): ChannelApiResponse
    {
        if (empty($availabilityData)) {
            return ChannelApiResponse::success('no-op', ['processed' => 0]);
        }

        // ─── Step 1: Resolve tenant + property + listing mapping ─────
        $firstItem = $availabilityData[0];
        $propertyId = $firstItem['property_id'] ?? null;

        if ($propertyId === null) {
            return ChannelApiResponse::failure('property_id is required', 'MISSING_PROPERTY');
        }

        // Load IlanTakvimSync for the property
        $channelSync = IlanTakvimSync::where('ilan_id', $propertyId)
            ->where('platform', self::CHANNEL_ID)
            ->where('is_sync_active', true)
            ->first();

        if ($channelSync === null) {
            Log::warning('AirbnbChannelAdapter: No active sync config', [
                'property_id' => $propertyId,
                'channel' => self::CHANNEL_ID,
            ]);
            return ChannelApiResponse::failure(
                "No active Airbnb sync configuration for property {$propertyId}",
                'NO_SYNC_CONFIG'
            );
        }

        // ─── Tenant isolation: verify ownership ─────────────────────
        // Query Ilan directly (not via relation) to avoid TenantScope dependency issues
        $property = Ilan::withoutGlobalScopes()
            ->where('id', $propertyId)
            ->first(['id', 'tenant_id']);

        if ($property === null) {
            return ChannelApiResponse::failure('Property not found', 'PROPERTY_NOT_FOUND');
        }

        $tenantId = $property->tenant_id ?? null;
        if ($tenantId === null) {
            return ChannelApiResponse::failure('Tenant not found for property', 'TENANT_NOT_FOUND');
        }

        // ─── Step 2: Map canonical → Airbnb payload ────────────────
        $airbnbListingId = $channelSync->external_listing_id;
        if (empty($airbnbListingId)) {
            return ChannelApiResponse::failure(
                "Airbnb listing ID not configured for property {$propertyId}",
                'MISSING_LISTING_ID'
            );
        }

        // Group dates by availability state for range optimization
        $dateAvailability = [];
        foreach ($availabilityData as $item) {
            $dateAvailability[$item['date']] = $item['available'];
        }

        $idempotencyKey = $this->buildIdempotencyKey($tenantId, $propertyId, $dateAvailability);

        // ─── Step 3: Send to Airbnb ────────────────────────────────
        if ($this->client === null) {
            // No client configured — sandbox mode (testing only)
            Log::info('AirbnbChannelAdapter: Sandbox mode, no client configured', [
                'property_id' => $propertyId,
                'tenant_id' => $tenantId,
                'dates_count' => count($dateAvailability),
            ]);
            return ChannelApiResponse::success('sandbox:' . $idempotencyKey, [
                'mode' => 'sandbox',
                'processed' => count($dateAvailability),
                'idempotency_key' => $idempotencyKey,
            ]);
        }

        try {
            // Build Airbnb request (range-optimized)
            $requests = $this->mapper->mapBatch(
                airbnbListingId: $airbnbListingId,
                dateAvailabilities: $dateAvailability,
                idempotencyKeyPrefix: $idempotencyKey,
            );

            $references = [];
            foreach ($requests as $airbnbRequest) {
                $response = $this->client->updateAvailability($airbnbRequest, $tenantId);
                if (!$response->success) {
                    return $this->mapAirbnbResponseToChannelResponse($response, $tenantId);
                }
                $references[] = $response->airbnbReference;
            }

            return ChannelApiResponse::success(
                channelReference: implode(',', array_filter($references)),
                metadata: [
                    'processed' => count($availabilityData),
                    'idempotency_key' => $idempotencyKey,
                    'listing_id' => $airbnbListingId,
                ]
            );

        } catch (AirbnbAuthenticationException $e) {
            Log::error('AirbnbChannelAdapter: Auth failed', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                // NOTE: Never log credentials, tokens, or secrets
            ]);
            return ChannelApiResponse::failure(
                $e->getMessage(),
                'AUTH_FAILED'
            );

        } catch (AirbnbRateLimitException $e) {
            Log::warning('AirbnbChannelAdapter: Rate limited', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'retry_after' => $e->getRetryAfter(),
            ]);
            return ChannelApiResponse::failure(
                'Rate limited: retry after ' . $e->getRetryAfter() . ' seconds',
                'RATE_LIMIT'
            );

        } catch (AirbnbRejectedRequestException $e) {
            Log::warning('AirbnbChannelAdapter: Request rejected', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'rejection_code' => $e->rejectionCode,
                'rejection_details' => $e->rejectionDetails,
            ]);
            return ChannelApiResponse::failure(
                $e->getMessage(),
                'REJECTED'
            );

        } catch (AirbnbTransportException $e) {
            Log::error('AirbnbChannelAdapter: Transport error', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'retryable' => $e->isRetryable(),
            ]);
            return ChannelApiResponse::failure(
                $e->getMessage(),
                'TRANSPORT_ERROR'
            );

        } catch (\Throwable $e) {
            Log::error('AirbnbChannelAdapter: Unexpected error', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
            ]);
            return ChannelApiResponse::failure(
                'Unexpected error: ' . $e->getMessage(),
                'UNEXPECTED'
            );
        }
    }

    /**
     * Pull availability from Airbnb (not implemented in E03)
     *
     * @throws \RuntimeException
     */
    public function pullAvailability(string $fromDate, string $toDate): ChannelApiResponse
    {
        return ChannelApiResponse::failure(
            'Pull availability is not yet implemented (E03 scope: push-only)',
            'NOT_IMPLEMENTED'
        );
    }

    /**
     * Push a reservation to Airbnb (not implemented in E03)
     *
     * @throws \RuntimeException
     */
    public function pushReservation(array $reservationData): ChannelApiResponse
    {
        return ChannelApiResponse::failure(
            'Push reservation is not yet implemented (E03 scope: availability-only)',
            'NOT_IMPLEMENTED'
        );
    }

    /**
     * Fetch channel connection status
     */
    public function fetchStatus(): ChannelApiResponse
    {
        if ($this->client === null) {
            return ChannelApiResponse::success('sandbox', [
                'mode' => 'sandbox',
                'connected' => false,
            ]);
        }

        try {
            $connected = $this->client->testConnection();
            return ChannelApiResponse::success('connected', [
                'connected' => $connected,
                'mode' => 'production',
            ]);
        } catch (\Throwable $e) {
            return ChannelApiResponse::failure('Connection test failed: ' . $e->getMessage(), 'CONNECTION_FAILED');
        }
    }

    // ─── Private helpers ────────────────────────────────────────────

    /**
     * Build idempotency key from operation parameters
     *
     * Format: airbnb:{tenant_id}:{property_id}:{dates_hash}
     */
    private function buildIdempotencyKey(int $tenantId, int $propertyId, array $dateAvailability): string
    {
        $datesHash = md5(json_encode($dateAvailability));
        return sprintf('airbnb:%d:%d:%s', $tenantId, $propertyId, $datesHash);
    }

    /**
     * Map Airbnb response to ChannelApiResponse
     */
    private function mapAirbnbResponseToChannelResponse(
        \App\Infrastructure\ChannelManager\Airbnb\DTOs\AirbnbAvailabilityResponse $response,
        int $tenantId,
    ): ChannelApiResponse {
        if ($response->isConflict()) {
            return ChannelApiResponse::failure(
                'Airbnb conflict: ' . ($response->errorMessage ?? 'conflict'),
                'CONFLICT'
            )->withMetadata(['conflict' => $response->metadata]);
        }

        return ChannelApiResponse::failure(
            $response->errorMessage ?? 'Unknown Airbnb error',
            $response->errorCode ?? 'AIRBNB_ERROR'
        );
    }
}

<?php

namespace App\Infrastructure\ChannelManager\Adapters;

use App\Contracts\ChannelManager\ChannelSyncContract;
use App\Contracts\ChannelManager\ChannelTransportContract;
use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;
use App\Models\IlanTakvimSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AirbnbChannelAdapter — ChannelSyncContract implementation for Airbnb via Channex transport.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * OTA identity: Airbnb (Channel::AIRBNB)
 * Transport: ChannelTransportContract (currently ChannexTransport — replaceable)
 *
 * ADR-006 invariants enforced here:
 * - This adapter NEVER injects ChannexClient or ChannexTransport directly
 * - Transport is injected via ChannelTransportContract
 * - If Airbnb direct API becomes available, only transport changes — this class stays
 * - NO PropertyAvailability writes
 * - NO conflict or priority decisions
 * - Credentials resolved via IlanTakvimSync (platform = 'airbnb')
 *
 * @see ADR-006
 */
class AirbnbChannelAdapter implements ChannelSyncContract
{
    public function __construct(
        private readonly ChannelTransportContract $transport,
    ) {}

    public function getChannel(): Channel
    {
        return Channel::AIRBNB;
    }

    public function getChannelName(): string
    {
        return Channel::AIRBNB->label();
    }

    public function supportsPush(): bool
    {
        return true;
    }

    public function supportsPull(): bool
    {
        return true;
    }

    /**
     * Push availability FROM YALIHAN TO Airbnb (via transport).
     */
    public function pushAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelSyncResponse {
        // Resolve external listing ID
        $externalListingId = $this->resolveExternalListingId($tenantId, $propertyId);
        if ($externalListingId === null) {
            return ChannelSyncResponse::failure(
                channel: Channel::AIRBNB,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: 'NO_LISTING_MAPPING',
                errorMessage: "No active Airbnb listing mapping for property {$propertyId} / tenant {$tenantId}",
                retryable: false,
            );
        }

        $result = $this->transport->pushAvailability(
            tenantId: $tenantId,
            externalListingId: $externalListingId,
            correlationId: $correlationId,
            availabilityData: $availabilityData,
        );

        if ($result->success) {
            return ChannelSyncResponse::success(
                channel: Channel::AIRBNB,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                channelRef: $result->providerReference ?? $correlationId,
                metadata: $result->metadata,
            );
        }

        return ChannelSyncResponse::failure(
            channel: Channel::AIRBNB,
            direction: SyncDirection::EXPORT,
            correlationId: $correlationId,
            errorCode: $result->errorCode,
            errorMessage: $result->errorMessage ?? 'Unknown transport error',
            retryable: $result->retryable,
        );
    }

    /**
     * Pull availability FROM Airbnb TO YALIHAN (via transport).
     *
     * Returns raw events in metadata['events'].
     * Caller is responsible for writing to PropertyAvailability
     * via CanonicalAvailabilityService.
     */
    public function pullAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelSyncResponse {
        $externalListingId = $this->resolveExternalListingId($tenantId, $propertyId);
        if ($externalListingId === null) {
            return ChannelSyncResponse::failure(
                channel: Channel::AIRBNB,
                direction: SyncDirection::IMPORT,
                correlationId: $correlationId,
                errorCode: 'NO_LISTING_MAPPING',
                errorMessage: "No active Airbnb listing mapping for property {$propertyId} / tenant {$tenantId}",
                retryable: false,
            );
        }

        $result = $this->transport->pullAvailability(
            tenantId: $tenantId,
            externalListingId: $externalListingId,
            correlationId: $correlationId,
            fromDate: $fromDate,
            toDate: $toDate,
        );

        if ($result->success) {
            return ChannelSyncResponse::success(
                channel: Channel::AIRBNB,
                direction: SyncDirection::IMPORT,
                correlationId: $correlationId,
                channelRef: $result->providerReference ?? $correlationId,
                metadata: $result->metadata,
            );
        }

        return ChannelSyncResponse::failure(
            channel: Channel::AIRBNB,
            direction: SyncDirection::IMPORT,
            correlationId: $correlationId,
            errorCode: $result->errorCode,
            errorMessage: $result->errorMessage ?? 'Unknown transport error',
            retryable: $result->retryable,
        );
    }

    /**
     * Test connection to Airbnb via transport.
     */
    public function testConnection(int $tenantId): ChannelSyncResponse
    {
        $result = $this->transport->testConnection($tenantId);

        if ($result->success) {
            return ChannelSyncResponse::success(
                channel: Channel::AIRBNB,
                direction: SyncDirection::EXPORT,
                correlationId: 'connection-test',
                channelRef: 'connected',
                metadata: $result->metadata,
            );
        }

        return ChannelSyncResponse::failure(
            channel: Channel::AIRBNB,
            direction: SyncDirection::EXPORT,
            correlationId: 'connection-test',
            errorCode: $result->errorCode,
            errorMessage: $result->errorMessage ?? 'Connection failed',
            retryable: $result->retryable,
        );
    }

    /**
     * Rates push not implemented for Airbnb in Wave 5.
     * ADR-W5-01: Rates push requires RateProjectionService + RateSynchronizationService
     * to be built and wired before any channel adapter can receive rates.
     */
    public function pushRates(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        array  $ratesData,
    ): ChannelSyncResponse {
        return ChannelSyncResponse::failure(
            channel: Channel::AIRBNB,
            direction: SyncDirection::EXPORT,
            correlationId: $correlationId,
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: 'Airbnb rates push is not implemented in Wave 5.',
            retryable: false,
        );
    }

    // ─── Private ────────────────────────────────────────────────────

    /**
     * Resolve Channex external listing ID from IlanTakvimSync.
     * Returns null if no active configuration found OR if the listing
     * does not belong to the calling tenant (tenant isolation).
     *
     * T1 invariant: cross-tenant listing access is blocked at adapter level.
     */
    private function resolveExternalListingId(int $tenantId, int $propertyId): ?string
    {
        // Tenant isolation: join through ilanlar to verify ownership
        // @sab-ignore-query
        $sync = DB::table('ilan_takvim_sync')
            ->join('ilanlar', 'ilan_takvim_sync.ilan_id', '=', 'ilanlar.id')
            ->where('ilan_takvim_sync.ilan_id', $propertyId)
            ->where('ilan_takvim_sync.platform', 'airbnb')
            ->where('ilan_takvim_sync.is_sync_active', true)
            ->where('ilanlar.tenant_id', $tenantId)
            ->whereNotNull('ilan_takvim_sync.external_listing_id')
            ->where('ilan_takvim_sync.external_listing_id', '!=', '')
            ->select('ilan_takvim_sync.external_listing_id')
            ->first();

        if ($sync === null) {
            Log::warning('AirbnbChannelAdapter: no active listing mapping', [
                'property_id' => $propertyId,
                'tenant_id'   => $tenantId,
            ]);
            return null;
        }

        return (string) $sync->external_listing_id;
    }
}

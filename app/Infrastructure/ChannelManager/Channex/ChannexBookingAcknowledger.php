<?php

namespace App\Infrastructure\ChannelManager\Channex;

use App\Models\IlanTakvimSync;
use Illuminate\Support\Facades\Log;

/**
 * ChannexBookingAcknowledger — Acknowledges booking revisions to Channex API.
 *
 * WAVE 7 Phase B1.1R — Channex Reliability Recovery
 *
 * Explicit Booking ACK: POST /api/v1/booking_revisions/{id}/ack
 * - HTTP 200 = Success (Revision acknowledged)
 * - HTTP 5xx / Network = Retryable error
 * - Irreversible: Called ONLY AFTER database commit (COMMIT -> ACK)
 * - Invariant: ACK failure must NOT rollback committed reservation
 */
class ChannexBookingAcknowledger
{
    public function __construct(
        private readonly ?ChannexClient $client = null,
    ) {}

    /**
     * Acknowledge a booking revision to Channex.
     *
     * @param int    $tenantId   Tenant ID
     * @param string $revisionId Channex booking revision UUID
     * @return bool
     *
     * @throws ChannexAcknowledgementException on failure
     */
    public function acknowledgeRevision(int $tenantId, string $revisionId): bool
    {
        if (empty($revisionId)) {
            Log::warning('ChannexBookingAcknowledger: empty revisionId, skipping ACK', [
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $apiKey = $this->resolveApiKey($tenantId);

        Log::info('ChannexBookingAcknowledger: sending ACK to Channex', [
            'tenant_id'   => $tenantId,
            'revision_id' => $revisionId,
        ]);

        $client = $this->resolveClient();

        try {
            $success = $client->acknowledgeBookingRevision(
                apiKey: $apiKey,
                revisionId: $revisionId,
            );

            if ($success) {
                Log::info('ChannexBookingAcknowledger: ACK success', [
                    'tenant_id'   => $tenantId,
                    'revision_id' => $revisionId,
                ]);
            }

            return $success;

        } catch (ChannexAcknowledgementException $e) {
            Log::error('ChannexBookingAcknowledger: ACK failed (typed)', [
                'tenant_id'   => $tenantId,
                'revision_id' => $revisionId,
                'http_status' => $e->httpStatus,
                'retryable'   => $e->isRetryable,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('ChannexBookingAcknowledger: ACK unexpected error', [
                'tenant_id'   => $tenantId,
                'revision_id' => $revisionId,
                'error'       => $e->getMessage(),
            ]);
            throw new ChannexAcknowledgementException(
                httpStatus: 0,
                isRetryable: true,
                message: "Unexpected ACK failure: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    private function resolveClient(): ChannexClient
    {
        return $this->client ?? new ChannexClient();
    }

    private function resolveApiKey(int $tenantId): string
    {
        $apiKey = config('services.channex.api_key')
            ?? config('channels.channex.api_key');

        if (!empty($apiKey)) {
            return $apiKey;
        }

        // Resolve from IlanTakvimSync for tenant
        $syncRow = IlanTakvimSync::withoutGlobalScopes()
            ->join('ilanlar', 'ilanlar.id', '=', 'ilan_takvim_sync.ilan_id')
            ->where('ilanlar.tenant_id', $tenantId)
            ->where('ilan_takvim_sync.is_sync_active', true)
            ->whereNotNull('ilan_takvim_sync.api_key')
            ->select('ilan_takvim_sync.api_key')
            ->orderBy('ilan_takvim_sync.id')
            ->first();

        return $syncRow?->api_key ?? '';
    }
}

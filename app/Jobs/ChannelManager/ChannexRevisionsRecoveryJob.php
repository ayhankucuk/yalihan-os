<?php

namespace App\Jobs\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Infrastructure\ChannelManager\Channex\ChannexClient;
use App\Models\IlanTakvimSync;
use App\Services\ChannelManager\ChannexRevisionProcessor;
use App\Services\ChannelManager\ChannexWebhookTenantResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ChannexRevisionsRecoveryJob — 15-Minute Poller for Missed Webhooks / Revisions Feed.
 *
 * WAVE 7 Phase B1.1R — Channex Reliability Recovery
 *
 * Converges directly onto ChannexRevisionProcessor (Single Ingestion SSOT).
 */
class ChannexRevisionsRecoveryJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly ?int $targetTenantId = null,
    ) {}

    public function handle(
        ChannexClient                $client,
        ChannexRevisionProcessor     $processor,
        ChannexWebhookTenantResolver $tenantResolver,
    ): array {
        $startedAt = now();
        $metrics = [
            'started_at'           => $startedAt->toIso8601String(),
            'provider'             => 'channex',
            'revisions_discovered' => 0,
            'revisions_processed'  => 0,
            'ack_successes'        => 0,
            'ack_failures'         => 0,
            'tenant_id'            => $this->targetTenantId,
        ];

        Log::info('ChannexRevisionsRecoveryJob: starting revisions feed sync', $metrics);

        // Find active sync configs
        $query = IlanTakvimSync::withoutGlobalScopes()
            ->join('ilanlar', 'ilanlar.id', '=', 'ilan_takvim_sync.ilan_id')
            ->where('ilan_takvim_sync.is_sync_active', true)
            ->whereNotNull('ilan_takvim_sync.api_key');

        if ($this->targetTenantId !== null) {
            $query->where('ilanlar.tenant_id', $this->targetTenantId);
        }

        $syncConfigs = $query->select(
            'ilan_takvim_sync.id as sync_id',
            'ilan_takvim_sync.api_key',
            'ilan_takvim_sync.external_listing_id',
            'ilanlar.tenant_id',
            'ilanlar.id as ilan_id',
        )->get();

        $processedRevisionIds = [];

        foreach ($syncConfigs as $config) {
            $apiKey = $config->api_key;
            if (empty($apiKey)) {
                $apiKey = config('services.channex.api_key') ?? config('channels.channex.api_key') ?? '';
            }

            if (empty($apiKey)) {
                continue;
            }

            try {
                $feedResponse = $client->getBookingRevisionsFeed($apiKey);
                $revisions = $feedResponse['data'] ?? [];

                $metrics['revisions_discovered'] += count($revisions);

                foreach ($revisions as $rev) {
                    $revisionId = $rev['id'] ?? null;
                    if (!$revisionId || in_array($revisionId, $processedRevisionIds, true)) {
                        continue;
                    }

                    $processedRevisionIds[] = $revisionId;

                    try {
                        $payload = ChannexReservationPayload::fromChannexRevision($rev);

                        // Resolve tenant
                        $tenantId = $config->tenant_id
                            ?: $tenantResolver->resolveFromPropertyId($payload->externalListingId);

                        if (!$tenantId) {
                            Log::warning('ChannexRevisionsRecoveryJob: cannot resolve tenant for revision', [
                                'revision_id' => $revisionId,
                                'listing_id'  => $payload->externalListingId,
                            ]);
                            continue;
                        }

                        $reservation = $processor->process($payload, $tenantId);

                        if ($reservation !== null) {
                            $metrics['revisions_processed']++;
                            $metrics['ack_successes']++;
                        }

                    } catch (\Throwable $e) {
                        $metrics['ack_failures']++;
                        Log::error('ChannexRevisionsRecoveryJob: revision processing failed', [
                            'revision_id' => $revisionId,
                            'error'       => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('ChannexRevisionsRecoveryJob: feed pull error', [
                    'sync_id'   => $config->sync_id,
                    'tenant_id' => $config->tenant_id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $metrics['completed_at'] = now()->toIso8601String();
        $metrics['duration_seconds'] = now()->diffInSeconds($startedAt);

        Log::info('ChannexRevisionsRecoveryJob: completed revisions feed sync', $metrics);

        return $metrics;
    }
}

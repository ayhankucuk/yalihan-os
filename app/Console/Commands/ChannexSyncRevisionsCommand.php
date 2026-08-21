<?php

namespace App\Console\Commands;

use App\Jobs\ChannelManager\ChannexRevisionsRecoveryJob;
use Illuminate\Console\Command;

/**
 * ChannexSyncRevisionsCommand — Pulls unacknowledged booking revisions from Channex.
 *
 * WAVE 7 Phase B1.1R — Channex Reliability Recovery
 * Cadence: Scheduled every 15 minutes in app/Console/Kernel.php
 */
class ChannexSyncRevisionsCommand extends Command
{
    protected $signature = 'channex:sync-revisions {--tenant= : Optional tenant ID} {--sync : Execute synchronously without queue}';

    protected $description = 'Polls Channex Booking Revisions Feed for missed webhooks and processes canonical ingest';

    public function handle(): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $isSync = (bool) $this->option('sync');

        $this->info("🚀 Starting Channex Booking Revisions Feed recovery sync...");

        if ($isSync) {
            $metrics = app(ChannexRevisionsRecoveryJob::class, ['targetTenantId' => $tenantId])->handle(
                client: app(\App\Infrastructure\ChannelManager\Channex\ChannexClient::class),
                processor: app(\App\Services\ChannelManager\ChannexRevisionProcessor::class),
                tenantResolver: app(\App\Services\ChannelManager\ChannexWebhookTenantResolver::class),
            );

            $this->info("✅ Completed synchronous revisions sync:");
            $this->line("   - Revisions Discovered: {$metrics['revisions_discovered']}");
            $this->line("   - Revisions Processed:  {$metrics['revisions_processed']}");
            $this->line("   - ACK Successes:        {$metrics['ack_successes']}");
            $this->line("   - ACK Failures:         {$metrics['ack_failures']}");
        } else {
            ChannexRevisionsRecoveryJob::dispatch($tenantId);
            $this->info("✅ ChannexRevisionsRecoveryJob dispatched to queue.");
        }

        return 0;
    }
}

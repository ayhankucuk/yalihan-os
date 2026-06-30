<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReplayProjectionDlq extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projection:dlq:replay {--limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replays failed projection events from the Dead Letter Queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $this->info("Starting replay of up to {$limit} events from proj_dlq...");

        $events = \Illuminate\Support\Facades\DB::table('proj_dlq')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($events->isEmpty()) {
            $this->info('DLQ is empty. Nothing to replay.');
            return \Illuminate\Console\Command::SUCCESS;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($events as $record) {
            try {
                $payload = json_decode($record->payload, true);

                // Extremely simple dispatcher: we reconstruct the event
                if ($record->event_type === \App\Events\ListingCreated::class) {
                    $event = new \App\Events\ListingCreated(
                        $payload['listingId'],
                        $payload['title'] ?? '',
                        $payload['yayinDurumu'] ?? 1,
                        $payload['price'] ?? 0,
                        $payload['currencyId'] ?? null,
                        $payload['ownerId'] ?? null,
                        $payload['categoryId'] ?? null,
                        $payload['cityId'] ?? null
                    );
                    $event->eventId = $record->event_id; // Keep original ID for idempotency

                    event($event);

                } elseif ($record->event_type === \App\Events\ListingUpdated::class) {
                    $event = new \App\Events\ListingUpdated(
                        $payload['listingId'],
                        $payload['title'] ?? null,
                        $payload['yayinDurumu'] ?? 1,
                        $payload['price'] ?? 0,
                        $payload['currencyId'] ?? null,
                        $payload['ownerId'] ?? null,
                        $payload['categoryId'] ?? null,
                        $payload['cityId'] ?? null,
                        $payload['yayinDurumuChanged'] ?? false,
                        $payload['priceChanged'] ?? false,
                        $payload['isStale'] ?? false
                    );
                    $event->eventId = $record->event_id;

                    event($event);
                }

                // On success, we delete the DLQ item
                \Illuminate\Support\Facades\DB::table('proj_dlq')->where('id', $record->id)->delete();
                $successCount++;

            } catch (\Throwable $e) {
                $this->error("Failed to replay event {$record->event_id}: {$e->getMessage()}");
                \Illuminate\Support\Facades\DB::table('proj_dlq')
                    ->where('id', $record->id)
                    ->update([
                        'attempts' => $record->attempts + 1,
                        'error_message' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString(),
                        'updated_at' => now(),
                    ]);
                $failCount++;
            }
        }

        $this->info("Replay completed. Success: {$successCount}, Failed: {$failCount}.");
        return \Illuminate\Console\Command::SUCCESS;
    }
}

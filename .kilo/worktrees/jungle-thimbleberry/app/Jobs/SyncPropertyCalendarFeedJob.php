<?php

namespace App\Jobs;

use App\Models\PropertyAvailability;
use App\Models\PropertyCalendarFeed;
use App\Services\ICalParserService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPropertyCalendarFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = [60, 300, 900];

    protected int $feedId;

    public function __construct(int $feedId)
    {
        $this->feedId = $feedId;
    }

    public function handle(ICalParserService $parser): void
    {
        $feed = PropertyCalendarFeed::find($this->feedId);
        if (!$feed || !$feed->sync_enabled) {
            return;
        }

        try {
            $parsedData = $parser->fetchAndParse($feed->ical_url);
            $hash = $parsedData['hash'];
            $events = $parsedData['events'];

            // 1. Idempotency Check
            if ($feed->last_sync_hash === $hash) {
                // No changes in feed
                $feed->update([
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                ]);
                return;
            }

            DB::transaction(function () use ($feed, $events, $hash) {
                $activeUidsFromFeed = [];
                $today = now()->startOfDay();

                // 2. Insert/Update Feed events
                foreach ($events as $event) {
                    $start = $event['start']->startOfDay();
                    $end = $event['end']->startOfDay();
                    $uid = $event['uid'];

                    if ($end->lt($today)) {
                        continue; // Skip past events
                    }

                    $activeUidsFromFeed[] = $uid;

                    $dates = [];
                    $curr = $start->copy();
                    while ($curr->lt($end)) {
                        $dates[] = $curr->format('Y-m-d');
                        $curr->addDay();
                    }

                    $insertData = [];
                    $now = now();
                    foreach ($dates as $dateStr) {
                        $insertData[] = [
                            'property_id' => $feed->property_id,
                            'date' => $dateStr,
                            'is_available' => false,
                            'source_system' => 'airbnb_ical',
                            'block_reason' => 'airbnb_busy',
                            'external_ref' => $uid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    PropertyAvailability::insertOrIgnore($insertData);

                    // Update existing ones to ensure they remain false if previously inserted
                    PropertyAvailability::where('property_id', $feed->property_id)
                        ->where('source_system', 'airbnb_ical')
                        ->whereIn('date', $dates)
                        ->update([
                            'is_available' => false,
                            'external_ref' => $uid,
                            'block_reason' => 'airbnb_busy'
                        ]);
                }

                // 3. Reconciliation (Free up cancelled Airbnb slots)
                PropertyAvailability::where('property_id', $feed->property_id)
                    ->where('source_system', 'airbnb_ical')
                    ->where('date', '>=', $today)
                    ->whereNotIn('external_ref', $activeUidsFromFeed)
                    ->update([
                        'is_available' => true,
                        'source_system' => 'internal',
                        'block_reason' => null,
                        'external_ref' => null,
                    ]);

                // 4. Mühürle
                $feed->update([
                    'last_sync_hash' => $hash,
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                ]);
            });

        } catch (Throwable $e) {
            Log::error("iCal Sync Error: " . $e->getMessage(), [
                'feed_id' => $this->feedId,
                'attempt' => $this->attempts()
            ]);

            $feed->update([
                'last_sync_error' => substr($e->getMessage(), 0, 255)
            ]);

            throw $e; // Re-throw to trigger queue retry
        }
    }
}

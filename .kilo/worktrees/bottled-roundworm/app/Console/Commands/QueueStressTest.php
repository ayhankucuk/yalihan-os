<?php

namespace App\Console\Commands;

use App\Jobs\SyncPropertyCalendarFeedJob;
use App\Jobs\GenerateListingReportJob;
use App\Jobs\NotifyN8nAboutIlanPriceChange;
use App\Models\Ilan;
use App\Models\PropertyCalendarFeed;
use Illuminate\Console\Command;

class QueueStressTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enterprise:queue-stress {--count=50 : Number of jobs per type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulates a massive job burst for Gate C stress testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $this->info("🚀 Starting Enterprise Queue Stress Test (Gate C)");
        $this->info("📊 Target: {$count} jobs per critical type");

        // 1. iCal Sync Stress
        $feed = PropertyCalendarFeed::first();
        if ($feed) {
            $this->comment("📡 Dispatching {$count} iCal Sync jobs...");
            for ($i = 0; $i < $count; $i++) {
                SyncPropertyCalendarFeedJob::dispatch($feed->id);
            }
        } else {
            $this->warn("⚠️ No PropertyCalendarFeed found, skipping iCal stress.");
        }

        // 2. Report Generation Stress
        $ilan = Ilan::where('firsat_mühru', true)->first() ?: Ilan::first();
        if ($ilan) {
            $this->comment("📄 Dispatching {$count} Report Generation jobs...");
            for ($i = 0; $i < $count; $i++) {
                GenerateListingReportJob::dispatch($ilan->id, 'en');
            }
        } else {
            $this->warn("⚠️ No Ilan found, skipping report stress.");
        }

        // 3. n8n Notification Stress
        if ($ilan) {
            $this->comment("🤖 Dispatching {$count} n8n Price Change jobs...");
            for ($i = 0; $i < $count; $i++) {
                NotifyN8nAboutIlanPriceChange::dispatch(
                    $ilan->id,
                    1000000,
                    950000,
                    'TRY'
                );
            }
        }

        $this->success("✅ Total " . ($count * 3) . " jobs dispatched to Redis.");
        $this->info("👉 Check Horizon dashboard: " . config('app.url') . "/horizon");

        return Command::SUCCESS;
    }
}

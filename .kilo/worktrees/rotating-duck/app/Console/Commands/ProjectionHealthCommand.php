<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProjectionHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projection:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the health of the CQRS projection system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking CQRS Projection Health...");

        $listingsCount = \Illuminate\Support\Facades\DB::table('proj_listings')->count();
        $offsetsCount = \Illuminate\Support\Facades\DB::table('proj_event_offsets')->count();
        $dlqCount = \Illuminate\Support\Facades\DB::table('proj_dlq')->count();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Listings Sync Count', $listingsCount],
                ['Processed Events', $offsetsCount],
                ['DLQ Failed Events', $dlqCount],
            ]
        );

        if ($dlqCount > 0) {
            $this->error("ALARM: DLQ has {$dlqCount} pending failed events. Run projection:dlq:replay.");
            return \Illuminate\Console\Command::FAILURE;
        }

        $this->info('System is HEALTHY.');
        return \Illuminate\Console\Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\AI\TelemetryAggregator;
use Illuminate\Console\Command;

/**
 * AI Telemetry Hourly Aggregation Command
 *
 * Schedule: Runs every hour to aggregate AI usage metrics
 * Usage: php artisan ai:aggregate-telemetry
 */
class AggregateAITelemetry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:aggregate-telemetry
                            {--hour= : Specific hour to aggregate (Y-m-d H format)}
                            {--force : Force re-aggregation even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate AI logs into hourly telemetry metrics (Phase 13 - Epic 2)';

    /**
     * Execute the console command.
     */
    public function handle(TelemetryAggregator $aggregator): int
    {
        $this->info('🔄 AI Telemetry: Starting hourly aggregation...');

        $targetHour = $this->option('hour')
            ? \Carbon\Carbon::createFromFormat('Y-m-d H', $this->option('hour'))
            : null;

        try {
            $result = $aggregator->aggregateHourly($targetHour);

            $this->info('✅ Aggregation completed successfully');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Target Hour', $result['target_hour']],
                    ['Combinations Processed', $result['combinations_processed']],
                    ['Timestamp', $result['timestamp']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Aggregation failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}

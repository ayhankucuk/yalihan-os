<?php

namespace App\Console\Commands;

use App\Services\AI\AiAlertService;
use App\Services\AI\AiArchiveService;
use App\Services\AI\AiRetentionPolicyService;
use Illuminate\Console\Command;

class AiDataHygiene extends Command
{
    protected $signature = 'ai:data-hygiene';
    protected $description = 'Archive and cleanup old AI telemetry data (90+ days)';

    public function handle(
        AiRetentionPolicyService $retentionPolicy,
        AiArchiveService $archiveService,
        AiAlertService $alertService
    ): int {
        $this->info('🧹 Starting AI Data Hygiene Process');

        $startTime = microtime(true);
        $cutoffDate = $retentionPolicy->getCutoffDate();

        $this->info("Cutoff Date: {$cutoffDate->toDateTimeString()}");
        $this->info("Records older than this will be archived");
        $this->newLine();

        try {
            // Archive old records
            $this->info('📦 Archiving old records...');
            $results = $archiveService->archiveAllTables();

            $totalMoved = 0;
            $totalDuration = 0;

            foreach ($results as $table => $stats) {
                if (isset($stats['error'])) {
                    $this->error("❌ {$table}: {$stats['error']}");
                    continue;
                }

                $moved = $stats['moved'];
                $duration = $stats['duration_ms'];
                $totalMoved += $moved;
                $totalDuration += $duration;

                if ($moved > 0) {
                    $this->info("✅ {$table}: {$moved} records archived in {$duration}ms");
                } else {
                    $this->comment("⏭️  {$table}: No records to archive");
                }
            }

            $this->newLine();
            $this->info("📊 Summary:");
            $this->info("Total Records Archived: {$totalMoved}");
            $this->info("Total Duration: {$totalDuration}ms");

            // Send telemetry alert if significant data was moved
            if ($totalMoved > 0) {
                $alertService->sendAlert(
                    'ai_data_hygiene_completed',
                    'info',
                    "AI Data Hygiene: {$totalMoved} records archived",
                    [
                        'total_moved' => $totalMoved,
                        'duration_ms' => $totalDuration,
                        'cutoff_date' => $cutoffDate->toDateTimeString(),
                        'tables' => $results
                    ]
                );
            }

            $this->info('✅ AI Data Hygiene completed successfully');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ AI Data Hygiene failed: ' . $e->getMessage());

            // Send WARNING alert
            $alertService->sendAlert(
                'ai_data_hygiene_failed',
                'warning',
                "AI Data Hygiene failed: {$e->getMessage()}",
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );

            return self::FAILURE;
        }
    }
}

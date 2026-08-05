<?php

namespace App\Console\Commands;

use App\Services\Property\AvailabilityReplayService;
use Illuminate\Console\Command;

/**
 * availability:rebuild — RESERVATION_CORE Phase 2 E03
 *
 * Reconstructs availability projections from canonical reservation data.
 *
 * USAGE:
 *   php artisan availability:rebuild {tenantId} {startDate} {endDate} [--property=]
 *   php artisan availability:rebuild 1 2026-01-01 2026-12-31
 *   php artisan availability:rebuild 1 2026-01-01 2026-12-31 --property=123
 *
 * RULES:
 * - Only processes CONFIRMED reservations
 * - PENDING and CANCELLED reservations are ignored
 * - Runs in transaction: failure = no partial state
 * - Idempotent: running twice produces same result
 * - Creates audit record for every execution
 */
class RebuildAvailabilityProjections extends Command
{
    protected $signature = 'availability:rebuild
                            {tenantId : Tenant ID to rebuild projections for}
                            {startDate : Start date (YYYY-MM-DD)}
                            {endDate : End date (YYYY-MM-DD)}
                            {--property= : Optional specific property ID}
                            {--initiated-by= : User/system identifier for audit}';

    protected $description = 'Rebuild availability projections from canonical reservations (RESERVATION_CORE E03)';

    public function handle(AvailabilityReplayService $replayService): int
    {
        $tenantId = (int) $this->argument('tenantId');
        $startDate = $this->argument('startDate');
        $endDate = $this->argument('endDate');
        $propertyId = $this->option('property') ? (int) $this->option('property') : null;
        $initiatedBy = $this->option('initiated-by') ?? 'cli';

        $this->info('=== Availability Projection Rebuild ===');
        $this->table(
            ['Parameter', 'Value'],
            [
                ['Tenant ID', $tenantId],
                ['Start Date', $startDate],
                ['End Date', $endDate],
                ['Property ID', $propertyId ?? 'ALL'],
                ['Initiated By', $initiatedBy],
            ]
        );

        $this->newLine();

        if (!$this->confirm('This will rebuild availability projections. Continue?')) {
            $this->warn('Aborted.');
            return Command::FAILURE;
        }

        $this->info('Starting rebuild...');
        $this->newLine();

        $startTime = microtime(true);

        $result = $replayService->rebuild(
            $tenantId,
            $propertyId,
            $startDate,
            $endDate,
            $initiatedBy
        );

        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();

        if ($result->success) {
            $this->info('=== Rebuild Completed Successfully ===');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Execution ID', $result->executionId],
                    ['Properties Processed', $result->propertiesProcessed],
                    ['Reservations Processed', $result->reservationsProcessed],
                    ['Blocked Days', $result->blockedDays],
                    ['Duration', "{$duration}s"],
                ]
            );

            if ($result->hasErrors()) {
                $this->warn('Completed with errors:');
                foreach ($result->errors as $error) {
                    $this->line("  - Property {$error['property_id']}: {$error['error']}");
                }
            }
        } else {
            $this->error('=== Rebuild FAILED ===');
            $this->error("Error: {$result->errors[0]['error']}");
            if ($result->executionId) {
                $this->line("Execution ID: {$result->executionId}");
            }
        }

        $this->newLine();

        return $result->success ? Command::SUCCESS : Command::FAILURE;
    }
}

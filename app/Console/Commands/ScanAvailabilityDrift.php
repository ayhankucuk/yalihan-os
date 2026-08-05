<?php

namespace App\Console\Commands;

use App\Services\Property\AvailabilityDriftDetector;
use Illuminate\Console\Command;

/**
 * availability:drift-scan — RESERVATION_CORE Phase 2 E05
 *
 * Detects divergence between canonical reservations and availability projections.
 *
 * USAGE:
 *   php artisan availability:drift-scan {tenantId} {startDate} {endDate} [--property=]
 *   php artisan availability:drift-scan 1 2026-01-01 2026-12-31
 *   php artisan availability:drift-scan 1 2026-01-01 2026-12-31 --property=123
 *
 * RULES (SAAB E05):
 * - Drift detection is READ-ONLY
 * - No auto-remediation in this phase
 * - Creates audit/observability evidence
 */
class ScanAvailabilityDrift extends Command
{
    protected $signature = 'availability:drift-scan
                            {tenantId : Tenant ID to scan}
                            {startDate : Start date (YYYY-MM-DD)}
                            {endDate : End date (YYYY-MM-DD)}
                            {--property= : Optional specific property ID}
                            {--json : Output as JSON}
                            {--suggest-rebuild : Include rebuild suggestions}';

    protected $description = 'Scan for drift between reservations and availability projections (RESERVATION_CORE E05)';

    public function handle(AvailabilityDriftDetector $detector): int
    {
        $tenantId = (int) $this->argument('tenantId');
        $startDate = $this->argument('startDate');
        $endDate = $this->argument('endDate');
        $propertyId = $this->option('property') ? (int) $this->option('property') : null;
        $json = $this->option('json');
        $suggestRebuild = $this->option('suggest-rebuild');

        $this->info('=== Availability Drift Scan ===');
        $this->table(
            ['Parameter', 'Value'],
            [
                ['Tenant ID', $tenantId],
                ['Start Date', $startDate],
                ['End Date', $endDate],
                ['Property ID', $propertyId ?? 'ALL'],
            ]
        );
        $this->newLine();

        $this->info('Scanning for drift...');
        $this->newLine();

        try {
            if ($propertyId !== null) {
                $report = $detector->detect($tenantId, $propertyId, $startDate, $endDate);
                $reports = [$report];
            } else {
                $report = $detector->detectForTenant($tenantId, $startDate, $endDate);
                $reports = $report['drift_reports'] ?? [];
            }

            if ($json) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT));
                return Command::SUCCESS;
            }

            // Human-readable output
            $this->displayReport($report, $suggestRebuild);

            return $report['has_drift'] ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Scan failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function displayReport(array $report, bool $suggestRebuild): void
    {
        if ($propertyId = ($report['property_id'] ?? null)) {
            // Single property report
            $this->displaySinglePropertyReport($report, $suggestRebuild);
        } else {
            // Tenant-wide report
            $this->displayTenantReport($report, $suggestRebuild);
        }
    }

    private function displaySinglePropertyReport(array $report, bool $suggestRebuild): void
    {
        $this->info("Property ID: {$report['property_id']}");
        $this->info("Date Range: {$report['start_date']} - {$report['end_date']}");
        $this->info("Nights Checked: {$report['checked_nights']}");
        $this->newLine();

        if ($report['has_drift']) {
            $this->error("⚠️  DRIFT DETECTED!");
            $this->newLine();

            if (!empty($report['missing_blocks'])) {
                $this->warn("Missing Blocks ({$report['total_drifts']} total):");
                $this->table(
                    ['Date', 'Reservation ID', 'Drift Type'],
                    array_map(fn($b) => [$b['date'], $b['reservation_id'], $b['drift_type']], $report['missing_blocks'])
                );
                $this->newLine();
            }

            if (!empty($report['phantom_blocks'])) {
                $this->warn("Phantom Blocks:");
                $this->table(
                    ['Date', 'Reservation ID', 'Drift Type'],
                    array_map(fn($b) => [$b['date'], $b['reservation_id'], $b['drift_type']], $report['phantom_blocks'])
                );
                $this->newLine();
            }

            if ($suggestRebuild) {
                $this->info("💡 Suggestion:");
                $this->line("  php artisan availability:rebuild {$report['tenant_id']} {$report['start_date']} {$report['end_date']} --property={$report['property_id']}");
                $this->newLine();
            }
        } else {
            $this->info("✅ No drift detected. Availability is synchronized with reservations.");
        }

        $this->line($report['summary']);
    }

    private function displayTenantReport(array $report, bool $suggestRebuild): void
    {
        $this->info("Tenant: {$report['tenant_id']}");
        $this->info("Properties Checked: {$report['properties_checked']}");
        $this->info("Properties with Drift: {$report['properties_with_drift']}");
        $this->newLine();

        if ($report['properties_with_drift'] > 0) {
            $this->error("⚠️  {$report['properties_with_drift']} property(ies) have drift!");
            $this->newLine();

            foreach ($report['drift_reports'] as $propReport) {
                $this->warn("Property {$propReport['property_id']}: {$propReport['total_drifts']} drift(s)");
                $this->line("  {$propReport['summary']}");

                if ($suggestRebuild) {
                    $this->line("  💡 Fix: php artisan availability:rebuild {$report['tenant_id']} {$report['start_date']} {$report['end_date']} --property={$propReport['property_id']}");
                }
                $this->newLine();
            }
        } else {
            $this->info("✅ All properties synchronized. No drift detected.");
        }
    }
}

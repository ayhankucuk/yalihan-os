<?php

namespace App\Console\Commands\Ups;

use App\Services\Ups\UpsFeatureGovernanceService;
use Illuminate\Console\Command;

/**
 * UPS Governance Report Command
 *
 * Read-only reporting for feature lifecycle governance
 *
 * Context7: No data mutations, only reporting
 */
class GovernanceReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ups:governance:report';

    /**
     * The console command description.
     */
    protected $description = 'UPS Feature Governance Report (read-only)';

    public function __construct(
        private UpsFeatureGovernanceService $governanceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 UPS Feature Governance Report');
        $this->newLine();

        $summary = $this->governanceService->getSummaryReport();

        // Summary table
        $this->table(
            ['Metric', 'Count'],
            [
                ['Archived but still assigned', $summary['archived_but_assigned']],
                ['Inactive but still assigned', $summary['inactive_but_assigned']],
                ['Deprecated features (assigned)', $summary['deprecated_assigned']],
                ['Orphaned features (0 assignments)', $summary['orphaned_count']],
            ]
        );

        $this->newLine();

        // Lifecycle breakdown
        $this->info('📊 Lifecycle Distribution:');
        $this->table(
            ['Lifecycle', 'Count'],
            [
                ['Draft', $summary['total_by_lifecycle']['draft']],
                ['Active', $summary['total_by_lifecycle']['active']],
                ['Deprecated', $summary['total_by_lifecycle']['deprecated']],
                ['Archived', $summary['total_by_lifecycle']['archived']],
            ]
        );

        // Warnings
        if ($summary['archived_but_assigned'] > 0) {
            $this->warn("⚠️  {$summary['archived_but_assigned']} archived features are still assigned!");
        }

        if ($summary['inactive_but_assigned'] > 0) {
            $this->warn("⚠️  {$summary['inactive_but_assigned']} inactive features are still assigned!");
        }

        $this->newLine();
        $this->info('✅ Report complete (read-only, no changes made)');

        return Command::SUCCESS;
    }
}

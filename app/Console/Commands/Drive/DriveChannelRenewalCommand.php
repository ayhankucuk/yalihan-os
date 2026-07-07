<?php

namespace App\Console\Commands\Drive;

use App\Models\PortfolioDriveWorkspace;
use App\Services\Drive\DriveWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * DriveChannelRenewalCommand
 *
 * Sprint 4.8: Workspace Integration Platform
 *
 * Renews expiring Google Drive push notification channels.
 * Google Drive channels expire after ~7 days — this command must run daily
 * to prevent webhook delivery interruption.
 *
 * Usage:
 *   php artisan drive:renew-channels          # Dry-run (shows what would be renewed)
 *   php artisan drive:renew-channels --force  # Actually renew
 *
 * Schedule (app/Console/Kernel.php or scheduler()):
 *   $schedule->command('drive:renew-channels')->daily();
 *
 * Supervisor/Queue: Can also be queued as a job for async execution.
 */
class DriveChannelRenewalCommand extends Command
{
    protected $signature = 'drive:renew-channels
                            {--force : Actually renew channels (without this flag, runs in dry-run mode)}
                            {--workspace= : Renew only a specific workspace ID}';

    protected $description = 'Renew expiring Google Drive push notification channels';

    public function __construct(
        private readonly DriveWebhookService $webhookService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $workspaceId = $this->option('workspace');

        if (!$force) {
            $this->warn('DRY RUN — use --force to actually renew channels');
        }

        $this->info('Scanning for workspaces needing channel renewal...');

        $workspaces = $this->resolveWorkspaces($workspaceId);

        if ($workspaces->isEmpty()) {
            $this->info('No workspaces found.');
            return Command::SUCCESS;
        }

        $needsRenewalIds = $this->webhookService->workspacesNeedingRenewal();
        $needingRenewal = $workspaces->filter(fn($ws) => in_array($ws->id, $needsRenewalIds));

        if ($needingRenewal->isEmpty()) {
            $this->info('No channels need renewal.');
            return Command::SUCCESS;
        }

        $this->table(
            ['Workspace ID', 'Ilan ID', 'Folder ID', 'Status'],
            $needingRenewal->map(fn($ws) => [
                $ws->id,
                $ws->ilan_id,
                Str::limit($ws->drive_folder_id ?? '—', 20),
                $ws->workspace_status,
            ])
        );

        $this->line("Found {$needingRenewal->count()} channel(s) needing renewal.");

        if (!$force) {
            $this->warn('Run with --force to renew.');
            return Command::SUCCESS;
        }

        $renewed = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($needingRenewal->count());
        $bar->start();

        foreach ($needingRenewal as $workspace) {
            $bar->advance();

            $result = $this->webhookService->renewChannel($workspace);

            if ($result['success']) {
                $renewed++;
                Log::info('[drive:renew-channels] Channel renewed', [
                    'workspace_id' => $workspace->id,
                    'channel_id'  => $result['channel_id'] ?? null,
                    'expiration'  => $result['expiration'] ?? null,
                ]);
            } else {
                $failed++;
                $this->error("\n  Failed to renew workspace {$workspace->id}: " . ($result['error'] ?? 'Unknown'));
                Log::error('[drive:renew-channels] Renewal failed', [
                    'workspace_id' => $workspace->id,
                    'error'       => $result['error'] ?? 'Unknown',
                ]);
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("Renewal complete: {$renewed} renewed, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function resolveWorkspaces(?string $workspaceId): \Illuminate\Support\Collection
    {
        if ($workspaceId) {
            $ws = PortfolioDriveWorkspace::query()
                ->withoutGlobalScopes()
                ->where('id', (int) $workspaceId)
                ->get();

            if ($ws->isEmpty()) {
                $this->warn("Workspace {$workspaceId} not found.");
            }

            return $ws;
        }

        return PortfolioDriveWorkspace::query()
            ->withoutGlobalScopes()
            ->where('workspace_status', PortfolioDriveWorkspace::STATUS_READY)
            ->whereNotNull('drive_folder_id')
            ->get();
    }
}

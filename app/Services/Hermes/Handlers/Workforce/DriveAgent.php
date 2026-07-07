<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Events\Workforce\PropertyWorkspaceCreated;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Drive\DriveWebhookService;
use App\Services\Drive\DriveWorkspaceService;
use App\Services\Hermes\HermesService;
use Illuminate\Support\Facades\Log;

/**
 * DriveAgent — AI Workforce Sprint 4.4
 *
 * Triggered by: portfolio.created
 * Role: Creates Google Drive workspace with 12 subfolders for each portfolio
 *
 * Idempotent: Skips creation if workspace already exists for the ilan.
 * All Drive API calls go through DriveWorkspaceService only.
 */
class DriveAgent implements HermesHandlerContract
{
    public function __construct(
        private DriveWorkspaceService $driveService,
        private DriveWebhookService $webhookService,
        private HermesService $hermesService,
    ) {}

    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            'portfolio.created',
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $startTime = microtime(true);
        $payload = $event->toPayload();
        $ilanId = $payload['ilan_id'] ?? null;
        $tenantId = $event->tenantId();
        $ilanBaslik = $payload['ilan_baslik'] ?? 'Bilinmeyen İlan';

        if ($ilanId === null) {
            Log::warning('[DriveAgent] No ilan_id in event payload');
            return [
                'handler' => self::class,
                'error' => 'Missing ilan_id in event payload',
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }

        // Record execution log
        $execLog = WorkforceExecutionLog::create([
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $payload['chain_id'] ?? null,
            'agent_name' => 'drive_agent',
            'agent_class' => self::class,
            'event_received' => $event->eventName(),
            'event_chain_step' => 0,
            'input_payload' => $payload,
            'output_payload' => [],
            // @sab-ignore-context7 — status maps to workforce_execution_logs.status column
            'status' => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            // ── Step 1: Idempotency check ───────────────────────────────
            if ($this->driveService->workspaceExistsForPortfolio($ilanId)) {
                $existing = PortfolioDriveWorkspace::forPortfolio($ilanId)->first();

                $execLog->markSkipped('Workspace already exists', [
                    'workspace_id' => $existing?->getKey(),
                    'workspace_status' => $existing?->workspace_status,
                ]);

                Log::info('[DriveAgent] Workspace already exists, skipping', [
                    'ilan_id' => $ilanId,
                    'workspace_id' => $existing?->getKey(),
                ]);

                return [
                    'handler' => self::class,
                    'ilan_id' => $ilanId,
                    'skipped' => true,
                    'workspace_id' => $existing?->getKey(),
                    'workspace_status' => $existing?->workspace_status,
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // ── Step 2: Generate portfolio identifier ───────────────────
            $portfolioNo = $this->generatePortfolioNo($ilanId);

            // ── Step 3: Create root workspace folder ────────────────────
            $workspaceResult = $this->driveService->createWorkspace(
                portfolioNo: $portfolioNo,
                title: $ilanBaslik,
                tenantId: $tenantId,
            );

            if (!$workspaceResult->isSuccessful()) {
                $this->handleFailure($ilanId, $tenantId, $workspaceResult->errorMessage ?? 'Unknown error', $execLog);

                return [
                    'handler' => self::class,
                    'ilan_id' => $ilanId,
                    'error' => $workspaceResult->errorMessage,
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // ── Step 4: Create 12 subfolders ───────────────────────────
            $subfolders = $this->driveService->createSubfolders(
                $workspaceResult->rootFolderId,
                $portfolioNo,
            );

            // ── Step 5: Store metadata in DB ──────────────────────────
            $workspace = $this->driveService->storeWorkspaceMeta(
                ilanId: $ilanId,
                tenantId: $tenantId,
                rootFolderId: $workspaceResult->rootFolderId,
                rootFolderUrl: $workspaceResult->rootFolderUrl,
                rootFolderName: $workspaceResult->metadata['folder_name'] ?? "YLH-{$portfolioNo} - {$ilanBaslik}",
                portfolioNo: $portfolioNo,
                subfolders: $subfolders,
            );

            // ── Step 6: Register Drive push webhook channel ─────────────
            $callbackUrl = config('app.url') . '/api/webhook/drive';
            $channelResult = $this->webhookService->registerChannel($workspace, $callbackUrl);

            if (!$channelResult['success']) {
                Log::warning('[DriveAgent] Webhook channel registration failed', [
                    'workspace_id' => $workspace->getKey(),
                    'error'        => $channelResult['error'] ?? 'Unknown',
                ]);
            } else {
                Log::info('[DriveAgent] Webhook channel registered', [
                    'workspace_id' => $workspace->getKey(),
                    'channel_id'   => $channelResult['channel_id'] ?? null,
                ]);
            }

            $execLog->markCompleted([
                'workspace_id' => $workspace->getKey(),
                'drive_folder_id' => $workspace->drive_folder_id,
                'subfolders_created' => count($subfolders),
                'portfolio_no' => $portfolioNo,
                'webhook_channel_registered' => $channelResult['success'],
                'channel_id' => $channelResult['channel_id'] ?? null,
            ]);

            Log::info('[DriveAgent] Workspace created successfully', [
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace->getKey(),
                'drive_folder_id' => $workspace->drive_folder_id,
                'subfolders_created' => count($subfolders),
            ]);

            // ── Step 7: Emit PropertyWorkspaceCreated event ──────────────
            $this->emitWorkspaceCreatedEvent($workspace, [
                'ilan_id' => $ilanId,
                'tenant_id' => $tenantId,
                'portfolio_no' => $portfolioNo,
                'subfolders_count' => count($subfolders),
                'created_by' => 'drive_agent',
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace->getKey(),
                'drive_folder_id' => $workspace->drive_folder_id,
                'drive_folder_url' => $workspace->drive_folder_url,
                'subfolders_created' => count($subfolders),
                'portfolio_no' => $portfolioNo,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());

            Log::error('[DriveAgent] Workspace creation failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * Handle workspace creation failure
     */
    private function handleFailure(
        int $ilanId,
        ?int $tenantId,
        string $errorMessage,
        WorkforceExecutionLog $execLog,
    ): void {
        // Store minimal record marking the error state
        try {
            PortfolioDriveWorkspace::updateOrCreate(
                ['ilan_id' => $ilanId],
                [
                    'tenant_id' => $tenantId,
                    'workspace_status' => PortfolioDriveWorkspace::STATUS_ERROR,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[DriveAgent] Could not mark workspace error in DB', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);
        }

        $execLog->markFailed($errorMessage);
    }

    /**
     * Emit PropertyWorkspaceCreated event through Hermes
     */
    private function emitWorkspaceCreatedEvent(
        PortfolioDriveWorkspace $workspace,
        array $metadata,
    ): void {
        $event = new PropertyWorkspaceCreated($workspace, $metadata);
        $this->hermesService->receive($event);
    }

    /**
     * Generate portfolio number for Drive folder naming
     */
    private function generatePortfolioNo(int $ilanId): string
    {
        return str_pad((string) $ilanId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false;
    }
}

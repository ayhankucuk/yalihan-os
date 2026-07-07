<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortfolioDriveWorkspace;
use App\Models\SaaS\Tenant;
use App\Services\Drive\DriveSyncService;
use App\Services\Drive\DriveWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DriveWebhookController
 *
 * Sprint 4.8: Workspace Integration Platform
 *
 * Receives Google Drive push notification webhooks.
 * Endpoint: POST /api/webhook/drive
 *
 * Google Drive sends via Cloud Pub/Sub:
 *   POST { "message": { "data": "<base64>", "messageId": "...", "publishTime": "..." } }
 *
 * Security: X-Goog-Channel-token HMAC header validation via DriveWebhookService
 * Tenant Resolution: channel token → workspace ID → tenant_id
 */
class DriveWebhookController extends Controller
{
    public function __construct(
        private readonly DriveWebhookService $webhookService,
        private readonly DriveSyncService $syncService,
    ) {}

    /**
     * POST /api/webhook/drive
     *
     * Validates the notification, resolves the workspace,
     * then delegates change processing to DriveWebhookService.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload       = $request->all();
        $channelToken  = $request->header('X-Goog-Channel-token');
        $channelId     = $request->header('X-Goog-Channel-id');

        Log::debug('[DriveWebhook] Received webhook', [
            'channel_id' => $channelId,
            'has_data'   => isset($payload['message']['data']),
        ]);

        // 1. Validate notification
        $validation = $this->webhookService->validateNotification(
            $payload,
            $channelToken,
            $channelId,
        );

        if (!$validation['valid']) {
            Log::warning('[DriveWebhook] Invalid notification', [
                'error' => $validation['error'],
            ]);
            return response()->json(['ok' => false, 'reason' => $validation['error']], 403);
        }

        $workspaceId = $validation['workspace_id'];

        // 2. Resolve workspace
        $workspace = null;
        if ($workspaceId) {
            $workspace = PortfolioDriveWorkspace::query()
                ->withoutGlobalScopes()
                ->find($workspaceId);
        }

        if (!$workspace) {
            Log::warning('[DriveWebhook] Workspace not found', ['workspace_id' => $workspaceId]);
            return response()->json(['ok' => false, 'reason' => 'workspace_not_found'], 404);
        }

        // 3. Set tenant context
        if ($workspace->tenant_id) {
            $tenant = Tenant::query()->where('id', $workspace->tenant_id)->first();
            if ($tenant) {
                app(\App\Services\SaaS\TenantContextService::class)->setTenant($tenant);
            }
        }

        // 4. Process webhook payload (normalize Drive envelope)
        $change = $this->syncService->processWebhookPayload($payload);

        if (empty($change['file_id'])) {
            Log::debug('[DriveWebhook] No file_id in payload, acknowledged');
            return response()->json(['ok' => true, 'processed' => 0]);
        }

        // 5. Process actual Drive changes
        $result = $this->webhookService->processChanges($workspace);

        Log::info('[DriveWebhook] Processed', [
            'workspace_id'      => $workspace->id,
            'file_id'          => $change['file_id'],
            'file_name'        => $change['file_name'],
            'changes_processed' => $result['changes_processed'],
            'success'          => $result['success'],
        ]);

        if (!$result['success']) {
            Log::error('[DriveWebhook] ProcessChanges failed', [
                'workspace_id' => $workspace->id,
                'error'        => $result['error'] ?? 'Unknown error',
            ]);
        }

        return response()->json([
            'ok'       => true,
            'processed' => $result['changes_processed'] ?? 0,
        ]);
    }
}

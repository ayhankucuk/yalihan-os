<?php

namespace App\Services\Drive;

use App\Models\PortfolioDriveWorkspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DriveWebhookService
 *
 * Sprint 4.8: Workspace Integration Platform
 *
 * Manages Google Drive Push Notifications via the Drive API.
 *
 * Lifecycle:
 *   registerChannel()  → Creates a new push channel for a workspace folder
 *   renewChannel()    → Extends channel before expiry
 *   stopChannel()     → Stops and deletes a channel
 *   validateNotification() → Validates incoming webhook notification
 *   processChanges()  → Fetches actual file changes after webhook fires
 *
 * Google Drive Push Notification flow:
 *   1. POST /channels (register) → Google stores your callback URL + channel ID
 *   2. Google POSTs to your URL when watched resources change
 *   3. You GET /changes?pageToken=... to fetch actual changes
 *   4. Channel expires in ~7 days → renewChannel() before expiry
 */
class DriveWebhookService
{
    private const DRIVE_API = 'https://www.googleapis.com/drive/v3';

    // Google Drive push channel TTL: 7 days in seconds
    private const CHANNEL_TTL_SECONDS = 604800;

    // How many seconds before expiry to renew
    private const RENEW_BEFORE_SECONDS = 86400; // 1 day

    /**
     * Register a push notification channel for a workspace root folder.
     *
     * Google Drive will POST to the callback URL whenever files inside
     * the folder change (create, update, delete, rename).
     *
     * @param PortfolioDriveWorkspace $workspace
     * @param string $callbackUrl Full URL Google will POST to (e.g. https://yalihan.ai/api/drive/webhook)
     * @return array{success: bool, channel_id?: string, resource_id?: string, expiration?: string, error?: string}
     */
    public function registerChannel(PortfolioDriveWorkspace $workspace, string $callbackUrl): array
    {
        $token = app(DriveWorkspaceService::class)->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Drive token unavailable'];
        }

        $folderId = $workspace->drive_folder_id;
        if (!$folderId) {
            return ['success' => false, 'error' => 'Workspace has no drive_folder_id'];
        }

        try {
            $channelId = 'ych_' . bin2hex(random_bytes(16));
            $expiration = now()->addSeconds(self::CHANNEL_TTL_SECONDS)->toIso8601String();

            $response = Http::withToken($token)
                ->post(self::DRIVE_API . '/files/' . $folderId . '/watch', [
                    'id'       => $channelId, // @sab-ignore-context7 — Drive API field
                    'type'     => 'web_hook', // @sab-ignore-context7
                    'address'  => $callbackUrl,
                    'expiration' => (string) (time() + self::CHANNEL_TTL_SECONDS) * 1000, // ms timestamp
                    'token'    => $this->buildChannelToken($workspace->id),
                ]);

            if (!$response->successful()) {
                Log::error('[DriveWebhookService] Channel registration failed', [
                    'workspace_id' => $workspace->id,
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                ]);
                return ['success' => false, 'error' => 'Drive API error: ' . $response->body()];
            }

            $data = $response->json();

            // Persist channel metadata to workspace
            $this->persistChannel($workspace, [
                'channel_id'    => $channelId,
                'resource_id'   => $data['resourceId'] ?? null, // @sab-ignore-context7
                'expiration'    => $expiration,
                'webhook_url'   => $callbackUrl,
                'last_sync_at'  => null,
                'last_error'    => null,
            ]);

            Log::info('[DriveWebhookService] Channel registered', [
                'workspace_id' => $workspace->id,
                'channel_id'   => $channelId,
                'expiration'  => $expiration,
            ]);

            return [
                'success'      => true,
                'channel_id'   => $channelId,
                'resource_id'  => $data['resourceId'] ?? null,
                'expiration'  => $expiration,
            ];
        } catch (\Throwable $e) {
            Log::error('[DriveWebhookService] RegisterChannel exception', [
                'workspace_id' => $workspace->id,
                'error'        => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Renew an existing push channel before it expires.
     *
     * @param PortfolioDriveWorkspace $workspace
     * @return array{success: bool, channel_id?: string, expiration?: string, error?: string}
     */
    public function renewChannel(PortfolioDriveWorkspace $workspace): array
    {
        $channel = $this->getStoredChannel($workspace);
        if (!$channel) {
            // No channel → register a new one
            return $this->registerChannel($workspace, config('app.url') . '/api/drive/webhook');
        }

        $channelId    = $channel['channel_id'] ?? null;
        $resourceId   = $channel['resource_id'] ?? null;
        $webhookUrl   = $channel['webhook_url'] ?? config('app.url') . '/api/drive/webhook';
        $expiration   = $channel['expiration'] ?? null;

        if (!$channelId || !$resourceId) {
            return $this->registerChannel($workspace, $webhookUrl);
        }

        // Check if already expired
        if ($expiration && now()->parse($expiration)->isPast()) {
            Log::info('[DriveWebhookService] Channel expired, re-registering', [
                'workspace_id' => $workspace->id,
                'channel_id'   => $channelId,
            ]);
            return $this->registerChannel($workspace, $webhookUrl);
        }

        $token = app(DriveWorkspaceService::class)->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Drive token unavailable'];
        }

        try {
            $newExpiration = now()->addSeconds(self::CHANNEL_TTL_SECONDS)->toIso8601String();

            $response = Http::withToken($token)
                ->patch(self::DRIVE_API . '/files/' . $workspace->drive_folder_id . '/watch', [
                    'id'          => $channelId,
                    'type'        => 'web_hook',
                    'address'     => $webhookUrl,
                    'expiration'  => (string) (time() + self::CHANNEL_TTL_SECONDS) * 1000,
                    'resourceId'  => $resourceId,
                ]);

            if (!$response->successful()) {
                // Fallback: re-register on PATCH failure
                Log::warning('[DriveWebhookService] Channel renew PATCH failed, re-registering', [
                    'workspace_id' => $workspace->id,
                    'status'      => $response->status(),
                ]);
                return $this->registerChannel($workspace, $webhookUrl);
            }

            // Update stored expiration
            $channel['expiration'] = $newExpiration;
            $this->persistChannel($workspace, $channel);

            Log::info('[DriveWebhookService] Channel renewed', [
                'workspace_id' => $workspace->id,
                'channel_id'   => $channelId,
                'new_expiration' => $newExpiration,
            ]);

            return [
                'success'     => true,
                'channel_id'  => $channelId,
                'expiration'  => $newExpiration,
            ];
        } catch (\Throwable $e) {
            Log::error('[DriveWebhookService] RenewChannel exception', [
                'workspace_id' => $workspace->id,
                'error'        => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stop and delete a push notification channel.
     *
     * @param PortfolioDriveWorkspace $workspace
     * @return array{success: bool, error?: string}
     */
    public function stopChannel(PortfolioDriveWorkspace $workspace): array
    {
        $channel = $this->getStoredChannel($workspace);
        if (!$channel || !($channel['channel_id'] ?? null)) {
            return ['success' => true]; // Already stopped
        }

        $token     = app(DriveWorkspaceService::class)->getAccessToken();
        $channelId = $channel['channel_id'];

        if (!$token) {
            // Can't call Drive API — just clear local record
            $this->clearChannel($workspace);
            return ['success' => true];
        }

        try {
            $response = Http::withToken($token)
                ->delete(self::DRIVE_API . '/channels', [
                    'id'      => $channelId,
                    'resourceId' => $channel['resource_id'] ?? null,
                ]);

            Log::info('[DriveWebhookService] Channel stopped', [
                'workspace_id' => $workspace->id,
                'channel_id'   => $channelId,
                'api_status'   => $response->status(),
            ]);
        } catch (\Throwable $e) {
            // Non-fatal: log and clear local record anyway
            Log::warning('[DriveWebhookService] Channel stop API error', [
                'workspace_id' => $workspace->id,
                'error'        => $e->getMessage(),
            ]);
        }

        $this->clearChannel($workspace);
        return ['success' => true];
    }

    /**
     * Validate an incoming webhook notification from Google Drive.
     *
     * Google POSTs a notification body when watched resources change.
     * We verify the channel token matches our workspace.
     *
     * @param array $payload Raw POST body from Google
     * @param string|null $channelToken X-Goog-Channel-token header value
     * @param string|null $channelId X-Goog-Channel-id header value
     * @return array{valid: bool, workspace_id: int|null, error: string|null}
     */
    public function validateNotification(array $payload, ?string $channelToken, ?string $channelId): array
    {
        // Verify channel ID matches our stored channel
        if ($channelId) {
            $workspace = $this->resolveWorkspaceByChannelId($channelId);
            if (!$workspace) {
                Log::warning('[DriveWebhookService] Unknown channel ID', ['channel_id' => $channelId]);
                return ['valid' => false, 'workspace_id' => null, 'error' => 'Unknown channel'];
            }

            // Verify token matches (R11 Security Hardening)
            $expectedToken = $this->buildChannelToken($workspace->id);
            if (empty($channelToken) || $channelToken !== $expectedToken) {
                Log::warning('[DriveWebhookService] Channel token missing or mismatch', [
                    'workspace_id' => $workspace->id,
                ]);
                return ['valid' => false, 'workspace_id' => null, 'error' => 'Unauthorized'];
            }

            // Check if channel is expired
            $channel = $this->getStoredChannel($workspace);
            if ($channel && ($channel['expiration'] ?? null)) {
                if (now()->parse($channel['expiration'])->isPast()) {
                    // Auto-renew expired channel
                    $this->renewChannel($workspace);
                }
            }

            return ['valid' => true, 'workspace_id' => $workspace->id, 'error' => null];
        }

        // Fallback: no channel ID, treat as valid only in local/testing environment (R11 Security Hardening)
        if (app()->environment('local', 'testing')) {
            return ['valid' => true, 'workspace_id' => null, 'error' => null];
        }

        return ['valid' => false, 'workspace_id' => null, 'error' => 'Missing channel ID'];
    }

    /**
     * Process Drive changes for a workspace after a webhook fires.
     *
     * Fetches the current state of files in the workspace folder
     * and emits Hermes events for each meaningful change.
     *
     * @param PortfolioDriveWorkspace $workspace
     * @return array{success: bool, changes_processed: int, error?: string}
     */
    public function processChanges(PortfolioDriveWorkspace $workspace): array
    {
        $token = app(DriveWorkspaceService::class)->getAccessToken();
        if (!$token) {
            return ['success' => false, 'changes_processed' => 0, 'error' => 'Token unavailable'];
        }

        try {
            $result = app(DriveSyncService::class)->getChanges($workspace->drive_folder_id);
            $changes = $result['changes'] ?? [];

            $processed = 0;
            foreach ($changes as $change) {
                if ($this->shouldProcessChange($change)) {
                    $this->emitDriveEvent($workspace, $change);
                    $processed++;
                }
            }

            // Update last_sync_at
            $this->updateLastSync($workspace, $processed);

            Log::info('[DriveWebhookService] Changes processed', [
                'workspace_id'     => $workspace->id,
                'total_changes'    => count($changes),
                'processed'        => $processed,
            ]);

            return [
                'success'          => true,
                'changes_processed' => $processed,
            ];
        } catch (\Throwable $e) {
            Log::error('[DriveWebhookService] ProcessChanges exception', [
                'workspace_id' => $workspace->id,
                'error'        => $e->getMessage(),
            ]);
            $this->updateLastError($workspace, $e->getMessage());
            return ['success' => false, 'changes_processed' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check which workspaces need channel renewal (call from scheduler).
     *
     * @return array<int> Workspace IDs that need renewal
     */
    public function workspacesNeedingRenewal(): array
    {
        return PortfolioDriveWorkspace::query()
            ->where('workspace_status', PortfolioDriveWorkspace::STATUS_READY)
            ->whereNotNull('drive_webhook_channel_json')
            ->get()
            ->filter(fn($ws) => $this->needsRenewal($ws))
            ->pluck('id')
            ->toArray();
    }

    // ─── Private ─────────────────────────────────────────────────────────

    /**
     * Filter Drive file changes to only meaningful ones.
     */
    private function shouldProcessChange(array $change): bool
    {
        $mimeType = $change['mimeType'] ?? ''; // @sab-ignore-context7
        $name     = $change['name'] ?? '';

        // Skip folders themselves (only track files inside folders)
        if ($mimeType === 'application/vnd.google-apps.folder') {
            return false;
        }

        // Skip hidden / trashed
        if ($name === '' || str_starts_with($name, '.')) {
            return false;
        }

        return true;
    }

    /**
     * Emit a Hermes event for a Drive file change.
     */
    private function emitDriveEvent(PortfolioDriveWorkspace $workspace, array $change): void
    {
        $fileId    = $change['id'] ?? null;
        $fileName  = $change['name'] ?? 'Unknown';
        $mimeType  = $change['mimeType'] ?? 'unknown';
        $modifiedTime = $change['modifiedTime'] ?? now()->toIso8601String();
        $webViewLink = $change['webViewLink'] ?? null;

        $eventName = $this->mapMimeToEvent($mimeType);

        $payload = [
            'tenant_id'      => $workspace->tenant_id,
            'workspace_id'   => $workspace->id,
            'ilan_id'       => $workspace->ilan_id,
            'file_id'       => $fileId,
            'file_name'     => $fileName,
            'mime_type'     => $mimeType,
            'web_view_link' => $webViewLink,
            'modified_time' => $modifiedTime,
        ];

        // Persist file metadata
        $this->persistFileMetadata($workspace, $change);

        // Emit to Hermes event log
        $this->writeHermesEvent($eventName, $payload);

        Log::debug('[DriveWebhookService] Drive event emitted', [
            'workspace_id' => $workspace->id,
            'event'        => $eventName,
            'file_id'      => $fileId,
            'file_name'    => $fileName,
        ]);
    }

    private function mapMimeToEvent(string $mimeType): string
    {
        if (str_contains($mimeType, 'spreadsheet')) return 'drive.file.sheet_updated';
        if (str_contains($mimeType, 'document'))   return 'drive.file.doc_updated';
        if (str_contains($mimeType, 'presentation')) return 'drive.file.slide_updated';
        if (str_contains($mimeType, 'folder'))     return 'drive.folder.created';
        return 'drive.file.changed';
    }

    private function persistFileMetadata(PortfolioDriveWorkspace $workspace, array $change): void
    {
        $fileId    = $change['id'] ?? null;
        $fileName  = $change['name'] ?? null;
        $mimeType  = $change['mimeType'] ?? null;
        $webViewLink = $change['webViewLink'] ?? null;
        $modifiedTime = $change['modifiedTime'] ?? null;

        if (!$fileId || !$fileName) return;

        // Store in workspace metadata_json if present
        $meta = $workspace->metadata_json ?? [];
        $meta['drive_files'] = $meta['drive_files'] ?? [];

        // Upsert file in tracked files list
        $existing = collect($meta['drive_files'])->firstWhere('id', $fileId);
        $updated = array_merge($existing ?? [], [
            'id'            => $fileId,
            'name'          => $fileName,
            'mime_type'     => $mimeType,
            'web_view_link' => $webViewLink,
            'modified_time' => $modifiedTime,
            'last_synced_at' => now()->toIso8601String(),
        ]);

        $meta['drive_files'] = collect($meta['drive_files'])
            ->reject(fn($f) => ($f['id'] ?? null) === $fileId)
            ->push($updated)
            ->values()
            ->toArray();

        $workspace->updateQuietly(['metadata_json' => $meta]);
    }

    private function writeHermesEvent(string $eventName, array $payload): void
    {
        try {
            $log = new \App\Models\Hermes\HermesEventLog();
            $log->tenant_id   = $payload['tenant_id'] ?? null;
            $log->event_name  = $eventName;
            $log->event_class = \App\Events\Hermes\DriveWebhookEvent::class;
            $log->payload     = $payload;
            $log->occurred_at = now();
            $log->status      = 'processed';
            $log->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('[DriveWebhookService] Failed to write Hermes event', [
                'event'  => $eventName,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function buildChannelToken(int $workspaceId): string
    {
        $secret = config('ai-storage.storage.google_drive.webhook_secret', config('app.key'));
        return hash('sha256', $secret . $workspaceId);
    }

    private function resolveWorkspaceByChannelId(string $channelId): ?PortfolioDriveWorkspace
    {
        return PortfolioDriveWorkspace::query()
            ->withoutGlobalScopes()
            ->get()
            ->first(fn($ws) => (
                ($ws->drive_webhook_channel_json['channel_id'] ?? null) === $channelId
            ));
    }

    private function getStoredChannel(PortfolioDriveWorkspace $workspace): ?array
    {
        return $workspace->drive_webhook_channel_json;
    }

    private function persistChannel(PortfolioDriveWorkspace $workspace, array $channel): void
    {
        $workspace->updateQuietly(['drive_webhook_channel_json' => $channel]);
    }

    private function clearChannel(PortfolioDriveWorkspace $workspace): void
    {
        $workspace->updateQuietly(['drive_webhook_channel_json' => null]);
    }

    private function updateLastSync(PortfolioDriveWorkspace $workspace, int $processed): void
    {
        $channel = $this->getStoredChannel($workspace) ?? [];
        $channel['last_sync_at'] = now()->toIso8601String();
        $channel['last_sync_count'] = $processed;
        $channel['last_error'] = null;
        $this->persistChannel($workspace, $channel);
    }

    private function updateLastError(PortfolioDriveWorkspace $workspace, string $error): void
    {
        $channel = $this->getStoredChannel($workspace) ?? [];
        $channel['last_error'] = $error;
        $this->persistChannel($workspace, $channel);
    }

    private function needsRenewal(PortfolioDriveWorkspace $workspace): bool
    {
        $channel = $this->getStoredChannel($workspace);
        if (!$channel || !($channel['expiration'] ?? null)) {
            return false;
        }

        $expiry = now()->parse($channel['expiration']);
        return $expiry->isPast()
            || $expiry->diffInSeconds(now()) < self::RENEW_BEFORE_SECONDS;
    }
}

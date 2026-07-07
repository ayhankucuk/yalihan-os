<?php

namespace App\Services\Drive;

use App\Models\PortfolioDriveWorkspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DriveSyncService
 *
 * Sprint 4.8: Workspace Integration Platform
 *
 * Bidirectional sync between Workspace (DB) and Google Drive.
 *
 * OUTBOUND (CMS → Drive):
 *   uploadToSubfolder() — uploads a file into a workspace subfolder
 *
 * INBOUND (Drive → CMS — called by DriveWebhookController):
 *   processWebhookPayload() — normalizes Drive webhook POST body
 *   getChanges() — calls Drive changes.list for Drive folder tracking
 */
class DriveSyncService
{
    private const DRIVE_API = 'https://www.googleapis.com/drive/v3';

    // ─── Outbound ──────────────────────────────────────────────────────────────

    /**
     * Upload a file to a workspace subfolder.
     */
    public function uploadToSubfolder(
        PortfolioDriveWorkspace $workspace,
        string $subfolderKey,
        string $fileName,
        string $mimeType,
        string $content,
    ): array {
        $token = $this->token();
        if (!$token) {
            return $this->fail($fileName, 'Drive token unavailable');
        }

        $folderId = $this->folderId($workspace, $subfolderKey);
        if (!$folderId) {
            return $this->fail($fileName, "Folder '{$subfolderKey}' not found in workspace");
        }

        try {
            $result = $this->uploadMultipart($token, $fileName, $mimeType, $content, $folderId);
            if (!$result['success']) {
                return $this->fail($fileName, $result['error'] ?? 'Upload failed');
            }

            $driveId  = $result['drive_id'] ?? null;
            $driveUrl = $driveId ? "https://drive.google.com/file/d/{$driveId}/view" : null;

            Log::info('[DriveSyncService] Uploaded', [
                'workspace_id' => $workspace->id,
                'file'       => $fileName,
                'folder'     => $subfolderKey,
                'drive_id'   => $driveId,
            ]);

            return [
                'drive_id'  => $driveId,
                'drive_url' => $driveUrl,
                'success'   => true,
                'error'     => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[DriveSyncService] Upload exception', [
                'workspace_id' => $workspace->id,
                'file'        => $fileName,
                'error'        => $e->getMessage(),
            ]);
            return $this->fail($fileName, $e->getMessage());
        }
    }

    // ─── Inbound ─────────────────────────────────────────────────────────────

    /**
     * Normalize a Google Drive webhook POST body into a clean change record.
     *
     * Google Drive sends via Cloud Pub/Sub:
     *   POST { "message": { "data": "<base64-encoded JSON>", ... } }
     * We decode the base64, extract fileId + type + name.
     *
     * @return array{workspace_id: int|null, change_type: string, file_id: string|null, file_name: string, timestamp: string}
     */
    public function processWebhookPayload(array $payload): array
    {
        $token = $this->token();

        // Decode Google Cloud Pub/Sub envelope
        $message = $payload['message'] ?? $payload;
        $rawData = $message['data'] ?? null;

        if (is_string($rawData)) {
            $decoded = base64_decode($rawData, true);
            $change = $decoded !== false ? (json_decode($decoded, true) ?? []) : [];
        } else {
            $change = $message; // Already decoded
        }

        $changeType = $change['changeType'] ?? 'change';
        $fileId     = $change['fileId'] ?? null;
        $fileName   = $change['fileName'] ?? $change['name'] ?? 'Unknown';
        $fileUrl    = $change['webViewLink'] ?? null;

        // Resolve workspace
        $workspaceId = null;
        if ($fileId && $token) {
            $workspaceId = $this->resolveWorkspaceForFile($fileId, $token);
        }

        return [
            'workspace_id' => $workspaceId,
            'change_type' => $changeType,
            'file_id'     => $fileId,
            'file_name'   => $fileName,
            'file_url'   => $fileUrl,
            'timestamp'  => now()->toIso8601String(),
        ];
    }

    /**
     * Fetch Drive changes for a root folder using saved page token.
     *
     * @return array{changes: array, next_token: string|null}
     */
    public function getChanges(string $rootFolderId): array
    {
        $token = $this->token();
        if (!$token) {
            return ['changes' => [], 'next_token' => null];
        }

        $savedToken = config('ai-storage.storage.google_drive.changes_token', '');
        if (empty($savedToken)) {
            Log::debug('[DriveSyncService] No changes token configured');
            return ['changes' => [], 'next_token' => null];
        }

        try {
            $response = Http::withToken($token)
                ->get(self::DRIVE_API . '/files', [
                    "q"        => "'{$rootFolderId}' in parents and trashed=false", // @sab-ignore-context7
                    'fields'   => 'files(id,name,mimeType,modifiedTime,webViewLink)',
                    'pageToken' => $savedToken,
                    'pageSize'  => 100,
                ]);

            if (!$response->successful()) {
                Log::warning('[DriveSyncService] Drive list failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['changes' => [], 'next_token' => null];
            }

            $data      = $response->json();
            $nextToken = $data['nextPageToken'] ?? null;

            return [
                'changes'   => $data['files'] ?? [],
                'next_token' => $nextToken,
            ];
        } catch (\Throwable $e) {
            Log::error('[DriveSyncService] getChanges exception', ['error' => $e->getMessage()]);
            return ['changes' => [], 'next_token' => null];
        }
    }

    // ─── Private ────────────────────────────────────────────────────────────

    private function folderId(PortfolioDriveWorkspace $workspace, string $key): ?string
    {
        $folders = collect($workspace->subfolders_json ?? []);
        $found   = $folders->firstWhere('name', $key);
        return $found['id'] ?? null;
    }

    private function resolveWorkspaceForFile(string $driveFileId, string $token): ?int
    {
        // Get file parents chain
        $folderChain = $this->getFolderChain($driveFileId, $token);
        foreach ($folderChain as $folderId) {
            $ws = PortfolioDriveWorkspace::query()
                ->where('drive_folder_id', $folderId)
                ->first();
            if ($ws) {
                return $ws->id;
            }
        }
        return null;
    }

    /** Walk up the Drive folder tree to find a workspace root */
    private function getFolderChain(string $fileId, string $token): array
    {
        $chain = [];
        $currentId = $fileId;

        for ($i = 0; $i < 10; $i++) { // max 10 levels deep
            try {
                $response = Http::withToken($token)
                    ->get(self::DRIVE_API . '/files/' . $currentId, [
                        'fields' => 'id,parents,name',
                    ]);
                if (!$response->successful()) {
                    break;
                }
                $data = $response->json();
                $parentId = $data['parents'][0] ?? null;
                if (!$parentId) {
                    break;
                }
                $chain[] = $parentId;
                $currentId = $parentId;
            } catch (\Throwable) {
                break;
            }
        }

        return $chain;
    }

    private function uploadMultipart(
        string $token,
        string $name,
        string $mimeType,
        string $content,
        string $parentId,
    ): array {
        $boundary = 'boundary_' . bin2hex(random_bytes(8));
        $meta = json_encode([
            'name'    => $name,
            'parents' => [$parentId],
        ]);

        $body = implode("\r\n", [
            "--{$boundary}\r\n"
                . "Content-Type: application/json\r\n\r\n"
                . $meta,
            "--{$boundary}\r\n"
                . "Content-Type: {$mimeType}\r\n\r\n"
                . $content,
            "--{$boundary}--",
        ]);

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type'  => "multipart/related; boundary=\"{$boundary}\"",
                    'Content-Length' => (string) strlen($body),
                ])
                ->withBody($body, "multipart/related; boundary=\"{$boundary}\"")
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

            if ($response->successful()) {
                $data = $response->json();
                return ['success' => true, 'drive_id' => $data['id'] ?? null, 'error' => null];
            }

            return ['success' => false, 'drive_id' => null, 'error' => $response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'drive_id' => null, 'error' => $e->getMessage()];
        }
    }

    private function token(): ?string
    {
        return app(DriveWorkspaceService::class)->getAccessToken();
    }

    private function fail(string $fileName, string $reason): array
    {
        return ['drive_id' => null, 'drive_url' => null, 'success' => false, 'error' => "{$fileName}: {$reason}"];
    }
}

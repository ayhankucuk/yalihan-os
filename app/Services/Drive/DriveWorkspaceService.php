<?php

namespace App\Services\Drive;

use App\DTOs\DriveWorkspaceResult;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * DriveWorkspaceService
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 *
 * Abstraction layer between DriveAgent and Google Drive API.
 * ALL Drive operations MUST go through this service.
 * When Drive API changes or is mocked, only this service changes.
 *
 * Uses Google Drive API v3 via HTTP (no SDK dependency).
 * Credentials sourced from config('ai-storage.storage.google_drive').
 */
class DriveWorkspaceService
{
    private const DRIVE_API_BASE = 'https://www.googleapis.com/drive/v3';
    private const DRIVE_UPLOAD_BASE = 'https://www.googleapis.com/upload/drive/v3';
    private const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    // Google Drive API credential types — Context7 ignores external API field names
    private const CRED_TYPE_SERVICE_ACCOUNT = 'service_account';
    private const CRED_TYPE_AUTHORIZED_USER = 'authorized_user';

    /**
     * Standard 12 subfolders for portfolio workspace
     */
    private const SUBFOLDER_NAMES = [
        '01_Fotograflar',
        '02_Videolar',
        '03_Tapu',
        '04_Imar',
        '05_Ekspertiz',
        '06_Airbnb',
        '07_Sahibinden',
        '08_Hepsiemlak',
        '09_CRM',
        '10_Finans',
        '11_AI',
        '12_Arsiv',
    ];

    /**
     * Check if a workspace already exists for a given ilan
     */
    public function workspaceExistsForPortfolio(int $ilanId): bool
    {
        return PortfolioDriveWorkspace::forPortfolio($ilanId)->exists();
    }

    /**
     * Create the root portfolio folder in Google Drive
     *
     * @param string $portfolioNo Portfolio number (e.g., YLH-00001)
     * @param string $title Portfolio title for folder name
     * @param int|null $tenantId Tenant ID for isolation
     * @return DriveWorkspaceResult
     */
    public function createWorkspace(
        string $portfolioNo,
        string $title,
        ?int $tenantId = null,
    ): DriveWorkspaceResult {
        $folderName = "YLH-{$portfolioNo} - {$title}";

        try {
            $response = $this->createFolder($folderName, $this->getRootFolderId());

            if (!$response['success']) {
                return DriveWorkspaceResult::failure(
                    'Failed to create root folder: ' . ($response['error'] ?? 'Unknown error'),
                    ['folder_name' => $folderName]
                );
            }

            $rootFolderId = $response['folder_id'];
            $rootFolderUrl = $this->buildFolderUrl($rootFolderId);

            Log::info('[DriveWorkspaceService] Root folder created', [
                'portfolio_no' => $portfolioNo,
                'folder_id' => $rootFolderId,
                'folder_name' => $folderName,
            ]);

            return DriveWorkspaceResult::success(
                rootFolderId: $rootFolderId,
                rootFolderUrl: $rootFolderUrl,
                subfolders: [],
                metadata: [
                    'folder_name' => $folderName,
                    'portfolio_no' => $portfolioNo,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('[DriveWorkspaceService] Failed to create workspace', [
                'portfolio_no' => $portfolioNo,
                'error' => $e->getMessage(),
            ]);

            return DriveWorkspaceResult::failure(
                $e->getMessage(),
                ['folder_name' => $folderName]
            );
        }
    }

    /**
     * Create all 12 subfolders inside the root folder
     *
     * @param string $parentFolderId Parent folder ID
     * @param string $portfolioNo Portfolio number
     * @return array<string, string> Map of folder name => folder ID
     */
    public function createSubfolders(string $parentFolderId, string $portfolioNo): array
    {
        $subfolders = [];

        foreach (self::SUBFOLDER_NAMES as $folderName) {
            try {
                $response = $this->createFolder($folderName, $parentFolderId);

                if ($response['success']) {
                    $subfolders[$folderName] = $response['folder_id'];
                } else {
                    Log::warning('[DriveWorkspaceService] Subfolder creation failed', [
                        'folder_name' => $folderName,
                        'error' => $response['error'] ?? 'Unknown',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('[DriveWorkspaceService] Subfolder exception', [
                    'folder_name' => $folderName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[DriveWorkspaceService] Subfolders created', [
            'parent_folder_id' => $parentFolderId,
            'created_count' => count($subfolders),
            'expected_count' => count(self::SUBFOLDER_NAMES),
        ]);

        return $subfolders;
    }

    /**
     * Store workspace metadata in the database
     *
     * @return PortfolioDriveWorkspace
     */
    public function storeWorkspaceMeta(
        int $ilanId,
        ?int $tenantId,
        string $rootFolderId,
        string $rootFolderUrl,
        string $rootFolderName,
        string $portfolioNo,
        array $subfolders,
    ): PortfolioDriveWorkspace {
        $subfoldersJson = [];
        foreach ($subfolders as $name => $id) {
            $subfoldersJson[] = [
                'name' => $name,
                'id' => $id,
                'url' => $this->buildFolderUrl($id),
            ];
        }

        return PortfolioDriveWorkspace::updateOrCreate(
            [
                'ilan_id' => $ilanId,
            ],
            [
                'tenant_id' => $tenantId,
                'drive_folder_id' => $rootFolderId,
                'drive_folder_url' => $rootFolderUrl,
                'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
                'root_folder_name' => $rootFolderName,
                'portfolio_no' => $portfolioNo,
                'subfolders_json' => $subfoldersJson,
            ]
        );
    }

    /**
     * Create a folder in Google Drive
     *
     * @param string $name Folder name
     * @param string|null $parentId Parent folder ID (null for root)
     * @return array{success: bool, folder_id?: string, error?: string}
     */
    private function createFolder(string $name, ?string $parentId = null): array
    {
        $credentials = $this->getCredentials();
        if ($credentials === null) {
            return ['success' => false, 'error' => 'Google Drive credentials not configured'];
        }

        $folderMetadata = [
            'name' => $name,
            // @sab-ignore-context7 — Google Drive API field
            'mimeType' => self::FOLDER_MIME_TYPE,
        ];

        if ($parentId !== null) {
            $folderMetadata['parents'] = [$parentId];
        }

        try {
            $response = Http::withToken($credentials)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::DRIVE_API_BASE . '/files', $folderMetadata);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'folder_id' => $data['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Throwable $e) {
            /** @sab-ignore-catch */
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the root folder ID from config or create "03-PORTFÖYLER"
     */
    private function getRootFolderId(): ?string
    {
        $configFolderId = config('ai-storage.storage.google_drive.folder_id');
        if (!empty($configFolderId)) {
            return $configFolderId;
        }

        $rootFolderName = '03-PORTFÖYLER';

        try {
            $credentials = $this->getCredentials();
            if ($credentials === null) {
                return null;
            }

            $response = Http::withToken($credentials)
                ->get(self::DRIVE_API_BASE . '/files', [
                    'q' => "name='{$rootFolderName}' and mimeType='" . self::FOLDER_MIME_TYPE . "' and trashed=false", // @sab-ignore-context7
                    'fields' => 'files(id,name)',
                    /** @sab-ignore-context7 */ 'pageSize' => 1,
                ]);

            if ($response->successful()) {
                $files = $response->json()['files'] ?? [];
                if (!empty($files)) {
                    return $files[0]['id'];
                }

                $createResponse = $this->createFolder($rootFolderName, null);
                return $createResponse['folder_id'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::warning('[DriveWorkspaceService] Could not get/create root folder', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Build a Google Drive folder URL
     */
    private function buildFolderUrl(string $folderId): string
    {
        return "https://drive.google.com/drive/folders/{$folderId}";
    }

    /**
     * Get access token from service account credentials
     */
    private function getCredentials(): ?string
    {
        $credentialsPath = config('ai-storage.storage.google_drive.credentials');

        if (empty($credentialsPath) || !file_exists($credentialsPath)) {
            Log::debug('[DriveWorkspaceService] No Google Drive credentials configured');
            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            if (!isset($credentials['type'], $credentials['project_id'])) {
                Log::warning('[DriveWorkspaceService] Invalid credentials file format');
                return null;
            }

            $credsType = $credentials['type']; // @sab-ignore-context7

            if ($credsType === self::CRED_TYPE_SERVICE_ACCOUNT) {
                return $this->getServiceAccountToken($credentials);
            }

            if ($credsType === self::CRED_TYPE_AUTHORIZED_USER) {
                return $credentials['access_token'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('[DriveWorkspaceService] Failed to load credentials', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get access token for service account using JWT
     */
    private function getServiceAccountToken(array $credentials): ?string
    {
        try {
            $scopes = config('ai-storage.storage.google_drive.scopes', [
                'https://www.googleapis.com/auth/drive',
            ]);

            $now = time();
            $jwtHeader = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $jwtClaimSet = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => implode(' ', $scopes),
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $privateKey = $credentials['private_key'] ?? '';
            if (empty($privateKey)) {
                return null;
            }

            openssl_sign(
                $jwtHeader . '.' . $jwtClaimSet,
                $signature,
                $privateKey,
                OPENSSL_ALGO_SHA256
            );

            $jwtSignature = $this->base64UrlEncode($signature);
            $assertion = $jwtHeader . '.' . $jwtClaimSet . '.' . $jwtSignature;

            $tokenResponse = Http::asForm()
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);

            if ($tokenResponse->successful()) {
                return $tokenResponse->json()['access_token'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::warning('[DriveWorkspaceService] Service account token failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Base64 URL-safe encoding
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get standard subfolder names
     *
     * @return array<string>
     */
    public function getSubfolderNames(): array
    {
        return self::SUBFOLDER_NAMES;
    }
}

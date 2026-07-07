<?php

namespace App\Services\Drive;

use App\Models\PortfolioDriveWorkspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DriveTemplateService
 *
 * Sprint 4.8: Workspace Integration Platform
 *
 * Creates Google Docs from pre-configured template files inside each workspace subfolder.
 * Templates are COPY'd (not MOVED) from source so the master templates stay pristine.
 *
 * Templates created per workspace provisioning:
 *   - portfoy_karti     → 01_Fotograflar
 *   - ai_summary       → 11_AI
 *   - yetki_belgesi    → 03_Tapu
 *   - ekspertiz_notlari → 05_Ekspertiz
 *   - crm_karti       → 09_CRM
 *
 * Template source IDs are configured in: config('ai-storage.storage.google_drive.templates.{key}')
 */
class DriveTemplateService
{
    private const DOCS_API = 'https://www.googleapis.com/drive/v3';

    /** @var array<string, array{name: string, folder: string, mime: string} */
    public const TEMPLATES = [
        'portfoy_karti'     => ['name' => 'Portföy Kartı',     'folder' => '01_Fotograflar',  'mime' => 'application/vnd.google-apps.document'],
        'ai_summary'        => ['name' => 'AI Summary',          'folder' => '11_AI',           'mime' => 'application/vnd.google-apps.document'],
        'yetki_belgesi'    => ['name' => 'Yetki Belgesi',      'folder' => '03_Tapu',           'mime' => 'application/vnd.google-apps.document'],
        'ekspertiz_notlari' => ['name' => 'Ekspertiz Notları',  'folder' => '05_Ekspertiz',      'mime' => 'application/vnd.google-apps.document'],
        'crm_karti'        => ['name' => 'CRM Kartı',            'folder' => '09_CRM',            'mime' => 'application/vnd.google-apps.document'],
    ];

    /**
     * Copy all configured templates into a workspace's Drive subfolders.
     *
     * @return array<string, array{name, doc_id, doc_url, success, error}>
     */
    public function createAllTemplates(PortfolioDriveWorkspace $workspace): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error('[DriveTemplateService] No Drive token — templates skipped');
            return $this->failedResults('Google Drive credentials not configured');
        }

        $subfolders = collect($workspace->subfolders_json ?? [])->keyBy('name');
        $results = [];

        foreach (self::TEMPLATES as $key => $def) {
            $folder = $subfolders->get($def['folder']);

            if (!$folder || empty($folder['id'])) {
                $results[$key] = $this->result($def['name'], null, null, false,
                    "Folder {$def['folder']} not yet created in workspace");
                Log::warning('[DriveTemplateService] Skipping template — folder missing', [
                    'workspace_id' => $workspace->id,
                    'template'    => $key,
                    'folder'      => $def['folder'],
                ]);
                continue;
            }

            $results[$key] = $this->copyTemplate(
                $token, $workspace, $key, $def, $folder['id']
            );
        }

        Log::info('[DriveTemplateService] Templates processed', [
            'workspace_id' => $workspace->id,
            'total'      => count($results),
            'succeeded'  => count(array_filter($results, fn($r) => $r['success'])),
        ]);

        return $results;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function copyTemplate(
        string $token,
        PortfolioDriveWorkspace $workspace,
        string $key,
        array $def,
        string $folderId,
    ): array {
        $sourceId = $this->resolveTemplateId($key);
        if (!$sourceId) {
            return $this->result($def['name'], null, null, false,
                "Template source ID not configured for '{$key}'");
        }

        $title = $this->buildTitle($def['name'], $workspace);

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::DOCS_API . '/files/' . $sourceId . '/copy', [
                    'name'    => $title,
                    'parents' => [$folderId],
                ]);

            if (!$response->successful()) {
                $body = $response->body();
                Log::warning('[DriveTemplateService] Drive API copy failed', [
                    'template' => $key,
                    'workspace_id' => $workspace->id,
                    'status' => $response->status(),
                    'error' => $body,
                ]);
                return $this->result($def['name'], null, null, false, $body);
            }

            $data   = $response->json();
            $docId  = $data['id'] ?? null;
            $docUrl = $docId
                ? "https://docs.google.com/document/d/{$docId}/edit"
                : null;

            Log::info('[DriveTemplateService] Template copied', [
                'template'    => $key,
                'workspace_id' => $workspace->id,
                'doc_id'      => $docId,
            ]);

            return $this->result($def['name'], $docId, $docUrl, true, null);
        } catch (\Throwable $e) {
            Log::error('[DriveTemplateService] Exception', [
                'template' => $key,
                'error'    => $e->getMessage(),
            ]);
            return $this->result($def['name'], null, null, false, $e->getMessage());
        }
    }

    private function resolveTemplateId(string $key): ?string
    {
        $id = config("ai-storage.storage.google_drive.templates.{$key}");
        return is_string($id) && $id !== '' ? $id : null;
    }

    private function buildTitle(string $templateName, PortfolioDriveWorkspace $ws): string
    {
        $no = $ws->portfolio_no ?? '—';
        $root = $ws->root_folder_name ?? '';
        return "{$templateName} — {$no}" . ($root ? " {$root}" : '');
    }

    private function result(
        string $name,
        ?string $docId,
        ?string $docUrl,
        bool $success,
        ?string $error,
    ): array {
        return [
            'name'    => $name,
            'doc_id'  => $docId,
            'doc_url' => $docUrl,
            'success' => $success,
            'error'   => $error,
        ];
    }

    /** All templates failed with the same reason */
    private function failedResults(string $reason): array
    {
        $out = [];
        foreach (self::TEMPLATES as $key => $def) {
            $out[$key] = $this->result($def['name'], null, null, false, $reason);
        }
        return $out;
    }

    private function getAccessToken(): ?string
    {
        return app(DriveWorkspaceService::class)->getAccessToken();
    }
}

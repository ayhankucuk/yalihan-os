<?php

declare(strict_types=1);

namespace Tests\Feature\Drive;

use App\Models\PortfolioDriveWorkspace;
use App\Models\SaaS\Tenant;
use App\Models\Ilan;
use App\Models\Hermes\HermesEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriveWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private PortfolioDriveWorkspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Drive Security Tenant',
            'domain' => 'drive-security.test',
            'aktiflik_durumu' => 1,
        ]);

        $ilan = Ilan::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Drive Security Listing',
            'yayin_durumu' => 'aktif',
            'aktiflik_durumu' => 1,
        ]);

        $this->workspace = PortfolioDriveWorkspace::create([
            'tenant_id' => $this->tenant->id,
            'ilan_id' => $ilan->id,
            'drive_folder_id' => 'folder_test_123',
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
            'drive_webhook_channel_json' => [
                'channel_id' => 'chan-test-999',
                'resource_id' => 'res-test-999',
                'expiration' => now()->addDay()->toIso8601String(),
            ]
        ]);
    }

    /** @test */
    public function it_rejects_drive_webhook_when_channel_token_is_missing(): void
    {
        $response = $this->postJson('/api/v1/webhook/drive', [
            'message' => [
                'data' => base64_encode(json_encode(['id' => 'file_123', 'name' => 'document.docx', 'mimeType' => 'application/vnd.google-apps.document'])),
            ]
        ], [
            'X-Goog-Channel-id' => 'chan-test-999',
            // Missing X-Goog-Channel-token header
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['reason' => 'Unauthorized']);
    }

    /** @test */
    public function it_rejects_drive_webhook_when_channel_token_mismatches(): void
    {
        $response = $this->postJson('/api/v1/webhook/drive', [
            'message' => [
                'data' => base64_encode(json_encode(['id' => 'file_123', 'name' => 'document.docx', 'mimeType' => 'application/vnd.google-apps.document'])),
            ]
        ], [
            'X-Goog-Channel-id' => 'chan-test-999',
            'X-Goog-Channel-token' => 'invalid-token-value',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['reason' => 'Unauthorized']);
    }

    /** @test */
    public function it_propagates_tenant_id_to_hermes_event_log_on_successful_webhook(): void
    {
        $this->withoutExceptionHandling();
        $secret = config('ai-storage.storage.google_drive.webhook_secret', config('app.key'));
        $expectedToken = hash('sha256', $secret . $this->workspace->id);

        // Mock DriveWorkspaceService to return dummy token
        $mockDriveService = $this->mock(\App\Services\Drive\DriveWorkspaceService::class);
        $mockDriveService->shouldReceive('getAccessToken')
            ->andReturn('dummy-token-abc');

        // Mock DriveSyncService to return custom changes so it emits an event
        $mockSyncService = $this->mock(\App\Services\Drive\DriveSyncService::class);
        
        // Mock webhook payload parsing
        $mockSyncService->shouldReceive('processWebhookPayload')
            ->once()
            ->andReturn(['file_id' => 'file_123', 'file_name' => 'doc.gdoc']);
            
        // Mock getChanges returning the files
        $mockSyncService->shouldReceive('getChanges')
            ->once()
            ->with('folder_test_123')
            ->andReturn([
                'changes' => [
                    [
                        'id' => 'file_123',
                        'name' => 'doc.gdoc',
                        'mimeType' => 'application/vnd.google-apps.document',
                        'webViewLink' => 'https://drive.google.com/doc',
                        'modifiedTime' => now()->toIso8601String(),
                    ]
                ]
            ]);

        $response = $this->postJson('/api/v1/webhook/drive', [
            'message' => [
                'data' => base64_encode(json_encode(['id' => 'file_123', 'name' => 'doc.gdoc', 'mimeType' => 'application/vnd.google-apps.document'])),
            ]
        ], [
            'X-Goog-Channel-id' => 'chan-test-999',
            'X-Goog-Channel-token' => $expectedToken,
        ]);

        $response->assertStatus(200);

        // Assert HermesEventLog contains the tenant_id from workspace
        $eventLog = HermesEventLog::where('event_name', 'drive.file.doc_updated')->first();
        $this->assertNotNull($eventLog);
        $this->assertEquals($this->tenant->id, $eventLog->tenant_id);
    }
}

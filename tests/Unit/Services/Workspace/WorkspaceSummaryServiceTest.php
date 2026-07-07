<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Workspace;

use App\Models\Belge;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Workspace\WorkspaceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class WorkspaceSummaryServiceTest
 *
 * Sprint 6.1-E04: Integration tests for Workspace readiness
 *
 * @package Tests\Unit\Services\Workspace
 */
class WorkspaceSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private WorkspaceSummaryService $service;
    private Tenant $tenant;
    private User $user;
    private Ilan $ilan;
    private PortfolioDriveWorkspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(WorkspaceSummaryService::class);

        $this->tenant = Tenant::create([
            'name' => 'Integration Test Tenant',
            'domain' => 'integration.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->ilan = Ilan::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Bodrum Villa',
            'aciklama' => 'Description of villa',
            'fiyat' => 5000000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'aktif',
            'aktiflik_durumu' => 1,
        ]);

        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->workspace = PortfolioDriveWorkspace::create([
            'tenant_id' => $this->tenant->id,
            'ilan_id' => $this->ilan->id,
            'drive_folder_id' => 'folder_abc_123',
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);
        $this->workspace->markWorkspaceCreated();
    }

    public function test_summary_includes_readiness_payload(): void
    {
        $summary = $this->service->getSummary($this->workspace);

        $this->assertArrayHasKey('readiness', $summary);
        $readiness = $summary['readiness'];

        $this->assertNotNull($readiness);
        $this->assertEquals('satilik', $readiness['intent']);
        $this->assertEquals('tpl_satilik', $readiness['template_id']);
        $this->assertArrayHasKey('readiness_score', $readiness);
        $this->assertArrayHasKey('readiness_status', $readiness);
        $this->assertArrayHasKey('missing_fields', $readiness);
        $this->assertArrayHasKey('missing_documents', $readiness);
        $this->assertArrayHasKey('missing_ai_hooks', $readiness);
        $this->assertArrayHasKey('summary', $readiness);
    }

    public function test_readiness_score_updates_when_fields_are_filled(): void
    {
        // 1. Initially, with few fields filled (baslik, aciklama, fiyat, para_birimi)
        // missing: kapak_resmi, il, ilce, brut_metrekare, oda_sayisi, tapusu_var
        $summary1 = $this->service->getSummary($this->workspace);
        $score1 = $summary1['readiness']['readiness_score'];
        $status1 = $summary1['readiness']['readiness_status'];

        $this->assertEquals('incomplete', $status1);

        // 2. Fill more required fields (e.g. brut_m2, oda_sayisi)
        $this->ilan->update([
            'brut_m2' => 200,
            'oda_sayisi' => '4+1',
        ]);

        $summary2 = $this->service->getSummary($this->workspace);
        $score2 = $summary2['readiness']['readiness_score'];

        $this->assertGreaterThan($score1, $score2);
    }

    public function test_readiness_score_grows_with_uploaded_documents(): void
    {
        // Initially, no documents uploaded
        $summary1 = $this->service->getSummary($this->workspace);
        $score1 = $summary1['readiness']['readiness_score'];

        // Upload tapu document
        Belge::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'ilan_id' => $this->ilan->id,
            'baslik' => 'Tapu Kaydı',
            'dosya_yolu' => '/path/to/tapu.pdf',
            'dosya_tipi' => 'pdf',
            'belge_turu' => 'tapu_fotokopisi',
            'boyut_kb' => 1024,
        ]);

        $summary2 = $this->service->getSummary($this->workspace);
        $score2 = $summary2['readiness']['readiness_score'];

        $this->assertGreaterThan($score1, $score2);
        $this->assertNotContains('tapu_fotokopisi', $summary2['readiness']['missing_documents']);
    }

    public function test_readiness_score_grows_with_completed_ai_agents(): void
    {
        // Initially, no AI agents complete
        $summary1 = $this->service->getSummary($this->workspace);
        $score1 = $summary1['readiness']['readiness_score'];

        // Mark description agent complete (maps to generate_title & generate_description, which are required for scoring)
        $this->workspace->markAiAgentComplete('description_agent', ['status' => 'success']);

        $summary2 = $this->service->getSummary($this->workspace);
        $score2 = $summary2['readiness']['readiness_score'];

        $this->assertGreaterThan($score1, $score2);
        $this->assertNotContains('generate_title', $summary2['readiness']['missing_ai_hooks']);
        $this->assertNotContains('generate_description', $summary2['readiness']['missing_ai_hooks']);
    }
}

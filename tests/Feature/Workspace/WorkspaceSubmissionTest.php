<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Models\Belge;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PropertyWorkspace;
use App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate;
use App\Support\AgentContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class WorkspaceSubmissionTest
 *
 * Sprint 6.1-E06: Feature tests for Workspace form submission and lifecycle transitions.
 *
 * @package Tests\Feature\Workspace
 */
class WorkspaceSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Ilan $ilan;
    private PortfolioDriveWorkspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        AgentContext::reset();

        $this->tenant = Tenant::create([
            'name' => 'Submission Test Tenant',
            'domain' => 'submission.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_id' => 1, // Admin role
            'email_verified_at' => now(),
        ]);

        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user->assignRole($adminRole);

        $this->actingAs($this->user);

        $il = \App\Models\Il::firstOrCreate(
            ['id' => 48],
            ['plaka_kodu' => 48, 'il_adi' => 'Bodrum', 'aktiflik_durumu' => 1]
        );
        $ilce = \App\Models\Ilce::firstOrCreate(
            ['id' => 1, 'il_id' => 48],
            ['ilce_adi' => 'Merkez', 'aktiflik_durumu' => 1]
        );

        $this->ilan = Ilan::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Bodrum Villa',
            'aciklama' => 'Description of villa',
            'fiyat' => 5000000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'aktif',
            'aktiflik_durumu' => 1,
            'il_id' => 48,
            'ilce_id' => 1,
        ]);

        $photoData = [
            'ilan_id' => $this->ilan->id,
            'dosya_adi' => 'yes_image.jpg',
            'dosya_yolu' => 'yes_image.jpg',
            'display_order' => 1,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('ilan_fotograflari', 'kapak_fotografi')) {
            $photoData['kapak_fotografi'] = true;
        } else {
            $photoData['kapak_mi'] = true;
        }
        \Illuminate\Support\Facades\DB::table('ilan_fotograflari')->insert($photoData);

        $this->workspace = PortfolioDriveWorkspace::create([
            'tenant_id' => $this->tenant->id,
            'ilan_id' => $this->ilan->id,
            'drive_folder_id' => 'folder_abc_123',
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);
        $this->workspace->markWorkspaceCreated();
    }

    /**
     * Test validation failure on required template fields.
     */
    public function test_save_validation_fails_on_missing_required_fields(): void
    {
        $this->actingAs($this->user);

        // Submit empty baslik and aciklama which are required in base fields
        $response = $this->postJson(route('admin.workspace.save', ['id' => $this->workspace->id]), [
            'data' => [
                'baslik' => '',
                'aciklama' => '',
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => [
                'baslik',
                'aciklama',
            ]
        ]);
    }

    /**
     * Test successful field persistence and keeping state as draft if not ready.
     */
    public function test_save_persists_fields_and_keeps_draft_if_incomplete(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('admin.workspace.save', ['id' => $this->workspace->id]), [
            'data' => [
                'baslik' => 'Bodrum Luxury Mansion',
                'aciklama' => 'Updated description text',
                'fiyat' => 8000000,
                'para_birimi' => 'EUR',
                'brut_metrekare' => 450,
            ]
        ]);

        file_put_contents(base_path('response_dump.json'), $response->getContent());
        $response->assertStatus(200);
        $response->assertJsonPath('readiness.readiness_status', 'incomplete');
        $response->assertJsonPath('lifecycle_state', PropertyWorkspaceAggregate::STATE_DRAFT);

        // Assert database updated
        $this->ilan->refresh();
        $this->assertEquals('Bodrum Luxury Mansion', $this->ilan->baslik);
        $this->assertEquals('Updated description text', $this->ilan->aciklama);
        $this->assertEquals(8000000, $this->ilan->fiyat);
        $this->assertEquals('EUR', $this->ilan->para_birimi);
        $this->assertEquals(450, $this->ilan->brut_m2);
    }

    /**
     * Test workspace transitions to ready_for_review when all requirements are satisfied.
     */
    public function test_transitions_to_ready_for_review_when_fully_satisfied(): void
    {
        $this->actingAs($this->user);

        // Set all required fields for 'satilik' intent:
        // baslik, aciklama, fiyat, kapak_resmi, il, ilce, brut_metrekare, oda_sayisi, tapusu_var.
        // 1. Fields:
        $postData = [
            'baslik' => 'Fully Loaded Bodrum Villa',
            'aciklama' => 'Description of villa that is very long and detailed',
            'fiyat' => 12000000,
            'para_birimi' => 'TRY',
            'brut_metrekare' => 350,
            'oda_sayisi' => '3+1',
            'tapusu_var' => 'kat-mulkiyeti',
        ];

        // 2. Kapak resmi presence: already set up in setUp()

        // 3. Location fields already set up in setUp()

        // 4. Upload required documents: tapu_fotokopisi & iskan_belgesi
        Belge::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'ilan_id' => $this->ilan->id,
            'baslik' => 'Tapu Senedi',
            'dosya_yolu' => '/docs/tapu.pdf',
            'dosya_tipi' => 'pdf',
            'belge_turu' => 'tapu_fotokopisi',
            'boyut_kb' => 2048,
        ]);

        Belge::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'ilan_id' => $this->ilan->id,
            'baslik' => 'İskan Raporu',
            'dosya_yolu' => '/docs/iskan.pdf',
            'dosya_tipi' => 'pdf',
            'belge_turu' => 'iskan_belgesi',
            'boyut_kb' => 1024,
        ]);

        // 5. Complete required AI Agents (maps to generate_title and generate_description)
        $this->workspace->markAiAgentComplete('description_agent', ['status' => 'success']);

        // Send submission
        $response = $this->postJson(route('admin.workspace.save', ['id' => $this->workspace->id]), [
            'data' => $postData
        ]);

        $response->dump();
        $response->assertStatus(200);
        $response->assertJsonPath('readiness.readiness_status', 'ready');
        $response->assertJsonPath('lifecycle_state', PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);

        // Verify Event Sourcing DB events recorded
        $this->assertDatabaseHas('etki_alani_olaylari', [
            'tenant_id' => $this->tenant->id,
            'aggregate_id' => PropertyWorkspace::where('ilan_id', $this->ilan->id)->first()->id,
            'event_type' => 'StateChanged',
        ]);
    }
}

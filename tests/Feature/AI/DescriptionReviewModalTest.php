<?php

namespace Tests\Feature\AI;

use App\Enums\AIDescriptionStatus;
use App\Enums\AktiflikDurumu;
use App\Enums\IlanDurumu;
use App\Models\AIDescriptionDraft;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Services\AI\YalihanCortex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DescriptionReviewModalTest
 *
 * Verifies the 6 key scenarios of the Description Review Modal (AI Workspace):
 * 1. Modal opens when no AI draft exists (clean 404/empty check).
 * 2. First draft generation via AI is successful (201 response, draft stored).
 * 3. Approve draft persists it to ilan.aciklama.
 * 4. Reject draft preserves the original description.
 * 5. State / data matches correctly after page reload / fetching latest draft.
 * 6. Subsequent generations produce new draft versions (Draft history / versioning).
 *
 * @group ai
 * @group description-review
 */
class DescriptionReviewModalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Ilan $ilan;

    private $cortexMock;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();

        // 1. Seed necessary entities
        $il = new Il;
        $il->id = 1;
        $il->il_adi = 'Muğla';
        $il->plaka_kodu = '48';
        $il->aktiflik_durumu = AktiflikDurumu::AKTIF;
        $il->save();

        $kategori = IlanKategori::factory()->create([
            'slug' => 'yazlik',
            'parent_id' => null,
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        $this->admin = User::factory()->create([
            'email' => 'admin@yalihan.com',
            'role_id' => $role->id,
        ]);

        $this->ilan = Ilan::factory()->create([
            'user_id' => $this->admin->id,
            'yayin_durumu' => IlanDurumu::TASLAK->value,
            'aciklama' => 'Orijinal mülk açıklaması.',
            'baslik' => 'Bodrumda Kiralık Villa',
        ]);

        // 2. Mock YalihanCortex
        $this->cortexMock = \Mockery::mock(YalihanCortex::class);
        $this->cortexMock->shouldReceive('checkIlanQuality')
            ->andReturn([
                'passed' => true,
                'message' => 'Quality check passed',
                'missing_fields' => [],
            ])
            ->byDefault();

        $this->app->instance(YalihanCortex::class, $this->cortexMock);
    }

    /**
     * Scenario 1: Accessing the draft when none exists returns 404 (representing clean initial state).
     */
    public function test_scenario_1_returns_404_when_no_draft_exists()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.ilan-ai.draft.show', $this->ilan->id));

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Henüz taslak oluşturulmamış',
            ]);
    }

    /**
     * Scenario 2: Generate draft successfully: POST /admin/ilan-ai/draft/generate/{ilan}
     */
    public function test_scenario_2_generate_draft_successfully()
    {
        $this->cortexMock->shouldReceive('generateStructuredDescription')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'aciklama' => 'Yapay zeka tarafından üretilmiş premium villa açıklaması.',
                ],
                'provider' => 'gemini-1.5-pro',
                'model' => 'gemini-1.5-pro',
                'metadata' => [
                    'tokens' => 850,
                    'duration_ms' => 320,
                ],
            ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.ilan-ai.draft.generate', $this->ilan->id));

        $response->assertStatus(201)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'AI taslak oluşturuldu. Lütfen inceleyip onaylayın veya reddedin.',
            ]);

        $this->assertDatabaseHas('ai_description_drafts', [
            'ilan_id' => $this->ilan->id,
            'draft_content' => 'Yapay zeka tarafından üretilmiş premium villa açıklaması.',
            'original_content' => 'Orijinal mülk açıklaması.',
            'durum' => AIDescriptionStatus::TASLAK->value,
        ]);
    }

    /**
     * Scenario 3 & 5: Approve draft updates ilan.aciklama and updates status to approved/applied.
     */
    public function test_scenario_3_and_5_approve_persists_description_correctly()
    {
        // 1. Create a draft directly in the DB
        $draft = AIDescriptionDraft::create([
            'ilan_id' => $this->ilan->id,
            'user_id' => $this->admin->id,
            'draft_content' => 'Yapay zeka tarafından üretilmiş onaylanacak açıklama.',
            'original_content' => 'Orijinal mülk açıklaması.',
            'durum' => AIDescriptionStatus::TASLAK->value,
            'provider' => 'gemini-1.5-pro',
        ]);

        // 2. Approve draft
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.ilan-ai.draft.approve', $draft->id));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Açıklama onaylandı ve uygulandı.',
            ]);

        // Assert description is updated on Ilan model
        $this->assertEquals(
            'Yapay zeka tarafından üretilmiş onaylanacak açıklama.',
            $this->ilan->fresh()->aciklama
        );

        // Assert draft state is updated to UYGULANDI (or appropriate final state)
        $this->assertEquals(
            AIDescriptionStatus::UYGULANDI->value,
            $draft->fresh()->durum
        );

        // Scenario 5: Reload show route and verify it returns correct status
        $showResponse = $this->actingAs($this->admin)
            ->getJson(route('admin.ilan-ai.draft.show', $this->ilan->id));

        $showResponse->assertStatus(200)
            ->assertJsonFragment([
                'id' => $draft->id,
                'draft_content' => 'Yapay zeka tarafından üretilmiş onaylanacak açıklama.',
                'durum' => AIDescriptionStatus::UYGULANDI->value,
            ]);
    }

    /**
     * Scenario 4: Reject draft preserves the original description.
     */
    public function test_scenario_4_reject_preserves_original_description()
    {
        // 1. Create a draft directly in the DB
        $draft = AIDescriptionDraft::create([
            'ilan_id' => $this->ilan->id,
            'user_id' => $this->admin->id,
            'draft_content' => 'Yapay zeka tarafından üretilmiş reddedilecek açıklama.',
            'original_content' => 'Orijinal mülk açıklaması.',
            'durum' => AIDescriptionStatus::TASLAK->value,
            'provider' => 'gemini-1.5-pro',
        ]);

        // 2. Reject draft
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.ilan-ai.draft.reject', $draft->id), [
                'note' => 'Oda sayıları yanlış belirtilmiş.',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Taslak reddedildi. Orijinal açıklama korundu.',
            ]);

        // Assert description on Ilan model is preserved
        $this->assertEquals(
            'Orijinal mülk açıklaması.',
            $this->ilan->fresh()->aciklama
        );

        // Assert draft state is updated to REDDEDILDI
        $this->assertEquals(
            AIDescriptionStatus::REDDEDILDI->value,
            $draft->fresh()->durum
        );
        $this->assertEquals(
            'Oda sayıları yanlış belirtilmiş.',
            $draft->fresh()->rejection_note
        );
    }

    /**
     * Scenario 6: Subsequent generations produce new draft versions (Draft history / versioning).
     */
    public function test_scenario_6_generating_multiple_drafts_creates_multiple_versions()
    {
        $this->cortexMock->shouldReceive('generateStructuredDescription')
            ->twice()
            ->andReturn([
                'success' => true,
                'data' => [
                    'aciklama' => 'Taslak Versiyon 1.',
                ],
                'provider' => 'gemini-1.5-pro',
                'model' => 'gemini-1.5-pro',
            ], [
                'success' => true,
                'data' => [
                    'aciklama' => 'Taslak Versiyon 2.',
                ],
                'provider' => 'gemini-1.5-pro',
                'model' => 'gemini-1.5-pro',
            ]);

        // Generate first draft
        $this->actingAs($this->admin)
            ->postJson(route('admin.ilan-ai.draft.generate', $this->ilan->id))
            ->assertStatus(201);

        // Generate second draft
        $this->actingAs($this->admin)
            ->postJson(route('admin.ilan-ai.draft.generate', $this->ilan->id))
            ->assertStatus(201);

        // Get latest draft (Scenario 5/6) - should be the second version
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.ilan-ai.draft.show', $this->ilan->id));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'draft_content' => 'Taslak Versiyon 2.',
            ]);

        // Get history and assert there are 2 drafts
        $historyResponse = $this->actingAs($this->admin)
            ->getJson(route('admin.ilan-ai.draft.history', $this->ilan->id));

        $historyResponse->assertStatus(200);
        $drafts = $historyResponse->json('data.drafts');
        $this->assertCount(2, $drafts);
    }
}

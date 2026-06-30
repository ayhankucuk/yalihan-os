<?php

namespace Tests\Feature\Admin;

use App\Models\CopilotActionLog;
use App\Models\User;
use App\Services\Wizard\CopilotListingGenerator;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * WizardCopilotActionController — HTTP acceptance tests.
 *
 * Covers: generate, apply, undo, reject endpoints.
 * All routes are under POST /admin/copilot/actions/* and require session auth.
 */
class WizardCopilotActionApiTest extends TestCase
{

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Database\Eloquent\Model::unguard();

        $this->user = User::factory()->create(['email' => 'copilot-test@yalihan.com']);
        $this->otherUser = User::factory()->create(['email' => 'other-test@yalihan.com']);

        $this->assignAdminRole($this->user);
        $this->assignAdminRole($this->otherUser);
    }

    /**
     * Assign Spatie 'admin' role to a user via direct DB insert (matches RoleMiddleware logic).
     */
    private function assignAdminRole(User $user): void
    {
        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web']
        );

        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    // ── GENERATE ────────────────────────────────────────────────

    /** @test */
    public function generate_requires_authentication(): void
    {
        $response = $this->postJson('/admin/copilot/actions', [
            'form_state' => ['ana_kategori_id' => 1],
        ]);

        // Unauthenticated → redirect or 401/403
        $this->assertContains($response->status(), [302, 401, 403]);
    }

    /** @test */
    public function generate_requires_form_state(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['form_state']);
    }

    /** @test */
    public function generate_returns_actions_with_correct_structure(): void
    {
        // Mock CopilotListingGenerator to avoid real AI calls
        $mockGenerator = Mockery::mock(CopilotListingGenerator::class);
        $mockGenerator->shouldReceive('generate')
            ->once()
            ->andReturn([
                'actions' => [
                    [
                        'id' => 'title_abc',
                        'type' => 'field_autofill',
                        'label' => 'Başlık Önerisi',
                        'description' => 'AI önerisi',
                        'target' => 'baslik',
                        'value' => 'Satılık Daire Bodrum',
                        'alternatives' => [],
                        'priority' => 10,
                        'confidence' => 0.85,
                        'requires_confirmation' => true,
                        'source' => 'ai_title_generator',
                    ],
                ],
                'mode' => 'suggest',
                'confidence' => 0.85,
                'meta' => [
                    'action_count' => 1,
                    'duration_ms' => 120,
                    'category_id' => 1,
                    'listing_type_id' => 1,
                    'schema_loaded' => false,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        $this->app->instance(CopilotListingGenerator::class, $mockGenerator);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions', [
                'form_state' => [
                    'ana_kategori_id' => 1,
                    'yayin_tipi_id' => 1,
                    'baslik' => '',
                ],
                'mode' => 'suggest',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'actions',
                'mode',
                'confidence',
                'meta' => ['action_count', 'duration_ms'],
            ]);

        $this->assertIsArray($response->json('actions'));
    }

    /** @test */
    public function generate_creates_a_copilot_action_log_with_preview_status(): void
    {
        $mockGenerator = Mockery::mock(CopilotListingGenerator::class);
        $mockGenerator->shouldReceive('generate')->andReturn([
            'actions' => [],
            'mode' => 'suggest',
            'confidence' => 0.0,
            'meta' => ['action_count' => 0, 'duration_ms' => 10, 'category_id' => 2, 'listing_type_id' => 1, 'schema_loaded' => false, 'generated_at' => now()->toIso8601String()],
        ]);
        $this->app->instance(CopilotListingGenerator::class, $mockGenerator);

        $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions', [
                'form_state' => ['ana_kategori_id' => 2, 'yayin_tipi_id' => 1],
                'mode' => 'suggest',
            ]);

        $this->assertDatabaseHas('copilot_action_logs', [
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'preview',
            'main_category_id' => 2,
        ]);
    }

    /** @test */
    public function generate_rejects_invalid_mode(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions', [
                'form_state' => ['ana_kategori_id' => 1],
                'mode' => 'invalid_mode',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mode']);
    }

    // ── APPLY ────────────────────────────────────────────────────

    /** @test */
    public function apply_marks_log_as_applied(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'preview',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/apply', [
                'log_id' => $log->id,
                'applied_fields' => ['baslik' => 'Satılık Daire'],
                'diff_snapshot' => ['before' => '', 'after' => 'Satılık Daire'],
            ]);

        $response->assertOk()
            ->assertJson([
                'basarili' => true,
                'aksiyon_durumu' => 'applied',
                'log_id' => $log->id,
            ]);

        $this->assertDatabaseHas('copilot_action_logs', [
            'id' => $log->id,
            'aksiyon_durumu' => 'applied',
        ]);
    }

    /** @test */
    public function apply_fails_when_log_belongs_to_another_user(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->otherUser->id,
            'aksiyon_durumu' => 'preview',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/apply', [
                'log_id' => $log->id,
                'applied_fields' => ['baslik' => 'Hack'],
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function apply_returns_422_when_log_already_applied(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'applied',
            'applied_at' => now(),
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/apply', [
                'log_id' => $log->id,
                'applied_fields' => ['baslik' => 'Duplicate'],
            ]);

        $response->assertStatus(422)
            ->assertJson(['basarili' => false]);
    }

    /** @test */
    public function apply_requires_applied_fields(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'preview',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/apply', [
                'log_id' => $log->id,
                // applied_fields missing
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['applied_fields']);
    }

    // ── UNDO ─────────────────────────────────────────────────────

    /** @test */
    public function undo_marks_log_as_undone_and_returns_diff_snapshot(): void
    {
        $diffSnapshot = ['before' => '', 'after' => 'Satılık Daire'];
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'applied',
            'applied_at' => now(),
            'diff_snapshot' => $diffSnapshot,
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/undo', [
                'log_id' => $log->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'basarili' => true,
                'aksiyon_durumu' => 'undone',
                'log_id' => $log->id,
            ]);

        $this->assertDatabaseHas('copilot_action_logs', [
            'id' => $log->id,
            'aksiyon_durumu' => 'undone',
        ]);

        // diff_snapshot must be returned for frontend restore
        $this->assertNotNull($response->json('diff_snapshot'));
    }

    /** @test */
    public function undo_fails_when_log_is_not_yet_applied(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'preview',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/undo', [
                'log_id' => $log->id,
            ]);

        $response->assertStatus(422)
            ->assertJson(['basarili' => false]);
    }

    /** @test */
    public function undo_fails_when_log_belongs_to_another_user(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->otherUser->id,
            'aksiyon_durumu' => 'applied',
            'applied_at' => now(),
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/undo', [
                'log_id' => $log->id,
            ]);

        $response->assertStatus(404);
    }

    // ── REJECT ───────────────────────────────────────────────────

    /** @test */
    public function reject_marks_log_as_rejected(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'preview',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/reject', [
                'log_id' => $log->id,
                'reason' => 'Fiyat yanlış',
            ]);

        $response->assertOk()
            ->assertJson([
                'basarili' => true,
                'aksiyon_durumu' => 'rejected',
            ]);

        $this->assertDatabaseHas('copilot_action_logs', [
            'id' => $log->id,
            'aksiyon_durumu' => 'rejected',
            'rejection_reason' => 'Fiyat yanlış',
        ]);
    }

    /** @test */
    public function reject_works_without_reason(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->user->id,
            'aksiyon_durumu' => 'preview',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/reject', [
                'log_id' => $log->id,
            ]);

        $response->assertOk()
            ->assertJson(['basarili' => true]);
    }

    /** @test */
    public function reject_fails_when_log_belongs_to_another_user(): void
    {
        $log = CopilotActionLog::create([
            'action_type' => 'multi_field_apply',
            'user_id' => $this->otherUser->id,
            'aksiyon_durumu' => 'preview',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/admin/copilot/actions/reject', [
                'log_id' => $log->id,
            ]);

        $response->assertStatus(404);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

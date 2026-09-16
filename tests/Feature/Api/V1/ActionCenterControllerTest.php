<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Ilan;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActionCenterControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['tenant_id' => $this->tenantId]);
    }

    // ── Auth guard ───────────────────────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/action-center/tasks')
            ->assertStatus(401);
    }

    // ── dashboard ───────────────────────────────────────────────────────────────

    public function test_dashboard_returns_my_queue_overdue_and_unassigned(): void
    {
        Sanctum::actingAs($this->user);

        $overdue = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'atanan_user_id' => $this->user->id,
            'gorev_durumu' => 'devam_ediyor',
            'bitis_tarihi' => now()->subDay(),
            'source_event' => 'IlanCreated',
        ]);
        $unassigned = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->getJson('/api/v1/action-center/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.overdue.count', 1)
            ->assertJsonPath('data.unassigned.count', 1);
    }

    // ── index / tasks ──────────────────────────────────────────────────────────

    public function test_index_returns_paginated_tasks_for_tenant(): void
    {
        Sanctum::actingAs($this->user);
        Gorev::factory()->count(3)->create(['tenant_id' => $this->tenantId, 'source_event' => 'IlanCreated']);
        Gorev::factory()->count(2)->create(['tenant_id' => 999, 'source_event' => 'IlanCreated']);

        $response = $this->getJson('/api/v1/action-center/tasks');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_filters_by_durum(): void
    {
        Sanctum::actingAs($this->user);
        Gorev::factory()->create(['tenant_id' => $this->tenantId, 'gorev_durumu' => 'bekliyor', 'source_event' => 'IlanCreated']);
        Gorev::factory()->create(['tenant_id' => $this->tenantId, 'gorev_durumu' => 'tamamlandi', 'source_event' => 'IlanCreated']);

        $response = $this->getJson('/api/v1/action-center/tasks?durum=bekliyor');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_index_filters_by_oncelik(): void
    {
        Sanctum::actingAs($this->user);
        Gorev::factory()->create(['tenant_id' => $this->tenantId, 'oncelik' => 'acil', 'source_event' => 'IlanCreated']);
        Gorev::factory()->create(['tenant_id' => $this->tenantId, 'oncelik' => 'normal', 'source_event' => 'IlanCreated']);

        $response = $this->getJson('/api/v1/action-center/tasks?oncelik=acil');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_index_filters_by_overdue(): void
    {
        Sanctum::actingAs($this->user);
        Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'devam_ediyor',
            'bitis_tarihi' => now()->subDay(),
            'source_event' => 'IlanCreated',
        ]);
        Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'devam_ediyor',
            'bitis_tarihi' => now()->addDay(),
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->getJson('/api/v1/action-center/tasks?overdue=1');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_index_pagination_respects_per_page(): void
    {
        Sanctum::actingAs($this->user);
        Gorev::factory()->count(15)->create(['tenant_id' => $this->tenantId, 'source_event' => 'IlanCreated']);

        $response = $this->getJson('/api/v1/action-center/tasks?per_page=5');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 15);
    }

    public function test_index_rejects_invalid_durum(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/action-center/tasks?durum=gecersiz')
            ->assertStatus(422);
    }

    // ── show ────────────────────────────────────────────────────────────────────

    public function test_show_returns_gorev_for_owned_tenant(): void
    {
        Sanctum::actingAs($this->user);
        $gorev = Gorev::factory()->create(['tenant_id' => $this->tenantId, 'source_event' => 'IlanCreated']);

        $response = $this->getJson("/api/v1/action-center/tasks/{$gorev->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $gorev->id);
    }

    public function test_show_returns_404_for_other_tenant(): void
    {
        Sanctum::actingAs($this->user);
        $gorev = Gorev::factory()->create(['tenant_id' => 999, 'source_event' => 'IlanCreated']);

        $response = $this->getJson("/api/v1/action-center/tasks/{$gorev->id}");

        $response->assertStatus(404);
    }

    // ── assign ─────────────────────────────────────────────────────────────────

    public function test_assign_sets_atanan_user_id_and_returns_updated_gorev(): void
    {
        Sanctum::actingAs($this->user);
        $target = User::factory()->create(['tenant_id' => $this->tenantId]);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/assign", [
            'user_id' => $target->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.atanan_user_id', $target->id);
        $this->assertNotNull($gorev->fresh()->assigned_at);
    }

    public function test_assign_returns_422_when_user_not_in_same_tenant(): void
    {
        Sanctum::actingAs($this->user);
        $otherTenantUser = User::factory()->create(['tenant_id' => 999]);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/assign", [
            'user_id' => $otherTenantUser->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Atanacak kullanıcı bu işletmeye ait değil.');
    }

    public function test_assign_returns_404_for_other_tenant_gorev(): void
    {
        Sanctum::actingAs($this->user);
        $target = User::factory()->create(['tenant_id' => $this->tenantId]);
        $gorev = Gorev::factory()->create(['tenant_id' => 999, 'source_event' => 'IlanCreated']);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/assign", [
            'user_id' => $target->id,
        ]);

        $response->assertStatus(404);
    }

    // ── updateStatus / lifecycle ─────────────────────────────────────────────────

    public function test_update_status_bekliyor_to_devam_ediyor_sets_started_at(): void
    {
        Sanctum::actingAs($this->user);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'started_at' => null,
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/status", [
            'durum' => 'devam_ediyor',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($gorev->fresh()->started_at);
    }

    public function test_update_status_to_tamamlandi_sets_completed_at_and_percentage(): void
    {
        Sanctum::actingAs($this->user);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'devam_ediyor',
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/status", [
            'durum' => 'tamamlandi',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($gorev->fresh()->completed_at);
        $this->assertEquals(100, $gorev->fresh()->tamamlanma_yuzdesi);
    }

    public function test_update_status_invalid_transition_returns_422(): void
    {
        Sanctum::actingAs($this->user);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'tamamlandi',
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/status", [
            'durum' => 'devam_ediyor',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Durum geçişi izin verilmiyor: tamamlandi → devam_ediyor']);
    }

    public function test_update_status_beklemede_to_bekliyor_returns_422(): void
    {
        Sanctum::actingAs($this->user);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'beklemede',
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/status", [
            'durum' => 'bekliyor',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Durum geçişi izin verilmiyor: beklemede → bekliyor']);
    }

    public function test_update_status_appends_note_to_notlar(): void
    {
        Sanctum::actingAs($this->user);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'devam_ediyor',
            'notlar' => null,
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->patchJson("/api/v1/action-center/tasks/{$gorev->id}/status", [
            'durum' => 'tamamlandi',
            'not' => 'İşlem tamamlandı.',
        ]);

        $response->assertStatus(200);
        $notes = json_decode($gorev->fresh()->notlar, true);
        $this->assertCount(1, $notes);
        $this->assertEquals('İşlem tamamlandı.', $notes[0]['note']);
        $this->assertEquals($this->user->id, $notes[0]['author_id']);
    }

    // ── auto-assign ─────────────────────────────────────────────────────────────

    public function test_auto_assign_sets_atanan_user_id(): void
    {
        Sanctum::actingAs($this->user);
        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenantId,
            'danisman_id' => $this->user->id,
        ]);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => $ilan->id,
            'gorev_tipi' => 'ilan_foto_yukle',
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->postJson("/api/v1/action-center/tasks/{$gorev->id}/auto-assign");

        $response->assertStatus(200);
        $this->assertEquals($this->user->id, $gorev->fresh()->atanan_user_id);
    }

    public function test_auto_assign_returns_422_when_already_assigned(): void
    {
        Sanctum::actingAs($this->user);
        $existingUser = User::factory()->create(['tenant_id' => $this->tenantId]);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => $existingUser->id,
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->postJson("/api/v1/action-center/tasks/{$gorev->id}/auto-assign");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Bu görev zaten atanmış. Önce mevcut atamayı kaldırın.']);
    }

    // ── stats ───────────────────────────────────────────────────────────────────

    public function test_stats_returns_correct_counts(): void
    {
        Sanctum::actingAs($this->user);
        Gorev::factory()->create(['tenant_id' => $this->tenantId, 'gorev_durumu' => 'bekliyor', 'source_event' => 'IlanCreated']);
        Gorev::factory()->create(['tenant_id' => $this->tenantId, 'gorev_durumu' => 'tamamlandi', 'source_event' => 'IlanCreated']);
        Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'devam_ediyor',
            'atanan_user_id' => $this->user->id,
            'bitis_tarihi' => now()->subDay(),
            'source_event' => 'IlanCreated',
        ]);

        $response = $this->getJson('/api/v1/action-center/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.open', 2)
            ->assertJsonPath('data.completed', 1);
    }

    public function test_stats_returns_0_for_empty_tenant(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/action-center/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.open', 0)
            ->assertJsonPath('data.completed', 0);
    }
}

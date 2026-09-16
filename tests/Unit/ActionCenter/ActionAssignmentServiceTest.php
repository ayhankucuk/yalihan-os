<?php

namespace Tests\Unit\ActionCenter;

use App\Models\User;
use App\Models\Ilan;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\ActionCenter\ActionAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActionAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActionAssignmentService $service;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ActionAssignmentService::class);

        foreach (['admin', 'danisman', 'temizlik', 'operator'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    // ── autoAssign: ilan owner task ─────────────────────────────────────────────

    public function test_auto_assign_ilan_owner_sets_danisman_as_assignee(): void
    {
        $owner = User::factory()->create(['tenant_id' => $this->tenantId]);
        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenantId,
            'danisman_id' => $owner->id,
        ]);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => $ilan->id,
            'gorev_tipi' => 'ilan_foto_yukle',
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
        ]);

        $result = $this->service->autoAssign($gorev);

        $this->assertEquals($owner->id, $result->fresh()->atanan_user_id);
        $this->assertNotNull($result->fresh()->assigned_at);
    }

    public function test_auto_assign_ilan_owner_falls_back_to_first_admin(): void
    {
        $admin = User::factory()->create(['tenant_id' => $this->tenantId]);
        $admin->assignRole('admin');

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenantId,
            'danisman_id' => null,
        ]);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => $ilan->id,
            'gorev_tipi' => 'ilan_foto_yukle',
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
        ]);

        $result = $this->service->autoAssign($gorev);

        $this->assertEquals($admin->id, $result->fresh()->atanan_user_id);
    }

    // ── autoAssign: round-robin ─────────────────────────────────────────────────

    public function test_auto_assign_uses_round_robin_for_lead_contact_task(): void
    {
        $agent1 = User::factory()->create(['tenant_id' => $this->tenantId]);
        $agent1->assignRole('danisman');
        $agent2 = User::factory()->create(['tenant_id' => $this->tenantId]);
        $agent2->assignRole('danisman');

        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => null,
            'gorev_tipi' => 'contact_lead_sla',
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
        ]);

        $result = $this->service->autoAssign($gorev);

        $this->assertNotNull($result->fresh()->atanan_user_id);
        $this->assertContains($result->fresh()->atanan_user_id, [$agent1->id, $agent2->id]);
    }

    public function test_auto_assign_no_agent_returns_gorev_unchanged(): void
    {
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => null,
            'gorev_tipi' => 'contact_lead_sla',
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
        ]);

        $result = $this->service->autoAssign($gorev);

        $this->assertNull($result->fresh()->atanan_user_id);
        $this->assertEquals($gorev->id, $result->id);
    }

    // ── autoAssign: workload ───────────────────────────────────────────────────

    public function test_auto_assign_uses_workload_for_operational_task(): void
    {
        $light = User::factory()->create(['tenant_id' => $this->tenantId]);
        $light->assignRole('temizlik');
        $heavy = User::factory()->create(['tenant_id' => $this->tenantId]);
        $heavy->assignRole('temizlik');

        Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'atanan_user_id' => $heavy->id,
            'gorev_durumu' => 'devam_ediyor',
        ]);
        Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'atanan_user_id' => $heavy->id,
            'gorev_durumu' => 'devam_ediyor',
        ]);

        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => null,
            'gorev_tipi' => 'temizlik',
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
        ]);

        $result = $this->service->autoAssign($gorev);

        $this->assertEquals($light->id, $result->fresh()->atanan_user_id);
    }

    // ── assignToUser ───────────────────────────────────────────────────────────

    public function test_assign_to_user_sets_atanan_user_id_and_assigned_at(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenantId]);
        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'gorev_durumu' => 'bekliyor',
            'atanan_user_id' => null,
            'assigned_at' => null,
        ]);

        $result = $this->service->assignToUser($gorev, $user->id);

        $this->assertEquals($user->id, $result->fresh()->atanan_user_id);
        $this->assertNotNull($result->fresh()->assigned_at);
    }

    // ── Tenant isolation ───────────────────────────────────────────────────────

    public function test_round_robin_assign_respects_tenant_boundary(): void
    {
        $agent1 = User::factory()->create(['tenant_id' => $this->tenantId]);
        $agent1->assignRole('danisman');
        $agentOther = User::factory()->create(['tenant_id' => 999]);
        $agentOther->assignRole('danisman');

        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => null,
            'gorev_tipi' => 'contact_lead_sla',
            'gorev_durumu' => 'bekliyor',
        ]);

        $result = $this->service->autoAssign($gorev);

        $this->assertEquals($agent1->id, $result->fresh()->atanan_user_id);
        $this->assertNotEquals($agentOther->id, $result->fresh()->atanan_user_id);
    }

    public function test_workload_assign_only_counts_open_gorev_for_same_tenant(): void
    {
        $agent = User::factory()->create(['tenant_id' => $this->tenantId]);
        $agent->assignRole('danisman');
        $agentOther = User::factory()->create(['tenant_id' => 999]);
        $agentOther->assignRole('danisman');

        Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'atanan_user_id' => $agent->id,
            'gorev_durumu' => 'devam_ediyor',
        ]);
        Gorev::factory()->create([
            'tenant_id' => 999,
            'atanan_user_id' => $agentOther->id,
            'gorev_durumu' => 'devam_ediyor',
        ]);

        $gorev = Gorev::factory()->create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => null,
            'gorev_tipi' => 'contact_lead_sla',
            'gorev_durumu' => 'bekliyor',
        ]);

        $result = $this->service->autoAssign($gorev);

        $this->assertEquals($agent->id, $result->fresh()->atanan_user_id);
    }

    // ── getAvailableAgents ─────────────────────────────────────────────────────

    public function test_get_available_agents_excludes_deleted_users(): void
    {
        $active = User::factory()->create(['tenant_id' => $this->tenantId, 'deleted_at' => null]);
        $active->assignRole('danisman');
        $deleted = User::factory()->create(['tenant_id' => $this->tenantId, 'deleted_at' => now()]);
        $deleted->assignRole('danisman');

        $agents = $this->service->getAvailableAgents($this->tenantId, 'danisman');

        $this->assertTrue($agents->contains('id', $active->id));
        $this->assertFalse($agents->contains('id', $deleted->id));
    }

    public function test_get_available_agents_respects_tenant(): void
    {
        $agent = User::factory()->create(['tenant_id' => $this->tenantId]);
        $agent->assignRole('danisman');
        User::factory()->create(['tenant_id' => 999])->assignRole('danisman');

        $agents = $this->service->getAvailableAgents($this->tenantId, 'danisman');

        $this->assertCount(1, $agents);
        $this->assertEquals($agent->id, $agents->first()->id);
    }
}

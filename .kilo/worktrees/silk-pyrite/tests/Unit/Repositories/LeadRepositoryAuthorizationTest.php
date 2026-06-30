<?php

namespace Tests\Unit\Repositories;

use App\Models\Lead;
use App\Models\User;
use App\Repositories\LeadRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LeadRepositoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected LeadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(LeadRepository::class);
    }

    protected function createUserWithRole(string $name, int $id, bool $isAdmin = false): User
    {
        $user = User::factory()->create(['id' => $id, 'name' => $name]);

        if ($isAdmin) {
            $user = Mockery::mock($user)->makePartial();
            $user->shouldReceive('isAdmin')->andReturn(true);
            $user->shouldReceive('hasRole')->andReturn(true);
        }

        return $user;
    }

    /** @test */
    public function null_user_sees_nothing_deterministic_fail()
    {
        $agent = User::factory()->create();

        Lead::create([
            'name' => 'Test Lead',
            'platform_user_id' => uniqid(),
            'assigned_agent_id' => $agent->id,
            'crm_durumu' => Lead::CRM_NEW,
        ]);

        $this->assertNull(auth()->user());

        $results = $this->repository->getLeads();
        $this->assertCount(0, $results);
    }

    /** @test */
    public function agent_sees_only_own_leads()
    {
        $agent1 = $this->createUserWithRole('Agent 1', 1, false);
        $agent2 = $this->createUserWithRole('Agent 2', 2, false);

        for ($i=0; $i<3; $i++) {
            Lead::create([
                'name' => 'Agent1 Lead',
                'platform_user_id' => uniqid(),
                'assigned_agent_id' => $agent1->id,
                'crm_durumu' => Lead::CRM_NEW
            ]);
        }

        for ($i=0; $i<2; $i++) {
            Lead::create([
                'name' => 'Agent2 Lead',
                'platform_user_id' => uniqid(),
                'assigned_agent_id' => $agent2->id,
                'crm_durumu' => Lead::CRM_NEW
            ]);
        }

        $this->actingAs($agent1);
        $results = $this->repository->getLeads();

        $this->assertCount(3, $results);
        foreach ($results as $lead) {
            $this->assertEquals($agent1->id, $lead->assigned_agent_id);
        }
    }

    /** @test */
    public function admin_sees_all_leads()
    {
        $admin = $this->createUserWithRole('Admin', 1, true);
        $agent = $this->createUserWithRole('Agent', 2, false);

        for ($i=0; $i<3; $i++) {
            Lead::create([
                'name' => 'Agent Lead',
                'platform_user_id' => uniqid(),
                'assigned_agent_id' => $agent->id,
                'crm_durumu' => Lead::CRM_NEW
            ]);
        }

        for ($i=0; $i<2; $i++) {
            Lead::create([
                'name' => 'Admin Lead',
                'platform_user_id' => uniqid(),
                'assigned_agent_id' => $admin->id,
                'crm_durumu' => Lead::CRM_NEW
            ]);
        }

        $this->actingAs($admin);
        $results = $this->repository->getLeads();

        $this->assertCount(5, $results);
    }

    /** @test */
    public function agent_cannot_find_cross_tenant_lead_returns_404()
    {
        $agent1 = $this->createUserWithRole('Agent 1', 1, false);
        $agent2 = $this->createUserWithRole('Agent 2', 2, false);

        $lead2 = Lead::create([
            'name' => 'Agent2 Lead',
            'platform_user_id' => uniqid(),
            'assigned_agent_id' => $agent2->id,
            'crm_durumu' => Lead::CRM_NEW
        ]);

        $this->actingAs($agent1);

        $this->expectException(ModelNotFoundException::class);
        
        $this->repository->findOrFail($lead2->id);
    }

    /** @test */
    public function aggregation_stats_reflect_only_owned_leads()
    {
        $agent1 = $this->createUserWithRole('Agent 1', 1, false);
        $agent2 = $this->createUserWithRole('Agent 2', 2, false);

        Lead::create(['name' => 'A1', 'platform_user_id' => uniqid(), 'assigned_agent_id' => $agent1->id, 'crm_durumu' => Lead::CRM_NEW]);
        Lead::create(['name' => 'A1', 'platform_user_id' => uniqid(), 'assigned_agent_id' => $agent1->id, 'crm_durumu' => Lead::CRM_WON]);

        for ($i=0; $i<3; $i++) {
            Lead::create(['name' => 'A2', 'platform_user_id' => uniqid(), 'assigned_agent_id' => $agent2->id, 'crm_durumu' => Lead::CRM_NEW]);
        }

        $this->actingAs($agent1);
        $stats1 = $this->repository->getSummaryStats();

        $this->assertEquals(2, $stats1['toplam']);
        $this->assertEquals(1, $stats1['yeni']);
        $this->assertEquals(1, $stats1['kazanildi']);
        $this->assertEquals(0, $stats1['kayip']);

        $this->actingAs($agent2);
        $stats2 = $this->repository->getSummaryStats();

        $this->assertEquals(3, $stats2['toplam']);
        $this->assertEquals(3, $stats2['yeni']);
        $this->assertEquals(0, $stats2['kazanildi']);
    }

    /** @test */
    public function admin_aggregation_stats_reflect_all_leads()
    {
        $admin = $this->createUserWithRole('Admin', 1, true);
        $agent = $this->createUserWithRole('Agent 1', 2, false);

        for ($i=0; $i<2; $i++) {
            Lead::create(['name' => 'Agent', 'platform_user_id' => uniqid(), 'assigned_agent_id' => $agent->id, 'crm_durumu' => Lead::CRM_NEW]);
        }
        Lead::create(['name' => 'Admin', 'platform_user_id' => uniqid(), 'assigned_agent_id' => $admin->id, 'crm_durumu' => Lead::CRM_NEW]);

        $this->actingAs($admin);
        $stats = $this->repository->getSummaryStats();

        $this->assertEquals(3, $stats['toplam']);
        $this->assertEquals(3, $stats['yeni']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

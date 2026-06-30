<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3.1: Controller Capability Hardening Validation (Lead)
 *
 * Success Metric: "Does the system return 404 for cross-tenant data and 403 for denied capabilities?"
 */
class LeadControllerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $danisman1;
    protected User $danisman2;
    protected ?Role $adminRole = null;
    protected ?Role $danismanRole = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable role middleware to test defense-in-depth (policy level)
        $this->withoutMiddleware([\App\Http\Middleware\RoleMiddleware::class]);

        if (!\Illuminate\Support\Facades\Schema::hasTable('ai_lead_scores')) {
            \Illuminate\Support\Facades\Schema::create('ai_lead_scores', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->integer('lead_id');
                $table->integer('skor_degeri')->default(50);
                $table->string('skor_etiketi')->nullable();
                $table->text('skor_nedeni')->nullable();
                $table->integer('win_probability')->default(10);
                $table->timestamp('hesaplama_tarihi')->nullable();
                $table->timestamps();
            });
        }

        $this->mock(\App\Services\CRM\LeadAuthorityService::class, function ($mock) {
            $mock->shouldReceive('getEnrichedLead')->andReturnUsing(function ($lead) {
                if (!$lead->updated_at) $lead->updated_at = now();
                return [
                    'lead' => $lead,
                    'score' => (object)['skor_degeri' => 50, 'skor_etiketi' => 'Ilık', 'win_probability' => 10, 'hesaplama_tarihi' => now(), 'updated_at' => now(), 'model_versiyonu' => 'v1'],
                    'recommendation' => null
                ];
            });
        });

        $this->adminRole = Role::where('name', 'admin')->first();
        if (!$this->adminRole) {
            $this->adminRole = new Role();
            $this->adminRole->name = 'admin';
            $this->adminRole->save();
        }

        $this->danismanRole = Role::where('name', 'danisman')->first();
        if (!$this->danismanRole) {
            $this->danismanRole = new Role();
            $this->danismanRole->name = 'danisman';
            $this->danismanRole->save();
        }

        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id, 'name' => 'Admin User']);
        $this->danisman1 = User::factory()->create(['role_id' => $this->danismanRole->id, 'name' => 'Danisman 1']);
        $this->danisman2 = User::factory()->create(['role_id' => $this->danismanRole->id, 'name' => 'Danisman 2']);
    }

    /** @test */
    public function index_respects_policy_and_scoping()
    {
        $this->withoutExceptionHandling();

        // Arrange
        Lead::create(['name' => 'Lead 1', 'platform' => 'instagram', 'platform_user_id' => uniqid(), 'assigned_agent_id' => $this->danisman1->id, 'crm_durumu' => Lead::CRM_NEW]);
        Lead::create(['name' => 'Lead 2', 'platform' => 'instagram', 'platform_user_id' => uniqid(), 'assigned_agent_id' => $this->danisman2->id, 'crm_durumu' => Lead::CRM_NEW]);

        // Act & Assert: Danisman 1 sees only their own
        $this->actingAs($this->danisman1)
            ->get(route('admin.leads.index'))
            ->assertStatus(200)
            ->assertViewHas('leads', function ($leads) {
                return $leads->count() === 1 && $leads->first()->assigned_agent_id === $this->danisman1->id;
            });

        // Act & Assert: Admin sees all
        $this->actingAs($this->admin)
            ->get(route('admin.leads.index'))
            ->assertStatus(200)
            ->assertViewHas('leads', function ($leads) {
                return $leads->total() === 2;
            });
    }

    /** @test */
    public function show_blocks_cross_tenant_access_with_404()
    {
        $lead = Lead::create([
            'name' => 'Target Lead',
            'platform' => 'instagram',
            'platform_user_id' => uniqid(),
            'assigned_agent_id' => $this->danisman2->id,
            'crm_durumu' => Lead::CRM_NEW
        ]);

        // 1. Cross-tenant access: Should be blocked at Layer 2 (Repository Scope) before Layer 1 (Policy)
        // Hence, returns 404.
        $this->actingAs($this->danisman1)
            ->get(route('admin.leads.show', ['lead' => $lead->id]))
            ->assertStatus(404);

        // 2. Own resource: 200 OK
        $this->withoutExceptionHandling();
        $this->actingAs($this->danisman2)
            ->get(route('admin.leads.show', ['lead' => $lead->id]))
            ->assertStatus(200);

        // 3. Admin bypass: 200 OK
        $this->actingAs($this->admin)
            ->get(route('admin.leads.show', ['lead' => $lead->id]))
            ->assertStatus(200);
    }
}

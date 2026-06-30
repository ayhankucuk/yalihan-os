<?php

namespace Tests\Feature\Admin\Dashboard;

use Tests\TestCase;
use App\Models\User; // Use standard User model
use App\Services\Dashboard\AgentProductivityService;
use Mockery;

class AgentDashboardTest extends TestCase
{

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Context7: Use 'web' guard role creation
        \Illuminate\Database\Eloquent\Model::unguard();
        $role = \App\Modules\Auth\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create([
             'role_id' => $role->id
        ]);
    }

    /** @test */
    public function it_can_access_agent_dashboard()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.dashboard.agent'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard.agent');
    }

    /** @test */
    public function it_receives_correct_stats_data()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.dashboard.agent'));

        // Check if stats variable exists
        $response->assertViewHas('stats');

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total_listings', $stats);
        $this->assertArrayHasKey('active_listings', $stats);
        $this->assertArrayHasKey('new_leads', $stats);
    }

    /** @test */
    public function it_displays_ai_insights()
    {
        // Mock Service for Insights
        /** @var \Mockery\MockInterface|AgentProductivityService $mockService */
        $mockService = Mockery::mock(AgentProductivityService::class);
        $mockService->shouldReceive('getStats')
            ->with(Mockery::any())
            ->andReturnUsing(function () {
                return [
                    'total_listings' => 10,
                    'active_listings' => 5,
                    'new_leads' => 2,
                    'portfolio_value' => 1000000,
                    'roi_month' => 10
                ];
            });
        $mockService->shouldReceive('getTasks')->andReturn([]);
        $mockService->shouldReceive('getAiInsights')->andReturn([
            [
                'type' => 'opportunity',
                'message' => 'Fiyat güncellemesi fırsatı',
                'action_url' => '#'
            ]
        ]);

        // Bind mock
        $this->app->instance(AgentProductivityService::class, $mockService);

        $response = $this->withoutExceptionHandling()->actingAs($this->user)
            ->get(route('admin.dashboard.agent'));

        $response->assertStatus(200);
        $response->assertSee('Cortex AI Önerileri');
        $response->assertSee('Fiyat güncellemesi fırsatı');
    }
}

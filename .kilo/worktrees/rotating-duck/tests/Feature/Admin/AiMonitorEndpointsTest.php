<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AiMonitorEndpointsTest extends TestCase
{
    use WithFaker;

    protected function actingAsAdmin()
    {
        $user = User::factory()->create([
            'role_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        return $this->actingAs($user)->withoutMiddleware();
    }

    public function test_code_health_endpoint_returns_json()
    {
        $this->actingAsAdmin()
            ->get('/api/v1/admin/ai-monitor/code-health')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_issues',
                    'health_score',
                    'issues',
                ],
            ]);
    }

    public function test_duplicates_endpoint_returns_json()
    {
        $this->actingAsAdmin()
            ->get('/api/v1/admin/ai-monitor/duplicates')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_conflicts_endpoint_returns_json()
    {
        $this->actingAsAdmin()
            ->get('/api/v1/admin/ai-monitor/conflicts')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_pages_health_endpoint_returns_json()
    {
        $this->actingAsAdmin()
            ->get('/api/v1/admin/ai-monitor/pages-health')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_run_context7_fix_returns_suggestions()
    {
        $this->actingAsAdmin()
            ->post('/api/v1/admin/ai-monitor/run-context7-fix')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'suggestions',
                'action',
            ]);
    }

    public function test_apply_suggestion_requires_manual()
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/admin/ai-monitor/apply-suggestion', [
                'suggestion' => 'Context7 kontrol çalıştırın',
                'index' => 0,
            ])
            ->assertOk()
            ->assertJson([
                'manual_required' => true,
            ]);
    }
}

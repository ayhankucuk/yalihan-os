<?php

namespace Tests\Feature\AI;

use App\Models\AiProviderDecision;
use App\Modules\Auth\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $role->id]);
    }

    /** @test */
    public function it_persists_debug_metadata_in_provider_decisions()
    {
        $decision = AiProviderDecision::create([
            'correlation_id' => 'test-correlation-123',
            'kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'chosen_provider' => 'openai',
            'scores_json' => ['openai' => 0.95, 'vertex' => 0.85],
            'reason_json' => ['trigger' => 'dynamic_scoring'],
            'debug_metadata' => [
                'window_used' => 'cat_7d',
                'sample_size' => 150,
                'all_scores' => ['openai' => 0.95],
                'timestamp' => now()->toIso8601String()
            ]
        ]);

        $this->assertDatabaseHas('ai_provider_decisions', [
            'correlation_id' => 'test-correlation-123',
            'chosen_provider' => 'openai'
        ]);

        $this->assertNotNull($decision->debug_metadata);
        $this->assertEquals('cat_7d', $decision->debug_metadata['window_used']);
        $this->assertEquals(150, $decision->debug_metadata['sample_size']);
    }

    /** @test */
    public function admin_can_access_debug_decisions_interface()
    {
        // Create sample decision
        AiProviderDecision::create([
            'correlation_id' => 'test-123',
            'chosen_provider' => 'vertex',
            'scores_json' => ['vertex' => 0.88],
            'reason_json' => ['trigger' => 'dynamic_scoring'],
            'debug_metadata' => ['window_used' => 'global_7d', 'sample_size' => 200]
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.ai.debug.decisions'));

        $response->assertStatus(200);
        $response->assertSee('AI Provider Decisions');
        $response->assertSee('vertex');
        $response->assertSee('test-123');
    }

    /** @test */
    public function debug_interface_filters_work_correctly()
    {
        AiProviderDecision::create([
            'correlation_id' => 'openai-test',
            'chosen_provider' => 'openai',
            'scores_json' => [],
            'reason_json' => [],
            'debug_metadata' => []
        ]);

        AiProviderDecision::create([
            'correlation_id' => 'vertex-test',
            'chosen_provider' => 'vertex',
            'scores_json' => [],
            'reason_json' => [],
            'debug_metadata' => []
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.ai.debug.decisions', ['provider' => 'openai']));

        $response->assertStatus(200);
        $response->assertSee('openai-test');
        $response->assertDontSee('vertex-test');
    }
    /** @test */
    public function admin_can_view_roi_dashboard()
    {
        // Create sample usage data with ROI metrics
        \App\Models\AiFeatureUsage::create([
            'ilan_id' => 1,
            'kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'feature_slug' => 'test-feature',
            'confidence' => 0.9,
            'aksiyon' => 'accepted',
            'source_tipi' => 'system', // Required field
            'tahmini_tasarruf_sn' => 3600, // 1 hour
            'maliyet_usd' => 0.05
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.ai.roi-dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('experiments');

        $stats = $response->viewData('stats');
        $this->assertEquals(1.0, $stats['total_saved_hours']); // 3600s = 1h
        $this->assertEquals('0.0500', $stats['total_cost_usd']);
    }
}

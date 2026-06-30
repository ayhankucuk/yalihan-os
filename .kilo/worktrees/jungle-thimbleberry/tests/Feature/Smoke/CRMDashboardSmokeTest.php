<?php

namespace Tests\Feature\Smoke;

use Tests\TestCase;
use App\Models\Kisi;
use App\Models\User;
use App\Enums\KisiDurumu;

class CRMDashboardSmokeTest extends TestCase
{
     

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Create admin user for actingAs
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Test: CRM Dashboard loads correctly via CRMOrchestrator
     *
     * @test
     * @group smoke
     * @group crm
     */
    public function crm_dashboard_data_loads_correctly(): void
    {
        // Arrange: Prepare some data
        Kisi::factory()->count(5)->create([
            'crm_surec_asamasi' => KisiDurumu::SICAK->value,
            'aktiflik_durumu' => true
        ]);
        
        Kisi::factory()->count(3)->create([
            'crm_surec_asamasi' => KisiDurumu::POTANSIYEL->value,
            'aktiflik_durumu' => true
        ]);

        // Act: Hit the dashboard endpoint
        $response = $this->actingAs($this->admin)->getJson('/admin/crm');

        // Assert: Success and structure
        $response->assertStatus(200);
        $response->assertViewIs('admin.crm.dashboard');
        
        // Assert: View data has expected orchestrator keys
        $response->assertViewHas('stats');
        $response->assertViewHas('customerSegments');
        $response->assertViewHas('recentActivities');

        $stats = $response->viewData('stats');
        $this->assertEquals(8, $stats['total_customers']);
        $this->assertEquals(8, $stats['active_customers']);
        $this->assertArrayHasKey('pending_followups', $stats);
    }
}

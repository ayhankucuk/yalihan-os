<?php

namespace Tests\Feature\Smoke;

use Tests\TestCase;
use App\Models\Talep;
use App\Models\User;

class TalepOrchestrationParityTest extends TestCase
{
     

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Test: Talep orchestration parity for list and form data
     *
     * @test
     * @group smoke
     * @group talep
     */
    public function talep_list_and_stats_parity_works(): void
    {
        // SKIP: talepler.tip column missing — blocked by pending Context7 migration (type→tip)
        // Unblock after running: php artisan migrate --path=database/migrations/2026_*_add_tip_to_talepler*
        $this->markTestSkipped('talepler.tip column not yet migrated — pending Context7 schema migration.');

        Talep::factory()->count(3)->create(['talep_durumu' => 'Aktif']);
        Talep::factory()->count(2)->create(['talep_durumu' => 'Beklemede']);

        // Act: Hit the demands list
        $response = $this->actingAs($this->admin)->getJson('/admin/talepler');

        // Assert:
        $response->assertStatus(200);
        $response->assertViewHas('talepler');
        $response->assertViewHas('stats');
        
        $stats = $response->viewData('stats');
        $this->assertEquals(5, $stats['toplam']);
        $this->assertEquals(3, $stats['aktif']);
        $this->assertEquals(2, $stats['beklemede']);

        // Act: Hit the matching cockpit for a demand
        $talep = Talep::first();
        $responseMatch = $this->actingAs($this->admin)->getJson("/admin/talepler/{$talep->id}/matches");
        
        $responseMatch->assertStatus(200);
        $responseMatch->assertViewHas('talep');
        $responseMatch->assertViewHas('matches');
    }
}

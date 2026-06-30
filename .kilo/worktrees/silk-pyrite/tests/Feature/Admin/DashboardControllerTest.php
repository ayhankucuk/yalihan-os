<?php

namespace Tests\Feature\Admin;

use App\Modules\Auth\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name' => 'admin']);
        $this->admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test DashboardController index page
     */
    public function test_dashboard_controller_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test DashboardController requires authentication
     */
    public function test_dashboard_controller_requires_authentication(): void
    {
        $response = $this->get('/admin/dashboard');

        // Should redirect to login
        $response->assertStatus(302);
    }

    /**
     * Test DashboardController stats endpoint
     */
    public function test_dashboard_controller_stats(): void
    {
        // Mock CacheService to avoid DB queries for missing tables
        $this->mock(\App\Services\Cache\CacheService::class, function ($mock) {
            $mock->shouldReceive('key')->andReturn('test_key');
            $mock->shouldReceive('remember')
                ->andReturn([
                    'quickStats' => [],
                    'recentIlanlar' => [],
                    'recentUsers' => [],
                ]);
        });

        $response = $this->actingAs($this->admin)
            ->getJson('/admin/dashboard/stats');

        // Should return JSON response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }



    /**
     * Test DashboardController with filters
     */
    public function test_dashboard_controller_with_filters(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard?period=week');

        $response->assertStatus(200);
    }
}

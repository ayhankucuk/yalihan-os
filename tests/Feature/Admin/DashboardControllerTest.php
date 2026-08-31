<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
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
     *
     * @group pending-migration
     * @skip Pending: ilan_goruntulenme_gunluk table has no migration
     *        This is a production table created manually. Test requires either:
     *        1. A migration for the table, OR
     *        2. Mocking at integration test level
     */
    public function test_dashboard_controller_stats(): void
    {
        $this->markTestSkipped('PENDING: ilan_goruntulenme_gunluk table has no migration - production only table');
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

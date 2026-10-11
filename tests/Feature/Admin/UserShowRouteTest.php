<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CF003 Regression Test
 *
 * Finding: AuthController@show() was returning view('auth::users.show')
 * which did not exist (auth:: namespace was not registered).
 *
 * Fix: Changed to view('admin.users.show') with new show.blade.php
 *
 * @see CF003_ADMIN_USER_SHOW_REVALIDATE_REMEDIATE_01
 */
class UserShowRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;
    private Role $adminRole;
    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Yönetici',
        ]);

        $this->userRole = Role::create([
            'name' => 'danisman',
            'display_name' => 'Danışman',
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'aktiflik_durumu' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $this->userRole->id,
            'aktiflik_durumu' => true,
        ]);
    }

    /**
     * @test
     *
     * Authenticated admin user accessing user detail should receive HTTP 200
     * and render the canonical admin.users.show view.
     */
    public function authenticated_admin_can_view_user_detail(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.kullanicilar.show', $this->regularUser->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.show');
        $response->assertViewHas('user');
    }

    /**
     * @test
     *
     * Admin viewing user detail should see user name in response.
     */
    public function admin_user_show_displays_user_name(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.kullanicilar.show', $this->regularUser->id));

        $response->assertStatus(200);
        $response->assertSee($this->regularUser->name);
    }

    /**
     * @test
     *
     * Admin viewing user detail should see user email in response.
     */
    public function admin_user_show_displays_user_email(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.kullanicilar.show', $this->regularUser->id));

        $response->assertStatus(200);
        $response->assertSee($this->regularUser->email);
    }

    /**
     * @test
     *
     * Authenticated regular user (non-admin) should NOT be able to access
     * admin user detail route. This is a security boundary test.
     */
    public function non_admin_user_cannot_access_admin_user_detail_route(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.kullanicilar.show', $this->regularUser->id));

        // Admin-only route - should be 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * @test
     *
     * Unauthenticated request to user detail should redirect to login.
     */
    public function unauthenticated_request_redirects_to_login(): void
    {
        $response = $this->get(route('admin.kullanicilar.show', $this->regularUser->id));

        $response->assertRedirect('/login');
    }

    /**
     * @test
     *
     * Admin viewing a passive user should see the passive alert.
     */
    public function admin_sees_passive_user_alert(): void
    {
        $passiveUser = User::factory()->create([
            'role_id' => $this->userRole->id,
            'aktiflik_durumu' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.kullanicilar.show', $passiveUser->id));

        $response->assertStatus(200);
        $response->assertSee('pasif durumda');
    }
}

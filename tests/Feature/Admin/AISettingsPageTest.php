<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AISettingsPageTest extends TestCase
{
    /**
     * Requirement 1: User with one valid role.
     */
    public function test_user_with_one_valid_role_returns_true(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'role' => 'admin',
        ]);
        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole($role));
        $this->assertFalse($user->hasRole('editor'));
    }

    /**
     * Requirement 2: User with multiple roles (handles collection or array).
     */
    public function test_user_with_multiple_roles(): void
    {
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleEditor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'role_id' => $roleAdmin->id,
            'role' => 'admin',
        ]);
        $user->assignRole([$roleAdmin, $roleEditor]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('editor'));
        $this->assertTrue($user->hasRole(['admin', 'editor']));
        $this->assertTrue($user->hasRole(collect([$roleAdmin, $roleEditor])));
        $this->assertFalse($user->hasRole('danisman'));
    }

    /**
     * Requirement 3: User without a role.
     */
    public function test_user_without_a_role_returns_false(): void
    {
        $user = User::factory()->create([
            'role_id' => null,
            'role' => null,
        ]);

        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole(['admin', 'editor']));
        $this->assertFalse($user->hasRole(collect([])));
    }

    /**
     * Requirement 4: Unauthorized user denied access to /admin/ai-settings.
     */
    public function test_unauthorized_user_denied_access(): void
    {
        $roleDanisman = Role::firstOrCreate(['name' => 'danisman', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'role_id' => $roleDanisman->id,
            'role' => 'danisman',
        ]);
        $user->assignRole($roleDanisman);

        // Danisman does not have admin role or manage-settings permission
        $response = $this->actingAs($user)->get(route('admin.ai-settings.index'));

        // Route middleware 'role:admin' or controller 'can:manage-settings' denies access
        $this->assertContains($response->status(), [403, 302]);
    }

    /**
     * Requirement 5: Authorized user allowed access.
     */
    public function test_authorized_user_allowed_access(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'role' => 'admin',
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.ai-settings.index'));

        $response->assertStatus(200);
    }

    /**
     * Requirement 6: /admin/ai-settings loads without exception and renders page contents.
     */
    public function test_ai_settings_page_renders_without_exception(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'role' => 'admin',
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.ai-settings.index'));

        $response->assertStatus(200);
        $response->assertSee('AI Ayarları');
        $response->assertSee('Sağlayıcı Seçimi');
    }

    /**
     * Specific regression test for Spatie passing a Collection instance to hasRole().
     */
    public function test_user_has_role_supports_collection_instance(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'role' => 'admin',
        ]);
        $user->assignRole($role);

        $collectionOfRoles = collect([$role]);
        $this->assertTrue($user->hasRole($collectionOfRoles), 'hasRole must handle Collection instances without throwing Property [name] does not exist');
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\SaaS\Tenant;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class V2UsersApiSecurityTest extends TestCase
{
    use WithFaker;

    /**
     * Test 1: Guest request to index returns 401 Unauthenticated.
     */
    public function test_guest_cannot_access_user_index(): void
    {
        $response = $this->getJson(route('api.users.index'));

        $response->assertStatus(401);
    }

    /**
     * Test 2: Guest request to show returns 401 Unauthenticated.
     */
    public function test_guest_cannot_access_user_show(): void
    {
        $response = $this->getJson(route('api.users.show', ['user' => 1]));

        $response->assertStatus(401);
    }

    /**
     * Test 3: Authenticated user can access user index.
     */
    public function test_authenticated_user_can_access_index(): void
    {
        $tenant = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant 1',
            'domain' => 'tenant1.local',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'User One',
            'email' => 'user1@tenant1.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.users.index'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test 4: Valid route model binding returns exact user details.
     */
    public function test_valid_route_binding_returns_user_details(): void
    {
        $tenant = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant 1',
            'domain' => 'tenant1.local',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'User Target',
            'email' => 'target@tenant1.local',
            'password' => bcrypt('secret123'),
            'telefon' => '5551234567',
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.users.show', ['user' => $user->id]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'User Target')
            ->assertJsonPath('data.email', 'target@tenant1.local');
    }

    /**
     * Test 5: Non-existent user ID returns 404 Not Found.
     */
    public function test_non_existent_user_returns_404(): void
    {
        $user = User::create([
            'name' => 'Auth User',
            'email' => 'auth@tenant.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.users.show', ['user' => 999999]));

        $response->assertStatus(404);
    }

    /**
     * Test 6: Tenant A user cannot retrieve Tenant B user via show endpoint.
     */
    public function test_tenant_a_cannot_access_tenant_b_user(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A',
            'domain' => 'tenanta.local',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B',
            'domain' => 'tenantb.local',
            'status' => 'active',
        ]);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'User A',
            'email' => 'usera@tenanta.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'User B',
            'email' => 'userb@tenantb.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson(route('api.users.show', ['user' => $userB->id]));

        $response->assertStatus(404);
    }

    /**
     * Test 7: Tenant A user index listing excludes Tenant B users.
     */
    public function test_tenant_a_user_index_excludes_tenant_b_users(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A',
            'domain' => 'tenanta.local',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B',
            'domain' => 'tenantb.local',
            'status' => 'active',
        ]);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'User A Unique',
            'email' => 'usera_unique@tenanta.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'User B Secret',
            'email' => 'userb_secret@tenantb.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson(route('api.users.index'));

        $response->assertStatus(200);

        $emails = collect($response->json('data.data'))->pluck('email')->toArray();
        $this->assertContains('usera_unique@tenanta.local', $emails);
        $this->assertNotContains('userb_secret@tenantb.local', $emails);
    }

    /**
     * Test 8: Super admin user with tenant_id can access users across tenants.
     */
    public function test_super_admin_can_access_cross_tenant_users(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A Master',
            'domain' => 'tenanta_master.local',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B',
            'domain' => 'tenantb.local',
            'status' => 'active',
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        $superAdmin = User::create([
            'tenant_id' => $tenantA->id,
            'role_id' => 1, // Super admin role
            'name' => 'Super Admin',
            'email' => 'admin@yalihan.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);
        $superAdmin->assignRole($role);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'User B Under Admin',
            'email' => 'userb_under_admin@tenantb.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->getJson(route('api.users.show', ['user' => $userB->id]));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $userB->id);
    }

    /**
     * Test 9: Tenant A POST creates user assigned to Tenant A.
     */
    public function test_tenant_a_post_creates_user_in_tenant_a(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A',
            'domain' => 'tenanta.local',
            'status' => 'active',
        ]);

        $authUser = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Auth User A',
            'email' => 'auth@tenanta.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->postJson(route('api.users.store'), [
            'ad_soyad' => 'New User A',
            'email' => 'newuser@tenanta.local',
            'telefon' => '5551112233',
            'sifre_hash' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $createdUser = User::where('email', 'newuser@tenanta.local')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($tenantA->id, $createdUser->tenant_id, 'Created user must belong to Tenant A');
    }

    /**
     * Test 10: Tenant A payload with Tenant B tenant_id cannot create a Tenant B user.
     */
    public function test_tenant_a_cannot_inject_tenant_b_id_during_store(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A',
            'domain' => 'tenanta.local',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B',
            'domain' => 'tenantb.local',
            'status' => 'active',
        ]);

        $authUserA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Auth User A',
            'email' => 'auth@tenanta.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($authUserA);

        // Attacker payload: try to inject Tenant B tenant_id
        $response = $this->postJson(route('api.users.store'), [
            'ad_soyad' => 'Injected User',
            'email' => 'injected@tenantb.local',
            'telefon' => '5559998877',
            'sifre_hash' => 'password123',
            'tenant_id' => $tenantB->id, // Malicious injection attempt
        ]);

        $response->assertStatus(201);

        $createdUser = User::where('email', 'injected@tenantb.local')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($tenantA->id, $createdUser->tenant_id, 'Server must enforce authenticated tenant, ignoring client-supplied tenant_id');
        $this->assertNotEquals($tenantB->id, $createdUser->tenant_id, 'Must NOT create user in Tenant B');
    }

    /**
     * Test 11: Tenant A cross-tenant PUT remains denied AND target DB record unchanged.
     */
    public function test_tenant_a_cross_tenant_update_blocked_and_db_unchanged(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A',
            'domain' => 'tenanta.local',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B',
            'domain' => 'tenantb.local',
            'status' => 'active',
        ]);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'User A',
            'email' => 'usera@tenanta.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Original Name B',
            'email' => 'userb@tenantb.local',
            'telefon' => '5550000000',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->putJson(route('api.users.update', ['user' => $userB->id]), [
            'name' => 'Malicious Update',
            'telefon' => '5559999999',
        ]);

        $response->assertStatus(404);

        $userB->refresh();
        $this->assertEquals('Original Name B', $userB->name, 'Target user name must remain unchanged');
        $this->assertEquals('5550000000', $userB->telefon, 'Target user phone must remain unchanged');
    }

    /**
     * Test 12: Tenant A cross-tenant DELETE remains denied AND target DB record still exists.
     */
    public function test_tenant_a_cross_tenant_delete_blocked_and_db_unchanged(): void
    {
        $tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant A',
            'domain' => 'tenanta.local',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant B',
            'domain' => 'tenantb.local',
            'status' => 'active',
        ]);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'User A',
            'email' => 'usera@tenanta.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'User B Protected',
            'email' => 'userb_protected@tenantb.local',
            'password' => bcrypt('secret123'),
            'aktiflik_durumu' => true,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->deleteJson(route('api.users.destroy', ['user' => $userB->id]));

        $response->assertStatus(404);

        $this->assertDatabaseHas('users', [
            'id' => $userB->id,
            'email' => 'userb_protected@tenantb.local',
        ]);
    }
}

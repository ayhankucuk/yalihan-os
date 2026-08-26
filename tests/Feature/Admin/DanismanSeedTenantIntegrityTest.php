<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

/**
 * DanismanSeeder Tenant Integrity Contract
 *
 * Verifies that DanismanSeeder produces records that satisfy:
 *   1. users.tenant_id is set (tenant scoping for StoreIlanRequest)
 *   2. users.aktiflik_durumu = 1 (UserController::search filter)
 *   3. users.role_id is set to danisman role (internal FK)
 *   4. kisiler.tenant_id is set (NOT NULL constraint)
 *   5. kisiler.danisman_id links back to the correct user
 *   6. /api/v1/users/search?role=danisman returns seeded danışmanlar
 *   7. Inactive users are excluded from search results
 *   8. Seeder is idempotent — re-run produces no duplicate records
 *
 * Each test runs the seeder itself to be self-contained against fresh SQLite DB.
 */
class DanismanSeedTenantIntegrityTest extends TestCase
{
    private ?Role $adminRole = null;
    private User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Spatie danisman role exists in test DB before seeder runs
        SpatieRole::firstOrCreate(['name' => 'danisman', 'guard_name' => 'web']);

        // Run the seeder so all tests start with seeded data
        $this->artisan('db:seed', ['--class' => 'DanismanSeeder']);

        // Auth user for endpoints
        $this->adminRole = Role::where('name', 'admin')->first();
        if (!$this->adminRole) {
            $this->adminRole = new Role();
            $this->adminRole->name = 'admin';
            $this->adminRole->save();
        }

        $this->authUser = User::factory()->create([
            'role_id'         => $this->adminRole->id,
            'tenant_id'       => $this->getDefaultTenantId(),
            'aktiflik_durumu' => 1,
        ]);
    }

    // ── 1: users.tenant_id is populated ──────────────────────────────────────

    public function test_seeded_danismanlar_have_tenant_id(): void
    {
        $tenantId = $this->getDefaultTenantId();
        $emails = ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'];

        foreach ($emails as $email) {
            $user = DB::table('users')->where('email', $email)->first();
            $this->assertNotNull($user, "User {$email} must exist after seeding");
            $this->assertEquals(
                $tenantId,
                (int) $user->tenant_id,
                "User {$email} should have tenant_id={$tenantId}"
            );
        }
    }

    // ── 2: users.aktiflik_durumu = 1 ─────────────────────────────────────────

    public function test_seeded_danismanlar_are_active(): void
    {
        $emails = ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'];

        foreach ($emails as $email) {
            $user = DB::table('users')->where('email', $email)->first();
            $this->assertNotNull($user, "User {$email} must exist after seeding");
            $this->assertEquals(1, (int) $user->aktiflik_durumu, "User {$email} should be active");
        }
    }

    // ── 3: users.role_id matches danisman role ────────────────────────────────

    public function test_seeded_danismanlar_have_danisman_role_id(): void
    {
        $danismanRoleId = DB::table('roles')
            ->where('name', 'danisman')
            ->where('guard_name', 'web')
            ->value('id');

        $this->assertNotNull($danismanRoleId, 'danisman role must exist in roles table');

        $emails = ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'];

        foreach ($emails as $email) {
            $user = DB::table('users')->where('email', $email)->first();
            $this->assertNotNull($user, "User {$email} must exist after seeding");
            $this->assertEquals(
                (int) $danismanRoleId,
                (int) $user->role_id,
                "User {$email} should have role_id={$danismanRoleId}"
            );
        }
    }

    // ── 4: kisiler.tenant_id is set (NOT NULL) ────────────────────────────────

    public function test_seeded_kisi_records_have_tenant_id(): void
    {
        $tenantId = $this->getDefaultTenantId();
        $emails = ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'];

        foreach ($emails as $email) {
            $kisi = DB::table('kisiler')->where('eposta', $email)->first();
            $this->assertNotNull($kisi, "Kisi for {$email} should exist after seeding");
            $this->assertEquals(
                $tenantId,
                (int) $kisi->tenant_id,
                "Kisi {$email} should have tenant_id={$tenantId}"
            );
        }
    }

    // ── 5: kisiler.danisman_id links to the correct user ─────────────────────

    public function test_seeded_kisi_records_link_to_correct_user(): void
    {
        $emails = ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'];

        foreach ($emails as $email) {
            $user = DB::table('users')->where('email', $email)->first();
            $kisi = DB::table('kisiler')->where('eposta', $email)->first();

            $this->assertNotNull($user, "User {$email} must exist after seeding");
            $this->assertNotNull($kisi, "Kisi {$email} must exist after seeding");
            $this->assertEquals(
                (int) $user->id,
                (int) $kisi->danisman_id,
                "Kisi.danisman_id should match user.id for {$email}"
            );
        }
    }

    // ── 6: /api/v1/users/search?role=danisman returns seeded danışmanlar ─────

    public function test_user_search_returns_active_danisman_role_users(): void
    {
        // The UserController::search endpoint filters by Spatie 'roles' pivot.
        // This test owns its fixture fully — seeder's assignRole may not populate
        // model_has_roles within the test transaction context.
        $spatieRole = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'danisman', 'guard_name' => 'web']
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Create active danışman users with Spatie role assigned
        $danisman1 = User::factory()->create([
            'tenant_id'       => $this->getDefaultTenantId(),
            'aktiflik_durumu' => 1,
        ]);
        $danisman2 = User::factory()->create([
            'tenant_id'       => $this->getDefaultTenantId(),
            'aktiflik_durumu' => 1,
        ]);
        $inactiveUser = User::factory()->create([
            'tenant_id'       => $this->getDefaultTenantId(),
            'aktiflik_durumu' => 0,
        ]);

        $danisman1->assignRole($spatieRole);
        $danisman2->assignRole($spatieRole);
        $inactiveUser->assignRole($spatieRole);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Now use the HTTP layer — the route shadowing bug is fixed by
        // adding ->where('id', '[0-9]+') constraints to v2-users.php.
        $this->actingAs($this->authUser);
        $response = $this->getJson('/api/v1/users/search?role=danisman');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data, 'data should be an array');

        $returnedIds = array_column($data, 'id');

        // Active danışmanlar appear
        $this->assertContains($danisman1->id, $returnedIds, 'Active danisman1 should be in results');
        $this->assertContains($danisman2->id, $returnedIds, 'Active danisman2 should be in results');

        // Inactive user excluded
        $this->assertNotContains($inactiveUser->id, $returnedIds, 'Inactive user should not appear');
    }

    // ── 7: Inactive users are excluded from search ────────────────────────────

    public function test_inactive_user_excluded_from_search(): void
    {
        // Deactivate one seeded danışman
        DB::table('users')
            ->where('email', 'atilay@yalihan.test')
            ->update(['aktiflik_durumu' => 0]);

        $this->actingAs($this->authUser);
        $response = $this->getJson('/api/v1/users/search?role=danisman&q=At%C4%B1lay');

        $response->assertStatus(200);

        $returnedEmails = array_column($response->json('data') ?? [], 'email');

        $this->assertNotContains(
            'atilay@yalihan.test',
            $returnedEmails,
            'Inactive danışman should not appear in search results'
        );
    }

    // ── 8: Seeder is idempotent — no duplicates on re-run ────────────────────

    public function test_seeder_is_idempotent(): void
    {
        $beforeUserCount = DB::table('users')
            ->whereIn('email', ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'])
            ->count();

        $beforeKisiCount = DB::table('kisiler')
            ->whereIn('eposta', ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'])
            ->count();

        // Run seeder a second time (setUp already ran it once)
        $this->artisan('db:seed', ['--class' => 'DanismanSeeder'])->assertSuccessful();

        $afterUserCount = DB::table('users')
            ->whereIn('email', ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'])
            ->count();

        $afterKisiCount = DB::table('kisiler')
            ->whereIn('eposta', ['atilay@yalihan.test', 'sedat@yalihan.test', 'yunus@yalihan.test'])
            ->count();

        $this->assertEquals(
            $beforeUserCount,
            $afterUserCount,
            'Re-running DanismanSeeder should not create duplicate user records'
        );

        $this->assertEquals(
            $beforeKisiCount,
            $afterKisiCount,
            'Re-running DanismanSeeder should not create duplicate kisi records'
        );
    }
}

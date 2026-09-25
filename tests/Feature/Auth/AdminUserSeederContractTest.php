<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TenantBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed foundational baseline data
        $this->seed(TenantBaselineSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_seeder_source_contains_no_literal_password(): void
    {
        $seederSource = file_get_contents(database_path('seeders/AdminUserSeeder.php'));

        $this->assertStringNotContainsString('admin123', $seederSource);
        $this->assertStringNotContainsString('password123', $seederSource);
        $this->assertStringNotContainsString("'secret'", $seederSource);
        $this->assertStringNotContainsString('"secret"', $seederSource);
    }

    public function test_seeder_fails_closed_when_credential_is_unconfigured(): void
    {
        Config::set('auth.admin_password', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_LOCAL_PASSWORD (config auth.admin_password) is not configured');

        $this->seed(AdminUserSeeder::class);
    }

    public function test_seeder_fails_closed_when_credential_is_whitespace_only(): void
    {
        Config::set('auth.admin_password', '   ');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_LOCAL_PASSWORD (config auth.admin_password) is not configured');

        $this->seed(AdminUserSeeder::class);
    }

    public function test_seeder_fails_closed_in_production_environment(): void
    {
        Config::set('auth.admin_password', 'Valid_Secret_123!');
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AdminUserSeeder cannot be executed in production environment');

        (new AdminUserSeeder())->run();
    }

    public function test_seeder_succeeds_and_persists_canonical_spatie_pivot_and_auth(): void
    {
        $testPassword = 'Test_Secure_Bootstrap_Credential_2026!';
        Config::set('auth.admin_password', $testPassword);

        $this->seed(AdminUserSeeder::class);

        $users = User::whereIn('email', ['ayhankucuk@gmail.com', 'yalihanemlak@gmail.com'])->get();
        $this->assertCount(2, $users);

        // Prove exactly 2 pivot assignments exist in model_has_roles
        $this->assertEquals(2, DB::table('model_has_roles')->count());

        foreach ($users as $user) {
            // 1. Password stored as valid bcrypt hash
            $this->assertNotEquals($testPassword, $user->password);
            $this->assertTrue(Hash::check($testPassword, $user->password));

            // 2. Tenant ID and role_id compatibility values are valid
            $this->assertEquals(1, $user->tenant_id);
            $this->assertEquals(1, $user->role_id);

            // 3. Direct Spatie Pivot Persistence (cannot fall back to legacy role_id)
            $this->assertTrue($user->roles()->where('name', 'super-admin')->exists());
            $this->assertContains('super-admin', $user->getRoleNames()->map(fn ($r) => strtolower(trim($r)))->toArray());
            $this->assertDatabaseHas('model_has_roles', [
                'model_id' => $user->id,
                'role_id' => 1,
                'model_type' => $user->getMorphClass(),
            ]);

            // 4. Invalid credential fails Auth::attempt
            $this->assertFalse(Auth::attempt([
                'email' => $user->email,
                'password' => 'Wrong_Password_Attempt_123',
            ]));

            // 5. Valid credential succeeds Auth::attempt
            $this->assertTrue(Auth::attempt([
                'email' => $user->email,
                'password' => $testPassword,
            ]));

            Auth::logout();
        }
    }

    public function test_seeder_is_idempotent_and_does_not_duplicate_pivot_assignments(): void
    {
        $testPassword = 'Test_Secure_Bootstrap_Credential_2026!';
        Config::set('auth.admin_password', $testPassword);

        // Run 1st time
        $this->seed(AdminUserSeeder::class);
        $this->assertEquals(2, User::count());
        $this->assertEquals(2, DB::table('model_has_roles')->count());

        // Run 2nd time
        $this->seed(AdminUserSeeder::class);
        $this->assertEquals(2, User::count());
        $this->assertEquals(2, DB::table('model_has_roles')->count());
    }
}

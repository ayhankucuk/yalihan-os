<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TenantBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
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

    public function test_seeder_succeeds_with_configured_credential_and_validates_auth_and_roles(): void
    {
        $testPassword = 'Test_Secure_Bootstrap_Credential_2026!';
        Config::set('auth.admin_password', $testPassword);

        $this->seed(AdminUserSeeder::class);

        $users = User::whereIn('email', ['ayhankucuk@gmail.com', 'yalihanemlak@gmail.com'])->get();
        $this->assertCount(2, $users);

        foreach ($users as $user) {
            // 1. Password stored as valid bcrypt hash
            $this->assertNotEquals($testPassword, $user->password);
            $this->assertTrue(Hash::check($testPassword, $user->password));

            // 2. Tenant ID is valid
            $this->assertEquals(1, $user->tenant_id);

            // 3. Spatie super-admin role assigned
            $this->assertTrue($user->hasRole('super-admin'));

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
}

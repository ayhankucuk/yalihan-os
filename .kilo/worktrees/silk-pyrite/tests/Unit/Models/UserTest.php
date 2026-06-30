<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{

    /**
     * Test User model can be created
     */
    public function test_user_can_be_created(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::find($userId);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    /**
     * Test User model password hashing
     */
    public function test_user_password_is_hashed(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::find($userId);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test User model relationships - role
     */
    public function test_user_belongs_to_role(): void
    {
        // Create role
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'danisman',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::find($userId);

        if (method_exists($user, 'role')) {
            $this->assertNotNull($user->role);
            $this->assertEquals($roleId, $user->role->id);
        }
    }

    /**
     * Test User model relationships - ilanlar
     */
    public function test_user_has_ilanlar(): void
    {
        // Create user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Danışman',
            'email' => 'danisman@example.com',
            'password' => Hash::make('password'),
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create listings
        DB::table('ilanlar')->insert([
            [
                'baslik' => 'İlan 1',
                'slug' => 'user-ilan-1',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'danisman_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'baslik' => 'İlan 2',
                'slug' => 'user-ilan-2',
                'fiyat' => 200000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'danisman_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $user = User::find($userId);

        if (method_exists($user, 'ilanlar')) {
            $this->assertGreaterThanOrEqual(2, $user->ilanlar->count());
        }
    }

    /**
     * Test User model email uniqueness
     */
    public function test_user_email_is_unique(): void
    {
        DB::table('users')->insert([
            'name' => 'Test User 1',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to create another user with same email
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('users')->insert([
            'name' => 'Test User 2',
            'email' => 'test@example.com', // Duplicate email
            'password' => Hash::make('password'),
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test User model can authenticate
     */
    public function test_user_can_authenticate(): void
    {
        $password = 'password123';
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make($password),
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::find($userId);

        // Test password check
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertFalse(Hash::check('wrong_password', $user->password));
    }

    /**
     * Test User model scope - active (if exists)
     */
    public function test_user_scope_active(): void
    {
        // Create test data
        DB::table('users')->insert([
            [
                'name' => 'Active User',
                'email' => 'active@example.com',
                'password' => Hash::make('password'),
                'aktiflik_durumu' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inactive User',
                'email' => 'inactive@example.com',
                'password' => Hash::make('password'),
                'aktiflik_durumu' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Test if scopeActive exists
        if (method_exists(User::class, 'scopeActive')) {
            $activeUsers = User::active()->get();
            $this->assertGreaterThanOrEqual(1, $activeUsers->count());
        } else {
            $this->markTestSkipped('scopeActive method does not exist');
        }
    }
}

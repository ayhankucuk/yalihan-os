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

        // Create listings — tenant_id is required because Ilan model uses
        // BelongsToTenant trait + TenantScope, which filters by tenant_id
        // when TenantContextService has a tenant set (injected by TestCase::setUp).
        $tenantId = $this->getDefaultTenantId();
        DB::table('ilanlar')->insert([
            [
                'baslik' => 'İlan 1',
                'slug' => 'user-ilan-1',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'danisman_id' => $userId,
                'tenant_id' => $tenantId,
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
                'tenant_id' => $tenantId,
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

    // =========================================================================
    // CDH-002 Regression Tests — aktiflik_durumu canonical alignment
    // =========================================================================

    /**
     * Test: aktiflik_durumu is mass-assignable via $fillable
     * Proof: User::make() with aktiflik_durumu preserves the attribute
     */
    public function test_aktiflik_durumu_is_mass_assignable(): void
    {
        $user = User::make([
            'name' => 'Aktiflik Test User',
            'email' => 'aktiflik-test@example.com',
            'password' => Hash::make('password'),
            'aktiflik_durumu' => true,
        ]);

        $this->assertArrayHasKey('aktiflik_durumu', $user->getAttributes());
        $this->assertEquals(true, $user->aktiflik_durumu);
    }

    /**
     * Test: aktiflik_durumu=false mass assignment preserves false state
     * Proof: fill() does not silently drop the attribute
     */
    public function test_aktiflik_durumu_false_is_preserved(): void
    {
        $user = new User();
        $user->fill(['aktiflik_durumu' => false]);

        $this->assertFalse($user->aktiflik_durumu);
    }

    /**
     * Test: aktiflik_durumu=true mass assignment preserves true state
     * Proof: fill() works for the canonical active state
     */
    public function test_aktiflik_durumu_true_is_preserved(): void
    {
        $user = new User();
        $user->fill(['aktiflik_durumu' => true]);

        $this->assertTrue($user->aktiflik_durumu);
    }

    /**
     * Test: aktiflik_durumu boolean cast returns correct semantics
     * Proof: accessing the attribute returns boolean type
     */
    public function test_aktiflik_durumu_cast_returns_boolean(): void
    {
        // Create user with aktiflik_durumu = 0
        $userId = DB::table('users')->insertGetId([
            'name' => 'Boolean Cast Test',
            'email' => 'boolean-cast-' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'aktiflik_durumu' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::find($userId);
        $this->assertIsBool($user->aktiflik_durumu);
        $this->assertFalse($user->aktiflik_durumu);

        // Update to aktiflik_durumu = 1
        $user->update(['aktiflik_durumu' => true]);
        $user->refresh();

        $this->assertIsBool($user->aktiflik_durumu);
        $this->assertTrue($user->aktiflik_durumu);
    }

    /**
     * Test: legacy is_active is NOT mass-assignable
     * Proof: is_active is removed from $fillable, mass assignment should fail silently
     *        (attribute not in model, no exception - just not persisted)
     */
    public function test_is_active_legacy_not_mass_assignable(): void
    {
        $fillable = (new User())->getFillable();

        $this->assertNotContains('is_active', $fillable);
        $this->assertContains('aktiflik_durumu', $fillable);
    }

    /**
     * Test: Context7 guard blocks legacy is_active during create
     * Proof: is_active in $globalForbiddenFields throws Context7ViolationException
     */
    public function test_context7_guard_blocks_is_active_on_create(): void
    {
        $this->expectException(\App\Exceptions\Context7ViolationException::class);

        User::create([
            'name' => 'Context7 Guard Test',
            'email' => 'context7-' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'is_active' => 1, // Legacy forbidden field
        ]);
    }

    /**
     * Test: aktiflik_durumu write via canonical mass assignment
     * Proof: full create + retrieve cycle for canonical field
     */
    public function test_aktiflik_durumu_create_and_retrieve(): void
    {
        $user = User::create([
            'name' => 'Canonical Aktiflik Test',
            'email' => 'canonical-' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'aktiflik_durumu' => false,
        ]);

        $this->assertFalse($user->aktiflik_durumu);

        // Retrieve from DB
        $retrieved = User::find($user->id);
        $this->assertFalse($retrieved->aktiflik_durumu);

        // Toggle to active
        $retrieved->update(['aktiflik_durumu' => true]);
        $retrieved->refresh();

        $this->assertTrue($retrieved->aktiflik_durumu);
    }
}

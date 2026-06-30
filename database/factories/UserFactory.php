<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // role_id dinamik atanacak; varsayılanı admin yerine null bırakıyoruz.
            // Test senaryolarında state() ile spesifik rol set edilecek.
            'role_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function danisman(): static
    {
        return $this->afterCreating(function ($user) {
            // Legacy single role tablosu için bir "danisman" kaydı bul veya oluştur
            $roleModelClass = Role::class;
            $role = $roleModelClass::where('name', 'danisman')->first();
            if (! $role) {
                $role = new $roleModelClass;
                $role->name = 'danisman';
                $role->guard_name = 'web';
                $role->description = 'Danışman';
                $role->saveQuietly();
            }
            $user->role()->associate($role);
            $user->saveQuietly();
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            $roleModelClass = Role::class;
            $role = $roleModelClass::where('name', 'admin')->first();
            if (! $role) {
                $role = new $roleModelClass;
                $role->name = 'admin';
                $role->description = 'Admin';
                $role->saveQuietly();
            }
            $user->role()->associate($role);
            $user->saveQuietly();
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('admin');
            }
        });
    }

    public function editor(): static
    {
        return $this->afterCreating(function ($user) {
            $roleModelClass = Role::class;
            $role = $roleModelClass::where('name', 'editor')->first();
            if (! $role) {
                $role = new $roleModelClass;
                $role->name = 'editor';
                $role->description = 'Editor';
                $role->saveQuietly();
            }
            $user->role()->associate($role);
            $user->saveQuietly();
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('editor');
            }
        });
    }

    /**
     * Owner rolü ile kullanıcı.
     */
    public function owner(): static
    {
        return $this->afterCreating(function ($user) {
            $roleModelClass = Role::class;
            $role = $roleModelClass::where('name', 'owner')->first();
            if (! $role) {
                $role = new $roleModelClass;
                $role->name = 'owner';
                $role->description = 'Owner';
                $role->saveQuietly();
            }
            $user->role()->associate($role);
            $user->saveQuietly();
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('owner');
            }
        });
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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

    /**
     * Danışman rolü ile kullanıcı oluştur.
     */
    public function danisman(): static
    {
        return $this->afterCreating(function ($user) {
            // Legacy single role tablosu için bir "danisman" kaydı bul veya oluştur
            $roleModelClass = \App\Modules\Auth\Models\Role::class;
            $role = $roleModelClass::firstOrCreate(
                ['name' => 'danisman', 'guard_name' => 'web'],
                ['description' => 'Danışman']
            );
            $user->role()->associate($role);
            $user->saveQuietly();
        });
    }

    /**
     * Admin rolü ile kullanıcı.
     */
    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            $roleModelClass = \App\Modules\Auth\Models\Role::class;
            $role = $roleModelClass::firstOrCreate(['name' => 'admin'], ['description' => 'Admin']);
            $user->role()->associate($role);
            $user->saveQuietly();
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('admin');
            }
        });
    }

    /**
     * Editor rolü ile kullanıcı.
     */
    public function editor(): static
    {
        return $this->afterCreating(function ($user) {
            $roleModelClass = \App\Modules\Auth\Models\Role::class;
            $role = $roleModelClass::firstOrCreate(['name' => 'editor'], ['description' => 'Editor']);
            $user->role()->associate($role);
            $user->saveQuietly();
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('editor');
            }
        });
    }

    /**
     * Owner (sahip) rolü ile kullanıcı.
     */
    public function owner(): static
    {
        return $this->afterCreating(function ($user) {
            $roleModelClass = \App\Modules\Auth\Models\Role::class;
            $role = $roleModelClass::firstOrCreate(['name' => 'owner'], ['description' => 'Owner']);
            $user->role()->associate($role);
            $user->saveQuietly();
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('owner');
            }
        });
    }
}

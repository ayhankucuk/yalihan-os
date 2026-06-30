<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'ayhankucuk@gmail.com'],
            [
                'name' => 'Ayhan Küçük',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'ulke_id' => 1, // Default (TR)
            ]
        );

        // Yalıhan Emlak super-admin
        $yalihan = User::firstOrCreate(
            ['email' => 'yalihanemlak@gmail.com'],
            [
                'name' => 'Yalıhan Emlak',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'ulke_id' => 1, // Default (TR)
            ]
        );

        // Ensure super-admin role exists and assign to both users
        if (class_exists(Role::class)) {
            $role = Role::firstOrCreate(['name' => 'super-admin']);

            foreach ([$admin, $yalihan] as $user) {
                if (!$user->hasRole('super-admin')) {
                    $user->assignRole($role);
                }
            }
        }
    }
}

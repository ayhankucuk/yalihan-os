<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

// Create admin role if not exists
if (!Role::where('name', 'admin')->exists()) {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    echo "✅ Admin role created\n";
}

// Create user
$user = User::firstOrCreate(
    ['email' => 'ayhankucuk@gmail.com'],
    [
        'name' => 'Ayhan Küçük',
        'password' => Hash::make('admin123'),
        'email_verified_at' => now(),
    ]
);

// Assign role
if (!$user->hasRole('admin')) {
    $user->assignRole('admin');
}

echo "\n✅ KULLANICI HAZIR!\n";
echo "ID: {$user->id}\n";
echo "Email: {$user->email}\n";
echo "Password: admin123\n";
echo "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";

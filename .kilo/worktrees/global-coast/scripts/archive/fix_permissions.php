<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create permissions
$permissions = [
    'view-admin-panel',
    'view dashboard',
    'manage users',
    'manage properties',
    'manage listings',
    'manage crm',
    'manage settings',
    'edit-ilanlar',
    'manage-ilanlar',
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
}

echo "✅ Permissions created\n";

// Create super-admin role
$superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
$superAdmin->givePermissionTo($permissions);

echo "✅ Super-admin role created\n";

// Get user
$user = User::where('email', 'ayhankucuk@gmail.com')->first();

if ($user) {
    // Assign role
    $user->assignRole('super-admin');
    
    // Give all permissions directly too
    $user->givePermissionTo($permissions);
    
    echo "\n✅ USER READY!\n";
    echo "Email: {$user->email}\n";
    echo "Password: admin123\n";
    echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
    echo "Permissions: " . $user->getAllPermissions()->pluck('name')->implode(', ') . "\n";
} else {
    echo "❌ User not found!\n";
}

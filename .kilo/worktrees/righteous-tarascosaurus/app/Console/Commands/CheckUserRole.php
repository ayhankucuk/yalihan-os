<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

class CheckUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-role {email? : User email to check} {--fix : Automatically fix missing roles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and fix user role assignment for admin panel access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("User with email '{$email}' not found.");
                return 1;
            }
            $this->checkUser($user);
        } else {
            // Check all users
            $users = User::with('role')->get();
            $this->info("Checking {$users->count()} users...");
            $this->newLine();
            
            foreach ($users as $user) {
                $this->checkUser($user);
            }
        }
        
        return 0;
    }
    
    private function checkUser(User $user)
    {
        $this->line("User: {$user->email} (ID: {$user->id})");
        
        // Check if user has role
        if (!$user->role) {
            $this->warn("  ⚠️  No role assigned!");
            
            // Try to find a role
            $superadminRole = Role::where('name', 'superadmin')
                ->orWhere('name', 'Süper Admin')
                ->orWhere('name', 'admin')
                ->first();
            
            if ($superadminRole) {
                $shouldAssign = $this->option('fix') || $this->confirm("  Assign '{$superadminRole->name}' role to this user?", true);
                if ($shouldAssign) {
                    $user->role_id = $superadminRole->id;
                    $user->save();
                    $this->info("  ✅ Role assigned successfully!");
                }
            } else {
                $this->error("  ❌ No suitable role found in database.");
                $this->line("  Available roles:");
                Role::all()->each(function ($role) {
                    $this->line("    - {$role->name} (ID: {$role->id})");
                });
            }
        } else {
            $roleName = $user->role->name;
            $this->line("  Role: {$roleName}");
            
            // Check if role name matches enum values
            $normalizedRole = strtolower(trim($roleName));
            $allowedRoles = [
                'superadmin',
                'süper admin',
                'süperadmin',
                'admin',
                'danisman',
                'danışman',
                'editor',
                'editör',
            ];
            
            if (in_array($normalizedRole, $allowedRoles)) {
                $this->info("  ✅ Role is valid for admin panel access");
            } else {
                $this->warn("  ⚠️  Role name '{$roleName}' may not be recognized by the gate");
            }
        }
        
        // Test gate access
        $canAccess = Gate::forUser($user)->allows('view-admin-panel');
        if ($canAccess) {
            $this->info("  ✅ User CAN access admin panel");
        } else {
            $this->error("  ❌ User CANNOT access admin panel");
        }
        
        $this->newLine();
    }
}


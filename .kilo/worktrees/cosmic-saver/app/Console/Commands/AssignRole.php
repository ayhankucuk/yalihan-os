<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

class AssignRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role {email : User email} {role : Role name (superadmin, danisman, editor)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a role to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');
        
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }
        
        // Find role (try multiple variations)
        $role = Role::where('name', $roleName)
            ->orWhere('name', ucfirst($roleName))
            ->orWhere('name', strtolower($roleName))
            ->first();
        
        if (!$role) {
            $this->error("Role '{$roleName}' not found.");
            $this->line("Available roles:");
            Role::all()->each(function ($r) {
                $this->line("  - {$r->name} (ID: {$r->id})");
            });
            return 1;
        }
        
        $user->role_id = $role->id;
        $user->save();
        
        $this->info("✅ Role '{$role->name}' assigned to user '{$email}' successfully!");
        
        // Test gate access
        $canAccess = Gate::forUser($user)->allows('view-admin-panel');
        if ($canAccess) {
            $this->info("✅ User can now access admin panel");
        } else {
            $this->warn("⚠️  User still cannot access admin panel. Check role name matches enum values.");
        }
        
        return 0;
    }
}


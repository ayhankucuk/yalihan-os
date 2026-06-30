<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        if (!$request->user()) {
            return redirect('login');
        }

        // Map role names to role IDs
        $roleMap = [
            'superadmin' => 1,
            'admin' => 1,
            'danisman' => 2,
            'editor' => 3,
            'user' => 4,
        ];

        // Get user's role ID
        $userRoleId = $request->user()->role_id;
        $userRoleName = array_search($userRoleId, $roleMap, true) ?: 'user';

        // Check if user has one of the required roles
        foreach ($roles as $role) {
            $requiredRoleId = $roleMap[$role] ?? null;
            
            if ($userRoleId === $requiredRoleId) {
                return $next($request);
            }
        }

        // User doesn't have required role
        abort(403, 'Unauthorized');
    }
}

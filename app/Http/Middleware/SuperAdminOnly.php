<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $allowed = ['Süper Admin', 'superadmin', 'süper admin', 'admin'];
        
        $hasAccess = $user->hasAnyRole($allowed);
        
        if (!$hasAccess && $user->role) {
            $roleName = strtolower(trim($user->role->name));
            if (in_array($roleName, array_map('strtolower', $allowed))) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Bu sayfaya sadece süper admin erişebilir.');
        }

        return $next($request);
    }
}

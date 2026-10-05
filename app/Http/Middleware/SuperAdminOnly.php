<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminOnly
{
    /**
     * Handle an incoming request.
     * SUPER-ADMIN ONLY — ordinary admin does NOT pass.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // SECURITY FIX: Canonical super-admin ONLY via Spatie
        // ordinary 'admin' does NOT pass this middleware
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        abort(403, 'Bu sayfaya sadece süper admin erişebilir.');
    }
}

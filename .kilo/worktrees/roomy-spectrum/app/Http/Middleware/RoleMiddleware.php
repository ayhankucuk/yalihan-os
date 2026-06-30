<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Gelen isteği belirtilen rollere göre kontrol eder.
     *
     * Bu middleware, Spatie\Permission\Middleware\RoleMiddleware üzerine yapılandırılmıştır
     * ancak özel işlemler için ek fonksiyonlar eklenebilir.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|array  $role
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (! Auth::check()) {
            // Oturum açılmamışsa login sayfasına yönlendir.
            return redirect()->route('login');
        }

        $roles = is_array($role) ? $role : explode('|', $role);
        $normalizedRequested = array_map('strtolower', array_map('trim', $roles));

        $user = Auth::user();

        // 1. Spatie check (case-insensitive)
        $userSpatieRoles = $user->getRoleNames()->map(fn($r) => strtolower(trim($r)))->toArray();

        // super-admin tüm rol kontrollerini geçer
        if (in_array('super-admin', $userSpatieRoles, true)) {
            return $next($request);
        }

        $hasRole = count(array_intersect($userSpatieRoles, $normalizedRequested)) > 0;

        // 2. Legacy check
        if (!$hasRole && $user->role) {
            $userRoleName = strtolower(trim($user->role->name));
            if (in_array($userRoleName, $normalizedRequested)) {
                $hasRole = true;
            }
        }

        if (!$hasRole) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }
}

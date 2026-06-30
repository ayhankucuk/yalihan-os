<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Spatie Permission ile rol kontrolü (Büyük-küçük harf ve Türkçe karakter duyarlılığı için normalize ediyoruz)
        $allowedRoles = [
            'superadmin', 'super-admin', 'süper-admin', 'süper admin',
            'admin', 'danisman',
            'Super Admin', 'Super-Admin', 'Süper Admin',
            'Admin', 'Danışman',
        ];

        $userRoles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn($role) => mb_strtolower($role, 'UTF-8'))->toArray()
            : [];

        $normalizedAllowedRoles = array_map(fn($role) => mb_strtolower($role, 'UTF-8'), $allowedRoles);

        $hasAllowedRole = count(array_intersect($userRoles, $normalizedAllowedRoles)) > 0;

        // Fallback for legacy role field or Context7 'rol' accessor
        if (!$hasAllowedRole) {
            $roleName = null;
            if (isset($user->rol)) {
                $roleName = mb_strtolower($user->rol, 'UTF-8');
            } elseif ($user->role) {
                $roleName = mb_strtolower($user->role->name, 'UTF-8');
            }

            if ($roleName && in_array($roleName, $normalizedAllowedRoles)) {
                $hasAllowedRole = true;
            }
        }

        if (!$hasAllowedRole) {
            Log::warning('AdminMiddleware: Access Denied (403)', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $userRoles,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Bu sayfaya erişim yetkiniz bulunmamaktadır.'], 403);
            }
            abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
        }

        return $next($request);
    }
}

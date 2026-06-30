<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureManagePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            abort(403, 'Giriş gerekli');
        }
        $user = auth()->user();
        if (! $user->can('viewAny', \App\Models\Feature::class)) {
            \Log::warning('Feature manage yetkisi reddedildi', [
                'user_id' => $user->id,
                'roles_method_any' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : null,
                'simple_role' => $user->role->name ?? null,
            ]);
            abort(403, 'Yetki yok');
        }

        return $next($request);
    }
}

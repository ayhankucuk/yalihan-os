<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckOwner Middleware
 *
 * /owner/* route'larını korur.
 * Kullanıcı giriş yapmamışsa owner login sayfasına yönlendirir.
 * Giriş yapmış ama 'owner' rolü yoksa 403 döner.
 *
 * super-admin tüm kontrolleri geçer (geliştirme/destek erişimi).
 *
 * SAB v6.1.2 — Owner Portal sprint.
 */
class CheckOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        // Giriş yoksa → owner login sayfasına
        if (! Auth::check()) {
            return redirect()->route('owner.login')
                ->with('bilgi', 'Devam etmek için giriş yapmanız gerekiyor.');
        }

        $user = Auth::user();

        // super-admin tüm kısıtlamaları geçer
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // owner rolü yoksa → 403
        if (! $user->hasRole('owner')) {
            abort(403, 'Bu alana erişim yetkiniz bulunmuyor.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware AppStateVersionMiddleware
 * 
 * Tüm JSON response'lara X-App-State-Version header'ı ekler.
 */
class AppStateVersionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Sadece JSON response'lara ve başarılı isteklere ekle
        if ($request->expectsJson()) {
            $version = Cache::get('app_state_version', now()->timestamp);
            $response->headers->set('X-App-State-Version', $version);
        }

        return $response;
    }
}

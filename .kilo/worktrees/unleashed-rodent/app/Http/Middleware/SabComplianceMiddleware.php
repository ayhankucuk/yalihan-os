<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SabComplianceMiddleware
{
    /**
     * Handle an incoming request.
     * SAB v1.3: Bu middleware API response'larının Context7 ve SAB standartlarına uyumluluğunu kontrol eder.
     * Runtime kontrolleri eklenebilir. Şu an sadece pass-through yapıyor.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}

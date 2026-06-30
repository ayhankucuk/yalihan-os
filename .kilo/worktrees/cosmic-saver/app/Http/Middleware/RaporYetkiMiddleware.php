<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rapor Yetki Middleware
 * 
 * [YALIHAN_REPORTING_0206]
 * - Signed URL doğrulama
 * - Hash verification
 * - Invalidation check (410 Gone)
 * - Policy check
 */
class RaporYetkiMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Signed URL kontrolü
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature.');
        }

        // 2. İlan al
        $ilan = $request->route('ilan');
        
        if (!$ilan) {
            abort(404, 'Listing not found.');
        }

        // 3. Hash verification
        $requestHash = $request->query('hash');
        
        if ($requestHash !== $ilan->rapor_hash) {
            abort(404, 'Report not found or hash mismatch.');
        }

        // 4. Geçersizleştirilmiş rapor kontrolü
        if ($ilan->rapor_gecersiz_mi) {
            abort(410, 'This report has been invalidated.');
        }

        // 5. Rapor var mı?
        if (!$ilan->rapor_yolu) {
            abort(404, 'Report not yet generated.');
        }

        // 6. Policy check (optional, signed URL usually enough)
        if (auth()->check() && Gate::denies('viewIlanRaporu', $ilan)) {
            abort(403, 'Unauthorized to view this report.');
        }

        return $next($request);
    }
}

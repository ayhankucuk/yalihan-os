<?php

namespace App\Http\Middleware\SAB;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlobalWriteGuard
{
    /**
     * SAB v12 Global Write Interceptor
     * Hedef: Admin rotalarında yazma işlemlerini denetlemek ve yetkilendirme boşluklarını saptamak.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Sadece admin rotalarında ve yazma (mutating) isteklerinde çalış
        if ($this->isAdminRoute($request) && $this->isMutating($request)) {
            $this->auditPolicyCoverage($request);
        }

        return $response;
    }

    private function isAdminRoute(Request $request): bool
    {
        return str_contains($request->getPathInfo(), '/admin');
    }

    private function isMutating(Request $request): bool
    {
        return in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    private function auditPolicyCoverage(Request $request)
    {
        // 1. Controller@method bilgisini al
        $action = $request->route()?->getActionName();

        // 2. Eğer authorize() çağrılmamışsa (log bazlı veya heuristic bazlı kontrol) uyar.
        // Şimdilik sadece "Policy Denetimi Aktif" logu düşüyoruz.
        // İleride burada Gate::check() veya Policy heuristic çalıştırılabilir.

        Log::channel('sab')->info('SAB_WRITE_INTERCEPTOR', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'action' => $action,
            'user_id' => auth()->id() ?? 'guest',
            'ip' => $request->ip()
        ]);
    }
}

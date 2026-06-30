<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetTenantContext Middleware
 *
 * SAB Kural #1 — Tenant Isolation HTTP Enforcer
 *
 * Her kimlik doğrulanmış API isteğinde kiracı (tenant) bağlamını kurar.
 * Bu middleware auth:sanctum'dan SONRA çalışmalıdır; kullanıcının
 * tenant_id alanından Tenant modelini yükler ve TenantContextService'e
 * bağlar. Böylece servis katmanındaki tüm tenant ID kontrolleri
 * tutarlı ve güvenilir hale gelir.
 *
 * Gereksinim: User modeli 'tenant_id' alanına sahip olmalıdır.
 *
 * Fix: #49 — SAB Kural #1 HTTP katmanında uygulanıyor (2026-05-15)
 */
class SetTenantContext
{
    public function __construct(
        private readonly TenantContextService $tenantContextService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Kimlik doğrulaması yapılmamışsa (guest route) bağlam kurulmaz
        if (!$user) {
            return $next($request);
        }

        // tenant_id alanı yoksa veya null ise: ihlal — erişimi reddet
        if (empty($user->tenant_id)) {
            Log::channel('governance_security')->critical('SAB_KURAL_1_IHLAL: tenant_id eksik', [
                'user_id' => $user->id,
                'uri'     => $request->getRequestUri(),
                'ip'      => $request->ip(),
            ]);

            return response()->json([
                'hata'       => 'Kiracı bağlamı kurulamadı.',
                'hata_kodu'  => 'TENANT_CONTEXT_MISSING',
            ], 403);
        }

        // Tenant modelini yükle — Redis cache ile (Fix #68: N+1 önlendi)
        $tenant = \Illuminate\Support\Facades\Cache::remember(
            "tenant:{$user->tenant_id}",
            300, // 5 dakika
            fn() => Tenant::find($user->tenant_id)
        );

        if (!$tenant) {
            Log::channel('governance_security')->critical('SAB_KURAL_1_IHLAL: Tenant bulunamadı', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'uri'       => $request->getRequestUri(),
            ]);

            return response()->json([
                'hata'      => 'Geçersiz kiracı bağlamı.',
                'hata_kodu' => 'TENANT_NOT_FOUND',
            ], 403);
        }

        // Kiracı bağlamını global olarak kur
        $this->tenantContextService->setTenant($tenant);

        return $next($request);
    }
}

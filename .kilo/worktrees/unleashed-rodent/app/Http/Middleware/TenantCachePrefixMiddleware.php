<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SaaS\TenantContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantCachePrefixMiddleware
 *
 * Dynamic Cache Isolation: Rewrites global cache prefix for active tenant context.
 * Resolves Cache Isolation vulnerability identified in Phase 18 Audit.
 */
class TenantCachePrefixMiddleware
{
    public function __construct(
        private readonly TenantContextService $tenantContextService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenantContextService->hasTenant()) {
            $tenantId = $this->tenantContextService->getTenant()->id;
            
            $originalPrefix = config('cache.prefix', 'laravel_cache');
            
            // Build tenant-isolated prefix
            $tenantPrefix = 'tenant_' . $tenantId . '_' . $originalPrefix;
            
            // 1. Update config so future resolutions use the prefix
            config(['cache.prefix' => $tenantPrefix]);
            
            // 2. Dynamically update the default cache store prefix if already resolved
            try {
                $store = Cache::driver();
                if (method_exists($store, 'setPrefix')) {
                    $store->setPrefix($tenantPrefix);
                } elseif (method_exists($store, 'getStore') && method_exists($store->getStore(), 'setPrefix')) {
                    $store->getStore()->setPrefix($tenantPrefix);
                }
            } catch (\Throwable $e) {
                // Graceful fallback if driver/store doesn't support prefix modification
            }
        }

        return $next($request);
    }
}

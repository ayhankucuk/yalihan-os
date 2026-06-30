<?php

namespace App\Http\Middleware\SaaS;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionMiddleware
 * 🛡️ SAB §12.3: Subscription & Feature Entitlements
 */
class SubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        $tenant = $request->user()?->tenant;

        if (!$tenant) {
            return response()->json(['error' => 'Tenant context missing.'], 403);
        }

        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return response()->json(['error' => 'Aktif bir abonelik bulunamadı.'], 402);
        }

        // Feature Entitlement Check
        if ($feature) {
            $plan = $subscription->plan;
            $features = $plan->features ?? [];

            if (!in_array($feature, $features) && !isset($features[$feature])) {
                Log::warning("Tenant [{$tenant->id}] attempted to access unauthorized feature [{$feature}].");
                return response()->json(['error' => "Bu özellik [{$feature}] mevcut planınıza dahil değildir."], 403);
            }
        }

        return $next($request);
    }
}

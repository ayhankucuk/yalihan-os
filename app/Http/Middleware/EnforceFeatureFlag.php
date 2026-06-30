<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SaaS\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceFeatureFlag
{
    public function __construct(
        protected FeatureFlagService $flagService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $flag
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $flag): Response
    {
        $user = $request->user();

        if (!$this->flagService->isEnabled($flag, $user)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu özellik şu anda aktif değildir.',
                    'meta' => [
                        'hata_kodu' => 'FEATURE_DISABLED',
                        'flag' => $flag,
                    ]
                ], 403);
            }

            abort(403, "Bu özellik şu anda aktif değildir.");
        }

        return $next($request);
    }
}

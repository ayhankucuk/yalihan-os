<?php

namespace App\Http\Middleware;

use App\Services\Cache\CacheHelper;
use Closure;
use Illuminate\Http\Request;

class ApiMetricsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('__start_ts', microtime(true));

        return $next($request);
    }

    public function terminate($request, $response): void
    {
        $start = $request->attributes->get('__start_ts');
        if (! $start) {
            return;
        }
        $duration = (int) (((microtime(true) - $start) * 1000));
        $minute = now()->format('YmdHi');

        $sum = (int) CacheHelper::get('api', 'duration_sum', 0, ['minute' => $minute]);
        CacheHelper::put('api', 'duration_sum', $sum + $duration, 'very_short', ['minute' => $minute]);

        $count = (int) CacheHelper::get('api', 'duration_count', 0, ['minute' => $minute]);
        CacheHelper::put('api', 'duration_count', $count + 1, 'very_short', ['minute' => $minute]);

        $rpm = (int) CacheHelper::get('api', 'rpm', 0, ['minute' => $minute]);
        CacheHelper::put('api', 'rpm', $rpm + 1, 'very_short', ['minute' => $minute]);

        $httpStatusCode = (int) ($response?->getStatusCode() ?? 0);
        if ($httpStatusCode >= 200 && $httpStatusCode < 400) {
            $succ = (int) CacheHelper::get('api', 'success_count', 0, ['minute' => $minute]);
            CacheHelper::put('api', 'success_count', $succ + 1, 'very_short', ['minute' => $minute]);
        } else {
            $err = (int) CacheHelper::get('api', 'error_count', 0, ['minute' => $minute]);
            CacheHelper::put('api', 'error_count', $err + 1, 'very_short', ['minute' => $minute]);
        }

        // Percentile buckets (ms): [0-100, 100-200, 200-500, 500-1000, 1000+]
        $bucketKey = function (int $d): string {
            if ($d < 100) {
                return 'b_0_100';
            }
            if ($d < 200) {
                return 'b_100_200';
            }
            if ($d < 500) {
                return 'b_200_500';
            }
            if ($d < 1000) {
                return 'b_500_1000';
            }

            return 'b_1000_plus';
        };
        $bk = $bucketKey($duration);
        $current = (int) CacheHelper::get('api', $bk, 0, ['minute' => $minute]);
        CacheHelper::put('api', $bk, $current + 1, 'very_short', ['minute' => $minute]);
    }
}

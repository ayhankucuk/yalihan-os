<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureAiRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature = 'general'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get role-based limits
        $role = $user->role ?? 'viewer';
        $limits = config("ai-rate-limits.roles.{$role}", config('ai-rate-limits.default'));

        // Admin has unlimited access
        if ($limits['daily'] === null && $limits['hourly'] === null) {
            return $next($request);
        }

        // Get feature cost weight
        $costWeight = config("ai-rate-limits.features.{$feature}.cost_weight", 1);

        // Check hourly limit
        if ($limits['hourly'] !== null) {
            $hourlyKey = "ai_rate_limit:{$user->id}:hourly";
            $hourlyUsage = Cache::get($hourlyKey, 0);
            $hourlyLimit = $limits['hourly'];

            if ($hourlyUsage >= $hourlyLimit) {
                return response()->json([
                    'error' => 'AI rate limit exceeded',
                    'limit_type' => 'hourly',
                    'limit' => $hourlyLimit,
                    'usage' => $hourlyUsage,
                    'reset_at' => now()->addHour()->toIso8601String(),
                ], 429);
            }

            // Check soft limit (80%)
            $utilizationHourly = $hourlyUsage / $hourlyLimit;
            if ($utilizationHourly >= config('ai-rate-limits.soft_limit_threshold', 0.80)) {
                \Log::warning('AI Rate Limit Soft Threshold Reached', [
                    'user_id' => $user->id,
                    'feature' => $feature,
                    'utilization' => $utilizationHourly,
                    'limit_type' => 'hourly',
                ]);
            }
        }

        // Check daily limit
        if ($limits['daily'] !== null) {
            $dailyKey = "ai_rate_limit:{$user->id}:daily";
            $dailyUsage = Cache::get($dailyKey, 0);
            $dailyLimit = $limits['daily'];

            if ($dailyUsage >= $dailyLimit) {
                return response()->json([
                    'error' => 'AI rate limit exceeded',
                    'limit_type' => 'daily',
                    'limit' => $dailyLimit,
                    'usage' => $dailyUsage,
                    'reset_at' => now()->addDay()->toIso8601String(),
                ], 429);
            }

            // Check soft limit (80%)
            $utilizationDaily = $dailyUsage / $dailyLimit;
            if ($utilizationDaily >= config('ai-rate-limits.soft_limit_threshold', 0.80)) {
                \Log::warning('AI Rate Limit Soft Threshold Reached', [
                    'user_id' => $user->id,
                    'feature' => $feature,
                    'utilization' => $utilizationDaily,
                    'limit_type' => 'daily',
                ]);
            }
        }

        // Increment usage counters
        if ($limits['hourly'] !== null) {
            $hourlyKey = "ai_rate_limit:{$user->id}:hourly";
            $newHourlyUsage = Cache::increment($hourlyKey, $costWeight);
            if ($newHourlyUsage === $costWeight) {
                Cache::put($hourlyKey, $costWeight, now()->addHour());
            }
        }

        if ($limits['daily'] !== null) {
            $dailyKey = "ai_rate_limit:{$user->id}:daily";
            $newDailyUsage = Cache::increment($dailyKey, $costWeight);
            if ($newDailyUsage === $costWeight) {
                Cache::put($dailyKey, $costWeight, now()->addDay());
            }
        }

        // Add rate limit headers
        $response = $next($request);
        
        if ($limits['hourly'] !== null) {
            $response->headers->set('X-RateLimit-Limit-Hourly', $limits['hourly']);
            $response->headers->set('X-RateLimit-Remaining-Hourly', max(0, $limits['hourly'] - ($hourlyUsage ?? 0)));
        }

        if ($limits['daily'] !== null) {
            $response->headers->set('X-RateLimit-Limit-Daily', $limits['daily']);
            $response->headers->set('X-RateLimit-Remaining-Daily', max(0, $limits['daily'] - ($dailyUsage ?? 0)));
        }

        return $response;
    }
}

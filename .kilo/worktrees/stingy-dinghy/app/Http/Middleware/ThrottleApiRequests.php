<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Cache\RateLimiter;

/**
 * ✅ P0 BLOCKER: API Rate Limiting
 *
 * Throttle endpoints based on:
 * - IP address
 * - User ID (if authenticated)
 * - API token
 *
 * Prevents:
 * - DDoS attacks
 * - Bot abuse
 * - Resource exhaustion
 * - Credential brute-forcing
 */
class ThrottleApiRequests
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Get identifier: user ID > API token > IP address
        $key = $this->getKey($request);

        // Get limit from config
        $limit = config('rate-limit.api.requests_per_minute', 60);
        $window = config('rate-limit.api.window_minutes', 1);

        // Check rate limit
        if ($this->limiter->tooManyAttempts($key, $limit, $window)) {
            return response()->json([
                'error' => 'Too many requests',
                'message' => "Rate limit exceeded: {$limit} requests per {$window} minute(s)",
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        // Increment attempt counter
        $this->limiter->hit($key, $window * 60);

        // Add rate limit headers to response
        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit - $this->limiter->attempts($key)));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($this->limiter->availableIn($key))->timestamp);

        return $response;
    }

    /**
     * Get rate limit key based on request
     */
    private function getKey(Request $request): string
    {
        // Authenticated user
        if ($request->user()) {
            return "rate-limit:user:{$request->user()->id}";
        }

        // API token
        if ($request->bearerToken()) {
            return "rate-limit:token:" . hash('sha256', $request->bearerToken());
        }

        // IP address fallback
        return "rate-limit:ip:{$request->ip()}";
    }
}

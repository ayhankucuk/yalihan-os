<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate Limit Middleware - Context7 Standard
 *
 * 🎯 Hedefler:
 * - API rate limiting
 * - Form submission limiting
 * - File upload limiting
 * - Brute force protection
 *
 * @version 1.0.0
 *
 * @author Context7 Team
 */
class RateLimitMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, string $type = 'general')
    {
        $key = $this->generateKey($request, $type);
        $limits = $this->getLimits($type);

        if (RateLimiter::tooManyAttempts($key, $limits['max_attempts'])) {
            $this->logRateLimitExceeded($request, $type);

            return response()->json([
                'error' => 'Çok fazla istek',
                'message' => 'Lütfen '.$limits['decay_minutes'].' dakika sonra tekrar deneyin.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, $limits['decay_minutes'] * 60);

        return $next($request);
    }

    /**
     * Generate rate limit key
     */
    private function generateKey(Request $request, string $type): string
    {
        $identifier = $request->ip();

        if (auth()->check()) {
            $identifier = 'user:'.auth()->id();
        }

        return "rate_limit:{$type}:{$identifier}";
    }

    /**
     * Get rate limits for different types
     */
    private function getLimits(string $type): array
    {
        $limits = [
            'general' => [
                'max_attempts' => 100,
                'decay_minutes' => 1,
            ],
            'api' => [
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],
            'login' => [
                'max_attempts' => 5,
                'decay_minutes' => 15,
            ],
            'register' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
            ],
            'password_reset' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
            ],
            'file_upload' => [
                'max_attempts' => 10,
                'decay_minutes' => 1,
            ],
            'form_submission' => [
                'max_attempts' => 20,
                'decay_minutes' => 1,
            ],
            'search' => [
                'max_attempts' => 30,
                'decay_minutes' => 1,
            ],
            'admin' => [
                'max_attempts' => 200,
                'decay_minutes' => 1,
            ],
        ];

        return $limits[$type] ?? $limits['general'];
    }

    /**
     * Log rate limit exceeded
     */
    private function logRateLimitExceeded(Request $request, string $type): void
    {
        Log::channel('security')->warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'type' => $type,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}

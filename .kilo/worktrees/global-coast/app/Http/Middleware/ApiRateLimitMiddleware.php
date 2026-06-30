<?php

namespace App\Http\Middleware;

use App\Services\Api\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiting Middleware
 * Tüm API endpoint'leri için genel rate limiting sağlar
 *
 * @version 1.0
 *
 * @author EmlakPro Team
 */
class ApiRateLimitMiddleware
{
    /**
     * API istekleri için rate limiting
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'general'): Response
    {
        $user = Auth::user();
        $identifier = $user ? $user->id : $request->ip();

        // Rate limit ayarlarını al
        $limits = $this->getRateLimits($type, $user);

        // Cache anahtarı oluştur
        $key = $this->resolveRequestSignature($request, $identifier, $type);

        // Mevcut istek sayısını al
        $attempts = Cache::get($key, 0);

        if ($attempts >= $limits['max_attempts']) {
            return ApiResponseService::rateLimitExceeded(
                'Rate limit exceeded. Try again in '.$limits['decay_minutes'].' minutes.'
            )->header('Retry-After', $limits['decay_minutes'] * 60);
        }

        // İstek sayısını artır
        Cache::put($key, $attempts + 1, now()->addMinutes($limits['decay_minutes']));

        $response = $next($request);

        // Response header'larına rate limit bilgilerini ekle
        $this->addRateLimitHeaders($response, $limits, $attempts);

        return $response;
    }

    /**
     * Rate limit türüne göre ayarları döner
     */
    private function getRateLimits(string $type, $user): array
    {
        $userRole = $user?->role?->name ?? 'guest';

        $limits = [
            'general' => [
                'guest' => ['max_attempts' => 60, 'decay_minutes' => 1],
                'user' => ['max_attempts' => 120, 'decay_minutes' => 1],
                'danisman' => ['max_attempts' => 200, 'decay_minutes' => 1],
                'admin' => ['max_attempts' => 300, 'decay_minutes' => 1],
                'super_admin' => ['max_attempts' => 500, 'decay_minutes' => 1],
            ],
            'search' => [
                'guest' => ['max_attempts' => 30, 'decay_minutes' => 1],
                'user' => ['max_attempts' => 60, 'decay_minutes' => 1],
                'danisman' => ['max_attempts' => 100, 'decay_minutes' => 1],
                'admin' => ['max_attempts' => 200, 'decay_minutes' => 1],
                'super_admin' => ['max_attempts' => 300, 'decay_minutes' => 1],
            ],
            'upload' => [
                'guest' => ['max_attempts' => 5, 'decay_minutes' => 1],
                'user' => ['max_attempts' => 20, 'decay_minutes' => 1],
                'danisman' => ['max_attempts' => 50, 'decay_minutes' => 1],
                'admin' => ['max_attempts' => 100, 'decay_minutes' => 1],
                'super_admin' => ['max_attempts' => 200, 'decay_minutes' => 1],
            ],
            'ai' => [
                'guest' => ['max_attempts' => 0, 'decay_minutes' => 1],
                'user' => ['max_attempts' => 10, 'decay_minutes' => 1],
                'danisman' => ['max_attempts' => 30, 'decay_minutes' => 1],
                'admin' => ['max_attempts' => 60, 'decay_minutes' => 1],
                'super_admin' => ['max_attempts' => 100, 'decay_minutes' => 1],
            ],
        ];

        return $limits[$type][$userRole] ?? $limits['general']['guest'];
    }

    /**
     * İstek imzası oluştur
     */
    private function resolveRequestSignature(Request $request, $identifier, string $type): string
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : 'unknown';

        return "api_rate_limit:{$type}:{$identifier}:{$routeName}";
    }

    /**
     * Rate limit header'larını ekle
     */
    private function addRateLimitHeaders($response, array $limits, int $attempts): void
    {
        $response->headers->set('X-RateLimit-Limit', $limits['max_attempts']);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limits['max_attempts'] - $attempts - 1));
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($limits['decay_minutes'])->timestamp);
        $response->headers->set('X-RateLimit-Type', 'API');
    }
}

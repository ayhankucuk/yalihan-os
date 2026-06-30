<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AIRateLimitMiddleware
{
    /**
     * AI istekleri için özel rate limiting
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 10, int $decayMinutes = 1): Response
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Kimlik doğrulama gerekli.',
            ], 401);
        }

        // Kullanıcı rolüne göre farklı limitler
        $limits = $this->getRoleLimits($user->role?->name ?? 'user');

        // Cache anahtarı oluştur
        $key = $this->resolveRequestSignature($request, $user->id);

        // Mevcut istek sayısını al
        $attempts = Cache::get($key, 0);

        if ($attempts >= $limits['max_attempts']) {
            return response()->json([
                'success' => false,
                'error' => 'Çok fazla istek. Lütfen '.$limits['decay_minutes'].' dakika sonra tekrar deneyin.',
                'retry_after' => $limits['decay_minutes'] * 60,
            ], 429);
        }

        // İstek sayısını artır
        Cache::put($key, $attempts + 1, now()->addMinutes($limits['decay_minutes']));

        $response = $next($request);

        // Response header'larına rate limit bilgilerini ekle
        $response->headers->set('X-RateLimit-Limit', $limits['max_attempts']);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limits['max_attempts'] - $attempts - 1));
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($limits['decay_minutes'])->timestamp);

        return $response;
    }

    /**
     * Kullanıcı rolüne göre rate limit ayarları
     */
    private function getRoleLimits(string $role): array
    {
        $limits = [
            'admin' => ['max_attempts' => 100, 'decay_minutes' => 1],
            'super_admin' => ['max_attempts' => 200, 'decay_minutes' => 1],
            'danisman' => ['max_attempts' => 50, 'decay_minutes' => 1],
            'user' => ['max_attempts' => 20, 'decay_minutes' => 1],
        ];

        return $limits[$role] ?? $limits['user'];
    }

    /**
     * İstek imzası oluştur
     */
    private function resolveRequestSignature(Request $request, int $userId): string
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : 'unknown';

        return 'ai_rate_limit:'.$userId.':'.$routeName;
    }
}

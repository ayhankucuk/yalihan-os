<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\Logging\LogService;

/**
 * Performance Optimization Middleware
 *
 * API response'larını optimize eder ve cache stratejileri uygular.
 */
class PerformanceOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Request cache key oluştur
        $cacheKey = $this->generateCacheKey($request);

        // Cache'den kontrol et
        if ($this->shouldCache($request) && Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);

            // Cache hit log
            LogService::info('Cache Hit', [
                'url' => $request->url(),
                'method' => $request->method(),
                'cache_key' => $cacheKey,
            ]);

            return response()->json($cachedResponse)
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        // Response'u al
        $response = $next($request);

        // JSON response ise cache'e kaydet
        if ($this->shouldCache($request) && $response->headers->get('content-type') === 'application/json') {
            $responseData = json_decode($response->getContent(), true);

            if ($responseData && isset($responseData['success']) && $responseData['success']) {
                $cacheTtl = $this->getCacheTtl($request);
                Cache::put($cacheKey, $responseData, $cacheTtl);

                // Cache miss log
                LogService::info('Cache Miss', [
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'cache_key' => $cacheKey,
                    'ttl' => $cacheTtl,
                ]);
            }
        }

        // Performance headers ekle
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $response->headers->set('X-Execution-Time', $executionTime . 'ms');
        $response->headers->set('X-Cache', 'MISS');
        $response->headers->set('X-Cache-Key', $cacheKey);

        // Yavaş query'leri logla
        if ($executionTime > 1000) { // 1 saniyeden fazla
            LogService::warning('Slow Query Detected', [
                'url' => $request->url(),
                'method' => $request->method(),
                'execution_time' => $executionTime . 'ms',
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    /**
     * Cache key oluştur
     */
    private function generateCacheKey(Request $request): string
    {
        $key = $request->method() . ':' . $request->url();

        // Query parametrelerini ekle
        if ($request->query()) {
            $key .= ':' . md5(serialize($request->query()));
        }

        // Auth user varsa ekle
        if ($request->user()) {
            $key .= ':user:' . $request->user()->id;
        }

        return 'api_cache:' . md5($key);
    }

    /**
     * Cache'lenmeli mi kontrol et
     */
    private function shouldCache(Request $request): bool
    {
        // Sadece GET request'leri cache'le
        if ($request->method() !== 'GET') {
            return false;
        }

        // Cache'lenmemesi gereken endpoint'ler
        $excludedPaths = [
            'api/auth/',
            'api/context7/advisors/stats',
            'api/context7/dashboard/',
        ];

        foreach ($excludedPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return false;
            }
        }

        // Cache-Control header kontrolü
        if ($request->header('Cache-Control') === 'no-cache') {
            return false;
        }

        return true;
    }

    /**
     * Cache TTL belirle
     */
    private function getCacheTtl(Request $request): int
    {
        $path = $request->path();

        // Endpoint'e göre TTL belirle
        if (str_contains($path, 'context7/advisors')) {
            return 300; // 5 dakika
        }

        if (str_contains($path, 'context7/crm')) {
            return 600; // 10 dakika
        }

        if (str_contains($path, 'context7/emlak')) {
            return 1800; // 30 dakika
        }

        if (str_contains($path, 'context7/dashboard')) {
            return 60; // 1 dakika
        }

        // Varsayılan TTL
        return 300; // 5 dakika
    }
}

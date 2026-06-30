<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthCheckController;

/*
|--------------------------------------------------------------------------
| Health Check API Routes (v1)
|--------------------------------------------------------------------------
|
| Context7 Standard: C7-HEALTH-API-2025-12-04
| API system health and state endpoints
|
| Prefix: /api/v1/health
| No authentication required (public endpoint)
|
| ✅ P1 INTEGRATION: Full health monitoring suite
*/

Route::prefix('health')->name('api.health.')->group(function () {
    /**
     * GET /api/v1/health
     * Full system health check (all dependencies)
     * Response: { durum, checks, metrics, timestamp }
     */
    Route::get('/', [HealthCheckController::class, 'check'])->name('check');

    /**
     * GET /api/v1/health/simple
     * Lightweight health check (for load balancers)
     * Response: { durum }
     */
    Route::get('/simple', [HealthCheckController::class, 'simple'])->name('simple');

    /**
     * GET /api/v1/health/detailed
     * Detailed diagnostics (admin only)
     * Response: { durum, checks, metrics, tables, recent_errors, cache_stats }
     * Auth: Admin only
     */
    Route::get('/detailed', [HealthCheckController::class, 'detailed'])->name('detailed');

    // Legacy endpoint compatibility
    Route::get('/check', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => now()->toISOString(),
            'version' => 'v1',
        ]);
    })->name('index');

    /**
     * GET /api/v1/health/database
     * Database connectivity check
     */
    Route::get('/database', function () {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();

            return response()->json([
                'success' => true,
                'service' => 'database',
                'state' => 'healthy',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'service' => 'database',
                'state' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    })->name('database');

    /**
     * GET /api/v1/health/cache
     * Cache service check
     */
    Route::get('/cache', function () {
        try {
            \Illuminate\Support\Facades\Cache::put('health-check', true, 1);
            $value = \Illuminate\Support\Facades\Cache::get('health-check');

            return response()->json([
                'success' => true,
                'service' => 'cache',
                'state' => $value ? 'healthy' : 'unhealthy',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'service' => 'cache',
                'state' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    })->name('cache');

    /**
     * GET /api/v1/health/full
     * Complete system health report
     */
    Route::get('/full', function () {
        $health = [
            'success' => true,
            'timestamp' => now()->toISOString(),
            'services' => [
                'api' => ['state' => 'healthy'],
                'database' => ['state' => 'unknown'],
                'cache' => ['state' => 'unknown'],
            ],
        ];

        // Database check
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $health['services']['database']['state'] = 'healthy';
        } catch (\Exception $e) {
            $health['services']['database']['state'] = 'unhealthy';
            $health['services']['database']['error'] = $e->getMessage();
            $health['success'] = false;
        }

        // Cache check
        try {
            \Illuminate\Support\Facades\Cache::put('health-check', true, 1);
            $value = \Illuminate\Support\Facades\Cache::get('health-check');
            $health['services']['cache']['state'] = $value ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            $health['services']['cache']['state'] = 'unhealthy';
            $health['services']['cache']['error'] = $e->getMessage();
            $health['success'] = false;
        }

        return response()->json($health, $health['success'] ? 200 : 500);
    })->name('full');
});

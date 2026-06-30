<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Explicit route model binding for danisman
        Route::bind('danisman', function ($value) {
            return \App\Models\User::findOrFail($value);
        });

        $this->routes(function () {
            // API Routes (Clean modular architecture)
            // ✅ All API routes integrated in routes/api.php with modular v1 structure
            // ❌ Removed legacy: api-admin.php, api-location.php (now in routes/api/v1/*)
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Admin API Routes — Session-based (same-origin admin panel)
            // ✅ SAB: Admin fetch çağrıları session cookie kullanır, bu yüzden web middleware gerekir
            // ✅ Prefix: /api/v1 (URL'ler değişmez, admin.php içinde 'admin' prefix zaten var)
            Route::middleware('web')
                ->prefix('api/v1')
                ->group(base_path('routes/api/v1/admin.php'));

            // Admin Routes (Load first to avoid conflicts)
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            // Admin AI Routes (Description Draft Pipeline)
            // ✅ Separate middleware in file: auth, admin, role:admin
            Route::middleware('web')
                ->group(base_path('routes/admin-ai.php'));

            // Main Web Routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Advanced AI Routes (AI Dashboard)
            Route::middleware('web')
                ->group(base_path('routes/ai-advanced.php'));
        });
    }
}

<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\SecurityMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\ProductionLockMiddleware::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\LocaleAndCurrencyMiddleware::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\TrackUserActivity::class,
            // \App\Http\Middleware\RoleBasedMenuMiddleware::class, // ❌ DISABLED: Causing infinite redirect loop
            \App\Http\Middleware\AppStateVersionMiddleware::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\PerformanceOptimizationMiddleware::class,
            \App\Http\Middleware\SabComplianceMiddleware::class,
            \App\Http\Middleware\LocaleAndCurrencyMiddleware::class,
            \App\Http\Middleware\AppStateVersionMiddleware::class,
            // SAB Kural #1 — Tenant Isolation: kimlik doğrulanmış her API isteğinde
            // tenant bağlamını kurar. Guest route'lar bypass edilir. Fix #77 (2026-05-15)
            \App\Http\Middleware\SetTenantContext::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        // Laravel 11'de middleware'ler bootstrap/app.php'de tanımlanıyor
    ];

    /**
     * The application's middleware aliases.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'superadmin' => \App\Http\Middleware\SuperAdminOnly::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'api.rate.limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
        'ai.rate.limit' => \App\Http\Middleware\AIRateLimitMiddleware::class,
        'features.manage' => \App\Http\Middleware\EnsureFeatureManagePermission::class,
        'role.menu' => \App\Http\Middleware\RoleBasedMenuMiddleware::class,

        'sab.compliance' => \App\Http\Middleware\SabComplianceMiddleware::class,
        'security' => \App\Http\Middleware\SecurityMiddleware::class,
        'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
        'n8n.secret' => \App\Http\Middleware\CheckN8nSecret::class,
        'telegram.secret' => \App\Http\Middleware\VerifyTelegramWebhookSecret::class,
        'frontend.api' => \App\Http\Middleware\VerifyFrontendApi::class,
        'ai.cost.guard' => \App\Http\Middleware\AICostGuard::class,
        'sab.write.guard' => \App\Http\Middleware\SAB\GlobalWriteGuard::class,
        'agent.scope' => \App\Http\Middleware\EnsureAgentScope::class, // legacy — use openclaw.* aliases
        'openclaw.enabled' => \App\Http\Middleware\EnsureOpenClawEnabled::class,
        'openclaw.scope' => \App\Http\Middleware\EnsureOpenClawScope::class,
        'openclaw.boundary' => \App\Http\Middleware\EnforceOpenClawBoundary::class,
        // SAB Kural #1 — Tenant Isolation HTTP katmanı (Fix: #49, 2026-05-15)
        'tenant.context' => \App\Http\Middleware\SetTenantContext::class,
        // Owner Portal — mülk sahibi erişim kontrolü (Task #14)
        'check.owner'    => \App\Http\Middleware\CheckOwner::class,
        'feature'        => \App\Http\Middleware\EnforceFeatureFlag::class,
    ];
}

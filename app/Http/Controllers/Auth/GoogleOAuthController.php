<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Email\GmailApiOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GoogleOAuthController
 *
 * Wave 2 Phase 2 — OAuth 2.0 Authorization Code Flow
 *
 * Ayhan'in Gmail hesabina bir kez yetki verir, refresh token'i depolar.
 *
 * Güvenlik:
 *   - CSRF state parametresi ile korunur
 *   - refresh_token APP_KEY ile encrypt edilir (Crypt::encryptString)
 *   - Token diske yazilmaz — sadece veritabaninda tutulur
 *   - Consent URL zaman asimi yok — bir kez verildiginde sonsuza kadar gecerli
 */
class GoogleOAuthController extends Controller
{
    public function __construct(
        private readonly GmailApiOAuthService $oauthService,
    ) {}

    /**
     * Adım 1: Ayhan'i Google consent URL'sine yönlendir.
     *
     * GET /auth/google
     */
    public function redirect(): RedirectResponse
    {
        $state = bin2hex(random_bytes(16));

        // State'i session'a kaydet (CSRF korumasi)
        session(['google_oauth_state' => $state]);

        $url = $this->oauthService->getConsentUrl($state);

        Log::info('[GoogleOAuth] Redirecting to consent URL', [
            'state' => $state,
        ]);

        return redirect()->away($url);
    }

    /**
     * Adım 2: Google'dan redirect ile donen callback.
     *
     * GET /auth/google/callback
     */
    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');

        // ── Hata kontrolü ────────────────────────────────────────
        if ($error) {
            Log::warning('[GoogleOAuth] OAuth error from Google', [
                'error' => $error,
                'error_description' => $request->get('error_description'),
            ]);

            return response()->json([
                'success' => false,
                'error' => $error,
                'description' => $request->get('error_description'),
            ], 400);
        }

        // ── CSRF state dogrulamasi ──────────────────────────────
        $savedState = session('google_oauth_state');
        if (! $savedState || ! hash_equals($savedState, $state ?? '')) {
            Log::warning('[GoogleOAuth] Invalid state parameter — possible CSRF');
            return response()->json([
                'success' => false,
                'error' => 'invalid_state',
                'description' => 'OAuth state mismatch. Please retry.',
            ], 400);
        }

        session()->forget('google_oauth_state');

        // ── Token exchange ──────────────────────────────────────
        if (! $code) {
            return response()->json([
                'success' => false,
                'error' => 'missing_code',
            ], 400);
        }

        try {
            $tokens = $this->oauthService->exchangeCodeForTokens($code);

            if (empty($tokens['refresh_token'])) {
                Log::warning('[GoogleOAuth] No refresh_token in response — may need prompt=consent');
                return response()->json([
                    'success' => false,
                    'error' => 'no_refresh_token',
                    'description' => 'Google did not return a refresh token. Try revoking access and retrying.',
                ], 400);
            }

            // Refresh token'i encrypt edip kaydet
            $tenantId = $this->resolveTenantId();
            $this->oauthService->saveRefreshToken($tokens['refresh_token'], $tenantId);

            Log::info('[GoogleOAuth] Gmail integration connected successfully', [
                'tenant_id' => $tenantId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gmail connected. Polling will begin automatically.',
                'expires_in' => $tokens['expires_in'] ?? 3600,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('[GoogleOAuth] Token exchange failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'token_exchange_failed',
                'description' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * OAuth baglantisinin durumunu kontrol et.
     *
     * GET /auth/google/status
     */
    public function status(): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $hasToken = $this->oauthService->hasRefreshToken($tenantId);

        return response()->json([
            'connected' => $hasToken,
            'service' => 'gmail',
            'mailbox' => config('services.gmail.oauth.user_email', 'ayhan@yalihanemlak.com.tr'),
            'message' => $hasToken
                ? 'Gmail connected and polling active.'
                : 'Gmail not connected. Visit /auth/google to connect.',
        ]);
    }

    /**
     * OAuth baglantisini kaldir.
     *
     * DELETE /auth/google
     */
    public function disconnect(): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->oauthService->deleteRefreshToken($tenantId);

        Log::info('[GoogleOAuth] Gmail disconnected', ['tenant_id' => $tenantId]);

        return response()->json(['success' => true, 'message' => 'Gmail disconnected.']);
    }

    // ── Private ─────────────────────────────────────────────────

    private function resolveTenantId(): int
    {
        try {
            $ctx = app(\App\Services\SaaS\TenantContextService::class);
            $t = $ctx->getTenant();
            return $t?->id ?? (int) config('services.gmail.oauth.default_tenant_id', 5);
        } catch (\Throwable) {
            return 5;
        }
    }
}

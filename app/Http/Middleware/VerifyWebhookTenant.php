<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SaaS\TenantContextService;
use App\Services\SaaS\TenantWebhookResolver;
use App\Exceptions\Tenant\TenantNotFoundException;

/**
 * VerifyWebhookTenant Middleware
 *
 * Phase 14 Sprint 2: Webhook Tenant Isolation & WhatsApp Hardening
 *
 * SAB Enforced Hybrid Multi-Tenant Webhook Ingress Boundary Guard.
 * Enforces optimized raw array access and absolute 404 Semantics.
 *
 * Anayasal Kararlar:
 * - Karar 1: Meta standart alan isimleri korunur (Explicit Exception Model)
 * - Karar 2: Hata yönetimi middleware katmanında sönümlenir (Boundary Termination)
 * - Karar 3: Ham array erişimi ile <10ms performans bütçesi korunur
 *
 * CRITICAL SECURITY: Webhook'lardan gelen isteklerde tenant kimliği
 * doğrulanmadan hiçbir işlem başlatılmaz.
 *
 * Kullanım:
 * Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handleWebhook'])
 *     ->middleware('verify.webhook.tenant');
 *
 * @see docs/webhook-tenant-security.md
 * @see .sab/authority.json (External Integration Exceptions)
 */
class VerifyWebhookTenant
{
    protected TenantContextService $tenantContextService;
    protected TenantWebhookResolver $webhookResolver;

    /**
     * VerifyWebhookTenant constructor.
     *
     * @param TenantContextService $tenantContextService
     * @param TenantWebhookResolver $webhookResolver
     */
    public function __construct(
        TenantContextService $tenantContextService,
        ?TenantWebhookResolver $webhookResolver = null
    ) {
        $this->tenantContextService = $tenantContextService;
        $this->webhookResolver = $webhookResolver ?? app(TenantWebhookResolver::class);
    }

    /**
     * Webhook istek sınırında kiracı kimliğini doğrular ve sönümler.
     *
     * SAB Madde 2: Fail-Loud Logging
     * SAB Madde 5: 404 Semantics (Absolute Masking)
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // EXT_06C FIX: HTTP Tenant Context Lifecycle Cleanup
        // Her webhook request'i başında miras alınan context'i temizle.
        // Bu, singleton TenantContextService'de önceki isteklerden kalan
        // tenant context'in yeni isteklere sızmasını önler.
        $this->tenantContextService->clearTenant();

        // Güvenlik & Doğrulama Sırası: HMAC İmzası Doğrulaması (Fail-Closed 403)
        // Meta webhook isteklerinde sahte veya geçersiz imzalı istekler kiracı arama (tenant lookup)
        // yapılmadan 403 ile reddedilir.
        if ($request->isMethod('POST') && ($request->is('*api/v1/webhook/*') || $request->hasHeader('X-Hub-Signature-256'))) {
            $signature = $request->header('X-Hub-Signature-256');
            if (!$signature) {
                Log::warning('Webhook Ingress Guard: missing X-Hub-Signature-256 header', [
                    'ip' => $request->ip(),
                ]);
                abort(403, 'Invalid signature');
            }

            $parts = explode('=', $signature, 2);
            if (count($parts) !== 2 || $parts[0] !== 'sha256') {
                Log::warning('Webhook Ingress Guard: malformed signature header', [
                    'ip' => $request->ip(),
                ]);
                abort(403, 'Invalid signature');
            }

            $appSecret = config('services.whatsapp.app_secret')
                ?: (config('services.facebook.app_secret')
                ?: config('services.instagram.app_secret'));

            if (empty($appSecret)) {
                Log::error('Webhook Ingress Guard: app_secret not configured');
                abort(403, 'Invalid signature');
            }

            $payload = $request->getContent();
            $expectedHash = hash_hmac('sha256', $payload, $appSecret);

            if (!hash_equals($expectedHash, $parts[1])) {
                Log::warning('Webhook Ingress Guard: invalid signature', [
                    'ip' => $request->ip(),
                ]);
                abort(403, 'Invalid signature');
            }
        }

        try {
            // SECURITY FIX EXT_06B: Tenant authority yalnızca Meta-imzalı payload'daki canonical identifier'dır.
            // Query/body fallback'leri KALDIRILDI — attacker-supplied parametreler kabul edilmez.
            // Signed payload'dan gelen metadata.phone_number_id zorunludur.
            $rawPayload = $request->json()->all();
            $phoneNumberId = $rawPayload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;

            if (!$phoneNumberId) {
                throw new TenantNotFoundException(
                    "Canonical phone_number_id missing from signed Meta payload. " .
                    "Tenant selection via unsigned query/body parameters is not permitted."
                );
            }

            // Anayasal Karar 2: Lookup ve yetkilendirmeyi servis katmanına delege et
            $tenant = $this->webhookResolver->resolveFromMetaId((string)$phoneNumberId);

            // Kiracının aktiflik durumunu doğrula (SAB Zero-Trust Enforcer)
            if (!$tenant->is_active || ($tenant->aktiflik_durumu ?? 'active') !== 'active') {
                throw new TenantNotFoundException("Tenant is inactive or suspended: {$tenant->id}");
            }

            // Çalışma zamanı in-memory hafızasını ve boru hattını kilitle
            $this->tenantContextService->setTenant($tenant);
            $request->attributes->set('verified_tenant_id', $tenant->id);

            // Başarılı doğrulama kaydı (INFO seviyesi)
            Log::info('Webhook tenant verified successfully', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'webhook_path' => $request->path(),
                'identifier' => $phoneNumberId,
            ]);

            return $next($request);
        } catch (\Throwable $exception) {
            // Fail-Loud: Adli izleme katmanına hatayı akıt (SAB Madde 2)
            Log::critical("FATAL WEBHOOK INGRESS FAILURE: {$exception->getMessage()}", [
                'ip_adresi' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'exception_type' => get_class($exception),
            ]);

            if ($exception instanceof TenantNotFoundException) {
                $exception->report();
            }

            // Anayasal Karar 2 & SAB Madde 5: Absolute 404 Semantics Masking
            // Hiçbir bilgi sızdırma - standart Laravel 404
            abort(404);
        } finally {
            // EXT_06D FIX: Request-Boundary Lifecycle Cleanup
            // Tenant context singleton'da biriken tüm tenant bilgisini request
            // sınırında temizle. Bu:
            //   - Normal tamamlanmada context'i temizler
            //   - Downstream $next() exception sonrası context'i temizler
            //   - HMAC/auth hatalarında context'i temizler
            // NOT: Entry'deki clearTenant() ile birlikte çalışır.
            //       Entry clear = önceki request'ten gelen sızmayı engeller.
            //       Finally clear = bu request'in kendi context'ini temizler.
            $this->tenantContextService->clearTenant();
        }
    }
}

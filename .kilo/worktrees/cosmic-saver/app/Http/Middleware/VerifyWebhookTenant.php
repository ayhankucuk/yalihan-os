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
        try {
            // Anayasal Karar 3: Latans-optimize ham array erişimi (<0.1ms)
            $rawPayload = $request->json()->all();
            $phoneNumberId = $rawPayload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id']
                ?? $request->query('phone_number_id')
                ?? $request->input('tenant_id');

            if (!$phoneNumberId) {
                throw new TenantNotFoundException(
                    "Inbound identifier signal is completely missing from stream context."
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
        }
    }
}

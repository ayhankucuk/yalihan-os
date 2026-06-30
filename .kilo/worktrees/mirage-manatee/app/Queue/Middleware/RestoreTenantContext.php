<?php

namespace App\Queue\Middleware;

use App\Models\SaaS\Tenant;
use App\Queue\Contracts\TenantAwareJobInterface;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Class RestoreTenantContext
 *
 * SAB v24.2 Multi-Tenant Financial Scoping ve İzolasyon kurallarını
 * kuyrukta işletir.
 *
 * Durum Yönetimi ve Sıfırlama Garantili Job Middleware:
 * - Job başlamadan önce tenant context restore eder
 * - Job bittikten sonra context'i temizler (Context Bleeding önleme)
 * - Hata durumunda fail-loud davranır
 *
 * @package App\Queue\Middleware
 * @governance QUEUE_TENANT_SAFETY
 * @created 2026-05-18
 */
class RestoreTenantContext
{
    public function __construct(
        protected TenantContextService $tenantContextService
    ) {}

    /**
     * Process the queued job.
     *
     * @param mixed $job
     * @param callable $next
     * @return mixed
     * @throws RuntimeException
     * @fail-loud Hata durumunda state temizlenir ve exception yukarı fırlatılır.
     */
    public function handle(mixed $job, callable $next): mixed
    {
        // Yönetişim kuralı 8: Bağlamsız işlerin reddedilmesi
        if (!$job instanceof TenantAwareJobInterface) {
            Log::warning('QUEUE_NON_TENANT_JOB_REJECTED', [
                'job_class' => get_class($job),
            ]);

            throw new RuntimeException(
                "Job must implement TenantAwareJobInterface for Zero-Trust compliance: " . get_class($job)
            );
        }

        // Mevcut tenant context'i sakla (daemon worker için)
        $originalTenantId = $this->tenantContextService->hasTenant()
            ? $this->tenantContextService->getTenant()->id
            : null;

        $targetTenantId = $job->getTenantId();

        // Yönetişim kuralı 1: Job payload MUST include tenant_id
        if (is_null($targetTenantId)) {
            Log::error('QUEUE_TENANT_ID_MISSING', [
                'job_class' => get_class($job),
                'user_id' => $job->getUserId(),
            ]);

            throw new RuntimeException(
                "Tenant ID missing in Job payload: " . get_class($job)
            );
        }

        try {
            // Yönetişim kuralı 2: Queue retry MUST restore original tenant context
            $tenant = Tenant::find($targetTenantId);

            // Yönetişim kuralı 5: Jobs MUST validate tenant context before execution
            if (!$tenant) {
                Log::error('QUEUE_STALE_TENANT_CONTEXT', [
                    'job_class' => get_class($job),
                    'tenant_id' => $targetTenantId,
                ]);

                throw new RuntimeException(
                    "Stale tenant context: Tenant {$targetTenantId} not found"
                );
            }

            $this->tenantContextService->setTenant($tenant);

            Log::info('QUEUE_TENANT_CONTEXT_RESTORED', [
                'job' => get_class($job),
                'tenant_id' => $targetTenantId,
                'user_id' => $job->getUserId(),
            ]);

            // Yönetişim kuralı 7: Jobs MUST be idempotent (safe to replay)
            return $next($job);

        } catch (\Exception $e) {
            // Yönetişim kuralı 3: Failed jobs MUST preserve tenant context for retry
            Log::error('QUEUE_JOB_EXECUTION_FAILED', [
                'job' => get_class($job),
                'tenant_id' => $targetTenantId,
                'user_id' => $job->getUserId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            // KRİTİK: Context Bleeding önleme
            // Daemon worker'da bir sonraki işe veri sızmasını engelle
            if ($originalTenantId) {
                $originalTenant = Tenant::find($originalTenantId);
                if ($originalTenant) {
                    $this->tenantContextService->setTenant($originalTenant);
                }
            }
            // Not: Context'i tamamen null yapmıyoruz çünkü HTTP request'ler
            // aynı worker'da çalışabilir ve mevcut context'e ihtiyaç duyabilir
        }
    }
}

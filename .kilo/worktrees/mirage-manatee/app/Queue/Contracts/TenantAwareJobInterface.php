<?php

namespace App\Queue\Contracts;

/**
 * Interface TenantAwareJobInterface
 *
 * Zero-Trust asenkron izolasyon kontratıdır.
 *
 * SAB v24.2 Multi-Tenant Financial Scoping ve İzolasyon kurallarını
 * kuyruk işlemlerinde işletmek için tüm Job sınıfları bu interface'i
 * implement etmelidir.
 *
 * @package App\Queue\Contracts
 * @governance QUEUE_TENANT_SAFETY
 * @created 2026-05-18
 */
interface TenantAwareJobInterface
{
    /**
     * Get the tenant ID for this job.
     *
     * @return int|null Tenant ID or null if not set
     */
    public function getTenantId(): ?int;

    /**
     * Get the user ID for this job.
     *
     * @return int|null User ID or null if not set
     */
    public function getUserId(): ?int;
}

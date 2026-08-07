<?php

namespace App\Domains\Finance\Services;

/**
 * FinanceAgentFeatureFlags
 *
 * EX-002 Finance Agent — WAVE 2
 *
 * Finance Agent otomasyonunu kontrol eden feature flag'ler.
 * Tüm değerler config('finance_agent.*') üzerinden okunur — env() yasak.
 */
class FinanceAgentFeatureFlags
{
    /**
     * Global kill switch — tüm Finance Agent özelliklerini açar/kapar.
     */
    public function isEnabled(): bool
    {
        return (bool) config('finance_agent.enabled', false);
    }

    /**
     * Payout import özelliğini kontrol eder.
     */
    public function isImportEnabled(int $tenantId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if ((bool) config('finance_agent.pilot.strict_mode', true)) {
            return $this->isTenantPilotEnabled($tenantId);
        }

        return (bool) config('finance_agent.import.enabled', true);
    }

    /**
     * Otomatik reconciliation özelliğini kontrol eder.
     */
    public function isAutoReconcileEnabled(int $tenantId): bool
    {
        if (!$this->isImportEnabled($tenantId)) {
            return false;
        }

        return (bool) config('finance_agent.reconciliation.auto_reconcile', false);
    }

    /**
     * Owner payout hazırlama özelliğini kontrol eder.
     */
    public function isOwnerPayoutEnabled(int $tenantId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if ((bool) config('finance_agent.pilot.strict_mode', true)) {
            return $this->isTenantPilotEnabled($tenantId);
        }

        return (bool) config('finance_agent.owner_payout.enabled', true);
    }

    /**
     * Belirli tenant için pilot modunu kontrol eder.
     */
    public function isTenantPilotEnabled(int $tenantId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $pilotTenants = config('finance_agent.pilot.tenants', []);

        return in_array($tenantId, $pilotTenants, true);
    }

    /**
     * Varsayılan YALIHAN komisyon oranını döner.
     */
    public function getDefaultCommissionRate(int $tenantId): float
    {
        // Tenant bazlı oran gelecekte eklenebilir
        return (float) config('finance_agent.commission.default_rate', 10.0);
    }

    /**
     * Admin onay gerektirip gerektirmediğini kontrol eder.
     */
    public function requiresAdminApproval(): bool
    {
        return (bool) config('finance_agent.approval.required', true);
    }

    /**
     * Retry mekanizmasını kontrol eder.
     */
    public function isRetryEnabled(): bool
    {
        return (bool) config('finance_agent.retry.enabled', true);
    }

    /**
     * Max retry sayısı.
     */
    public function getMaxRetries(): int
    {
        return (int) config('finance_agent.retry.max_attempts', 3);
    }
}

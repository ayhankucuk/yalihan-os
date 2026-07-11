<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use App\Models\EtkiAlaniOlayi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AutomationTelemetryService
 *
 * Calculates the Business Automation Index (BAI) and telemetry data.
 *
 * Formula:
 *   BAI = (E_auto / (E_auto + E_manual)) * 100
 */
class AutomationTelemetryService
{
    /**
     * Calculate the Business Automation Index (BAI) score for a given tenant.
     *
     * @param int|null $tenantId
     * @return int
     */
    public function calculateBusinessAutomationIndex(?int $tenantId): int
    {
        $thirtyDaysAgo = now()->subDays(30);

        // Automated events queries
        $queryAutoDomain = EtkiAlaniOlayi::query()
            ->withoutGlobalScopes()
            ->whereNull('user_id')
            ->where('created_at', '>=', $thirtyDaysAgo);

        $queryAutoActivity = DB::table('activity_log')
            ->whereNull('causer_id')
            ->where('created_at', '>=', $thirtyDaysAgo);

        // Manual events queries
        $queryManualDomain = EtkiAlaniOlayi::query()
            ->withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $thirtyDaysAgo);

        $queryManualActivity = DB::table('activity_log')
            ->whereNotNull('causer_id')
            ->where('created_at', '>=', $thirtyDaysAgo);

        // Apply tenant isolation filters
        if ($tenantId !== null) {
            $queryAutoDomain->where('tenant_id', $tenantId);
            $queryManualDomain->where('tenant_id', $tenantId);

            $queryAutoActivity->where('subject_type', 'App\Models\Ilan')
                ->whereIn('subject_id', function ($q) use ($tenantId) {
                    $q->select('id')->from('ilanlar')->where('tenant_id', $tenantId);
                });

            $queryManualActivity->where('subject_type', 'App\Models\Ilan')
                ->whereIn('subject_id', function ($q) use ($tenantId) {
                    $q->select('id')->from('ilanlar')->where('tenant_id', $tenantId);
                });
        }

        $hasActivityLog = Schema::hasTable('activity_log');

        $eAuto = $queryAutoDomain->count() + ($hasActivityLog ? $queryAutoActivity->count() : 0);
        $eManual = $queryManualDomain->count() + ($hasActivityLog ? $queryManualActivity->count() : 0);

        $total = $eAuto + $eManual;
        if ($total === 0) {
            return 100; // Default to 100% automation if no events recorded
        }

        return (int) round(($eAuto / $total) * 100);
    }
}

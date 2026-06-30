<?php

namespace App\Services\Governance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Governance Observability Service
 * Handles data retrieval for governance monitoring.
 */
class GovernanceObservabilityService
{
    /**
     * Get governance audit timeline.
     */
    public function getTimeline(int $limit = 50): Collection
    {
        return DB::table('property_config_audit_logs')
            ->select('property_config_audit_logs.*', 'users.name as kullanici_adi')
            ->leftJoin('users', 'users.id', '=', 'property_config_audit_logs.islem_yapan_id')
            ->orderByDesc('olusturma_tarihi') // context7-ignore
            ->orderByDesc('id') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Get drift telemetry metrics.
     */
    public function getDriftMetrics(int $limit = 24): Collection
    {
        return DB::table('governance_drift_telemetry')
            ->orderByDesc('olusturma_tarihi') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Get security incidents.
     */
    public function getIncidents(int $limit = 20): Collection
    {
        return DB::table('governance_tamper_incidents')
            ->orderByDesc('olusturma_tarihi') // context7-ignore
            ->limit($limit)
            ->get();
    }
}

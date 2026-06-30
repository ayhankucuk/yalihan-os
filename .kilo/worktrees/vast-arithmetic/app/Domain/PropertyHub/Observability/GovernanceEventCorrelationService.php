<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Observability;

use App\Models\GovernanceIncident;
use Illuminate\Support\Facades\Log;

/**
 * Class GovernanceEventCorrelationService
 *
 * Detects patterns and correlations between governance incidents.
 * ✅ SAB: Deterministic pattern matching.
 */
class GovernanceEventCorrelationService
{
    private const CORRELATION_WINDOW_MINUTES = 60;

    /**
     * Check for correlated incidents within a window.
     */
    public function detectCorrelations(string $tenantId): array
    {
        $windowStart = now()->subMinutes(self::CORRELATION_WINDOW_MINUTES);

        $recentIncidents = GovernanceIncident::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $windowStart)
            ->get();

        if ($recentIncidents->count() < 3) {
            return [];
        }

        $correlations = [];

        // 1. Detect Drift Burst (Multiple drifts in short time)
        $driftCount = $recentIncidents->where('olay_tipi', 'AUTONOMOUS_FIX_PROPOSED')->count();
        if ($driftCount >= 3) {
            $correlations[] = [
                'type' => 'DRIFT_BURST',
                'severity' => 'HIGH',
                'description' => "Detected {$driftCount} drift events in the last hour.",
                'correlation_hash' => hash('sha256', $tenantId . 'DRIFT_BURST' . $windowStart->toDateTimeString())
            ];
        }

        // 2. Detect Critical Escalation Trend
        $criticalCount = $recentIncidents->where('risk_seviyesi', 'CRITICAL')->count();
        if ($criticalCount >= 2) {
             $correlations[] = [
                'type' => 'CRITICAL_SPIKE',
                'severity' => 'EMERGENCY',
                'description' => "Multiple CRITICAL incidents detected for tenant.",
                'correlation_hash' => hash('sha256', $tenantId . 'CRITICAL_SPIKE' . $windowStart->toDateTimeString())
            ];
        }

        if (!empty($correlations)) {
            Log::channel('governance_security')->warning("CORRELATION ENGINE [Tenant: {$tenantId}]: Anomalies detected.", $correlations);
        }

        return $correlations;
    }
}

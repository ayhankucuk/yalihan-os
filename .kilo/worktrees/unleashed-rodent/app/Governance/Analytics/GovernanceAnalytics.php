<?php

namespace App\Governance\Analytics;

use App\Governance\Metrics\GovernanceMetrics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4C — Governance Analytics Engine (Week 2)
 *
 * =====================================================================
 * TEMEL PRENSİPLER (Safety Guardrails)
 * =====================================================================
 *
 * #11 Advisory-only     : Sadece danışmanlık yapar, asla zorlamaz/engellemez.
 * #13 Fixed Thresholds  : Drift eşikleri sabittir (ML/Adaptif yasak).
 * #5  Fail-Open         : Hata durumunda boş veri döner, iş akışını kesmez.
 * #17 Retention         : Geçmiş veriyi (occurred_at) kullanarak analiz yapar.
 *
 * =====================================================================
 * ANALİZ MANTIĞI
 * =====================================================================
 *
 * 1. Drift Detection    : 24 saatlik ihlal oranı vs 7 günlük baseline.
 * 2. Anomaly Detection  : 1 saatlik hacim vs 24 saatlik ortalama (Spike detection).
 * 3. Health Reporting   : Metrikler + Drift + Anomaliler birleşimi.
 */
class GovernanceAnalytics
{
    /**
     * Drift (Yönetişim Kayması) tespiti yapar.
     *
     * @return array{has_drift: bool, drift_percentage: float, severity: string}
     */
    public function detectDrift(): array
    {
        try {
            $threshold = config('governance.telemetry.drift_detection.threshold_percentage', 10);
            $baselineDays = config('governance.telemetry.drift_detection.baseline_period_days', 7);

            // 1. Baseline Oranı (Son 7 gün)
            $baselineTotal = DB::table('governance_events')
                ->where('occurred_at', '>=', now()->subDays($baselineDays))
                ->count();
            
            $baselineViolations = DB::table('governance_events')
                ->where('is_violation', true)
                ->where('occurred_at', '>=', now()->subDays($baselineDays))
                ->count();

            $baselineRate = $baselineTotal > 0 ? ($baselineViolations / $baselineTotal) * 100 : 0;

            // 2. Mevcut Oran (Son 24 saat)
            $currentTotal = DB::table('governance_events')
                ->where('occurred_at', '>=', now()->subHours(24))
                ->count();
            
            $currentViolations = DB::table('governance_events')
                ->where('is_violation', true)
                ->where('occurred_at', '>=', now()->subHours(24))
                ->count();

            $currentRate = $currentTotal > 0 ? ($currentViolations / $currentTotal) * 100 : 0;

            // 3. Fark Hesaplama
            $driftPercentage = $currentRate - $baselineRate;
            $hasDrift = $driftPercentage > $threshold;

            $severity = 'low';
            if ($driftPercentage > ($threshold * 2)) $severity = 'high';
            elseif ($driftPercentage > $threshold) $severity = 'medium';

            return [
                'has_drift'        => $hasDrift,
                'drift_percentage' => round($driftPercentage, 2),
                'current_rate'     => round($currentRate, 2),
                'baseline_rate'    => round($baselineRate, 2),
                'threshold'        => $threshold,
                'severity'         => $severity,
            ];

        } catch (\Throwable $e) {
            Log::error('[GovernanceAnalytics] Drift tespiti başarısız', ['error' => $e->getMessage()]);
            return [
                'has_drift'        => false,
                'drift_percentage' => 0,
                'error'            => 'Analysis unavailable'
            ];
        }
    }

    /**
     * Anomali (Spike) tespiti yapar.
     */
    public function detectAnomalies(): array
    {
        try {
            $anomalies = [];

            // 1. Spike Tespiti (Son 1 saat vs son 24 saatin saatlik ortalaması)
            $lastHourCount = DB::table('governance_events')
                ->where('is_violation', true)
                ->where('occurred_at', '>=', now()->subHour())
                ->count();

            $last24hCount = DB::table('governance_events')
                ->where('is_violation', true)
                ->where('occurred_at', '>=', now()->subHours(24))
                ->count();

            $avgPerHour = $last24hCount / 24;
            
            if ($lastHourCount > ($avgPerHour * 3) && $lastHourCount > 5) {
                $anomalies[] = [
                    'tip'       => 'violation_spike',
                    'severity'  => 'high',
                    'current'   => $lastHourCount,
                    'average'   => round($avgPerHour, 2),
                    'threshold' => round($avgPerHour * 3, 2),
                    'message'   => 'İhlal sayısında ani yükseliş tespit edildi.'
                ];
            }

            // 2. Kritik İhlal Oranı Kontrolü (Örn: Tenant Isolation)
            $missingTenantCount = DB::table('governance_events')
                ->where('is_violation', true)
                ->whereJsonContains('tags->violation_type', 'missing_tenant')
                ->where('occurred_at', '>=', now()->subHour())
                ->count();

            if ($missingTenantCount > 0) {
                $anomalies[] = [
                    'tip'      => 'critical_violation',
                    'severity' => 'critical',
                    'count'    => $missingTenantCount,
                    'message'  => 'Kritik tenant izolasyon ihlali tespit edildi.'
                ];
            }

            return $anomalies;

        } catch (\Throwable $e) {
            Log::error('[GovernanceAnalytics] Anomali tespiti başarısız', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Kapsamlı Yönetişim Sağlık Raporu üretir.
     */
    public function generateHealthReport(): array
    {
        return [
            'health_score'        => GovernanceMetrics::getHealthScore(),
            'drift'               => $this->detectDrift(),
            'anomalies'           => $this->detectAnomalies(),
            'violation_breakdown' => $this->getViolationBreakdown(),
            'timestamp'           => now()->toIso8601String(),
        ];
    }

    /**
     * İhlal tiplerine göre dağılım döner.
     */
    public function getViolationBreakdown(): array
    {
        try {
            $types = [
                'missing_tenant',
                'global_cache',
                'queue_without_tenant',
                'tenant_not_restored',
                'repository_bypass',
                'cross_tenant'
            ];

            $breakdown = [];
            foreach ($types as $type) {
                // Hem Redis'ten hem DB'den veri çekilebilir, burada DB tercih ediliyor (zaman serisi)
                $breakdown[$type] = DB::table('governance_events')
                    ->where('is_violation', true)
                    ->whereJsonContains('tags->violation_type', $type)
                    ->where('occurred_at', '>=', now()->subHours(24))
                    ->count();
            }

            return $breakdown;

        } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::error($e->getMessage()); return []; }
    }
}

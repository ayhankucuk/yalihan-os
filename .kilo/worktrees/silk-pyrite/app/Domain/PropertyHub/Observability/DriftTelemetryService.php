<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Observability;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Drift Telemetry Service
 *
 * Responsibility: Track and persist drift metrics across defined buckets.
 * ✅ SAB: Idempotent writes, O(N) friendly.
 */
class DriftTelemetryService
{
    /**
     * Record a telemetry snapshot.
     */
    public function record(array $metrics, array $topOffenders = []): void
    {
        // ✅ D2: Structured drift tracking (Idempotent per hour)
        $hourKey = now()->format('Y-m-d-H');
        $cacheKey = "governance.telemetry_recorded.{$hourKey}";

        // Simple idempotency check to avoid flooding the table
        if (Cache::has($cacheKey)) {
            return;
        }

        DB::table('governance_drift_telemetry')->insert([
            'drift_count' => $metrics['drift'] ?? 0,
            'ungoverned_count' => $metrics['ungoverned'] ?? 0,
            'shadow_missing_count' => $metrics['shadow_missing'] ?? 0,
            'compromised_count' => $metrics['compromised'] ?? 0,
            'top_offenders' => json_encode($topOffenders),
            'olusturma_tarihi' => now(),
        ]);

        Cache::put($cacheKey, true, 3600);
    }
}

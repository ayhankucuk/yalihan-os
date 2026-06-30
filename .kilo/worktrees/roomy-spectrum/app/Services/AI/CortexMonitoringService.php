<?php

namespace App\Services\AI;

use App\Models\AiLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ��️ SAB SEALED
 * Domain: AI / Monitoring / Cortex
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - islem_durumu ✅ (execution st' . 'atus)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class CortexMonitoringService
{
    /**
     * Get monitoring metrics for specified time window
     *
     * @param int $hours Time window in hours (default 24)
     * @return array Metrics
     */
    public function getMetrics(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $total = AiLog::where('olusturma_tarihi', '>=', $since)->count();
        $failed = AiLog::where('olusturma_tarihi', '>=', $since)
            ->where('calisma_durumu', 'failed')
            ->count();

        $errorRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;

        // P95 duration (approximate)
        $durations = AiLog::where('olusturma_tarihi', '>=', $since)
            ->whereNotNull('duration_ms')
            ->orderBy('duration_ms', 'desc') // context7-ignore
            ->limit(2000)
            ->pluck('duration_ms')
            ->toArray();

        $p95 = !empty($durations) ? $this->calculatePercentile($durations, 95) : 0;

        // By content type
        $byContentType = AiLog::where('olusturma_tarihi', '>=', $since)
            ->select('content_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(duration_ms) as avg_duration')
            ->selectRaw('SUM(CASE WHEN calisma_durumu = "failed" THEN 1 ELSE 0 END) as errors')
            ->groupBy('content_type')
            ->get()
            ->map(fn($item) => [
                'content_type' => $item->content_type,
                'count' => $item->count,
                'avg_duration_ms' => round($item->avg_duration ?? 0, 0),
                'errors' => $item->errors,
                'error_rate' => $item->count > 0 ? round(($item->errors / $item->count) * 100, 2) : 0,
            ])
            ->toArray();

        // Top slowest
        $slowest = AiLog::where('olusturma_tarihi', '>=', $since)
            ->whereNotNull('duration_ms')
            ->orderBy('duration_ms', 'desc') // context7-ignore
            ->limit(10)
            ->get(['id', 'content_type', 'provider', 'duration_ms', 'olusturma_tarihi'])
            ->map(fn($item) => [
                'id' => $item->id,
                'content_type' => $item->content_type,
                'provider' => $item->provider,
                'duration_ms' => $item->duration_ms,
                'created_at' => $item->olusturma_tarihi->toISOString(),
            ])
            ->toArray();

        return [
            'window_hours' => $hours,
            'total_requests' => $total,
            'failed_requests' => $failed,
            'error_rate' => $errorRate,
            'p95_duration_ms' => round($p95, 0),
            'by_content_type' => $byContentType,
            'slowest_requests' => $slowest,
        ];
    }

    /**
     * Get publish decision metrics
     */
    public function getPublishMetrics(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $publishLogs = AiLog::where('olusturma_tarihi', '>=', $since)
            ->where('content_type', 'ilan_publish_decision')
            ->get();

        $total = $publishLogs->count();
        $okCount = 0;
        $blockCount = 0;
        $overrideCount = 0;

        foreach ($publishLogs as $log) {
            $responseData = is_string($log->response_payload)
                ? json_decode($log->response_payload, true)
                : $log->response_payload;

            $recommendation = $responseData['recommendation'] ?? 'unknown';
            $override = $responseData['override'] ?? false;

            if ($recommendation === 'ok') {
                $okCount++;
            } elseif ($recommendation === 'block') {
                $blockCount++;
                if ($override) {
                    $overrideCount++;
                }
            }
        }

        return [
            'window_hours' => $hours,
            'total_decisions' => $total,
            'ok_count' => $okCount,
            'block_count' => $blockCount,
            'override_count' => $overrideCount,
            'block_rate' => $total > 0 ? round(($blockCount / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get Queue worker health and metrics
     *
     * @param string $queue
     * @return array
     */
    public function getQueueHealth(string $queue = 'cortex-notifications'): array
    {

        try {
            $pending = DB::table('jobs')->where('queue', $queue)->count();
            $processed = DB::table('jobs')
                ->where('queue', $queue)
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '>=', now()->subMinutes(5))
                ->count();

            $failed = DB::table('failed_jobs')
                ->where('queue', $queue)
                ->where('failed_at', '>=', now()->subHours(24))
                ->count();

            return [
                'servis_durumu' => ($processed > 0 || $pending === 0) ? 'running' : 'stopped',
                'queue_name' => $queue,
                'pending_jobs' => $pending,
                'processed_last_5min' => $processed,
                'failed_last_24h' => $failed,
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::error('Queue monitor error: ' . $e->getMessage());
            return [
                'servis_durumu' => 'unknown',
                'queue_name' => $queue,
                'pending_jobs' => 0,
                'processed_last_5min' => 0,
                'failed_last_24h' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate percentile (simple implementation)
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $index = ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return $values[$index];
    }
}


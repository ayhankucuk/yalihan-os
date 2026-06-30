<?php

namespace App\Services\AI\Copilot\Pipeline;

use App\Enums\PipelineDurumu;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use Illuminate\Support\Facades\DB;

/**
 * Collects and exposes pipeline execution metrics.
 * Used by dashboards, health checks, and anomaly detection.
 */
class PipelineMetricsCollector
{
    /**
     * Get aggregate metrics for a time window.
     *
     * @param int $minutesBack How far back to look (default: 60)
     * @return array{total_runs: int, completed: int, failed: int, avg_duration_ms: float|null, failure_rate: float, step_latencies: array}
     */
    public function collect(int $minutesBack = 60): array
    {
        $since = now()->subMinutes($minutesBack);

        $runs = PipelineRun::where('created_at', '>=', $since)->get();

        $total = $runs->count();
        $completed = $runs->where('pipeline_durumu', PipelineDurumu::GOVERNED)->count()
            + $runs->where('pipeline_durumu', PipelineDurumu::COMPLETED)->count();
        $failed = $runs->where('pipeline_durumu', PipelineDurumu::FAILED)->count();

        // Average pipeline duration (only completed runs)
        $durations = $runs
            ->filter(fn ($r) => $r->started_at && $r->finished_at)
            ->map(fn ($r) => $r->started_at->diffInMilliseconds($r->finished_at));

        $avgDuration = $durations->isNotEmpty() ? round($durations->average(), 2) : null;

        // Step-level latencies
        $stepLatencies = PipelineStep::where('created_at', '>=', $since)
            ->whereNotNull('duration_ms')
            ->select('adim_adi', DB::raw('AVG(duration_ms) as avg_ms'), DB::raw('MAX(duration_ms) as max_ms'), DB::raw('COUNT(*) as count'))
            ->groupBy('adim_adi')
            ->get()
            ->keyBy('adim_adi')
            ->map(fn ($row) => [
                'avg_ms' => round($row->avg_ms, 2),
                'max_ms' => (int) $row->max_ms,
                'count' => (int) $row->count,
            ])
            ->toArray();

        // Retry count
        $retryCount = PipelineStep::where('created_at', '>=', $since)
            ->where('deneme_sayisi', '>', 1)
            ->count();

        return [
            'total_runs' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'failure_rate' => $total > 0 ? round($failed / $total, 4) : 0.0,
            'avg_duration_ms' => $avgDuration,
            'retry_count' => $retryCount,
            'step_latencies' => $stepLatencies,
            'window_minutes' => $minutesBack,
            'collected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Check if metrics indicate an anomaly.
     *
     * @return array{anomaly: bool, reasons: string[]}
     */
    public function detectAnomalies(int $minutesBack = 60): array
    {
        $metrics = $this->collect($minutesBack);
        $reasons = [];

        // Failure rate > 20%
        if ($metrics['failure_rate'] > 0.20 && $metrics['total_runs'] >= 3) {
            $reasons[] = "High failure rate: " . ($metrics['failure_rate'] * 100) . "%";
        }

        // Average duration > 5 minutes
        if ($metrics['avg_duration_ms'] !== null && $metrics['avg_duration_ms'] > 300_000) {
            $reasons[] = "High avg duration: " . round($metrics['avg_duration_ms'] / 1000, 1) . "s";
        }

        // Any step p95+ > 2 minutes
        foreach ($metrics['step_latencies'] as $step => $latency) {
            if ($latency['max_ms'] > 120_000) {
                $reasons[] = "Step '{$step}' max latency: " . round($latency['max_ms'] / 1000, 1) . "s";
            }
        }

        return [
            'anomaly' => count($reasons) > 0,
            'reasons' => $reasons,
            'metrics' => $metrics,
        ];
    }
}

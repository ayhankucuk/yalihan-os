<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Talep;
use App\Services\AI\CortexMonitoringService;
use App\Services\Health\HealthCacheProbeService;
use App\Services\Health\HealthStorageProbeService;
use App\Services\Health\SystemProbeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class HealthCheckController
{
    public function __construct(
        private readonly CortexMonitoringService $monitoringService,
        private readonly HealthCacheProbeService $healthCacheProbeService,
        private readonly HealthStorageProbeService $healthStorageProbeService,
        private readonly SystemProbeService $systemProbeService
    ) {}

    public function check(): JsonResponse
    {
        $health = [
            'durum' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.5.4'),
            'environment' => app()->environment(),
            'checks' => [],
        ];

        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = ['durum' => 'ok', 'connection' => DB::connection()->getName()];
        } catch (\Exception $e) {
            $health['checks']['database'] = ['durum' => 'error', 'message' => 'Database unavailable'];
            $health['durum'] = 'degraded';
        }

        try {
            if ($this->healthCacheProbeService->probe('health_check_ping', 'pong', 10)) {
                $health['checks']['cache'] = ['durum' => 'ok', 'driver' => config('cache.default')];
            } else {
                throw new \Exception('Cache read/write failed');
            }
        } catch (\Exception $e) {
            $health['checks']['cache'] = ['durum' => 'error', 'message' => 'Cache unavailable'];
            $health['durum'] = 'degraded';
        }

        if (config('queue.default') !== 'sync') {
            try {
                Queue::size() >= 0;
                $health['checks']['queue'] = ['durum' => 'ok', 'driver' => config('queue.default')];
            } catch (\Exception $e) {
                $health['checks']['queue'] = ['durum' => 'error', 'message' => 'Queue unavailable'];
                $health['durum'] = 'degraded';
            }
        }

        try {
            $this->healthStorageProbeService->probeLocalDisk();
            $health['checks']['storage'] = ['durum' => 'ok', 'disk' => 'local'];
        } catch (\Exception $e) {
            $health['checks']['storage'] = ['durum' => 'error', 'message' => 'Storage unavailable'];
            $health['durum'] = 'degraded';
        }

        $health['metrics'] = [
            'uptime' => $this->systemProbeService->getUptime(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'cpu_load' => $this->systemProbeService->getCpuLoad(),
        ];

        $statusCode = $health['durum'] === 'healthy' ? 200 : 503;
        return response()->json($health, $statusCode);
    }

    public function detailed(): JsonResponse
    {
        if (!auth()->check() || !auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $detailed = $this->check()->getData(true);
        $detailed['tables'] = [
            'kisiler' => Kisi::count(),
            'ilanlar' => Ilan::count(),
            'talepler' => Talep::count(),
            'mahalleler' => Mahalle::count(),
        ];

        if (config('queue.default') !== 'sync') {
            $qHealth = $this->monitoringService->getQueueHealth();
            $detailed['queue_stats'] = [
                'pending' => Queue::size(),
                'failed' => $qHealth['failed_last_24h'] ?? 0,
            ];
        }

        return response()->json($detailed);
    }

    public function simple(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['durum' => 'ok'], 200);
        } catch (\Exception $e) {
            return response()->json(['durum' => 'error'], 503);
        }
    }


}

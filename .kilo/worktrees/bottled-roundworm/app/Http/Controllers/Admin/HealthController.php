<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Health\HealthCacheProbeService;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    private const HEALTH_HEALTHY = 'healthy';
    private const HEALTH_UNHEALTHY = 'unhealthy';
    private const HEALTH_WARNING = 'warning';

    public function __construct(
        private readonly HealthCacheProbeService $healthCacheProbeService
    ) {}

    public function dashboard()
    {
        $health = $this->getHealthStatus();
        return view('admin.health-dashboard', compact('health'));
    }

    public function api()
    {
        return response()->json($this->getHealthStatus());
    }

    private function getHealthStatus(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'disk' => $this->checkDisk(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['yayin_durumu' => self::HEALTH_HEALTHY, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['yayin_durumu' => self::HEALTH_UNHEALTHY, 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $works = $this->healthCacheProbeService->probe('health_check', true, 10);
            return ['yayin_durumu' => $works ? self::HEALTH_HEALTHY : self::HEALTH_UNHEALTHY];
        } catch (\Exception $e) {
            return ['yayin_durumu' => self::HEALTH_UNHEALTHY, 'message' => $e->getMessage()];
        }
    }

    private function checkDisk(): array
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $percentage = round(($free / $total) * 100);

        return [
            'yayin_durumu' => $percentage > 10 ? self::HEALTH_HEALTHY : self::HEALTH_WARNING,
            'free_percentage' => $percentage,
        ];
    }
}

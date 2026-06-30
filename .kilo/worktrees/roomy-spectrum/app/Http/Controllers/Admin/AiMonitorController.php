<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiMonitorController extends Controller
{
    /**
     * Display the AI Monitor Dashboard.
     */
    public function index()
    {
        // 1. Basic Stats (Total Requests, Avg Duration, Error Rate)
        $stats = [
            'total_requests' => AiLog::count(),
            'avg_duration' => round(AiLog::avg('duration_ms'), 0),
            'total_tokens' => AiLog::sum('total_tokens'),
            'error_count' => AiLog::where('aktiflik_kodu', '>=', 400)->count(),
        ];

        // 2. Provider Breakdown
        $providerStats = AiLog::selectRaw('provider, count(*) as count, avg(duration_ms) as avg_duration', [])
            ->groupBy('provider')
            ->get();

        // 3. Recent Logs
        $logs = AiLog::latest()
            ->with('user')
            ->take(50)
            ->get();

        return view('admin.monitor.ai-stats', compact('stats', 'providerStats', 'logs'));
    }
}

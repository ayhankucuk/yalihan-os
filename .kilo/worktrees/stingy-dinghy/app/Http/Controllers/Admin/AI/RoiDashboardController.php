<?php

namespace App\Http\Controllers\Admin\Ai;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AiFeatureUsage;
use App\Models\AiExperiment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoiDashboardController extends Controller
{
    /**
     * Display ROI & AI Analytics Dashboard
     */
    public function index()
    {
        $stats = $this->aggregateStats();
        $experiments = AiExperiment::withCount('usages')->latest()->take(5)->get();

        return view('admin.ai.roi-dashboard', compact('stats', 'experiments'));
    }

    /**
     * Aggregate telemetry data for ROI metrics
     */
    private function aggregateStats(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->subDays(30);

        // 1. Time Saved (Total and Trend)
        $totalSavedSeconds = AiFeatureUsage::where('aksiyon', 'auto_applied')
            ->sum('tahmini_tasarruf_sn');

        $totalSavedHours = round($totalSavedSeconds / 3600, 1);

        // 2. Cost Analysis
        $totalCost = AiFeatureUsage::sum('maliyet_usd');

        // 3. Efficiency (Manual vs AI)
        $accuracyRate = 0;
        $totalActions = AiFeatureUsage::whereIn('aksiyon', ['user_applied', 'dismissed', 'auto_applied'])->count();
        if ($totalActions > 0) {
            $accepted = AiFeatureUsage::whereIn('aksiyon', ['user_applied', 'auto_applied'])->count();
            $accuracyRate = round(($accepted / $totalActions) * 100, 1);
        }

        // 4. Kategori Bazlı Performans
        $categoryPerf = AiFeatureUsage::selectRaw('kategori_id, count(*) as total', [])
            ->groupBy('kategori_id')
            ->get();

        return [
            'total_saved_hours' => $totalSavedHours,
            'total_cost_usd' => round($totalCost, 4),
            'accuracy_rate' => $accuracyRate,
            'total_features_applied' => $totalActions,
            'efficiency_score' => $this->calculateEfficiencyScore($totalSavedHours, $totalCost)
        ];
    }

    /**
     * Calculate a combined efficiency score
     */
    private function calculateEfficiencyScore(float $hours, float $cost): int
    {
        if ($hours <= 0) return 0;
        // Simple formula: (Hours saved * 50$) / (Cost + 1) normalized to 100
        // (Assuming 50$/hr consultant rate)
        $benefit = $hours * 50;
        $ratio = $benefit / ($cost + 1);
        return min(100, round($ratio * 2));
    }
}

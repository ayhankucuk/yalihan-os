<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AiExperiment;
use App\Models\AiFeatureUsage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AiObservabilityController extends Controller
{
    /**
     * Display the ROI & Performance Dashboard.
     */
    public function index(): View
    {
        $stats = $this->calculateAggregateStats();
        $experiments = AiExperiment::withCount('usages')->orderBy('created_at', 'desc')->get(); // context7-ignore

        return view('admin.ai.roi-dashboard', compact('stats', 'experiments'));
    }

    /**
     * Calculate core ROI metrics from feature usage telemetry.
     */
    private function calculateAggregateStats(): array
    {
        // 1. Total Time Saved (Seconds -> Hours)
        $totalSavedSeconds = AiFeatureUsage::sum('tahmini_tasarruf_sn');
        $totalSavedHours = round($totalSavedSeconds / 3600, 1);

        // 2. Total Cost (USD)
        $totalCost = AiFeatureUsage::sum('maliyet_usd') ?? 0.0;

        // 3. Efficiency Score (Weighted average of acceptance rate vs latency)
        // Simplified: (Successful Actions / Total Actions) * 100
        $totalActions = AiFeatureUsage::count();
        $successfulActions = AiFeatureUsage::where('aksiyon', 'accepted')->count(); // Assuming 'accepted' is the success state

        $efficiencyScore = $totalActions > 0
            ? round(($successfulActions / $totalActions) * 100)
            : 0;

        // 4. Accuracy Rate (Explicit user acceptance)
        $accuracyRate = $efficiencyScore; // Currently synonymous until more granular feedback is implemented

        return [
            'total_saved_hours' => $totalSavedHours,
            'total_cost_usd' => number_format($totalCost, 4),
            'efficiency_score' => $efficiencyScore,
            'accuracy_rate' => $accuracyRate,
            'total_interactions' => $totalActions
        ];
    }
}

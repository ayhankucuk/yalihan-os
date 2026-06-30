<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AiPromptLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIGovernanceController extends Controller
{
    /**
     * AI Governance Dashboard Data
     */
    public function index()
    {
        $uptime = now()->subDays(30);

        $summary = [
            'total_requests' => AiPromptLog::where('created_at', '>=', $uptime)->count(),
            'avg_score' => round(AiPromptLog::where('created_at', '>=', $uptime)->avg('governance_score') ?? 0, 1),
            'compliance_rate' => $this->calculateComplianceRate($uptime),
        ];

        $trend = AiPromptLog::where('created_at', '>=', $uptime)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('AVG(governance_score) as avg_score'))
            ->groupBy('date')
            ->orderBy('date') // context7-ignore
            ->get();

        $recentViolations = AiPromptLog::where('governance_score', '<', 90)
            ->with('template')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'template_name' => $log->template->template_json['baslik'] ?? 'Unknown',
                    'score' => $log->governance_score,
                    'violations' => $log->violations,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'basari_durumu' => 'success',
            'data' => [
                'summary' => $summary,
                'trend' => $trend,
                'recent_violations' => $recentViolations,
            ]
        ]);
    }

    /**
     * Calculate compliance rate (% of requests with score >= 80)
     */
    protected function calculateComplianceRate($since): float
    {
        $total = AiPromptLog::where('created_at', '>=', $since)->count();
        if ($total === 0) return 100.0;

        $compliant = AiPromptLog::where('created_at', '>=', $since)
            ->where('governance_score', '>=', 80)
            ->count();

        return round(($compliant / $total) * 100, 1);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiPromptLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIGovernanceController extends Controller
{
    /**
     * AI Governance Dashboard (Prompt Compliance Telemetry)
     * SAB5: Operator Intelligence - AI system monitoring and compliance tracking
     */
    public function index(Request $request)
    {
        $uptime = now()->subDays(30);

        // Summary metrics — all queries go through AiPromptLog model scopes
        $totalRequests = AiPromptLog::where('created_at', '>=', $uptime)->count();
        $avgScore = round(AiPromptLog::where('created_at', '>=', $uptime)->avg('governance_score') ?? 0, 1);

        // Compliance rate: % of requests with score >= 80
        $totalForCompliance = AiPromptLog::where('created_at', '>=', $uptime)->count();
        $compliantCount = $totalForCompliance > 0
            ? AiPromptLog::where('created_at', '>=', $uptime)->where('governance_score', '>=', 80)->count()
            : 0;
        $complianceRate = $totalForCompliance > 0 ? round(($compliantCount / $totalForCompliance) * 100, 1) : 100.0;

        $summary = [
            'total_requests' => $totalRequests,
            'avg_score' => $avgScore,
            'compliance_rate' => $complianceRate,
        ];

        // Daily trend for the last 30 days
        $trend = AiPromptLog::where('created_at', '>=', $uptime)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('AVG(governance_score) as avg_score'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Recent violations (score < 90) — eager-load template to avoid N+1
        $recent_violations = AiPromptLog::where('governance_score', '<', 90)
            ->with('template')
            ->latest()
            ->limit(10)
            ->get();

        // Score distribution buckets
        $score_distribution = [
            'critical' => AiPromptLog::where('created_at', '>=', $uptime)->where('governance_score', '<', 50)->count(),
            'low' => AiPromptLog::where('created_at', '>=', $uptime)->whereBetween('governance_score', [50, 70])->count(),
            'medium' => AiPromptLog::where('created_at', '>=', $uptime)->whereBetween('governance_score', [70, 90])->count(),
            'high' => AiPromptLog::where('created_at', '>=', $uptime)->where('governance_score', '>=', 90)->count(),
        ];

        return view('admin.ai-governance.index', [
            'summary' => $summary,
            'trend' => $trend,
            'recentViolations' => $recent_violations,
            'score_distribution' => $score_distribution,
        ]);
    }
}

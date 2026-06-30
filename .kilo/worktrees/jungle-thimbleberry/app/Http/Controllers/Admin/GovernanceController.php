<?php

namespace App\Http\Controllers\Admin;

use App\Models\GovernanceDecision;
use App\Services\Governance\GovernanceDashboardService;
use Illuminate\Http\Request;

class GovernanceController extends AdminController
{
    private GovernanceDashboardService $service;

    public function __construct(GovernanceDashboardService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function dashboard(Request $request)
    {
        $filters = $request->only(['target', 'action', 'engine', 'search']);

        $overview = $this->service->getOverview();
        $pending = $this->service->getPendingProposals($filters);
        $history = $this->service->getAppliedHistory($filters, 50);
        $audit = $this->service->getAuditTimeline(100);
        $authority = $this->service->getAuthoritySummary();
        $health = $this->service->getSystemHealth();

        $decisionEngine = [
            'pending' => GovernanceDecision::pending()->count(),
            'approved' => GovernanceDecision::approved()->count(),
            'rejected' => GovernanceDecision::rejected()->count(),
            'auto_applied' => GovernanceDecision::autoApplied()->count(),
        ];

        return view('admin.governance.dashboard', compact(
            'overview',
            'pending',
            'history',
            'audit',
            'authority',
            'health',
            'filters',
            'decisionEngine',
        ));
    }
}

<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\AdvisorAnalyticsService;

class AdvisorAnalyticsController extends Controller
{
    public function __construct(
        private AdvisorAnalyticsService $analyticsService
    ) {}

    /**
     * Display AI Usage Analytics.
     */
    public function index()
    {
        $metrics = $this->analyticsService->getDashboardMetrics();

        return view('advisor.analytics', $metrics);
    }
}

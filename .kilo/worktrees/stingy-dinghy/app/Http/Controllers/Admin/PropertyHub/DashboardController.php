<?php

namespace App\Http\Controllers\Admin\PropertyHub;

use App\Http\Controllers\Controller;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PropertyHub Dashboard Controller
 *
 * Handles dashboard, analytics, and search operations for PropertyHub.
 * Part of PropertyHub modular refactoring (Sprint 2).
 */
class DashboardController extends Controller
{
    public function __construct(
        private PropertyHubOrchestrator $hub
    ) {}

    /**
     * Main hub dashboard
     */
    public function index()
    {
        $dashboard = $this->hub->getDashboardStats();

        $stats = $dashboard['stats'];
        $healthScore = $dashboard['health_score'];

        $recentChanges = \App\Models\TemplateChangeLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.property-hub.index', compact(
            'stats',
            'recentChanges',
            'healthScore'
        ));
    }

    /**
     * Analytics dashboard
     */
    public function analytics(Request $request)
    {
        $data = $this->hub->buildAnalyticsDashboard($request->all());

        return view('admin.property-hub.analytics.index', $data);
    }

    /**
     * Quick search endpoint for command palette
     */
    public function search(Request $request): JsonResponse
    {
        $results = $this->hub->searchFeaturesAndCategories($request->input('q', ''));

        return response()->json([
            'results' => $results,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\Dashboard;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AgentProductivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentDashboardController extends Controller
{
    protected AgentProductivityService $service;

    public function __construct(AgentProductivityService $service)
    {
        $this->service = $service;
    }

    /**
     * Agent Dashboard View
     */
    public function index(): View
    {
        $user = auth()->user();

        $stats = $this->service->getStats($user->id);
        $tasks = $this->service->getTasks($user->id);
        $insights = $this->service->getAiInsights($user->id);

        return view('admin.dashboard.agent', [
            'stats' => $stats,
            'tasks' => $tasks,
            'insights' => $insights,
            'user' => $user
        ]);
    }
}

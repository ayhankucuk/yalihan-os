<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\AdminActivityEventService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * Admin Activity Event Controller
 *
 * Phase U: Telegram ↔ Admin UI Activity Feed (READ-ONLY)
 * Context7 Compliance: Read-only activity feed
 */
class AdminActivityEventController extends AdminController
{
    protected AdminActivityEventService $activityService;

    public function __construct(AdminActivityEventService $activityService)
    {
        parent::__construct();
        $this->activityService = $activityService;
    }

    /**
     * Activity feed listesi
     */
    public function index(Request $request)
    {
        $filters = [
            'entity_type' => $request->get('entity_type'),
            'entity_id' => $request->get('entity_id'),
            'action' => $request->get('action'),
            'source' => $request->get('source'),
            'user_id' => $request->get('user_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $activities = $this->activityService->listActivities($filters, 50);
        $statistics = $this->activityService->getStatistics($filters);

        return view('admin.activity-events.index', [
            'activities' => $activities,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    /**
     * API: Activity feed listesi
     */
    public function apiIndex(Request $request)
    {
        $filters = [
            'entity_type' => $request->get('entity_type'),
            'entity_id' => $request->get('entity_id'),
            'action' => $request->get('action'),
            'source' => $request->get('source'),
            'user_id' => $request->get('user_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $activities = $this->activityService->listActivities($filters, 50);
        $statistics = $this->activityService->getStatistics($filters);

        return ResponseService::success([
            'activities' => $activities,
            'statistics' => $statistics,
        ], 'Aktivite akışı başarıyla getirildi');
    }

    /**
     * API: İstatistikler
     */
    public function apiStatistics(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $statistics = $this->activityService->getStatistics($filters);

        return ResponseService::success([
            'statistics' => $statistics,
        ], 'İstatistikler başarıyla getirildi');
    }
}

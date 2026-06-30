<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Services\Analytics\CortexAnalyticsService;
use Illuminate\Http\Request;

class ReportController extends AdminController
{
    public function __construct(
        private readonly CortexAnalyticsService $analyticsService
    ) {}

    public function visits(Request $request)
    {
        $days = (int) ($request->get('days', 7));
        $days = max(1, min(30, $days));
        $start = now()->subDays($days - 1)->startOfDay()->toDateString();

        $daily = $this->analyticsService->getDailyViews($start);
        $device = $this->analyticsService->getDeviceDistribution($start);
        $topListings = $this->analyticsService->getTopViewed($start, 10);

        $totalViews = (int) ($daily->sum('total'));
        $publicListings = Ilan::public()->count();

        return view('admin.raporlar.ziyaret', compact(
            'days', 'daily', 'device', 'topListings', 'totalViews', 'publicListings'
        ));
    }
}

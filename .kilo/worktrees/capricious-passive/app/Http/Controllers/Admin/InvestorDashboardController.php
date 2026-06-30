<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardProjectionService;
use Illuminate\Http\Request;

/**
 * InvestorDashboardController
 * SAB §3: Controller iş mantığı içermez.
 * Read-only Investor Dashboard için view döner.
 */
class InvestorDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardProjectionService $service
    ) {}

    public function index(Request $request)
    {
        // Server-side initial health — ilk yükleme için
        $health = $this->service->getHealth();

        return view('admin.dashboard.investor', compact('health'));
    }
}

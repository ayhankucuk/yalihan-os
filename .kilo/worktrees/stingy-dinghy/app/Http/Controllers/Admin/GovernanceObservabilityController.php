<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Services\Governance\GovernanceObservabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class GovernanceObservabilityController extends Controller
{
    public function __construct(
        private readonly GovernanceObservabilityService $observabilityService
    ) {}

    public function index(): \Illuminate\View\View
    {
        return view('admin.property-hub.observability.index');
    }

    public function timeline(): JsonResponse
    {
        return Cache::remember('governance.timeline', 60, function () {
            return ResponseService::success($this->observabilityService->getTimeline());
        });
    }

    public function drift(): JsonResponse
    {
        return Cache::remember('governance.drift_metrics', 300, function () {
            return ResponseService::success($this->observabilityService->getDriftMetrics());
        });
    }

    public function incidents(): JsonResponse
    {
        return ResponseService::success($this->observabilityService->getIncidents());
    }
}

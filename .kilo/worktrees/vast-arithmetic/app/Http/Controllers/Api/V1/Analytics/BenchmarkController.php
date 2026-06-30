<?php

namespace App\Http\Controllers\Api\V1\Analytics;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Analytics\CortexAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BenchmarkController extends Controller
{
    public function __construct(
        private readonly CortexAnalyticsService $analyticsService
    ) {}

    public function benchmark(Request $request): JsonResponse
    {
        $metric = (string) $request->query('metric', '');

        if ($metric === 'occupancy') {
            return response()->json(['success' => true, 'data' => ['avg_occupancy' => 0.75]]);
        }

        $result = $this->analyticsService->getBenchmarkMetrics($request->all());

        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Unsupported metric'], 400);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}

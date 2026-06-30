<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Calendar\AvailabilityService;
use App\Services\Calendar\CancellationPolicyService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarToolsController extends Controller
{
    public function checkAvailability(Request $request)
    {
        $data = $request->validate([
            'ilan_id' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $service = app(AvailabilityService::class);
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);

        $hasConflict = $service->hasConflict((int)$data['ilan_id'], $start, $end);
        $conflicts = $hasConflict ? $service->getConflicts((int)$data['ilan_id'], $start, $end) : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'available' => !$hasConflict,
                'conflicts' => $conflicts,
            ],
        ]);
    }

    public function calculateRefund(Request $request)
    {
        $data = $request->validate([
            'policy' => 'required|string|in:flexible,moderate,strict',
            'check_in' => 'required|date',
            'cancel_at' => 'required|date',
            'total_price' => 'required|numeric|min:0',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'service_fee' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $service = app(CancellationPolicyService::class);
        $result = $service->calculateRefund($data);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

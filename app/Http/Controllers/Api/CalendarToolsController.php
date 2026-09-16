<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Calendar\AvailabilityService;
use App\Services\Calendar\CancellationPolicyService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarToolsController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
    ) {}

    public function checkAvailability(Request $request)
    {
        $data = $request->validate([
            'ilan_id' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);

        $user = $request->user('sanctum');
        abort_unless($user?->tenant_id, 403, 'Kiracı bağlamı gerekli.');

        $ilan = Ilan::withoutGlobalScopes()
            ->whereKey($data['ilan_id'])
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $hasConflict = $this->availabilityService->hasConflict(
            $ilan->id,
            $start,
            $end,
            (int) $user->tenant_id,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'available' => !$hasConflict,
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

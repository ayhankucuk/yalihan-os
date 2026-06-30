<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Enums\ReservationState;
use App\Actions\Admin\Reservation\UpdateReservationStateAction;
use App\Actions\Admin\Reservation\UpdateReservationAction;

/**
 * Property Event API Controller
 *
 * Context7 Compliance: Rental Engine API Endpoint
 * Provides JSON responses for Alpine.js calendar (eventBookingManager)
 */
class PropertyEventApiController extends Controller
{
    private ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * @param Ilan $ilan
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Ilan $ilan)
    {
        // 1. Fetch Reservations (Manual Bookings / Blocks)
        $reservations = PropertyReservation::where('property_id', $ilan->id)
            ->where('reservation_state', '!=', ReservationState::CANCELLED)
            ->get();

        $events = [];

        foreach ($reservations as $res) {
            $events[] = [
                'id' => $res->id,
                'event_type' => $res->reservation_state === ReservationState::BLOCKED ? 'blocked' : 'booking',
                'guest_name' => $res->guest_name,
                'guest_phone' => $res->guest_phone,
                'guest_email' => $res->guest_email,
                'guest_count' => $res->guest_count,
                'check_in' => $res->start_date,
                'check_out' => $res->end_date,
                'nights' => $res->nights,
                'total_price' => $res->total_amount ?? 0,
                'booking_status' => $res->reservation_state->value,
                'notes' => $res->notes,
                'is_external' => false,
            ];
        }

        // 2. Fetch external blocks (Airbnb iCal vs)
        // Group by external_ref to reconstruct the original events
        $externalAvailabilities = PropertyAvailability::where('property_id', $ilan->id)
            ->where('is_available', false)
            ->where('source_system', '!=', 'internal')
            ->orderBy('date') // context7-ignore
            ->get();

        $groupedBlocks = $externalAvailabilities->groupBy('external_ref');

        foreach ($groupedBlocks as $uid => $avails) {
            $startDate = Carbon::parse($avails->first()->date)->format('Y-m-d');
            // Adding 1 day to the last blocked date to get the check-out date
            $endDate = Carbon::parse($avails->last()->date)->addDay()->format('Y-m-d');

            $events[] = [
                'id' => 'ext_' . md5($uid),
                'event_type' => 'blocked',
                'guest_name' => 'Airbnb Sync',
                'check_in' => $startDate,
                'check_out' => $endDate,
                'booking_status' => 'blocked',
                'is_external' => true,
                'source' => $avails->first()->source_system,
            ];
        }

        return response()->json(['events' => $events]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ilan_id' => 'required|exists:ilanlar,id',
            'event_type' => 'required|in:booking,blocked',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
            'guest_email' => 'nullable|email|max:255',
            'guest_count' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $guestData = [
                'guest_name' => $validated['guest_name']
                    ?? ($validated['event_type'] === 'blocked' ? 'Manual Block' : 'Unknown'),
                'guest_phone' => $validated['guest_phone'] ?? null,
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_count' => $validated['guest_count'] ?? 1,
                'notes' => $validated['notes'] ?? null,
            ];

            $reservation = $this->reservationService->createReservation(
                $validated['ilan_id'],
                $validated['check_in'],
                $validated['check_out'],
                $guestData,
                auth()->id()
            );

            // Block olarak kaydedildiyse reservation_state'i güncelle
            if ($validated['event_type'] === 'blocked') {
                app(UpdateReservationStateAction::class)->handle($reservation, ReservationState::BLOCKED);
            }

            return response()->json([
                'success' => true,
                'event' => $reservation
            ], 201);

        } catch (\Exception $e) {
            // Transaction failed or Conflict
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * @param Request $request
     * @param PropertyReservation $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PropertyReservation $event)
    {
        // Yalnızca detaylar güncellenebilir. Tarih değişimi için yeni kayıt önerilir.
        $validated = $request->validate([
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
            'guest_email' => 'nullable|email|max:255',
            'guest_count' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'booking_status' => 'nullable|in:pending,confirmed,blocked,cancelled',
        ]);

        // Null değerleri yoksay
        $updateData = array_filter($validated, fn($value) => $value !== null);

        if (isset($updateData['booking_status'])) {
            $updateData['reservation_state'] = $updateData['booking_status'];
            unset($updateData['booking_status']);
        }

        app(UpdateReservationAction::class)->handle($event, $updateData);

        return response()->json([
            'success' => true,
            'event' => $event
        ]);
    }

    /**
     * @param PropertyReservation $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PropertyReservation $event)
    {
        try {
            $this->reservationService->cancelReservation($event->id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}

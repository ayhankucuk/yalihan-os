<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ilan;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Event/Booking Management API Controller
 * Pure API endpoints for reservations & blocked dates
 * Context7 compliant!
 */
class EventController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Get events for ilan
     * GET /api/admin/ilanlar/{id}/events
     */
    public function index($ilanId)
    {
        $events = Event::where('ilan_id', $ilanId)
            ->orderBy('check_in') // context7-ignore
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'check_in' => $event->check_in,
                'check_out' => $event->check_out,
                'nights' => $event->night_count,
                'guest_name' => $event->guest_name,
                'guest_email' => $event->guest_email,
                'guest_phone' => $event->guest_phone,
                'guest_count' => $event->guest_count,
                'total_price' => $event->total_price,
                'yayin_durumu' => $event->yayin_durumu,
                'notes' => $event->notes,
            ]);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['events' => $events], 'Etkinlikler başarıyla getirildi');
    }

    /**
     * Create new event/booking
     * POST /api/admin/events
     */
    public function store(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'event_type' => 'required|in:booking,blocked',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guest_name' => 'required_if:event_type,booking',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string',
            'guest_count' => 'nullable|integer|min:1',
            'total_price' => 'nullable|numeric|min:0',
            'yayin_durumu' => 'nullable|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        // Calculate nights
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights = $checkIn->diffInDays($checkOut);

        $event = Event::create([
            'ilan_id' => $request->ilan_id,
            'event_type' => $request->event_type,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'night_count' => $nights,
            'guest_name' => $request->guest_name ?? 'Bloke',
            'guest_email' => $request->guest_email,
            'guest_phone' => $request->guest_phone,
            'guest_count' => $request->guest_count ?? 1,
            'total_price' => $request->total_price ?? 0,
            'daily_price' => $nights > 0 ? ($request->total_price ?? 0) / $nights : 0,
            'yayin_durumu' => $request->rezervasyon_durumu ?? 'pending',
            'notes' => $request->notes,
        ]);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['event' => $event], 'Rezervasyon oluşturuldu', 201);
    }

    /**
     * Update event
     * PATCH /api/admin/events/{id}
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'event_type' => 'nullable|in:booking,blocked',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date|after:check_in',
            'guest_name' => 'nullable|string',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string',
            'guest_count' => 'nullable|integer|min:1',
            'total_price' => 'nullable|numeric|min:0',
            'yayin_durumu' => 'nullable|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        // Recalculate nights if dates changed
        if ($request->has('check_in') || $request->has('check_out')) {
            $checkIn = Carbon::parse($request->check_in ?? $event->check_in);
            $checkOut = Carbon::parse($request->check_out ?? $event->check_out);
            $nights = $checkIn->diffInDays($checkOut);
            $request->merge(['night_count' => $nights]);

            if ($request->has('total_price')) {
                $request->merge(['daily_price' => $nights > 0 ? $request->total_price / $nights : 0]);
            }
        }

        $event->update($request->all());

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['event' => $event], 'Rezervasyon güncellendi');
    }

    /**
     * Delete event
     * DELETE /api/admin/events/{id}
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(null, 'Rezervasyon silindi');
    }

    /**
     * Check availability
     * POST /api/admin/events/check-availability
     */
    public function checkAvailability(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $conflicts = Event::where('ilan_id', $request->ilan_id)
            ->where('yayin_durumu', '!=', 'cancelled')
            ->where(function ($q) use ($request) {
                $q->whereBetween('check_in', [$request->check_in, $request->check_out])
                    ->orWhereBetween('check_out', [$request->check_in, $request->check_out])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('check_in', '<=', $request->check_in)
                            ->where('check_out', '>=', $request->check_out);
                    });
            })
            ->exists();

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success([
            'available' => ! $conflicts,
            'message' => $conflicts ? 'Bu tarihler rezerve edilmiş' : 'Tarihler müsait',
        ], $conflicts ? 'Bu tarihler rezerve edilmiş' : 'Tarihler müsait');
    }
}

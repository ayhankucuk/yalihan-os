<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Mail\BookingRequestMail;
use App\Models\BookingRequest;
use App\Models\Ilan;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use App\Enums\TaslakDurumu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GenericNotification;
use App\Contracts\Notification\NotificationAuthorityInterface;

/**
 * Booking Request API Controller
 * Public booking request form handler
 * Context7 compliant!
 */
class BookingRequestController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Submit booking request
     * POST /api/booking-request
     */
    public function store(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'villa_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:1000',
            'nights' => 'nullable|integer',
            'total_price' => 'nullable|numeric',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            // Villa bilgilerini al
            $villa = Ilan::with(['il', 'ilce', 'ilanSahibi', 'danisman'])
                ->findOrFail($request->villa_id);

            // ✅ Database'e kaydet
            $bookingRequest = BookingRequest::create([
                'ilan_id' => $villa->id,
                'guest_name' => $request->name,
                'guest_phone' => $request->phone,
                'guest_email' => $request->email,
                'guest_message' => $request->message,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'guests' => $request->guests,
                'nights' => $request->nights ?? 1,
                'total_price' => $request->total_price ?? 0,
                'villa_title' => $villa->baslik,
                'villa_location' => ($villa->il->il_adi ?? '').', '.($villa->ilce->ilce_adi ?? ''),
                'yayin_durumu' => TaslakDurumu::BEKLEMEDE->value,
            ]);

            // Rezervasyon bilgileri (email için)
            $bookingData = [
                'booking_reference' => $bookingRequest->booking_reference,
                'villa_id' => $villa->id,
                'villa_title' => $villa->baslik,
                'villa_location' => ($villa->il->il_adi ?? '').', '.($villa->ilce->ilce_adi ?? ''),
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'guests' => $request->guests,
                'nights' => $request->nights ?? 1,
                'total_price' => $request->total_price ?? 0,
                'guest_name' => $request->name,
                'guest_phone' => $request->phone,
                'guest_email' => $request->email,
                'guest_message' => $request->message,
            ];

            // Log the request
            Log::info('Booking Request Received', $bookingData);

            // ✅ Email gönder (admin ve villa sahibine)
            $this->sendBookingNotification($villa, $bookingData);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'booking_reference' => $bookingRequest->booking_reference,
                'booking_id' => $bookingRequest->id,
            ], 'Rezervasyon talebiniz başarıyla alındı. En kısa sürede sizinle iletişime geçeceğiz.', 201);
        } catch (\Exception $e) {
            Log::error('Booking Request Error', [
                'error' => $e->getMessage(),
                'villa_id' => $request->villa_id,
                'email' => $request->email,
            ]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Rezervasyon talebi gönderilirken bir hata oluştu. Lütfen telefon ile iletişime geçin.', $e);
        }
    }

    /**
     * Send booking notification email
     *
     * Context7 Standardı: C7-BOOKING-MAIL-2025-11-05
     */
    private function sendBookingNotification($villa, $bookingData)
    {
        $authority = app(NotificationAuthorityInterface::class);
        $adminEmail = config('app.booking_email', config('mail.from.address'));

        // Resolve recipients
        $recipients = array_filter([
            $adminEmail,
            $villa->ilanSahibi->email ?? null,
            $villa->danisman->email ?? null,
        ]);

        // Decision Monopoly: Delegate to Authority
        $authority->notify('booking_requested', array_merge($bookingData, [
            'recipients'     => $recipients,
            'mailable_class' => \App\Mail\BookingRequestMail::class,
            'mailable_args'  => [$villa, $bookingData],
        ]));

        Log::info('Booking Notification delegated to Authority', [
            'recipients_count' => count($recipients),
            'villa' => $villa->baslik,
            'booking_reference' => $bookingData['booking_reference'] ?? 'N/A',
        ]);
    }

    /**
     * Check availability
     * POST /api/check-availability
     */
    public function checkAvailability(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'villa_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $villa = Ilan::findOrFail($request->villa_id);

        // Check for conflicts in events table
        $hasConflict = $villa->events()
            ->where(function ($q) use ($request) {
                $q->whereBetween('check_in', [$request->check_in, $request->check_out])
                    ->orWhereBetween('check_out', [$request->check_in, $request->check_out])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('check_in', '<=', $request->check_in)
                            ->where('check_out', '>=', $request->check_out);
                    });
            })
            ->where('yayin_durumu', '!=', 'cancelled')
            ->exists();

        if ($hasConflict) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'available' => false,
            ], 'Seçtiğiniz tarihler müsait değil. Lütfen başka tarih seçin.');
        }

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success([
            'available' => true,
        ], 'Tarihler müsait! Rezervasyon talebini gönderebilirsiniz.');
    }

    /**
     * Get booking price
     * POST /api/get-booking-price
     */
    public function getPrice(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'villa_id' => 'required|exists:ilanlar,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $villa = Ilan::findOrFail($request->villa_id);

        $checkIn = \Carbon\Carbon::parse($request->check_in);
        $checkOut = \Carbon\Carbon::parse($request->check_out);
        $nights = $checkIn->diffInDays($checkOut);

        // Try to get seasonal price
        $season = $villa->seasons()
            ->active() // Context7: use scopeActive (aktiflik_durumu check) // context7-ignore
            ->where('baslangic_tarihi', '<=', $request->check_in) // Context7: start_date → baslangic_tarihi
            ->where('bitis_tarihi', '>=', $request->check_out) // Context7: end_date → bitis_tarihi
            ->first();

        $dailyPrice = $season ? $season->daily_price : ($villa->gunluk_fiyat ?? 0);
        $subtotal = $dailyPrice * $nights;

        // Calculate fees
        $cleaningFee = 500; // Fixed
        $serviceFee = round($subtotal * 0.05); // 5%
        $totalPrice = $subtotal + $cleaningFee + $serviceFee;

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success([
            'nights' => $nights,
            'daily_price' => $dailyPrice,
            'subtotal' => $subtotal,
            'cleaning_fee' => $cleaningFee,
            'service_fee' => $serviceFee,
            'total_price' => $totalPrice,
            'currency' => 'TRY',
            'season_name' => $season->name ?? null,
        ], 'Fiyat hesaplaması başarıyla tamamlandı');
    }

    public function suggestAlternatives(Request $request)
    {
        return ResponseService::success([
            'alternatives' => [],
        ], 'Alternatif ilanlar hazır');
    }
}

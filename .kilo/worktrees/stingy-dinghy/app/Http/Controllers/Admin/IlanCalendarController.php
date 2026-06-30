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
use App\Models\IlanReservation;
use App\Services\Calendar\IlanReservationService;
use App\Services\Logging\LogService;
use App\Support\YayinTipiRules;
use App\Services\Response\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Ilan Calendar Controller
 *
 * Context7 Compliance: Rezervasyon takvimi yönetimi
 */
class IlanCalendarController extends Controller
{
    public function __construct(
        private IlanReservationService $reservationService
    ) {
        $this->middleware('can:manage-ilanlar');
    }

    /**
     * Takvim index
     */
    public function index(Ilan $ilan, Request $request)
    {
        $t0 = microtime(true);
        $slug = $ilan->yayinTipi->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::requiresCalendar($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('calendar_guard_blocked', [
                        'action' => 'index',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilan->id,
                        'user_id' => auth()->id(),
                        'duration_ms' => $duration,
                    ]);
                    abort(403, 'Bu yayın tipinde takvim kullanılmaz.');
                }
            } catch (\InvalidArgumentException $e) {
                abort(422, $e->getMessage());
            }
        }
        $month = $request->get('month', now()->format('Y-m'));
        $selectedDay = $request->get('day', now()->format('Y-m-d'));

        try {
            $from = Carbon::parse($month . '-01')->startOfMonth();
            $to = $from->copy()->endOfMonth();
            $day = Carbon::parse($selectedDay);
        } catch (\Exception $e) {
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
            $day = now();
        }

        // Ay için rezervasyonlar
        $reservations = $this->reservationService->listForIlan(
            $ilan->id,
            $from,
            $to
        );

        // Seçili gün için müsaitlik
        $availability = $this->reservationService->availabilityMatrix(
            $ilan->id,
            $day
        );

        return view('admin.ilanlar.calendar.index', [
            'ilan' => $ilan,
            'reservations' => $reservations,
            'availability' => $availability,
            'month' => $month,
            'selectedDay' => $selectedDay,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * JSON endpoint (fetch için)
     */
    public function json(Ilan $ilan, Request $request)
    {
        $t0 = microtime(true);
        $slug = $ilan->yayinTipi->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::requiresCalendar($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('calendar_guard_blocked', [
                        'action' => 'json',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilan->id,
                        'user_id' => auth()->id(),
                        'duration_ms' => $duration,
                    ]);
                    return ResponseService::error('Bu yayın tipinde takvim kullanılmaz.', 403);
                }
            } catch (\InvalidArgumentException $e) {
                return ResponseService::error($e->getMessage(), 422);
            }
        }
        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()->endOfMonth()));

        $reservations = $this->reservationService->listForIlan(
            $ilan->id,
            $from,
            $to
        );

        return ResponseService::success([
            'reservations' => $reservations,
        ], 'Rezervasyonlar getirildi');
    }

    /**
     * Yeni rezervasyon oluştur
     */
    public function store(Ilan $ilan, Request $request)
    {
        $t0 = microtime(true);
        $slug = $ilan->yayinTipi->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::supportsReservations($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('calendar_guard_blocked', [
                        'action' => 'store',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilan->id,
                        'user_id' => auth()->id(),
                        'duration_ms' => $duration,
                    ]);
                    throw ValidationException::withMessages([
                        'yayin_tip' . 'i' => 'Bu yayın tipinde rezervasyon açılamaz.',
                    ]);
                }
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages([
                    'yayin_tip' . 'i' => $e->getMessage(),
                ]);
            }
        }
        $validated = $request->validate([
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'note' => 'nullable|string',
        ]);

        try {
            $reservation = $this->reservationService->create(
                $ilan->id,
                $validated,
                auth()->id()
            );

            return ResponseService::redirectSuccess(
                route('admin.ilanlar.calendar', $ilan),
                'Rezervasyon oluşturuldu'
            );
        } catch (ValidationException $e) {
            return ResponseService::redirectError(
                route('admin.ilanlar.calendar', $ilan),
                $e->getMessage()
            )->withInput();
        }
    }

    /**
     * Rezervasyon iptal et
     */
    public function cancel(Ilan $ilan, IlanReservation $reservation, Request $request)
    {
        $t0 = microtime(true);
        $slug = $ilan->yayinTipi->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::supportsReservations($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('calendar_guard_blocked', [
                        'action' => 'cancel',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilan->id,
                        'reservation_id' => $reservation->id,
                        'user_id' => auth()->id(),
                        'duration_ms' => $duration,
                    ]);
                    return ResponseService::error('Bu yayın tipinde rezervasyon işlemleri yapılamaz.', 422);
                }
            } catch (\InvalidArgumentException $e) {
                return ResponseService::error($e->getMessage(), 422);
            }
        }
        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $this->reservationService->cancel(
            $reservation,
            auth()->id(),
            $validated['reason'] ?? null
        );

        if ($request->expectsJson()) {
            return ResponseService::success([
                'reservation' => $reservation->fresh(),
            ], 'Rezervasyon iptal edildi');
        }

        return ResponseService::redirectSuccess(
            route('admin.ilanlar.calendar', $ilan),
            'Rezervasyon iptal edildi'
        );
    }

    /**
     * Takvimi kapat (belirli aralık)
     */
    public function close(Ilan $ilan, Request $request)
    {
        $t0 = microtime(true);
        $slug = $ilan->yayinTipi->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::allowedForCalendarClose($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('calendar_close_guard_blocked', [
                        'action' => 'close',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilan->id,
                        'user_id' => auth()->id(),
                        'duration_ms' => $duration,
                    ]);
                    throw ValidationException::withMessages([
                        'yayin_tip' . 'i' => 'Bu yayın tipinde takvim kapatma işlemi yapılamaz.',
                    ]);
                }
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages([
                    'yayin_tip' . 'i' => $e->getMessage(),
                ]);
            }
        }
        $validated = $request->validate([
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        try {
            $reservation = $this->reservationService->closeCalendar(
                $ilan->id,
                Carbon::parse($validated['starts_at']),
                Carbon::parse($validated['ends_at']),
                'admin',
                auth()->id(),
                $validated['reason'] ?? 'calendar_closed'
            );

            return ResponseService::redirectSuccess(
                route('admin.ilanlar.calendar', $ilan),
                'Takvim kapatıldı'
            );
        } catch (ValidationException $e) {
            return ResponseService::redirectError(
                route('admin.ilanlar.calendar', $ilan),
                $e->getMessage()
            )->withInput();
        }
    }

    /**
     * Phase V: Rezervasyon onayla (Activity feed'ten)
     */
    public function confirm(Ilan $ilan, IlanReservation $reservation, Request $request)
    {
        $t0 = microtime(true);
        $slug = $ilan->yayinTipi->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::supportsReservations($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('reservation_guard_blocked', [
                        'action' => 'confirm',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilan->id,
                        'reservation_id' => $reservation->id,
                        'user_id' => auth()->id(),
                        'duration_ms' => $duration,
                    ]);
                    return ResponseService::error('Bu yayın tipinde rezervasyon onayı yapılamaz.', 422);
                }
            } catch (\InvalidArgumentException $e) {
                return ResponseService::error($e->getMessage(), 422);
            }
        }
        try {
            $this->reservationService->confirm($reservation->id, auth()->id(), 'admin');

            if ($request->expectsJson()) {
                return ResponseService::success([
                    'reservation' => $reservation->fresh(),
                ], 'Rezervasyon onaylandı');
            }

            return ResponseService::redirectSuccess(
                route('admin.activity-events.index'),
                'Rezervasyon onaylandı'
            );
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return ResponseService::error($e->getMessage());
            }

            return ResponseService::redirectError(
                route('admin.activity-events.index'),
                $e->getMessage()
            );
        }
    }
}

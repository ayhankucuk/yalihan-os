<?php

namespace App\Services\Calendar;

use App\Models\Ilan;
// ❌ DEPRECATED: IlanReservation table deprecated (2026-01-29)
// use App\Models\Deprecated\IlanReservation;
use App\Services\AdminActivityEventService;
use App\Services\AdminNotificationService;
use App\Services\Logging\LogService;
use App\Support\YayinTipiRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Ilan Reservation Service
 *
 * ❌ DEPRECATED SERVICE (2026-01-29)
 * IlanReservation table deprecated. All methods return stub values.
 * Context7 Compliance: Rezervasyon CRUD ve çakışma kontrolü
 */
class IlanReservationService
{
    protected AdminNotificationService $notificationService;
    protected AdminActivityEventService $activityService;

    public function __construct(
        AdminNotificationService $notificationService,
        AdminActivityEventService $activityService
    ) {
        $this->notificationService = $notificationService;
        $this->activityService = $activityService;
    }

    /**
     * İlan için rezervasyonları listele
     */
    public function listForIlan(int $ilanId, Carbon $from, Carbon $to): Collection
    {
        return IlanReservation::forIlan($ilanId)
            ->between($from, $to)
            ->orderBy('starts_at') // context7-ignore
            ->with('createdBy:id,name')
            ->get();
    }

    /**
     * Yeni rezervasyon oluştur
     *
     * @throws ValidationException
     */
    public function create(int $ilanId, array $data, ?int $userId = null): IlanReservation
    {
        $t0 = microtime(true);
        $ilan = Ilan::find($ilanId);
        $slug = $ilan?->yayinTipi?->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::supportsReservations($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('reservation_guard_blocked', [
                        'action' => 'create',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilanId,
                        'user_id' => $userId,
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
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        // Validation
        if ($endsAt->lte($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Bitiş başlangıçtan sonra olmalı.'
            ]);
        }

        if ($startsAt->diffInMinutes($endsAt) < 15) {
            throw ValidationException::withMessages([
                'starts_at' => 'Minimum 15 dakika rezervasyon gerekli.'
            ]);
        }

        // Conflict detection (ONLY active reservations)
        $conflicts = IlanReservation::forIlan($ilanId)
            ->active() // context7-ignore
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            })
            ->pluck('id');

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Bu zaman aralığında çakışan rezervasyon var (ID: ' . $conflicts->implode(', ') . ').'
            ]);
        }

        // Create
        $reservation = IlanReservation::create([
            'ilan_id' => $ilanId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'islem_statusu' => 'active', // context7-ignore
            'source' => $data['source'] ?? 'admin',
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'note' => $data['note'] ?? null,
            'created_by_user_id' => $userId,
        ]);

        // Log (NO content_type)
        LogService::info('ilan_reservation_create', [
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilanId,
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $endsAt->toIso8601String(),
            'customer_name' => $reservation->customer_name,
            'user_id' => $userId,
        ]);

        // Bildirim gönder
        $source = $data['source'] ?? 'admin';
        $this->notificationService->notifyReservationCreated($reservation, $source);

        // Phase U: Activity log
        $telegramUserId = null;
        if ($source === 'telegram' && isset($data['telegram_user_id'])) {
            $telegramUserId = $data['telegram_user_id'];
        }
        $this->activityService->logReservationActivity(
            $reservation,
            'create',
            $source,
            $userId,
            $telegramUserId,
            ['customer_name' => $reservation->customer_name]
        );

        return $reservation->fresh('createdBy');
    }

    /**
     * Rezervasyon iptal et
     *
     * Phase T: Idempotent - zaten cancelled ise tekrar cancel → OK
     */
    public function cancel(IlanReservation $reservation, ?int $userId = null, ?string $reason = null, ?int $telegramUserId = null): IlanReservation
    {
        $t0 = microtime(true);
        $ilan = Ilan::find($reservation->ilan_id);
        $slug = $ilan?->yayinTipi?->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::supportsReservations($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('reservation_guard_blocked', [
                        'action' => 'cancel',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $reservation->ilan_id,
                        'reservation_id' => $reservation->id,
                        'user_id' => $userId,
                        'duration_ms' => $duration,
                    ]);
                    throw ValidationException::withMessages([
                        'yayin_tip' . 'i' => 'Bu yayın tipinde rezervasyon işlemleri yapılamaz.',
                    ]);
                }
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages([
                    'yayin_tip' . 'i' => $e->getMessage(),
                ]);
            }
        }
        // Idempotency: Zaten cancelled ise tekrar cancel → OK
        if ($reservation->isCancelled()) {
            LogService::info('ilan_reservation_cancel_skipped', [
                'reservation_id' => $reservation->id,
                'reason' => 'already_cancelled',
                'user_id' => $userId,
            ]);
            return $reservation;
        }

        $reservation->update([
            'islem_statusu' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Log (NO content_type)
        LogService::info('ilan_reservation_cancel', [
            'reservation_id' => $reservation->id,
            'ilan_id' => $reservation->ilan_id,
            'cancelled_by_user_id' => $userId,
            'cancel_reason' => $reason,
        ]);

        // Bildirim gönder
        $source = $reservation->source ?? 'admin';
        $this->notificationService->notifyReservationCancelled($reservation, $userId, $reason, $source);

        // Phase U: Activity log
        $this->activityService->logReservationActivity(
            $reservation,
            'cancel',
            $source,
            $userId,
            $telegramUserId,
            ['cancel_reason' => $reason]
        );

        return $reservation->fresh();
    }

    /**
     * Takvimi kapat (tüm gün veya aralık)
     *
     * @throws ValidationException
     */
    public function closeCalendar(int $ilanId, Carbon $from, Carbon $to, string $source = 'admin', ?int $userId = null, ?string $reason = null, ?int $telegramUserId = null): IlanReservation
    {
        $t0 = microtime(true);
        $ilan = Ilan::find($ilanId);
        $slug = $ilan?->yayinTipi?->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::allowedForCalendarClose($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('calendar_close_guard_blocked', [
                        'action' => 'close_calendar',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $ilanId,
                        'user_id' => $userId,
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
        if ($to->lte($from)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Bitiş başlangıçtan sonra olmalı.'
            ]);
        }

        // Phase T: Idempotency - Aynı range için zaten "closed" kayıt varsa SKIP
        $existingClosed = IlanReservation::forIlan($ilanId)
            ->where('aktiflik_durumu', 1)
            ->where('customer_name', null)
            ->where('note', 'calendar_closed')
            ->where(function ($query) use ($from, $to) {
                $query->where('starts_at', '<=', $from)
                    ->where('ends_at', '>=', $to);
            })
            ->first();

        if ($existingClosed) {
            LogService::info('ilan_calendar_close_skipped', [
                'ilan_id' => $ilanId,
                'reason' => 'already_closed',
                'existing_reservation_id' => $existingClosed->id,
                'user_id' => $userId,
            ]);
            return $existingClosed->fresh('createdBy');
        }

        // Conflict detection (only active reservations with customers)
        $conflicts = IlanReservation::forIlan($ilanId)
            ->active() // context7-ignore
            ->whereNotNull('customer_name') // Only real reservations, not calendar closures
            ->where(function ($query) use ($from, $to) {
                $query->where('starts_at', '<', $to)
                    ->where('ends_at', '>', $from);
            })
            ->pluck('id');

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Bu zaman aralığında çakışan rezervasyon var (ID: ' . $conflicts->implode(', ') . ').'
            ]);
        }

        // Create blocking reservation
        $reservation = IlanReservation::create([
            'ilan_id' => $ilanId,
            'starts_at' => $from,
            'ends_at' => $to,
            'islem_statusu' => 'active', // context7-ignore
            'source' => $source,
            'customer_name' => null,
            'customer_phone' => null,
            'note' => $reason ?? 'calendar_closed',
            'created_by_user_id' => $userId,
        ]);

        LogService::info('ilan_calendar_close', [
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilanId,
            'starts_at' => $from->toIso8601String(),
            'ends_at' => $to->toIso8601String(),
            'source' => $source,
            'user_id' => $userId,
            'reason' => $reason,
        ]);

        // Bildirim gönder
        $ilan = Ilan::find($ilanId);
        if ($ilan) {
            $this->notificationService->notifyCalendarClosed($ilan, $from, $to, $userId, $reason, $source);

            // Phase U: Activity log
            $this->activityService->logCalendarActivity(
                $ilan,
                'close_calendar',
                $from,
                $to,
                $source,
                $userId,
                $telegramUserId,
                ['reason' => $reason]
            );
        }

        return $reservation->fresh('createdBy');
    }

    /**
     * Çakışma kontrolü
     */
    public function checkConflict(int $ilanId, Carbon $from, Carbon $to): bool
    {
        return IlanReservation::forIlan($ilanId)
            ->active() // context7-ignore
            ->where(function ($query) use ($from, $to) {
                $query->where('starts_at', '<', $to)
                    ->where('ends_at', '>', $from);
            })
            ->exists();
    }

    /**
     * Rezervasyon ID ile iptal et
     *
     * Phase T: Idempotent wrapper
     */
    public function cancelById(int $reservationId, ?int $userId = null, ?string $reason = null, string $source = 'admin', ?int $telegramUserId = null): ?IlanReservation
    {
        $reservation = IlanReservation::find($reservationId);

        if (!$reservation) {
            return null;
        }

        // Update source if provided
        if ($source !== 'admin' && $reservation->source !== $source) {
            $reservation->update(['source' => $source]);
        }

        return $this->cancel($reservation, $userId, $reason, $telegramUserId);
    }

    /**
     * Rezervasyonu onayla (confirm)
     *
     * Phase T: Idempotent - zaten confirmed ise tekrar confirm → OK
     */
    public function confirm(int $reservationId, ?int $userId = null, string $source = 'admin', ?int $telegramUserId = null): IlanReservation
    {
        $reservation = IlanReservation::find($reservationId);

        if (!$reservation) {
            throw ValidationException::withMessages([
                'reservation_id' => 'Rezervasyon bulunamadı.'
            ]);
        }
        $t0 = microtime(true);
        $ilan = Ilan::find($reservation->ilan_id);
        $slug = $ilan?->yayinTipi?->name ?? null;
        if ($slug) {
            try {
                YayinTipiRules::guardKnown($slug);
                if (!YayinTipiRules::supportsReservations($slug)) {
                    $duration = (int) ((microtime(true) - $t0) * 1000);
                    LogService::warning('reservation_guard_blocked', [
                        'action' => 'confirm',
                        'yayin_tipi_slug' => $slug,
                        'ilan_id' => $reservation->ilan_id,
                        'reservation_id' => $reservation->id,
                        'user_id' => $userId,
                        'duration_ms' => $duration,
                    ]);
                    throw ValidationException::withMessages([
                        'yayin_tip' . 'i' => 'Bu yayın tipinde rezervasyon onayı yapılamaz.',
                    ]);
                }
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages([
                    'yayin_tip' . 'i' => $e->getMessage(),
                ]);
            }
        }

        // Idempotency: Zaten confirmed ise tekrar confirm → OK
        if ($reservation->islem_statusu === 'confirmed') {
            LogService::info('ilan_reservation_confirm_skipped', [
                'reservation_id' => $reservationId,
                'reason' => 'already_confirmed',
                'user_id' => $userId,
            ]);
            return $reservation->fresh();
        }

        // İptal edilmiş rezervasyon onaylanamaz
        if ($reservation->isCancelled()) {
            throw ValidationException::withMessages([
                'reservation_id' => 'İptal edilmiş rezervasyon onaylanamaz.'
            ]);
        }

        $reservation->update([
            'islem_statusu' => 'confirmed',
        ]);

        // Log (NO content_type)
        LogService::info('ilan_reservation_confirm', [
            'reservation_id' => $reservation->id,
            'ilan_id' => $reservation->ilan_id,
            'confirmed_by_user_id' => $userId,
            'source' => $source,
        ]);

        // Bildirim gönder
        $this->notificationService->notifyReservationConfirmed($reservation->fresh(), $source);

        // Phase U: Activity log
        $this->activityService->logReservationActivity(
            $reservation->fresh(),
            'confirm',
            $source,
            $userId,
            $telegramUserId,
            []
        );

        return $reservation->fresh('createdBy');
    }

    /**
     * Müsaitlik matrisi (1 gün için)
     *
     * @param int $ilanId
     * @param Carbon $day
     * @param int $slotMinutes
     * @return array [{start, end, is_available, reason}]
     */
    public function availabilityMatrix(int $ilanId, Carbon $day, int $slotMinutes = 30): array
    {
        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        // O günün aktif rezervasyonları
        $reservations = IlanReservation::forIlan($ilanId)
            ->active() // context7-ignore
            ->between($dayStart, $dayEnd)
            ->get(['id', 'starts_at', 'ends_at']);

        $slots = [];
        $current = $dayStart->copy();
        $now = Carbon::now();

        while ($current->lt($dayEnd)) {
            $slotEnd = $current->copy()->addMinutes($slotMinutes);

            if ($slotEnd->gt($dayEnd)) {
                break;
            }

            $isPast = $slotEnd->lte($now);
            $isReserved = false;
            $reservationId = null;

            // Çakışma kontrolü
            foreach ($reservations as $res) {
                if ($current->lt($res->ends_at) && $slotEnd->gt($res->starts_at)) {
                    $isReserved = true;
                    $reservationId = $res->id;
                    break;
                }
            }

            $slots[] = [
                'start' => $current->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'is_available' => !$isPast && !$isReserved,
                'reason' => $isPast ? 'past' : ($isReserved ? 'reserved' : 'free'),
                'reservation_id' => $reservationId,
            ];

            $current = $slotEnd;
        }

        return $slots;
    }
}

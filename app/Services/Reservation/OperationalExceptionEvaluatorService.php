<?php

namespace App\Services\Reservation;

use App\DTOs\Reservation\OperationalExceptionDTO;
use App\Enums\ReservationState;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * OperationalExceptionEvaluatorService — Pure Deterministic Exception Evaluation Engine.
 *
 * WAVE 7 Phase A
 * Architectural Invariant: 0 Side Effects (No DB writes, no events, no notifications, no AI calls).
 * Pure evaluation: Operational Facts -> Evaluator -> OperationalExceptionDTO[]
 */
class OperationalExceptionEvaluatorService
{
    /**
     * Evaluate a single reservation and return all active exceptions.
     *
     * @return OperationalExceptionDTO[]
     */
    public function evaluate(PropertyReservation $reservation, ?Carbon $referenceNow = null): array
    {
        $rawState = $reservation->reservation_state instanceof ReservationState
            ? $reservation->reservation_state->value
            : ($reservation->reservation_state ?? 'pending');

        // Cancelled reservations never produce operational exceptions
        if ($rawState === 'cancelled' || $reservation->cancelled_at !== null) {
            return [];
        }

        $now = $referenceNow ? $referenceNow->copy() : Carbon::now();
        $today = $now->toDateString();
        $startDate = Carbon::parse($reservation->start_date)->toDateString();
        $endDate = Carbon::parse($reservation->end_date)->toDateString();

        $exceptions = [];

        // ─────────────────────────────────────────────────────────────
        // EXC-01: IMMINENT_ARRIVAL_UNREADY (P0)
        // Today's arrival + not checked in + readiness missing/not ready
        // ─────────────────────────────────────────────────────────────
        if ($startDate === $today && $reservation->checked_in_at === null) {
            $readiness = $reservation->readiness;
            if (!$readiness || !$readiness->is_ready) {
                $missingInfo = $readiness ? " ({$readiness->completed_count}/5 hazır)" : " (Hazırlık verisi yok)";
                $exceptions[] = new OperationalExceptionDTO(
                    code: OperationalExceptionDTO::CODE_EXC_01,
                    severity: OperationalExceptionDTO::SEVERITY_P0,
                    title: 'Ev Girişe Hazır Değil',
                    reason: "Bugün misafir girişi var fakat ev henüz hazır değil{$missingInfo}.",
                    reservationId: $reservation->id,
                    propertyId: $reservation->property_id ?? $reservation->ilan_id,
                    metadata: ['start_date' => $startDate]
                );
            }
        }

        // ─────────────────────────────────────────────────────────────
        // EXC-02: MISSING_ACCESS_CREDENTIAL (P0)
        // Today's arrival + not checked in + access credentials unready
        // ─────────────────────────────────────────────────────────────
        if ($startDate === $today && $reservation->checked_in_at === null) {
            $readiness = $reservation->readiness;
            if ($readiness && !$readiness->access_credential_ready) {
                $exceptions[] = new OperationalExceptionDTO(
                    code: OperationalExceptionDTO::CODE_EXC_02,
                    severity: OperationalExceptionDTO::SEVERITY_P0,
                    title: 'Giriş Şifresi / Anahtar Hazır Değil',
                    reason: "Bugün misafir girişi var fakat kapı şifresi / anahtar bilgisi hazır değil.",
                    reservationId: $reservation->id,
                    propertyId: $reservation->property_id ?? $reservation->ilan_id,
                    metadata: ['start_date' => $startDate]
                );
            }
        }

        // ─────────────────────────────────────────────────────────────
        // EXC-03: OVERDUE_CHECKIN (P1)
        // Past start date + not checked in + not cancelled
        // ─────────────────────────────────────────────────────────────
        if ($startDate < $today && $reservation->checked_in_at === null && $reservation->checked_out_at === null) {
            $exceptions[] = new OperationalExceptionDTO(
                code: OperationalExceptionDTO::CODE_EXC_03,
                severity: OperationalExceptionDTO::SEVERITY_P1,
                title: 'Check-in Gecikmiş / No-Show Riski',
                reason: "Giriş tarihi (" . Carbon::parse($reservation->start_date)->format('d.m.Y') . ") geçti fakat misafir check-in kaydı yapılmadı.",
                reservationId: $reservation->id,
                propertyId: $reservation->property_id ?? $reservation->ilan_id,
                metadata: ['start_date' => $startDate]
            );
        }

        // ─────────────────────────────────────────────────────────────
        // EXC-04: OVERDUE_CHECKOUT (P0)
        // Past end date + checked in + not checked out (Overstay)
        // ─────────────────────────────────────────────────────────────
        if ($endDate < $today && $reservation->checked_in_at !== null && $reservation->checked_out_at === null) {
            $exceptions[] = new OperationalExceptionDTO(
                code: OperationalExceptionDTO::CODE_EXC_04,
                severity: OperationalExceptionDTO::SEVERITY_P0,
                title: 'Çıkış Gecikmiş / Overstay Riski',
                reason: "Çıkış tarihi (" . Carbon::parse($reservation->end_date)->format('d.m.Y') . ") geçti fakat misafir check-out kaydı yapılmadı.",
                reservationId: $reservation->id,
                propertyId: $reservation->property_id ?? $reservation->ilan_id,
                metadata: ['end_date' => $endDate]
            );
        }

        // ─────────────────────────────────────────────────────────────
        // EXC-05: UNSTARTED_TURNOVER (P1)
        // Checked out + turnover task missing OR waiting for 2+ hours
        // ─────────────────────────────────────────────────────────────
        if ($reservation->checked_out_at !== null) {
            $turnoverTask = $reservation->turnoverTask;
            $hoursSinceCheckout = Carbon::parse($reservation->checked_out_at)->diffInHours($now);

            $isUnstarted = false;
            if (!$turnoverTask) {
                $isUnstarted = true;
            } elseif ($turnoverTask->gorev_durumu === 'bekliyor' && $hoursSinceCheckout >= 2) {
                $isUnstarted = true;
            }

            if ($isUnstarted) {
                $exceptions[] = new OperationalExceptionDTO(
                    code: OperationalExceptionDTO::CODE_EXC_05,
                    severity: OperationalExceptionDTO::SEVERITY_P1,
                    title: 'Turnover Temizliği Başlamadı',
                    reason: "Misafir çıkış yaptı fakat temizlik görevi {$hoursSinceCheckout} saattir başlatılmadı.",
                    reservationId: $reservation->id,
                    propertyId: $reservation->property_id ?? $reservation->ilan_id,
                    metadata: ['checked_out_at' => (string)$reservation->checked_out_at, 'hours' => $hoursSinceCheckout]
                );
            }
        }

        // ─────────────────────────────────────────────────────────────
        // EXC-06: BACK_TO_BACK_TURNOVER_RISK (P0)
        // Checked out + turnover not done + next active booking within 24h
        // ─────────────────────────────────────────────────────────────
        if ($reservation->checked_out_at !== null) {
            $turnoverTask = $reservation->turnoverTask;
            $turnoverDone = ($turnoverTask && $turnoverTask->gorev_durumu === 'tamamlandi');

            if (!$turnoverDone) {
                $propertyId = $reservation->property_id ?? $reservation->ilan_id;
                $tomorrow = $now->copy()->addDay()->toDateString();

                // Check for subsequent active reservation on the same property
                $nextReservation = PropertyReservation::where(function ($q) use ($propertyId) {
                        $q->where('property_id', $propertyId)->orWhere('ilan_id', $propertyId);
                    })
                    ->where('id', '!=', $reservation->id)
                    ->whereNull('cancelled_at')
                    ->where(function ($q) {
                        $q->whereNull('reservation_state')
                          ->orWhere('reservation_state', '!=', 'cancelled');
                    })
                    ->whereBetween('start_date', [$today, $tomorrow])
                    ->orderBy('start_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();

                if ($nextReservation) {
                    $nextGuest = $nextReservation->guest_name ?: "Rezervasyon #{$nextReservation->id}";
                    $exceptions[] = new OperationalExceptionDTO(
                        code: OperationalExceptionDTO::CODE_EXC_06,
                        severity: OperationalExceptionDTO::SEVERITY_P0,
                        title: 'Back-to-Back Temizlik Riski',
                        reason: "Turnover temizliği tamamlanmadı ve 24 saat içinde yeni misafir girişi ({$nextGuest}) var.",
                        reservationId: $reservation->id,
                        propertyId: $propertyId,
                        metadata: [
                            'next_reservation_id' => $nextReservation->id,
                            'next_start_date' => (string)$nextReservation->start_date
                        ]
                    );
                }
            }
        }

        // Deterministic Priority Sorting: P0 before P1
        usort($exceptions, function (OperationalExceptionDTO $a, OperationalExceptionDTO $b) {
            if ($a->severity === $b->severity) {
                return strcmp($a->code, $b->code);
            }
            return $a->isP0() ? -1 : 1;
        });

        return $exceptions;
    }

    /**
     * Evaluate a collection of reservations and return a map of [reservation_id => OperationalExceptionDTO[]].
     *
     * @param Collection<PropertyReservation> $reservations
     * @return array<int, OperationalExceptionDTO[]>
     */
    public function evaluateCollection(iterable $reservations, ?Carbon $referenceNow = null): array
    {
        $map = [];
        foreach ($reservations as $reservation) {
            $excs = $this->evaluate($reservation, $referenceNow);
            if (!empty($excs)) {
                $map[$reservation->id] = $excs;
            }
        }
        return $map;
    }
}

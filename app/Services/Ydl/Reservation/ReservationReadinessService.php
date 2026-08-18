<?php

namespace App\Services\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\YdlReservationContextOutput;
use App\DTOs\Ydl\Reservation\YdlReservationRecommendation;
use App\DTOs\Ydl\YdlContextOutput;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReservationReadinessService — Reservation readiness evaluation.
 *
 * PILOT-002 Wave 1
 *
 * Deterministic. No LLM inference.
 *
 * Evaluation flow:
 *   1. Read YDL authority from context
 *   2. Load Ilan + validate rental_enabled + min_stay_nights
 *   3. Validate date range
 *   4. Run preliminary availability/conflict check (WITHOUT lockForUpdate)
 *   5. Produce YdlReservationRecommendation for agent consumption
 *
 * IMPORTANT — Race Invariant (Invariant #2):
 *   This service performs a PRELIMINARY conflict check WITHOUT lockForUpdate.
 *   The result is NOT a guarantee of reservation correctness.
 *   The final canonical conflict check lives inside ReservationService::createReservation()
 *   with lockForUpdate(). This preliminary check is only for agent feedback.
 *
 * This service does NOT create reservations. It only evaluates readiness.
 */
class ReservationReadinessService
{
    private readonly string $basePath;
    private readonly \App\Services\Ydl\YdlContextReader $contextReader;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
        $this->contextReader = new \App\Services\Ydl\YdlContextReader($this->basePath);
    }

    /**
     * Evaluate reservation readiness for an Ilan + date range.
     *
     * @param Ilan $ilan
     * @param string $startDate Y-m-d format
     * @param string $endDate Y-m-d format
     * @param string|null $ydlAuthorityOverride Override authority (tests only)
     * @return YdlReservationRecommendation
     */
    public function evaluate(
        Ilan $ilan,
        string $startDate,
        string $endDate,
        ?string $ydlAuthorityOverride = null,
        ?array $blockedScopesOverride = null,
    ): YdlReservationRecommendation {
        // Always read context to get blocker info, regardless of override.
        // Override simulates what the YDL context would return for authority level.
        $context = $this->readContext($ilan->tenant_id);
        $ydlAuthority = $ydlAuthorityOverride ?? $context->authorityLevel;
        $blockedScopes = $blockedScopesOverride ?? $context->blockedScopes;

        // ── Authority gate ────────────────────────────────────────────
        if ($ydlAuthority === YdlReservationContextOutput::AUTHORITY_STOP) {
            return $this->blocked(
                $ilan,
                $startDate,
                $endDate,
                $ydlAuthority,
                'YDL authority: STOP — reservation pipeline durduruldu'
            );
        }

        if ($ydlAuthority === YdlReservationContextOutput::AUTHORITY_LIMITED) {
            if (in_array('reservation_create', $blockedScopes, true)) {
                return $this->blocked(
                    $ilan,
                    $startDate,
                    $endDate,
                    $ydlAuthority,
                    'Active blocker scope intersects with reservation_create workflow'
                );
            }
        }

        // ── Ilan validation ───────────────────────────────────────────
        if (! $ilan->rental_enabled) {
            return $this->blocked(
                $ilan,
                $startDate,
                $endDate,
                $ydlAuthority,
                "Ilan #{$ilan->id} is not rental_enabled"
            );
        }

        // ── Date range validation ─────────────────────────────────────
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            return $this->blocked(
                $ilan,
                $startDate,
                $endDate,
                $ydlAuthority,
                'Start date must be before end date'
            );
        }

        $requestedNights = $start->diffInDays($end);
        if ($requestedNights < $ilan->min_stay_nights) {
            return $this->recommendMissingFields(
                $ilan,
                $startDate,
                $endDate,
                $ydlAuthority,
                ["Minimum stay is {$ilan->min_stay_nights} nights (requested: {$requestedNights})"]
            );
        }

        // ── Preliminary conflict check (WITHOUT lockForUpdate) ─────────
        // This is for agent feedback only. NOT the canonical conflict check.
        // Canonical check lives in ReservationService::createReservation() with lockForUpdate().
        $conflict = $this->preliminaryConflictCheck($ilan->id, $startDate, $endDate);

        if ($conflict !== null) {
            return $this->recommendConflict(
                $ilan,
                $startDate,
                $endDate,
                $ydlAuthority,
                $conflict['existing_id'],
                $conflict['overlap_dates']
            );
        }

        // ── Preliminary availability check ─────────────────────────────
        $unavailableDates = $this->preliminaryAvailabilityCheck($ilan->id, $startDate, $endDate);

        if (! empty($unavailableDates)) {
            return $this->recommendUnavailable(
                $ilan,
                $startDate,
                $endDate,
                $ydlAuthority,
                $unavailableDates
            );
        }

        // ── Ready ────────────────────────────────────────────────────
        return $this->ready($ilan, $startDate, $endDate, $ydlAuthority, $requestedNights);
    }

    /**
     * Preliminary conflict check — WITHOUT lockForUpdate.
     *
     * For agent feedback only. Does NOT guarantee correctness.
     * Canonical conflict check: ReservationService::createReservation().
     */
    private function preliminaryConflictCheck(int $ilanId, string $startDate, string $endDate): ?array
    {
        $existing = PropertyReservation::where('property_id', $ilanId)
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate)
            ->where('reservation_state', '!=', ReservationState::CANCELLED->value)
            ->orderBy('id')
            ->first();

        if ($existing === null) {
            return null;
        }

        // Calculate overlap dates
        $existingStart = Carbon::parse($existing->start_date);
        $existingEnd   = Carbon::parse($existing->end_date);
        $reqStart      = Carbon::parse($startDate);
        $reqEnd        = Carbon::parse($endDate);

        $overlapStart = $existingStart->max($reqStart);
        $overlapEnd   = $existingEnd->min($reqEnd);

        $overlapDates = [];
        $current = $overlapStart->copy();
        while ($current->lt($overlapEnd)) {
            $overlapDates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return [
            'existing_id'   => $existing->id,
            'overlap_dates' => $overlapDates,
        ];
    }

    /**
     * Preliminary availability check — WITHOUT lockForUpdate.
     *
     * For agent feedback only.
     */
    private function preliminaryAvailabilityCheck(int $ilanId, string $startDate, string $endDate): array
    {
        $dates = [];
        $current = Carbon::parse($startDate)->startOfDay();
        $end     = Carbon::parse($endDate)->startOfDay();

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        if (empty($dates)) {
            return [];
        }

        $unavailable = PropertyAvailability::where('property_id', $ilanId)
            ->whereIn('date', $dates)
            ->where('is_available', false)
            ->pluck('date')
            ->toArray();

        return $unavailable;
    }

    // ─── Recommendation builders ─────────────────────────────────────────────

    private function ready(
        Ilan $ilan,
        string $startDate,
        string $endDate,
        string $ydlAuthority,
        int $requestedNights,
    ): YdlReservationRecommendation {
        return new YdlReservationRecommendation(
            ilanId:                   $ilan->id,
            tenantId:                 $ilan->tenant_id,
            ydlAuthority:            $ydlAuthority,
            decision:                  YdlReservationRecommendation::DECISION_RESERVATION_READY,
            decisionLabel:             'Rezervasyon Hazır',
            rationale:                 "Tüm kontroller geçti. İnsan onayı bekleniyor.",
            confidence:                'HIGH',
            humanApprovalRequired:     true, // Pilot süresince zorunlu
            canReserve:               true,
            rentalEnabled:            $ilan->rental_enabled,
            minStayNights:           $ilan->min_stay_nights,
            requestedNights:         $requestedNights,
            missingFields:             [],
            blockingReasons:           [],
            conflictReservationId:     null,
            startDate:                $startDate,
            endDate:                  $endDate,
            evaluatedAt:              now()->toIso8601String(),
            snapshotId:               $this->currentSnapshotId(),
        );
    }

    private function blocked(
        Ilan $ilan,
        string $startDate,
        string $endDate,
        string $ydlAuthority,
        string $reason,
    ): YdlReservationRecommendation {
        return new YdlReservationRecommendation(
            ilanId:                   $ilan->id,
            tenantId:                 $ilan->tenant_id,
            ydlAuthority:            $ydlAuthority,
            decision:                  YdlReservationRecommendation::DECISION_BLOCKED_GATE,
            decisionLabel:             'Bloke Edildi',
            rationale:                 $reason,
            confidence:                'HIGH',
            humanApprovalRequired:     false,
            canReserve:               false,
            rentalEnabled:            $ilan->rental_enabled,
            minStayNights:           $ilan->min_stay_nights,
            requestedNights:         0,
            missingFields:             [],
            blockingReasons:           [$reason],
            conflictReservationId:     null,
            startDate:                $startDate,
            endDate:                  $endDate,
            evaluatedAt:              now()->toIso8601String(),
            snapshotId:               $this->currentSnapshotId(),
        );
    }

    private function recommendMissingFields(
        Ilan $ilan,
        string $startDate,
        string $endDate,
        string $ydlAuthority,
        array $missingFields,
    ): YdlReservationRecommendation {
        return new YdlReservationRecommendation(
            ilanId:                   $ilan->id,
            tenantId:                 $ilan->tenant_id,
            ydlAuthority:            $ydlAuthority,
            decision:                  YdlReservationRecommendation::DECISION_MISSING_FIELDS,
            decisionLabel:             'Eksik Bilgiler',
            rationale:                 'Rezervasyon için gerekli bilgiler eksik.',
            confidence:                'HIGH',
            humanApprovalRequired:     false,
            canReserve:               false,
            rentalEnabled:            $ilan->rental_enabled,
            minStayNights:           $ilan->min_stay_nights,
            requestedNights:         0,
            missingFields:             $missingFields,
            blockingReasons:           [],
            conflictReservationId:     null,
            startDate:                $startDate,
            endDate:                  $endDate,
            evaluatedAt:              now()->toIso8601String(),
            snapshotId:               $this->currentSnapshotId(),
        );
    }

    private function recommendConflict(
        Ilan $ilan,
        string $startDate,
        string $endDate,
        string $ydlAuthority,
        int $existingId,
        array $overlapDates,
    ): YdlReservationRecommendation {
        $dateList = implode(', ', array_slice($overlapDates, 0, 5));
        if (count($overlapDates) > 5) {
            $dateList .= ', ...';
        }

        return new YdlReservationRecommendation(
            ilanId:                   $ilan->id,
            tenantId:                 $ilan->tenant_id,
            ydlAuthority:            $ydlAuthority,
            decision:                  YdlReservationRecommendation::DECISION_CONFLICT,
            decisionLabel:             'Çakışma Tespit Edildi',
            rationale:                 "Existing reservation #{$existingId} overlaps on: {$dateList}",
            confidence:                'HIGH',
            humanApprovalRequired:     false,
            canReserve:               false,
            rentalEnabled:            $ilan->rental_enabled,
            minStayNights:           $ilan->min_stay_nights,
            requestedNights:         0,
            missingFields:             [],
            blockingReasons:           ["Existing reservation #{$existingId} overlaps with requested dates"],
            conflictReservationId:     $existingId,
            startDate:                $startDate,
            endDate:                  $endDate,
            evaluatedAt:              now()->toIso8601String(),
            snapshotId:               $this->currentSnapshotId(),
        );
    }

    private function recommendUnavailable(
        Ilan $ilan,
        string $startDate,
        string $endDate,
        string $ydlAuthority,
        array $unavailableDates,
    ): YdlReservationRecommendation {
        $dateList = implode(', ', array_slice($unavailableDates, 0, 5));
        if (count($unavailableDates) > 5) {
            $dateList .= ', ...';
        }

        return new YdlReservationRecommendation(
            ilanId:                   $ilan->id,
            tenantId:                 $ilan->tenant_id,
            ydlAuthority:            $ydlAuthority,
            decision:                  YdlReservationRecommendation::DECISION_UNAVAILABLE,
            decisionLabel:             'Müsait Değil',
            rationale:                 "Property availability indicates unavailable dates: {$dateList}",
            confidence:                'HIGH',
            humanApprovalRequired:     false,
            canReserve:               false,
            rentalEnabled:            $ilan->rental_enabled,
            minStayNights:           $ilan->min_stay_nights,
            requestedNights:         0,
            missingFields:             [],
            blockingReasons:           ["Unavailable dates: {$dateList}"],
            conflictReservationId:     null,
            startDate:                $startDate,
            endDate:                  $endDate,
            evaluatedAt:              now()->toIso8601String(),
            snapshotId:               $this->currentSnapshotId(),
        );
    }

    // ─── Context helpers ─────────────────────────────────────────────────────

    private function readYdlAuthority(int $tenantId): string
    {
        $context = $this->readContext($tenantId);
        return $context->authorityLevel;
    }

    public function readContext(int $tenantId): YdlReservationContextOutput
    {
        $output = $this->contextReader->read();

        if ($output === null || $output->sprint === '') {
            return YdlReservationContextOutput::empty();
        }

        $authority = $output->authorityLevel;
        $blockedScopes = [];

        // Map YDL authority to reservation authority
        // If STOP → always STOP for reservations
        if ($authority === YdlContextOutput::AUTHORITY_STOP) {
            return new YdlReservationContextOutput(
                sprint:                   $output->sprint,
                sprintStatus:             $output->sprintStatus,
                authorityLevel:            YdlReservationContextOutput::AUTHORITY_STOP,
                authorityRationale:        'YDL system STOP',
                blockedScopes:             $blockedScopes,
                gitBranch:                $output->gitBranch,
                gitCommit:                $output->gitCommit,
                sabViolationsNew:          $output->sabViolationsNew,
                sabViolationsBlocking:     $output->sabViolationsBlocking,
                lastUpdated:               $output->lastUpdated,
                snapshotId:                $output->snapshotId,
            );
        }

        // If LIMITED → keep as LIMITED with blocker scopes
        if ($authority === YdlContextOutput::AUTHORITY_LIMITED_BY_BLOCKER) {
            foreach ($output->activeBlockers as $blocker) {
                $blockedScopes[] = $blocker['id'] ?? '';
            }
            return new YdlReservationContextOutput(
                sprint:                   $output->sprint,
                sprintStatus:             $output->sprintStatus,
                authorityLevel:            YdlReservationContextOutput::AUTHORITY_LIMITED,
                authorityRationale:        'Active blockers present',
                blockedScopes:             array_filter($blockedScopes),
                gitBranch:                $output->gitBranch,
                gitCommit:                $output->gitCommit,
                sabViolationsNew:          $output->sabViolationsNew,
                sabViolationsBlocking:     $output->sabViolationsBlocking,
                lastUpdated:               $output->lastUpdated,
                snapshotId:                $output->snapshotId,
            );
        }

        // FULL
        return new YdlReservationContextOutput(
            sprint:                   $output->sprint,
            sprintStatus:             $output->sprintStatus,
            authorityLevel:            YdlReservationContextOutput::AUTHORITY_FULL,
            authorityRationale:        'All gates passed — FULL authority',
            blockedScopes:             [],
            gitBranch:                $output->gitBranch,
            gitCommit:                $output->gitCommit,
            sabViolationsNew:          $output->sabViolationsNew,
            sabViolationsBlocking:     $output->sabViolationsBlocking,
            lastUpdated:               $output->lastUpdated,
            snapshotId:                $output->snapshotId,
        );
    }

    public function currentSnapshotId(): string
    {
        try {
            $output = $this->contextReader->read();
            return $output?->snapshotId ?? now()->format('Ymd');
        } catch (\Throwable) {
            return now()->format('Ymd');
        }
    }
}

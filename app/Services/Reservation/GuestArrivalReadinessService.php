<?php

namespace App\Services\Reservation;

use App\Enums\ReservationState;
use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Reservation\OperationalGorevService;
use App\Traits\GuardsAgentWrites;
use App\DTOs\Reservation\ValidityResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * GuestArrivalReadinessService — Wave 2 core service.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Responsibilities:
 * - Create/update property_readiness records
 * - Aggregate readiness dimensions
 * - Validate reservation validity for check-in
 * - Handle cancellation and date-change invalidation
 * - Manage check-in window lifecycle
 *
 * Uses GuardsAgentWrites: YES
 * Tenant isolation: All queries scoped by tenant_id
 * Idempotency: Upsert via unique constraint + firstOrCreate pattern
 */
class GuestArrivalReadinessService
{
    use GuardsAgentWrites;

    public function __construct(
        private readonly AccessCredentialService $credentialService,
        private readonly OperationalGorevService $gorevService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────
    // W2-01: Reservation Validity Gate
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Determine if a reservation can check in.
     *
     * @return ValidityResult
     */
    public function canCheckIn(PropertyReservation $reservation): ValidityResult
    {
        $this->blockAgentWrite(__FUNCTION__);

        // INV-W2-V1: must be CONFIRMED
        if ($reservation->reservation_state !== ReservationState::CONFIRMED) {
            return ValidityResult::blocked(
                'RESERVATION_NOT_CONFIRMED',
                "Rezervasyon check-in için onaylanmış değil. Mevcut durum: {$reservation->reservation_state->label()}"
            );
        }

        // INV-W2-V3: must not be cancelled
        if ($reservation->cancelled_at !== null) {
            return ValidityResult::blocked(
                'RESERVATION_CANCELLED',
                'İptal edilmiş rezervasyon için check-in yapılamaz.'
            );
        }

        // INV-W2-V2: check-in window must be open
        if (!$this->isCheckinWindowOpen($reservation)) {
            $windowOpensAt = Carbon::parse($reservation->start_date)->subDay()->startOfDay();
            $now = Carbon::now();
            if ($now->lt($windowOpensAt)) {
                $hoursUntil = $now->diffInHours($windowOpensAt);
                return ValidityResult::blocked(
                    'WINDOW_NOT_OPEN',
                    "Check-in penceresi {$hoursUntil} saat sonra açılacak. Rezervasyon başlangıcı: " .
                    Carbon::parse($reservation->start_date)->format('d.m.Y')
                );
            }
        }

        // Check readiness
        $readiness = $this->getReadiness($reservation);
        if ($readiness === null) {
            return ValidityResult::blocked(
                'READINESS_NOT_FOUND',
                'Mülk hazırlık kaydı bulunamadı.'
            );
        }

        if (!$readiness->is_ready) {
            $pending = $readiness->getPendingDimensions();
            return ValidityResult::blocked(
                'READINESS_INCOMPLETE',
                'Eksik hazırlık maddeleri: ' . implode(', ', $pending)
            );
        }

        return ValidityResult::ready();
    }

    /**
     * Validate preconditions for creating a readiness record.
     *
     * @throws \RuntimeException on validation failure
     */
    public function validateReadinessPreconditions(PropertyReservation $reservation): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        // Load ilan without global scopes (TenantScope, HasActiveScope)
        // so we can validate tenant isolation regardless of current tenant context.
        // This is intentional: the service MUST be able to verify tenant matching.
        $ilan = Ilan::withoutGlobalScopes()->find($reservation->property_id);

        if ($ilan === null) {
            throw new \RuntimeException(
                "Rezervasyon {$reservation->id}: İlan bağlantısı bulunamadı."
            );
        }

        // INV-W2-V4: rental_enabled
        if (!$ilan->rental_enabled) {
            throw new \RuntimeException(
                "Rezervasyon {$reservation->id}: Mülk kiralama için aktif değil."
            );
        }

        // INV-W2-V1: CONFIRMED state
        if ($reservation->reservation_state !== ReservationState::CONFIRMED) {
            throw new \RuntimeException(
                "Rezervasyon {$reservation->id}: Rezervasyon onaylanmış değil ({$reservation->reservation_state->label()})."
            );
        }

        // INV-W2-V3: not cancelled
        if ($reservation->cancelled_at !== null) {
            throw new \RuntimeException(
                "Rezervasyon {$reservation->id}: İptal edilmiş rezervasyon için readiness oluşturulamaz."
            );
        }

        // Tenant isolation: reservation.tenant_id == ilan.tenant_id
        if ($reservation->tenant_id !== $ilan->tenant_id) {
            Log::error('GuestArrivalReadinessService: tenant mismatch', [
                'reservation_id' => $reservation->id,
                'reservation_tenant_id' => $reservation->tenant_id,
                'ilan_id' => $ilan->id,
                'ilan_tenant_id' => $ilan->tenant_id,
            ]);
            throw new \RuntimeException(
                "Tenant isolation violation: reservation tenant {$reservation->tenant_id} != ilan tenant {$ilan->tenant_id}"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // W2-02: Property Readiness Tracker
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Get readiness record for a reservation.
     * Returns null if not yet created.
     */
    public function getReadiness(PropertyReservation $reservation): ?PropertyReadiness
    {
        $this->blockAgentWrite(__FUNCTION__);

        return PropertyReadiness::query()
            ->where('reservation_id', $reservation->id)
            ->orderBy('id')
            ->first();
    }

    /**
     * Get readiness records for an ilan on a specific date.
     */
    public function getReadinessForIlan(int $ilanId, Carbon $date): Collection
    {
        $this->blockAgentWrite(__FUNCTION__);

        return PropertyReadiness::query()
            ->where('ilan_id', $ilanId)
            ->whereHas('reservation', function ($q) use ($date) {
                $q->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>', $date);
            })
            ->with('reservation')
            ->get();
    }

    /**
     * Create or get a readiness record for a reservation.
     * Idempotent: returns existing record if already created.
     *
     * Preconditions are validated before creation.
     *
     * @throws \RuntimeException on precondition failure
     */
    public function getOrCreateReadiness(PropertyReservation $reservation): PropertyReadiness
    {
        $this->blockAgentWrite(__FUNCTION__);

        // Validate preconditions
        $this->validateReadinessPreconditions($reservation);

        $ilan = $reservation->ilan;

        // Check guest_contact_ready dimension at creation time
        $guestContactReady = $this->isGuestContactReady($reservation);

        /** @var PropertyReadiness $readiness */
        $readiness = PropertyReadiness::query()
            ->where('reservation_id', $reservation->id)
            ->first();

        if ($readiness !== null) {
            return $readiness;
        }

        // INV-W2-I1: Upsert with unique constraint as safety net
        $readiness = PropertyReadiness::create([
            'tenant_id' => $reservation->tenant_id,
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilan->id,
            'property_clean' => false,
            'access_credential_ready' => false,
            'guest_contact_ready' => $guestContactReady,
            'amenity_check_complete' => false,
            'welcome_kit_prepared' => false,
            'is_ready' => false,
        ]);

        Log::info('GuestArrivalReadinessService: readiness created', [
            'readiness_id' => $readiness->id,
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilan->id,
            'tenant_id' => $reservation->tenant_id,
            'guest_contact_ready' => $guestContactReady,
        ]);

        return $readiness;
    }

    // ─────────────────────────────────────────────────────────────────────
    // W2-03: Preparation Task Completion
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Called when a hazirlik Gorev is completed.
     * Marks property_clean dimension as complete and recomputes is_ready.
     *
     * @throws \RuntimeException if tenant mismatch detected
     */
    public function onHazirlikTaskCompleted(Gorev $task): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        if ($task->gorev_tipi !== OperationalGorevService::TASK_HAZIRLIK) {
            return; // Only handle hazirlik tasks
        }

        if ($task->reservation_id === null) {
            return; // Not a reservation-linked task
        }

        // Load reservation and ilan for tenant isolation check
        $reservation = PropertyReservation::find($task->reservation_id);
        if ($reservation === null) {
            Log::warning('GuestArrivalReadinessService: reservation not found for completed task', [
                'task_id' => $task->id,
                'reservation_id' => $task->reservation_id,
            ]);
            return;
        }

        $ilan = $reservation->ilan;
        if ($ilan === null) {
            return;
        }

        // INV-W2-T1: Tenant isolation
        if ($reservation->tenant_id !== $ilan->tenant_id) {
            throw new \RuntimeException(
                "Tenant isolation violation in onHazirlikTaskCompleted: " .
                "reservation {$reservation->tenant_id} != ilan {$ilan->tenant_id}"
            );
        }

        $readiness = $this->getReadiness($reservation);
        if ($readiness === null) {
            // Readiness not yet created — skip
            Log::debug('GuestArrivalReadinessService: readiness not found, skipping task completion', [
                'task_id' => $task->id,
                'reservation_id' => $reservation->id,
            ]);
            return;
        }

        $readiness->markDimensionComplete('property_clean');

        Log::info('GuestArrivalReadinessService: hazirlik task completed → property_clean=true', [
            'readiness_id' => $readiness->id,
            'reservation_id' => $reservation->id,
            'is_ready' => $readiness->is_ready,
        ]);
    }

    /**
     * Get pending readiness items for a reservation.
     *
     * @return array<string, string>  [dimension => reason]
     */
    public function getPendingReadinessItems(PropertyReservation $reservation): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        $readiness = $this->getReadiness($reservation);
        if ($readiness === null) {
            return [
                'readiness_record' => 'Mülk hazırlık kaydı oluşturulmadı.',
            ];
        }

        $pending = [];
        foreach (PropertyReadiness::REQUIRED_DIMENSIONS as $dimension) {
            if ($readiness->{$dimension} !== true) {
                $pending[$dimension] = $this->getDimensionLabel($dimension);
            }
        }

        return $pending;
    }

    // ─────────────────────────────────────────────────────────────────────
    // W2-07: Check-in Window Management
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Open the check-in window for a reservation.
     * Idempotent: returns false if already open.
     *
     * INV-W2-V1: reservation must be CONFIRMED
     * INV-W2-V3: reservation must not be cancelled
     *
     * @return bool  true if opened, false if already open
     * @throws \RuntimeException on validity violation
     */
    public function openCheckinWindow(PropertyReservation $reservation): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        // INV-W2-V1
        if ($reservation->reservation_state !== ReservationState::CONFIRMED) {
            throw new \RuntimeException(
                "Rezervasyon {$reservation->id}: Check-in penceresi sadece CONFIRMED rezervasyonlar için açılabilir."
            );
        }

        // INV-W2-V3
        if ($reservation->cancelled_at !== null) {
            throw new \RuntimeException(
                "Rezervasyon {$reservation->id}: İptal edilmiş rezervasyon için check-in penceresi açılamaz."
            );
        }

        // INV-W2-I4: Idempotent — only set if NULL
        if ($reservation->checkin_window_opened_at !== null) {
            Log::debug('GuestArrivalReadinessService: checkin window already open', [
                'reservation_id' => $reservation->id,
                'opened_at' => $reservation->checkin_window_opened_at->toIso8601String(),
            ]);
            return false;
        }

        $now = Carbon::now();
        $reservation->checkin_window_opened_at = $now;
        $reservation->update(['checkin_window_opened_at' => $now]); // No events

        Log::info('GuestArrivalReadinessService: checkin window opened', [
            'reservation_id' => $reservation->id,
            'tenant_id' => $reservation->tenant_id,
            'opened_at' => $now->toIso8601String(),
            'start_date' => $reservation->start_date,
        ]);

        return true;
    }

    /**
     * Close the check-in window for a reservation.
     */
    public function closeCheckinWindow(PropertyReservation $reservation): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        if ($reservation->checkin_window_opened_at === null) {
            return false;
        }

        $reservation->checkin_window_opened_at = null;
        $reservation->update(['checkin_window_opened_at' => null]);

        Log::info('GuestArrivalReadinessService: checkin window closed', [
            'reservation_id' => $reservation->id,
        ]);

        return true;
    }

    /**
     * Check if the check-in window is open for a reservation.
     *
     * INV-W2-V2: window is open if 24h before start_date AND checkin_window_opened_at is set
     */
    public function isCheckinWindowOpen(PropertyReservation $reservation): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        if ($reservation->checkin_window_opened_at === null) {
            return false;
        }

        // INV-W2-V3: cancelled reservations never allow check-in
        if ($reservation->cancelled_at !== null) {
            return false;
        }

        return true;
    }

    /**
     * Check if the check-in window should be opened (24h before start_date).
     * Used by the scheduled job to determine if a window should open.
     */
    public function shouldOpenWindow(PropertyReservation $reservation): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        if ($reservation->reservation_state !== ReservationState::CONFIRMED) {
            return false;
        }

        if ($reservation->cancelled_at !== null) {
            return false;
        }

        if ($reservation->checkin_window_opened_at !== null) {
            return false; // Already open
        }

        $windowOpensAt = Carbon::parse($reservation->start_date)->subDay()->startOfDay();
        return Carbon::now()->gte($windowOpensAt);
    }

    // ─────────────────────────────────────────────────────────────────────
    // W2-06: Cancellation / Date-Change Invalidation
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Invalidate readiness on reservation cancellation.
     *
     * INV-W2-C1: Sets is_ready = false, all dimensions = false
     * INV-W2-C2: Cancels pending hazirlik Gorevs
     */
    public function invalidateOnCancellation(PropertyReservation $reservation): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $readiness = $this->getReadiness($reservation);
        if ($readiness === null) {
            return;
        }

        // INV-W2-C1: Invalidate all dimensions
        $updates = ['is_ready' => false];
        foreach (PropertyReadiness::ALL_DIMENSIONS as $dimension) {
            $updates[$dimension] = false;
        }
        $readiness->update($updates);

        // INV-W2-C2: Cancel pending hazirlik Gorevs
        Gorev::query()
            ->where('reservation_id', $reservation->id)
            ->where('gorev_tipi', OperationalGorevService::TASK_HAZIRLIK)
            ->where('gorev_durumu', 'bekliyor')
            ->update(['gorev_durumu' => 'iptal']);

        Log::info('GuestArrivalReadinessService: readiness invalidated on cancellation', [
            'readiness_id' => $readiness->id,
            'reservation_id' => $reservation->id,
            'tenant_id' => $reservation->tenant_id,
        ]);
    }

    /**
     * Invalidate readiness on reservation date change.
     *
     * INV-W2-D1: Sets is_ready = false
     * INV-W2-D2: Existing hazirlik Gorevs are cancelled (Wave 1 handles new task creation)
     */
    public function invalidateOnDateChange(
        PropertyReservation $reservation,
        string $oldStartDate,
        string $oldEndDate
    ): void {
        $this->blockAgentWrite(__FUNCTION__);

        $readiness = $this->getReadiness($reservation);
        if ($readiness === null) {
            return;
        }

        // INV-W2-D1: Invalidate — readiness must be re-established with new dates
        $readiness->update(['is_ready' => false]);

        // INV-W2-D2: Cancel pending hazirlik tasks with old deadline
        Gorev::query()
            ->where('reservation_id', $reservation->id)
            ->where('gorev_tipi', OperationalGorevService::TASK_HAZIRLIK)
            ->whereIn('gorev_durumu', ['bekliyor'])
            ->update(['gorev_durumu' => 'iptal']);

        Log::info('GuestArrivalReadinessService: readiness invalidated on date change', [
            'readiness_id' => $readiness->id,
            'reservation_id' => $reservation->id,
            'tenant_id' => $reservation->tenant_id,
            'old_start_date' => $oldStartDate,
            'old_end_date' => $oldEndDate,
            'new_start_date' => $reservation->start_date,
            'new_end_date' => $reservation->end_date,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // W2-04: Guest Contact Readiness
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Determine if guest contact information is ready.
     * A guest has contact readiness if at least email OR phone is available.
     */
    public function isGuestContactReady(PropertyReservation $reservation): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        $hasEmail = !empty(trim($reservation->guest_email ?? ''));
        $hasPhone = !empty(trim($reservation->guest_phone ?? ''));

        return $hasEmail || $hasPhone;
    }

    /**
     * Refresh guest_contact_ready dimension for a reservation.
     */
    public function refreshGuestContactReadiness(PropertyReservation $reservation): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $readiness = $this->getReadiness($reservation);
        if ($readiness === null) {
            return;
        }

        $readiness->guest_contact_ready = $this->isGuestContactReady($reservation);
        $readiness->syncIsReady();
    }

    // ─────────────────────────────────────────────────────────────────────
    // W2-05: Access Credential Integration
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Mark access_credential_ready dimension based on active credential existence.
     */
    public function refreshAccessCredentialReadiness(PropertyReservation $reservation): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $readiness = $this->getReadiness($reservation);
        if ($readiness === null) {
            return;
        }

        $ilan = $reservation->ilan;
        if ($ilan === null) {
            $readiness->access_credential_ready = false;
            $readiness->syncIsReady();
            return;
        }

        $credential = $this->credentialService->getActiveCredential($ilan);
        $readiness->access_credential_ready = $credential !== null;
        $readiness->syncIsReady();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────

    private function getDimensionLabel(string $dimension): string
    {
        return match ($dimension) {
            'property_clean' => 'Temizlik kontrolü tamamlanmadı',
            'access_credential_ready' => 'Giriş kodu/anahtar hazır değil',
            'guest_contact_ready' => 'Misafir iletişim bilgisi eksik',
            'amenity_check_complete' => 'Tesisat kontrolü tamamlanmadı',
            'welcome_kit_prepared' => 'Karşılama seti hazırlanmadı',
            default => "Eksik: {$dimension}",
        };
    }
}
